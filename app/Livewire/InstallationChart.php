<?php

namespace App\Livewire;

use App\Models\Project;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class InstallationChart extends Component
{
    public function render()
    {
        $installationStats = Project::selectRaw('
            inverter_types.name as inverter_type,
            COUNT(projects.id) as installation_count
        ')
        ->join('customers', 'projects.customer_id', '=', 'customers.id')
        ->join('inverter_types', 'customers.inverter_type_id', '=', 'inverter_types.id')
        ->whereMonth('projects.solar_install_date', now()->month)
        ->whereYear('projects.solar_install_date', now()->year)
        ->whereNotNull('projects.solar_install_date')
        ->groupBy('inverter_types.id', 'inverter_types.name')
        ->get();

        // Log the raw query results
        Log::info('Installation Stats Raw:', [
            'count' => $installationStats->count(),
            'data' => $installationStats->toArray()
        ]);

        // Ensure we have at least some dummy data for testing
        if ($installationStats->isEmpty()) {
            $installationStats = collect([
                (object)[
                    'inverter_type' => 'No Installations',
                    'installation_count' => 0
                ]
            ]);
        }

        $chartData = [
            'labels' => $installationStats->pluck('inverter_type')->toArray(),
            'data' => $installationStats->pluck('installation_count')->toArray(),
            'colors' => [
                '#4e73df',
                '#1cc88a',
                '#36b9cc',
                '#f6c23e',
                '#e74a3b',
                '#5a5c69',
                '#858796',
                '#6f42c1',
                '#20c9a6',
                '#f8f9fc'
            ]
        ];

        // Log the final chart data
        Log::info('Chart Data Prepared:', $chartData);

        return view('livewire.dashboard.installation-chart', [
            'chartData' => $chartData
        ]);
    }
}
