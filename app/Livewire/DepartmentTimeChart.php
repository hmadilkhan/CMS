<?php

namespace App\Livewire;

use App\Models\Task;
use Livewire\Component;

class DepartmentTimeChart extends Component
{
    public function render()
    {
        $departmentStats = Task::selectRaw('
            departments.name as department_name,
            COALESCE(AVG(TIMESTAMPDIFF(HOUR, tasks.created_at, tasks.updated_at)), 0) as average_duration,
            COUNT(tasks.id) as task_count
        ')
        ->join('departments', 'tasks.department_id', '=', 'departments.id')
        ->groupBy('departments.id', 'departments.name')
        ->get();

        // Ensure we have at least some dummy data for testing
        if ($departmentStats->isEmpty()) {
            $departmentStats = collect([
                (object)[
                    'department_name' => 'Sample Department',
                    'average_duration' => 5,
                    'task_count' => 1
                ]
            ]);
        }

        // Convert data to proper numeric format and ensure no null values
        $chartData = [
            'labels' => $departmentStats->pluck('department_name')->toArray(),
            'data' => $departmentStats->map(function($item) {
                return floatval($item->average_duration);
            })->toArray(),
            'colors' => [
                '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                '#5a5c69', '#858796', '#6f42c1', '#20c9a6', '#f8f9fc'
            ]
        ];

        // Ensure we have at least one data point
        if (empty($chartData['data'])) {
            $chartData['data'] = [0];
            $chartData['labels'] = ['No Data'];
        }

        return view('livewire.dashboard.department-time-chart', [
            'chartData' => $chartData
        ]);
    }
} 