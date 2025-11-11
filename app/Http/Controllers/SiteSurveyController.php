<?php

namespace App\Http\Controllers;

use App\Models\SiteSurvey;
use App\Models\User;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SiteSurveyController extends Controller
{
    // Fixed time slots configuration
    private const TIME_SLOTS = [
        ['start' => '08:00', 'end' => '10:00'],
        ['start' => '10:00', 'end' => '12:00'],
        ['start' => '12:00', 'end' => '14:00'],
        ['start' => '14:00', 'end' => '16:00'],
        ['start' => '16:00', 'end' => '18:00'],
    ];

    private const MAX_TRAVEL_TIME = 700; // minutes
    private const LOOKAHEAD_DAYS = 3; // Check upcoming schedule

    public function showScheduleForm($projectId)
    {
        $project = Project::with('customer')->findOrFail($projectId);
        
        // Check if project already has a pending/scheduled survey
        $existingSurvey = SiteSurvey::where('project_id', $projectId)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->first();
        
        return view('site-surveys.schedule', compact('project', 'existingSurvey'));
    }

    public function getAvailableSlots(Request $request)
    {
        $date = Carbon::parse($request->date);
        $customerLat = $request->customer_lat;
        $customerLng = $request->customer_lng;

        $technicians = User::whereHas('roles', function ($q) {
            $q->where('name', 'Technician');
        })->get();

        $availableSlots = [];

        foreach ($technicians as $technician) {
            $technicianSlots = $this->findAvailableSlots($technician, $date, $customerLat, $customerLng);
            
            if (!empty($technicianSlots['slots'])) {
                $availableSlots[] = $technicianSlots;
            }
        }

        // Sort by suitability score (best match first)
        usort($availableSlots, function ($a, $b) {
            return $b['suitability_score'] <=> $a['suitability_score'];
        });

        return response()->json($availableSlots);
    }

    private function findAvailableSlots($technician, $date, $customerLat, $customerLng)
    {
        $slots = [];
        $suitabilityScore = 0;

        // Get existing surveys for the day
        $existingSurveys = SiteSurvey::where('technician_id', $technician->id)
            ->where('survey_date', $date)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->get();

        // Get technician's home location from existing address fields
        $homeLat = $technician->latitude;
        $homeLng = $technician->longitude;

        // If coordinates are null, geocode the address
        if ((!$homeLat || !$homeLng) && $technician->address) {
            $coords = $this->geocodeAddress($technician->address);
            if ($coords) {
                $homeLat = $coords['lat'];
                $homeLng = $coords['lng'];
            }
        }

        // Check each fixed time slot
        foreach (self::TIME_SLOTS as $timeSlot) {
            $slotStart = Carbon::parse($date->format('Y-m-d') . ' ' . $timeSlot['start']);
            $slotEnd = Carbon::parse($date->format('Y-m-d') . ' ' . $timeSlot['end']);

            // Check if slot is occupied
            if ($this->isSlotOccupied($slotStart, $slotEnd, $existingSurveys)) {
                continue;
            }

            // Determine starting location for this slot
            $startLat = $homeLat;
            $startLng = $homeLng;

            // Check if there's a previous survey on same day
            $previousSurvey = $this->getPreviousSurvey($slotStart, $existingSurveys);
            if ($previousSurvey) {
                $startLat = $previousSurvey->customer_lat;
                $startLng = $previousSurvey->customer_lng;
            }

            // Calculate travel time and distance
            if ($startLat && $startLng) {
                $travelData = $this->getTravelTime($startLat, $startLng, $customerLat, $customerLng);
                
                if ($travelData && $travelData['duration'] <= self::MAX_TRAVEL_TIME) {
                    $slots[] = [
                        'start_time' => $timeSlot['start'],
                        'end_time' => $timeSlot['end'],
                        'travel_time' => $travelData['duration'],
                        'distance' => $travelData['distance'],
                        'from_location' => $previousSurvey ? 'previous_job' : 'home'
                    ];

                    // Calculate suitability score
                    $suitabilityScore += $this->calculateSlotScore($travelData, $previousSurvey);
                }
            }
        }

        // Boost score if technician has nearby jobs in next 2-3 days
        $upcomingProximityBonus = $this->checkUpcomingProximity($technician, $date, $customerLat, $customerLng);
        $suitabilityScore += $upcomingProximityBonus;

        return [
            'technician' => $technician,
            'slots' => $slots,
            'suitability_score' => $suitabilityScore,
            'upcoming_nearby' => $upcomingProximityBonus > 0
        ];
    }

    private function isSlotOccupied($slotStart, $slotEnd, $existingSurveys)
    {
        return $existingSurveys->contains(function ($survey) use ($slotStart, $slotEnd) {
            $dateStr = $survey->survey_date instanceof Carbon ? $survey->survey_date->format('Y-m-d') : $survey->survey_date;
            $surveyStart = Carbon::parse($dateStr . ' ' . $survey->start_time);
            $surveyEnd = Carbon::parse($dateStr . ' ' . $survey->end_time);
            return $slotStart->lt($surveyEnd) && $slotEnd->gt($surveyStart);
        });
    }

    private function getPreviousSurvey($currentSlotStart, $existingSurveys)
    {
        return $existingSurveys
            ->filter(function ($survey) use ($currentSlotStart) {
                $dateStr = $survey->survey_date instanceof Carbon ? $survey->survey_date->format('Y-m-d') : $survey->survey_date;
                $surveyEnd = Carbon::parse($dateStr . ' ' . $survey->end_time);
                return $surveyEnd->lte($currentSlotStart);
            })
            ->sortByDesc(function ($survey) {
                return $survey->end_time;
            })
            ->first();
    }

    private function calculateSlotScore($travelData, $previousSurvey)
    {
        $score = 100;

        // Deduct points for travel time (less travel = higher score)
        $score -= ($travelData['duration'] / self::MAX_TRAVEL_TIME) * 30;

        // Bonus if coming from previous job (route optimization)
        if ($previousSurvey) {
            $score += 20;
        }

        return max(0, $score);
    }

    private function checkUpcomingProximity($technician, $currentDate, $customerLat, $customerLng)
    {
        $startDate = $currentDate->copy()->addDay();
        $endDate = $currentDate->copy()->addDays(self::LOOKAHEAD_DAYS);

        $upcomingSurveys = SiteSurvey::where('technician_id', $technician->id)
            ->whereBetween('survey_date', [$startDate, $endDate])
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->get();

        foreach ($upcomingSurveys as $survey) {
            $distance = $this->calculateDistance(
                $survey->customer_lat,
                $survey->customer_lng,
                $customerLat,
                $customerLng
            );

            // If within 10km, give bonus points
            if ($distance <= 10) {
                return 50;
            }
        }

        return 0;
    }

    private function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    private function geocodeAddress($address)
    {
        $apiKey = env('GOOGLE_MAPS_API_KEY');

        if (!$apiKey) return null;

        try {
            $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $address,
                'key' => $apiKey
            ]);

            $data = $response->json();

            if ($data['status'] === 'OK' && isset($data['results'][0])) {
                $location = $data['results'][0]['geometry']['location'];
                return [
                    'lat' => $location['lat'],
                    'lng' => $location['lng']
                ];
            }
        } catch (\Exception $e) {
            \Log::error('Geocoding API Error: ' . $e->getMessage());
        }

        return null;
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

        // Prevent double-booking
        $slotStart = Carbon::parse($request->survey_date . ' ' . $request->start_time);
        $slotEnd = Carbon::parse($request->survey_date . ' ' . $request->end_time);

        $existingSurveys = SiteSurvey::where('technician_id', $request->technician_id)
            ->where('survey_date', $request->survey_date)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->get();

        if ($this->isSlotOccupied($slotStart, $slotEnd, $existingSurveys)) {
            return response()->json([
                'success' => false,
                'message' => 'This time slot is already occupied for the selected technician.'
            ], 422);
        }

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
