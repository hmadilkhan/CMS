<?php

namespace App\Http\Controllers;

use App\Models\SiteSurvey;
use App\Models\TechnicianSchedule;
use App\Models\User;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SiteSurveyController extends Controller
{
    public function showScheduleForm($projectId)
    {
        $project = Project::with('customer')->findOrFail($projectId);
        return view('site-surveys.schedule', compact('project'));
    }

    public function getAvailableSlots(Request $request)
    {
        $date = Carbon::parse($request->date);
        $customerAddress = $request->customer_address;
        $customerLat = $request->customer_lat;
        $customerLng = $request->customer_lng;

        $technicians = User::whereHas('roles', function($q) {
            $q->where('name', 'Technician');
        })->get();

        $availableSlots = [];

        foreach ($technicians as $technician) {
            $schedule = TechnicianSchedule::where('technician_id', $technician->id)
                ->where('date', $date)
                ->where('is_available', true)
                ->first();

            if (!$schedule) continue;

            $existingSurveys = SiteSurvey::where('technician_id', $technician->id)
                ->where('survey_date', $date)
                ->whereIn('status', ['scheduled', 'in_progress'])
                ->orderBy('start_time')
                ->get();

            $slots = $this->calculateTimeSlots($schedule, $existingSurveys, $customerLat, $customerLng);
            
            if (!empty($slots)) {
                $availableSlots[] = [
                    'technician' => $technician,
                    'slots' => $slots
                ];
            }
        }

        return response()->json($availableSlots);
    }

    private function calculateTimeSlots($schedule, $existingSurveys, $customerLat, $customerLng)
    {
        $slots = [];
        $currentTime = Carbon::parse($schedule->start_time);
        $endTime = Carbon::parse($schedule->end_time);
        $lastLat = $schedule->start_lat;
        $lastLng = $schedule->start_lng;

        while ($currentTime->lt($endTime)) {
            $slotEnd = $currentTime->copy()->addHours(2);
            
            if ($slotEnd->gt($endTime)) break;

            $isOccupied = $existingSurveys->contains(function($survey) use ($currentTime, $slotEnd) {
                $surveyStart = Carbon::parse($survey->start_time);
                $surveyEnd = Carbon::parse($survey->end_time);
                return $currentTime->lt($surveyEnd) && $slotEnd->gt($surveyStart);
            });

            if (!$isOccupied) {
                $travelData = $this->getTravelTime($lastLat, $lastLng, $customerLat, $customerLng);
                
                if ($travelData && $travelData['duration'] <= 60) {
                    $slots[] = [
                        'start_time' => $currentTime->format('H:i'),
                        'end_time' => $slotEnd->format('H:i'),
                        'travel_time' => $travelData['duration'],
                        'distance' => $travelData['distance']
                    ];
                }
            }

            $currentTime->addHours(2);
            if (!$isOccupied && $travelData) {
                $lastLat = $customerLat;
                $lastLng = $customerLng;
            }
        }

        return $slots;
    }

    private function getTravelTime($fromLat, $fromLng, $toLat, $toLng)
    {
        $apiKey = env('GOOGLE_MAPS_API_KEY');
        
        if (!$apiKey) return null;

        try {
            $response = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json', [
                'origins' => "{$fromLat},{$fromLng}",
                'destinations' => "{$toLat},{$toLng}",
                'departure_time' => 'now',
                'traffic_model' => 'best_guess',
                'key' => $apiKey
            ]);

            $data = $response->json();

            if ($data['status'] === 'OK' && isset($data['rows'][0]['elements'][0])) {
                $element = $data['rows'][0]['elements'][0];
                
                if ($element['status'] === 'OK') {
                    return [
                        'duration' => round($element['duration_in_traffic']['value'] / 60),
                        'distance' => round($element['distance']['value'] / 1000, 2)
                    ];
                }
            }
        } catch (\Exception $e) {
            \Log::error('Google Maps API Error: ' . $e->getMessage());
        }

        return null;
    }

    public function scheduleSurvey(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'technician_id' => 'required|exists:users,id',
            'survey_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'customer_address' => 'required',
            'customer_lat' => 'required|numeric',
            'customer_lng' => 'required|numeric',
            'estimated_travel_time' => 'nullable|integer',
            'estimated_distance' => 'nullable|numeric'
        ]);

        $survey = SiteSurvey::create($validated);

        $project = Project::find($request->project_id);
        $siteSurveyDept = \App\Models\SubDepartment::where('name', 'Site Survey')->first();
        
        if ($siteSurveyDept) {
            $project->update(['sub_department_id' => $siteSurveyDept->id]);
            
            \App\Models\Task::create([
                'project_id' => $project->id,
                'employee_id' => $request->technician_id,
                'department_id' => $siteSurveyDept->department_id,
                'sub_department_id' => $siteSurveyDept->id,
            ]);
        }

        $technician = User::find($request->technician_id);
        $technician->notify(new \App\Notifications\SiteSurveyScheduled($survey));

        activity('site_survey')
            ->performedOn($survey)
            ->causedBy(auth()->user())
            ->log('Site survey scheduled for ' . $survey->survey_date);

        return response()->json(['success' => true, 'survey' => $survey]);
    }

    public function updateLocation(Request $request)
    {
        $validated = $request->validate([
            'survey_id' => 'required|exists:site_surveys,id',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric'
        ]);

        $survey = SiteSurvey::find($request->survey_id);
        $survey->update([
            'actual_lat' => $request->lat,
            'actual_lng' => $request->lng
        ]);

        return response()->json(['success' => true]);
    }

    public function startSurvey(Request $request, $id)
    {
        $survey = SiteSurvey::findOrFail($id);
        $survey->update([
            'status' => 'in_progress',
            'actual_start_time' => now()
        ]);

        return response()->json(['success' => true]);
    }

    public function completeSurvey(Request $request, $id)
    {
        $survey = SiteSurvey::findOrFail($id);
        $survey->update([
            'status' => 'completed',
            'actual_end_time' => now(),
            'notes' => $request->notes
        ]);

        activity('site_survey')
            ->performedOn($survey)
            ->causedBy(auth()->user())
            ->log('Site survey completed');

        return response()->json(['success' => true]);
    }

    public function getTechnicianSurveys(Request $request)
    {
        $technicianId = $request->user()->id;
        $date = $request->date ?? now()->format('Y-m-d');

        $surveys = SiteSurvey::with('project.customer')
            ->where('technician_id', $technicianId)
            ->whereDate('survey_date', $date)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->orderBy('start_time')
            ->get();

        return response()->json($surveys);
    }
}
