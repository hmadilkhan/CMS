<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\SiteSurvey;
use Carbon\Carbon;

class TechnicianDashboard extends Component
{
    public $todaySurveys;
    public $todayDate;
    public $technician;

    public $techMapData;
    public $routeUrl;

    public function mount()
    {
        $this->technician = auth()->user();
        $this->todayDate = Carbon::today()->format('Y-m-d');

        $this->techMapData = [
            'lat' => (float)($this->technician->latitude ?? 33.7490),
            'lng' => (float)($this->technician->longitude ?? -84.3880),
            'hasLocation' => !empty($this->technician->latitude) && !empty($this->technician->longitude),
            'address' => $this->technician->address ?? null // Fallback address
        ];

        $this->loadSurveys();
    }

    public function loadSurveys()
    {
        $this->todaySurveys = SiteSurvey::with(['project.customer'])
            ->where('technician_id', auth()->id())
            ->whereDate('survey_date', $this->todayDate)
            ->whereIn('status', ['scheduled', 'in_progress', 'completed'])
            ->orderBy('start_time')
            ->get();

        $this->generateRouteUrl();
    }

    public function generateRouteUrl()
    {
        if ($this->todaySurveys->isEmpty()) {
            $this->routeUrl = null;
            return;
        }

        $origin = null;
        $destination = null;
        $waypoints = [];

        // Check for Home Base
        $hasHome = $this->techMapData['hasLocation'];
        $homeCoords = $hasHome ? "{$this->techMapData['lat']},{$this->techMapData['lng']}" : null;

        // Get Survey Coordinates
        $surveyCoords = $this->todaySurveys->map(function ($survey) {
            if ($survey->customer_lat && $survey->customer_lng) {
                return "{$survey->customer_lat},{$survey->customer_lng}";
            }
            return null;
        })->filter()->values(); // Ensure re-indexed array

        if ($surveyCoords->isEmpty()) {
            $this->routeUrl = null;
            return;
        }

        if ($hasHome) {
            // Loop: Home -> Jobs -> Home
            $origin = $homeCoords;
            $destination = $homeCoords;
            $waypoints = $surveyCoords->toArray();
        } else {
            // One-way: Current Location -> Jobs -> Last Job
            // Origin left as null (Google Defaults to 'Current Location')
            // Destination is the last job
            // Waypoints are all preceding jobs

            $destination = $surveyCoords->pop(); // Last one is dest
            $waypoints = $surveyCoords->toArray(); // Remaining are waypoints
        }

        $params = [
            'api' => 1,
            'travelmode' => 'driving',
            'destination' => $destination,
        ];

        if ($origin) {
            $params['origin'] = $origin;
        }

        if (!empty($waypoints)) {
            $params['waypoints'] = implode('|', $waypoints);
        }

        $this->routeUrl = "https://www.google.com/maps/dir/?" . http_build_query($params);
    }

    public function render()
    {
        return view('livewire.technician-dashboard');
    }
}
