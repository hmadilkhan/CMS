<?php

namespace App\Livewire;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

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
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        // Time a PROJECT spent in a department, in fractional days: from the moment
        // its first task in that department was created until its last one was
        // completed. A project passes through several sub-department steps and each
        // step is its own task row, so averaging per task reports the length of one
        // step rather than the length of the whole stage.
        // TIMESTAMPDIFF(DAY, ...) truncates (a 23-hour stage counts as 0 days), so
        // measure in hours and divide by 24 to keep the fraction.
        $spanExpression = $isSqlite
            ? 'julianday(MAX(tasks.updated_at)) - julianday(MIN(tasks.created_at))'
            : 'TIMESTAMPDIFF(HOUR, MIN(tasks.created_at), MAX(tasks.updated_at)) / 24';

        // Skip instant tasks (under a minute between created_at and updated_at):
        // auto-advanced or bulk-updated steps, not real work. None of them share an
        // identical timestamp -- they are seconds apart -- so a plain
        // updated_at > created_at check would not catch them.
        $minimumDuration = $isSqlite
            ? '(julianday(tasks.updated_at) - julianday(tasks.created_at)) >= (1.0 / 1440)'
            : 'TIMESTAMPDIFF(SECOND, tasks.created_at, tasks.updated_at) >= 60';

        // Only completed tasks have a meaningful duration: their updated_at is the
        // moment the status was set to Completed. Open tasks (In-Progress/Hold) are
        // still running, so including them would report a half-finished duration.
        $projectSpans = DB::table('tasks')
            ->join('departments', 'tasks.department_id', '=', 'departments.id')
            ->selectRaw("departments.id as department_id, departments.name as department_name, tasks.project_id, {$spanExpression} as span_days")
            ->whereNull('tasks.deleted_at')
            ->where('tasks.status', 'Completed')
            ->whereRaw($minimumDuration)
            ->whereDate('tasks.created_at', '>=', $this->startDate)
            ->whereDate('tasks.created_at', '<=', $this->endDate)
            ->groupBy('departments.id', 'departments.name', 'tasks.project_id');

        $departmentStats = DB::query()
            ->fromSub($projectSpans, 'project_spans')
            ->selectRaw('department_name, COALESCE(AVG(span_days), 0) as average_duration, COUNT(*) as project_count')
            ->groupBy('department_id', 'department_name')
            ->orderBy('department_id')
            ->get();

        // Ensure we have at least some dummy data for testing
        if ($departmentStats->isEmpty()) {
            $departmentStats = collect([
                (object) [
                    'department_name' => 'No Data',
                    'average_duration' => 0,
                    'project_count' => 0,
                ],
            ]);
        }

        // Validate and format data
        $labels = $departmentStats->pluck('department_name')->map(function ($label) {
            return $label ?: 'Unknown';
        })->toArray();

        $data = $departmentStats->pluck('average_duration')->map(function ($duration) {
            return is_numeric($duration) ? round(floatval($duration), 2) : 0;
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
                '#1d4ed8', '#ee8f45', '#0284c7', '#334155', '#64748b',
                '#2563eb', '#c8642d', '#475569', '#ffc18f', '#94a3b8',
            ],
        ];
        $this->dispatch('refreshDepartmentChart');

        return view('livewire.dashboard.department-time-chart');
    }
}
