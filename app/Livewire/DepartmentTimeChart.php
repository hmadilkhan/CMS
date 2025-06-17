<?php

namespace App\Livewire;

use App\Models\Task;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DepartmentTimeChart extends Component
{
    public $startDate;
    public $endDate;
    public $departmentChartData;

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
        $departmentStats = Task::selectRaw('
            departments.name as department_name,
             COALESCE(AVG(TIMESTAMPDIFF(DAY, tasks.created_at, tasks.updated_at)), 0) as average_duration,
            COUNT(tasks.id) as task_count
        ')
        ->join('departments', 'tasks.department_id', '=', 'departments.id')
        ->whereDate('tasks.created_at', '>=', $this->startDate)
        ->whereDate('tasks.created_at', '<=', $this->endDate)
        ->groupBy('departments.id', 'departments.name')
        ->get();


        // Ensure we have at least some dummy data for testing
        if ($departmentStats->isEmpty()) {
            $departmentStats = collect([
                (object)[
                    'department_name' => 'No Data',
                    'average_duration' => 0,
                    'task_count' => 0
                ]
            ]);
        }

        // Validate and format data
        $labels = $departmentStats->pluck('department_name')->map(function($label) {
            return $label ?: 'Unknown';
        })->toArray();

        $data = $departmentStats->pluck('average_duration')->map(function($duration) {
            return is_numeric($duration) ? floatval($duration) : 0;
        })->toArray();

        // Ensure we have valid data
        if (empty($data) || array_sum($data) === 0) {
            $labels = ['No Data'];
            $data = [0];
        }

        $this->departmentChartData = [
            'labels' => $labels,
            'data' => $data,
            'colors' => [
                '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                '#5a5c69', '#858796', '#6f42c1', '#20c9a6', '#f8f9fc'
            ]
        ];
        $this->dispatch('refreshDepartmentChart');
        return view('livewire.dashboard.department-time-chart');
    }
} 