<?php

namespace App\Livewire;

use App\Models\Project;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class InstallationChart extends Component
{
    public $startDate;
    public $endDate;
    public $chartData;

    protected $listeners = ['datesUpdated' => 'updateDates'];

    public function mount($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate ? Carbon::parse($startDate)->format('Y-m-d') : Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = $endDate ? Carbon::parse($endDate)->format('Y-m-d') : Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function updateDates($dates)
    {
        $this->startDate = Carbon::parse($dates['startDate'])->format('Y-m-d');
        $this->endDate = Carbon::parse($dates['endDate'])->format('Y-m-d');
    }

    public function render()
    {
        $installationStats = Project::selectRaw('
            inverter_types.name as inverter_type,
            COUNT(projects.id) as installation_count
        ')
        ->join('customers', 'projects.customer_id', '=', 'customers.id')
        ->join('inverter_types', 'customers.inverter_type_id', '=', 'inverter_types.id')
        ->whereDate('projects.solar_install_date', '>=', $this->startDate)
        ->whereDate('projects.solar_install_date', '<=', $this->endDate)
        ->whereNotNull('projects.solar_install_date')
        ->groupBy('inverter_types.id', 'inverter_types.name')
        ->get();

        // Ensure we have at least some dummy data for testing
        if ($installationStats->isEmpty()) {
            $installationStats = collect([
                (object)[
                    'inverter_type' => 'No Installations',
                    'installation_count' => 0
                ]
            ]);
        }

        // Validate and format data
        $labels = $installationStats->pluck('inverter_type')->map(function($label) {
            return $label ?: 'Unknown';
        })->toArray();

        $data = $installationStats->pluck('installation_count')->map(function($count) {
            return is_numeric($count) ? floatval($count) : 0;
        })->toArray();

        // Ensure we have valid data
        if (empty($data) || array_sum($data) === 0) {
            $labels = ['No Data'];
            $data = [0];
        }

        $this->chartData = [
            'labels' => $labels,
            'data' => $data,
            'colors' => [
                '#1d4ed8',
                '#ee8f45',
                '#0284c7',
                '#334155',
                '#64748b',
                '#2563eb',
                '#c8642d',
                '#475569',
                '#ffc18f',
                '#94a3b8'
            ]
        ];
        $this->dispatch('refreshInstallationChart');
        return view('livewire.dashboard.installation-chart');
    }
}
