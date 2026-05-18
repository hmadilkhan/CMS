<?php

namespace App\Livewire;

use App\Models\Project;
use Livewire\Component;
use Carbon\Carbon;

class PtoApprovalChart extends Component
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
        $ptoStats = Project::selectRaw('
            utility_company,
            COUNT(projects.id) as pto_count
        ')
        ->whereDate('projects.pto_approval_date', '>=', $this->startDate)
        ->whereDate('projects.pto_approval_date', '<=', $this->endDate)
        ->whereNotNull('projects.pto_approval_date')
        ->groupBy('utility_company')
        ->get();

        // Ensure we have at least some dummy data for testing
        if ($ptoStats->isEmpty()) {
            $ptoStats = collect([
                (object)[
                    'utility_company' => 'No PTO Approvals',
                    'pto_count' => 0
                ]
            ]);
        }

        // Validate and format data
        $labels = $ptoStats->pluck('utility_company')->map(function($label) {
            return $label ?: 'Unknown';
        })->toArray();

        $data = $ptoStats->pluck('pto_count')->map(function($count) {
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
                '#0f766e',
                '#0284c7',
                '#334155',
                '#64748b',
                '#2563eb',
                '#0891b2',
                '#475569',
                '#0e7490',
                '#94a3b8'
            ]
        ];
        $this->dispatch('refreshChart');
        return view('livewire.dashboard.pto-approval-chart');
    }
}
