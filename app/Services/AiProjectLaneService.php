<?php

namespace App\Services;

use App\Models\LaborCost;
use App\Models\Project;
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

    /**
     * Full detail for the project(s) matching a search term: per-department days,
     * notes, current status/department, assigned employee, project age, and the
     * bottleneck (the lane where the project spent the most time). Used to build a
     * human-readable project summary. Respects the same role scope as the lane
     * reports.
     *
     * @return array{projects: array<int, array>, project_count: int}
     */
    public function getProjectDetail(User $user, string $search, int $maxProjects = 5): array
    {
        $query = DB::table('tasks as t')
            ->join('projects as p', 'p.id', '=', 't.project_id')
            ->join('departments as d', 'd.id', '=', 't.department_id')
            ->leftJoin('customers as c', 'c.id', '=', 'p.customer_id')
            ->leftJoin('employees as e', 'e.id', '=', 't.employee_id')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->select([
                'p.id as project_id',
                'p.project_name',
                'p.code',
                'p.created_at as project_created_at',
                DB::raw("TRIM(CONCAT(COALESCE(c.first_name,''),' ',COALESCE(c.last_name,''))) as customer_name"),
                'd.name as department',
                't.created_at as entry_date',
                't.updated_at as exit_date',
                't.status',
                't.notes',
                't.assign_to_notes',
                'e.name as assigned_employee',
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
            return ['projects' => [], 'project_count' => 0];
        }

        $grouped  = $tasks->groupBy('project_id');
        $projects = [];

        foreach ($grouped as $projectTasks) {
            $list  = $projectTasks->values();
            $first = $list->first();
            $last  = $list->last(); // latest task = current lane (ordered by t.id)

            $departments = [];
            $deptDays    = [];

            foreach ($list as $task) {
                $entryDate = Carbon::parse($task->entry_date);
                $exitDate  = $task->status === 'In-Progress' ? now() : Carbon::parse($task->exit_date);
                $days      = max(1, (int) $entryDate->diffInDays($exitDate));

                $deptDays[$task->department] = ($deptDays[$task->department] ?? 0) + $days;

                $departments[] = [
                    'Department' => $task->department,
                    'Days'       => $days,
                    'Status'     => $task->status,
                    'Entry'      => $entryDate->format('d M Y'),
                    'Exit'       => $task->status === 'In-Progress' ? 'Active' : Carbon::parse($task->exit_date)->format('d M Y'),
                    'Notes'      => trim((string) ($task->notes ?: $task->assign_to_notes ?: '')) ?: '-',
                    'Action By'  => $task->action_by ?: 'N/A',
                ];
            }

            $totalDays = array_sum($deptDays);
            arsort($deptDays);
            $bottleneckDept = (string) array_key_first($deptDays);
            $bottleneckDays = (int) ($deptDays[$bottleneckDept] ?? 0);

            $createdAt = $first->project_created_at
                ? Carbon::parse($first->project_created_at)
                : Carbon::parse($first->entry_date);

            // Notes recorded against the bottleneck lane (the likely reason for delay).
            $bottleneckNotes = collect($departments)
                ->where('Department', $bottleneckDept)
                ->pluck('Notes')
                ->reject(fn ($n) => $n === '-' || $n === '')
                ->implode(' | ');

            $projects[] = [
                'project_id'         => $first->project_id,
                'project_name'       => $first->project_name,
                'code'               => $first->code ?: '-',
                'customer_name'      => trim($first->customer_name) ?: 'N/A',
                'assigned_employee'  => $last->assigned_employee ?: 'Unassigned',
                'current_department' => $last->department,
                'current_status'     => $last->status,
                'created_at'         => $createdAt->format('d M Y'),
                'age_days'           => max(1, (int) $createdAt->diffInDays(now())),
                'total_days'         => $totalDays,
                'bottleneck_dept'    => $bottleneckDept,
                'bottleneck_days'    => $bottleneckDays,
                'bottleneck_notes'   => $bottleneckNotes,
                'departments'        => $departments,
            ];

            if (count($projects) >= $maxProjects) {
                break;
            }
        }

        return ['projects' => $projects, 'project_count' => $grouped->count()];
    }

    /**
     * Estimated & Actual Project Costs — the deterministic equivalent of the CRM's
     * "Financial Ledger → Estimated & Actual Project Costs" panel
     * (App\Livewire\Project\ProjectCost). The estimated material/labor figures are
     * COMPUTED live from the catalogue + customer specs (not stored columns), so
     * Text-to-SQL cannot reproduce them — this mirrors the Livewire formula EXACTLY
     * by reusing the same Eloquent relations, keeping a single source of truth.
     *
     * Permission-gated identically to the CRM panel (`Pre and Post Project Cost`),
     * since it exposes internal costs + profit. Row-access is also applied so a
     * scoped user can only resolve their own projects.
     *
     * @return array{authorized:bool, projects:array<int,array>, project_count:int, ledger?:array}
     */
    public function getProjectCostLedger(User $user, string $search, int $maxProjects = 5): array
    {
        if (! $user->can('Pre and Post Project Cost')) {
            return ['authorized' => false, 'projects' => [], 'project_count' => 0];
        }

        $query = DB::table('projects as p')
            ->leftJoin('customers as c', 'c.id', '=', 'p.customer_id')
            ->whereNull('p.deleted_at')
            ->select([
                'p.id',
                'p.project_name',
                'p.code',
                DB::raw("TRIM(CONCAT(COALESCE(c.first_name,''),' ',COALESCE(c.last_name,''))) as customer_name"),
            ])
            ->orderBy('p.id');

        $query = $this->applyProjectScope($query, $user);

        if ($search !== '') {
            $tokens = $this->nameTokens($search);

            $query->where(function ($q) use ($search, $tokens) {
                $q->where('p.project_name', 'like', '%' . $search . '%')
                  ->orWhere('p.code', 'like', '%' . $search . '%')
                  ->orWhere(DB::raw("CONCAT(COALESCE(c.first_name,''),' ',COALESCE(c.last_name,''))"), 'like', '%' . $search . '%');

                // Token-wise fallback so a hyphenated/spaced name ("Yunjiao-Guan - 61st
                // Ave") still matches a loosely-typed search ("Yunjiao Guan 61st Ave").
                if ($tokens !== []) {
                    $q->orWhere(function ($qq) use ($tokens) {
                        foreach ($tokens as $token) {
                            $qq->where('p.project_name', 'like', '%' . $token . '%');
                        }
                    });
                }
            });
        }

        $matches = $query->limit($maxProjects + 1)->get();

        if ($matches->isEmpty()) {
            return ['authorized' => true, 'projects' => [], 'project_count' => 0];
        }

        $summaries = $matches->take($maxProjects)->map(fn ($p) => [
            'project_id'    => (int) $p->id,
            'project_name'  => $p->project_name,
            'code'          => $p->code ?: '-',
            'customer_name' => trim((string) $p->customer_name) ?: 'N/A',
        ])->values()->all();

        // Multiple matches → let the caller ask which one (by code).
        if ($matches->count() > 1) {
            return ['authorized' => true, 'projects' => $summaries, 'project_count' => $matches->count()];
        }

        return [
            'authorized'    => true,
            'projects'      => [$summaries[0]],
            'project_count' => 1,
            'ledger'        => $this->computeCostLedger($summaries[0]['project_id']),
        ];
    }

    /**
     * Replicate App\Livewire\Project\ProjectCost::mount()+calculateProfit() exactly,
     * with null-safe guards so a missing relation yields 0 instead of throwing.
     */
    private function computeCostLedger(int $projectId): array
    {
        $project  = Project::find($projectId);
        $customer = $project ? $project->customer : null;
        $finances = $customer ? $customer->finances : null;
        $labor    = LaborCost::whereNull('deleted_at')->first();

        $num = static fn ($v) => is_numeric($v) ? (float) $v : 0.0;

        $internalContract = $num(optional($finances)->redline_costs)
            + $num(optional($finances)->adders)
            + $num(optional($finances)->holdback_amount);

        $panelQty   = $num(optional($customer)->panel_qty);
        $moduleCost = $num(optional(optional($customer)->module)->internal_module_cost);
        $rate       = $customer ? optional($customer->inverter)->invertertyperates : null;
        $invBase    = $num(optional($rate)->internal_base_cost);
        $invLabor   = $num(optional($rate)->internal_labor_cost);
        $laborRate  = $num(optional($labor)->cost);

        $estMaterial = ($panelQty * $moduleCost) + $invBase;
        $estLabor    = ($laborRate * $panelQty) + $invLabor;
        $estPermit   = $num(optional($project)->pre_estimated_permit_costs);
        $estProfit   = $internalContract - ($estMaterial + $estLabor + $estPermit);

        $actMaterial = $num(optional($project)->actual_material_cost);
        $actLabor    = $num(optional($project)->actual_labor_cost);
        $actPermit   = $num(optional($project)->actual_permit_fee);
        $actProfit   = $internalContract - ($actMaterial + $actLabor + $actPermit);

        return [
            'internal_contract' => $internalContract,
            'estimated' => [
                'material'   => $estMaterial,
                'labor'      => $estLabor,
                'permit'     => $estPermit,
                'profit'     => $estProfit,
                'profit_pct' => $internalContract != 0.0 ? ($estProfit / $internalContract) * 100 : 0.0,
            ],
            'actual' => [
                'material'   => $actMaterial,
                'labor'      => $actLabor,
                'permit'     => $actPermit,
                'profit'     => $actProfit,
                'profit_pct' => $internalContract != 0.0 ? ($actProfit / $internalContract) * 100 : 0.0,
            ],
        ];
    }

    /**
     * Distinctive lowercase tokens from a free-typed name, dropping generic filler
     * (street suffixes, "project", "the", …) so a token-AND match stays specific.
     *
     * @return array<int,string>
     */
    private function nameTokens(string $search): array
    {
        $filler = ['the', 'of', 'project', 'for', 'and', 'a', 'an', 'ave', 'st', 'street',
            'rd', 'road', 'dr', 'drive', 'blvd', 'ln', 'lane', 'ct', 'way'];

        $parts  = preg_split('/[\s\-]+/u', mb_strtolower(trim($search))) ?: [];
        $tokens = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '' || mb_strlen($part) < 2 || in_array($part, $filler, true)) {
                continue;
            }
            $tokens[] = $part;
        }

        return array_slice(array_values(array_unique($tokens)), 0, 5);
    }

    /**
     * "Who moved project X" / project move history, from the Spatie activity log.
     *
     * Important gotchas this handles (which naive AI-written SQL gets wrong):
     *   - activity_log.subject_type stores the FQCN (App\Models\Project), not 'projects';
     *   - activity_log.subject_id is the project PRIMARY KEY id, not the project code —
     *     so we join projects and match on code/name, never on the raw number;
     *   - move events use event = 'move' with properties {old_lane,new_lane}.
     *
     * Respects the same role scope as the lane reports.
     *
     * @return array{rows: array<int,array>, row_count: int, columns: array<int,string>, project_label: string}
     */
    public function getProjectMoveActivity(User $user, string $search, int $limit = 20): array
    {
        $limit = max(1, min($limit, 50));

        $query = DB::table('activity_log as a')
            ->join('projects as p', 'p.id', '=', 'a.subject_id')
            ->leftJoin('users as u', 'u.id', '=', 'a.causer_id')
            ->where('a.subject_type', \App\Models\Project::class)
            ->where('a.event', 'move')
            ->select([
                'p.project_name',
                'p.code',
                'u.name as moved_by',
                'a.description',
                'a.properties',
                'a.created_at',
            ])
            ->orderByDesc('a.created_at')
            ->orderByDesc('a.id');

        $query = $this->applyProjectScope($query, $user);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('p.project_name', 'like', '%' . $search . '%')
                  ->orWhere('p.code', 'like', '%' . $search . '%');
            });
        }

        $logs = $query->limit($limit)->get();

        if ($logs->isEmpty()) {
            return ['rows' => [], 'row_count' => 0, 'columns' => [], 'project_label' => ''];
        }

        $rows = [];
        foreach ($logs as $logRow) {
            $rows[] = [
                'Project'  => $logRow->project_name,
                'Code'     => $logRow->code ?: '-',
                'Moved By' => $logRow->moved_by ?: 'System',
                'Movement' => $this->describeMove($logRow->properties, $logRow->description),
                'When'     => Carbon::parse($logRow->created_at)->format('d M Y H:i'),
            ];
        }

        $names = array_values(array_unique(array_map(fn ($r) => $r['Project'], $rows)));
        $label = count($names) === 1 ? (string) $names[0] : (count($names) . ' projects');

        return [
            'rows'          => $rows,
            'row_count'     => count($rows),
            'columns'       => array_keys($rows[0]),
            'project_label' => $label,
        ];
    }

    /**
     * Human-readable movement string: "Permitting → Installation" from the
     * properties JSON when present, otherwise the stored activity description.
     */
    private function describeMove(?string $properties, ?string $description): string
    {
        $data = json_decode((string) $properties, true);

        if (is_array($data) && ! empty($data['old_lane']) && ! empty($data['new_lane'])) {
            $old = (string) $data['old_lane'];
            $new = (string) $data['new_lane'];

            return $old === $new ? "Updated within {$new}" : "{$old} → {$new}";
        }

        return trim((string) $description) ?: 'Moved';
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
