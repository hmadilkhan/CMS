<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class AiProjectLaneService
{
    public function __construct() {}

    /**
     * Per-project department lane history (matches the Department Logs tab exactly).
     * Source: tasks table — each task record = one lane entry.
     */
    public function getMovementReport(User $user, array $options = []): array
    {
        $search   = trim($options['search'] ?? '');
        $limit    = min((int) ($options['limit'] ?? 30), 100);

        // Base query — mirrors exactly what ProjectController::show() does
        $query = DB::table('tasks as t')
            ->join('projects as p', 'p.id', '=', 't.project_id')
            ->join('departments as d', 'd.id', '=', 't.department_id')
            ->leftJoin('customers as c', 'c.id', '=', 'p.customer_id')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->select([
                'p.id as project_id',
                'p.project_name',
                'p.code',
                DB::raw("TRIM(CONCAT(COALESCE(c.first_name,''),' ',COALESCE(c.last_name,''))) as customer_name"),
                'd.name as department',
                't.created_at as entry_date',
                't.updated_at as exit_date',
                't.status',
                'u.name as action_by',
            ])
            ->orderBy('p.id')
            ->orderBy('t.id');

        $query = $this->applyProjectScope($query, $user);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('p.project_name', 'like', '%' . $search . '%')
                  ->orWhere('p.code', 'like', '%' . $search . '%')
                  ->orWhere(DB::raw("CONCAT(COALESCE(c.first_name,''),' ',COALESCE(c.last_name,''))"), 'like', '%' . $search . '%');
            });
        }

        $tasks = $query->get();

        if ($tasks->isEmpty()) {
            return ['rows' => [], 'row_count' => 0, 'columns' => [], 'totals' => []];
        }

        $rows   = [];
        $totals = [];

        // Group by project — show details + per-department totals per project
        foreach ($tasks->groupBy('project_id')->take($limit) as $projectTasks) {
            $list     = $projectTasks->values();
            $first    = $list->first();
            $customer = trim($first->customer_name) ?: 'N/A';

            foreach ($list as $task) {
                $exitDate = $task->status === 'In-Progress'
                    ? now()
                    : Carbon::parse($task->exit_date);

                $entryDate = Carbon::parse($task->entry_date);
                $days      = max(1, (int) $entryDate->diffInDays($exitDate));

                $rows[] = [
                    'Project'     => $task->project_name,
                    'Code'        => $task->code ?: '-',
                    'Customer'    => $customer,
                    'Department'  => $task->department,
                    'Entry Date'  => $entryDate->format('d M Y H:i'),
                    'Exit Date'   => $task->status === 'In-Progress' ? 'N/A (Active)' : Carbon::parse($task->exit_date)->format('d M Y H:i'),
                    'Action By'   => $task->action_by ?? 'N/A',
                    'Days'        => $days,
                ];

                // Accumulate totals per department for this project
                $dept = $task->department;
                $totals[$task->project_id][$dept] = ($totals[$task->project_id][$dept] ?? 0) + $days;
            }
        }

        // Flatten totals into readable rows appended after the detail rows
        $totalRows = [];
        foreach ($totals as $projectId => $deptDays) {
            $projectName = $tasks->firstWhere('project_id', $projectId)->project_name ?? '-';
            foreach ($deptDays as $dept => $days) {
                $totalRows[] = [
                    'Project'    => $projectName,
                    'Department' => $dept,
                    'Total Days' => $days,
                ];
            }
        }

        return [
            'rows'      => $rows,
            'row_count' => count($rows),
            'columns'   => empty($rows) ? [] : array_keys($rows[0]),
            'totals'    => $totalRows,
        ];
    }

    /**
     * Per-department totals for a specific project.
     * Default view for "project X kitne din kis lane me raha".
     */
    public function getProjectTotals(User $user, string $search): array
    {
        $query = DB::table('tasks as t')
            ->join('projects as p', 'p.id', '=', 't.project_id')
            ->join('departments as d', 'd.id', '=', 't.department_id')
            ->leftJoin('customers as c', 'c.id', '=', 'p.customer_id')
            ->select([
                'p.id as project_id',
                'p.project_name',
                'p.code',
                DB::raw("TRIM(CONCAT(COALESCE(c.first_name,''),' ',COALESCE(c.last_name,''))) as customer_name"),
                'd.name as department',
                't.created_at as entry_date',
                't.updated_at as exit_date',
                't.status',
            ])
            ->orderBy('p.id')
            ->orderBy('t.id');

        $query = $this->applyProjectScope($query, $user);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('p.project_name', 'like', '%' . $search . '%')
                  ->orWhere('p.code', 'like', '%' . $search . '%')
                  ->orWhere(DB::raw("CONCAT(COALESCE(c.first_name,''),' ',COALESCE(c.last_name,''))"), 'like', '%' . $search . '%');
            });
        }

        $tasks = $query->get();

        if ($tasks->isEmpty()) {
            return ['rows' => [], 'row_count' => 0, 'columns' => []];
        }

        $totals       = [];
        $projectNames = [];
        $projectCodes = [];

        foreach ($tasks as $task) {
            $pid      = $task->project_id;
            $dept     = $task->department;
            $exitDate = $task->status === 'In-Progress' ? now() : Carbon::parse($task->exit_date);
            $days     = max(1, (int) Carbon::parse($task->entry_date)->diffInDays($exitDate));

            $totals[$pid][$dept]  = ($totals[$pid][$dept] ?? 0) + $days;
            $projectNames[$pid]   = $task->project_name;
            $projectCodes[$pid]   = $task->code;
        }

        $rows = [];
        foreach ($totals as $pid => $deptDays) {
            foreach ($deptDays as $dept => $days) {
                $rows[] = [
                    'Project'     => $projectNames[$pid],
                    'Code'        => $projectCodes[$pid] ?: '-',
                    'Department'  => $dept,
                    'Total Days'  => $days,
                ];
            }
        }

        return [
            'rows'      => $rows,
            'row_count' => count($rows),
            'columns'   => empty($rows) ? [] : array_keys($rows[0]),
        ];
    }

    /**
     * Summary across all projects: total / avg days per department lane.
     */
    public function getSummaryReport(User $user): array
    {
        $query = DB::table('tasks as t')
            ->join('projects as p', 'p.id', '=', 't.project_id')
            ->join('departments as d', 'd.id', '=', 't.department_id')
            ->select([
                'd.name as department',
                't.created_at as entry_date',
                't.updated_at as exit_date',
                't.status',
            ])
            ->orderBy('t.id');

        $query = $this->applyProjectScope($query, $user);

        $tasks = $query->get();

        if ($tasks->isEmpty()) {
            return ['rows' => [], 'row_count' => 0, 'columns' => []];
        }

        $stats = [];

        foreach ($tasks as $task) {
            $exitDate = $task->status === 'In-Progress' ? now() : Carbon::parse($task->exit_date);
            $days     = max(1, (int) Carbon::parse($task->entry_date)->diffInDays($exitDate));
            $dept     = $task->department;

            if (! isset($stats[$dept])) {
                $stats[$dept] = ['total' => 0, 'count' => 0, 'min' => PHP_INT_MAX, 'max' => 0];
            }
            $stats[$dept]['total'] += $days;
            $stats[$dept]['count']++;
            $stats[$dept]['min'] = min($stats[$dept]['min'], $days);
            $stats[$dept]['max'] = max($stats[$dept]['max'], $days);
        }

        $rows = [];
        foreach ($stats as $dept => $s) {
            $rows[] = [
                'Department Lane'         => $dept,
                'Projects Passed Through' => $s['count'],
                'Avg Days'                => round($s['total'] / $s['count'], 1),
                'Min Days'                => $s['min'] === PHP_INT_MAX ? 1 : $s['min'],
                'Max Days'                => $s['max'],
            ];
        }

        usort($rows, fn ($a, $b) => $b['Avg Days'] <=> $a['Avg Days']);

        return [
            'rows'      => $rows,
            'row_count' => count($rows),
            'columns'   => empty($rows) ? [] : array_keys($rows[0]),
        ];
    }

    private function applyProjectScope(Builder $query, User $user): Builder
    {
        if ($user->hasAnyRole(['Super Admin', 'Admin', 'Finance'])) {
            return $query;
        }

        if ($user->hasAnyRole(['Manager', 'Sales Manager', 'Sub-Contractor Manager'])) {
            $deptIds = DB::table('employee_departments')
                ->whereIn('employee_id', DB::table('employees')->where('user_id', $user->id)->select('id'))
                ->pluck('department_id');
            return $query->whereIn('p.department_id', $deptIds);
        }

        if ($user->hasAnyRole(['Sales Person'])) {
            return $query->where('p.sales_partner_user_id', $user->id);
        }

        if ($user->hasAnyRole(['Sub-Contractor User'])) {
            return $query->where('p.sub_contractor_user_id', $user->id);
        }

        // Employee: only assigned projects
        $projectIds = DB::table('tasks as t2')
            ->whereIn('t2.employee_id', DB::table('employees')->where('user_id', $user->id)->select('id'))
            ->pluck('t2.project_id');

        return $query->whereIn('p.id', $projectIds);
    }
}
