<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeDepartment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AiSqlBuilderService
{
    public function __construct(
        private readonly AiSchemaService $aiSchemaService,
        private readonly AiPermissionService $aiPermissionService
    ) {
    }

    public function build(array $plan, User $user): array
    {
        // Security: never execute OpenAI SQL directly. Build SELECT queries with Laravel's query builder.
        // Security: always apply schema validation, role filters, and SQL validator approval before execution.
        $tables = $plan['tables'] ?? [];
        $baseTable = $tables[0] ?? null;

        if (! $baseTable || ! $this->aiPermissionService->canAccessTable($user, $baseTable)) {
            throw new InvalidArgumentException('The requested table is not allowed.');
        }

        $query = DB::table($baseTable);
        $this->applyJoins($query, $baseTable, array_slice($tables, 1));
        $this->applySelect($query, $plan, $baseTable, $user);
        $this->applyFilters($query, $plan, $baseTable, $user);
        $this->applyIntent($query, $plan, $baseTable);
        $this->applyRoleScope($query, $user, $baseTable);

        $limit = (int) config('ai.schema.max_query_limit', 100);
        $limit = max(1, min($limit, 100));
        $query->limit($limit);

        return [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'tables' => $this->queryTables($query, $tables),
            'columns' => $this->selectedColumns($plan, $baseTable),
            'limit' => $limit,
        ];
    }

    private function applySelect(Builder $query, array $plan, string $baseTable, User $user): void
    {
        if (($plan['answer_type'] ?? null) === 'count') {
            $query->selectRaw('count(*) as aggregate');
            return;
        }

        $tables = $plan['tables'] ?? [$baseTable];
        $columns = collect($plan['columns'] ?? [])
            ->map(fn ($column) => $this->resolveColumn($tables, $column, $user))
            ->filter()
            ->values()
            ->all();

        $query->select($columns ?: [$this->qualify($baseTable, 'id')]);
    }

    private function applyIntent(Builder $query, array $plan, string $baseTable): void
    {
        if (($plan['intent'] ?? null) === 'ticket_status') {
            $query->select($this->qualify($baseTable, 'status'), DB::raw('count(*) as aggregate'))
                ->groupBy($this->qualify($baseTable, 'status'));
        }

        if (($plan['intent'] ?? null) === 'ticket_creator_status_summary') {
            $this->ensureJoin($query, 'users', 'service_tickets.user_id', '=', 'users.id');
            $query->select(
                'users.name as user_name',
                DB::raw("sum(case when service_tickets.status = 'Pending' then 1 else 0 end) as pending_count"),
                DB::raw("sum(case when service_tickets.status = 'Resolved' then 1 else 0 end) as resolved_count"),
                DB::raw('count(*) as total_tickets')
            )->groupBy('users.id', 'users.name');
        }

        if (($plan['intent'] ?? null) === 'project_status_summary') {
            $this->ensureJoin($query, 'tasks', 'tasks.project_id', '=', 'projects.id');
            $query->select('tasks.status', DB::raw('count(distinct projects.id) as aggregate'))
                ->groupBy('tasks.status');
        }

        if (($plan['intent'] ?? null) === 'project_department_summary') {
            $this->ensureJoin($query, 'departments', 'projects.department_id', '=', 'departments.id');
            $this->ensureJoin($query, 'sub_departments', 'projects.sub_department_id', '=', 'sub_departments.id');
            $query->select(
                'departments.name as department_name',
                'sub_departments.name as sub_department_name',
                DB::raw('count(distinct projects.id) as aggregate')
            )->groupBy('departments.name', 'sub_departments.name');
        }

        if (($plan['intent'] ?? null) === 'project_customer') {
            $this->ensureJoin($query, 'customers', 'projects.customer_id', '=', 'customers.id');
            $query->select(
                'projects.id',
                'projects.project_name',
                'projects.code',
                'customers.first_name',
                'customers.last_name',
                'customers.email',
                'customers.phone',
                'customers.city',
                'customers.state'
            );
        }

        if (($plan['intent'] ?? null) === 'finance_summary') {
            $this->ensureJoin($query, 'projects', 'project_finances.project_id', '=', 'projects.id');
            $query->select(
                'projects.project_name',
                'projects.code',
                'project_finances.finance_option',
                'project_finances.financing_status',
                'project_finances.contract_amount',
                'project_finances.dealer_fee_amount',
                'project_finances.commission_amount'
            );
        }

        if (($plan['intent'] ?? null) === 'profitability_report') {
            $this->ensureJoin($query, 'projects', 'profitability_reports.project_id', '=', 'projects.id');
            $query->select(
                'projects.project_name',
                'projects.code',
                'profitability_reports.total_revenue',
                'profitability_reports.total_expense',
                'profitability_reports.gross_profit',
                'profitability_reports.margin_percent',
                'profitability_reports.report_date'
            );
        }

        if (($plan['intent'] ?? null) === 'customer_revenue') {
            $this->ensureJoin($query, 'customers', 'project_revenue.customer_id', '=', 'customers.id');
            $query->select(
                'customers.first_name',
                'customers.last_name',
                DB::raw('sum(project_revenue.revenue_amount) as revenue_amount')
            )->groupBy('project_revenue.customer_id', 'customers.first_name', 'customers.last_name');
        }

        if (($plan['intent'] ?? null) === 'crm_group_summary') {
            $groups = collect($plan['group_by'] ?? $plan['columns'] ?? [])
                ->map(fn ($column) => $this->resolveColumnMeta($plan['tables'] ?? [$baseTable], $column))
                ->filter()
                ->values();

            if ($groups->isEmpty()) {
                return;
            }

            $selects = $groups
                ->map(fn (array $meta) => DB::raw($meta['qualified'] . ' as ' . $meta['alias']))
                ->push(DB::raw('count(*) as aggregate'))
                ->all();

            $query->select($selects)->groupBy($groups->pluck('qualified')->all());
        }

        if (
            ($plan['intent'] ?? null) === 'crm_list'
            && $baseTable === 'tasks'
            && in_array('projects', $plan['tables'] ?? [], true)
            && in_array('employees', $plan['tables'] ?? [], true)
        ) {
            $this->ensureJoin($query, 'projects', 'tasks.project_id', '=', 'projects.id');
            $this->ensureJoin($query, 'employees', 'tasks.employee_id', '=', 'employees.id');
            $query->select(
                'projects.project_name',
                'projects.code',
                'employees.name as assigned_employee_name'
            )
                ->whereIn('tasks.status', ['In-Progress', 'Hold', 'Cancelled'])
                ->where('tasks.id', function ($latestTaskQuery) {
                    $latestTaskQuery->selectRaw('max(latest_tasks.id)')
                        ->from('tasks as latest_tasks')
                        ->whereColumn('latest_tasks.project_id', 'tasks.project_id')
                        ->whereIn('latest_tasks.status', ['In-Progress', 'Hold', 'Cancelled']);
                })
                ->whereNotNull('projects.id')
                ->whereNotNull('projects.project_name')
                ->where('projects.project_name', '!=', '')
                ->whereNotNull('employees.name')
                ->where('employees.name', '!=', '')
                ->groupBy('projects.id', 'projects.project_name', 'projects.code', 'employees.name');

            return;
        }

        if (($plan['intent'] ?? null) === 'crm_list' && $baseTable === 'tasks' && in_array('projects', $plan['tables'] ?? [], true)) {
            $this->ensureJoin($query, 'departments', 'tasks.department_id', '=', 'departments.id');
            $this->ensureJoin($query, 'sub_departments', 'tasks.sub_department_id', '=', 'sub_departments.id');
            $query->select(
                'projects.project_name',
                'projects.code',
                'tasks.status',
                'departments.name as department_name',
                'sub_departments.name as sub_department_name'
            );

            $query->where('tasks.id', function ($latestTaskQuery) {
                $latestTaskQuery->selectRaw('max(latest_tasks.id)')
                    ->from('tasks as latest_tasks')
                    ->whereColumn('latest_tasks.project_id', 'tasks.project_id');
            })
                ->whereNotNull('projects.id')
                ->whereNotNull('projects.project_name')
                ->where('projects.project_name', '!=', '')
                ->whereNotNull('projects.code')
                ->where('projects.code', '!=', '');
        }
    }

    private function applyFilters(Builder $query, array $plan, string $baseTable, User $user): void
    {
        foreach ($plan['filters'] ?? [] as $filter) {
            if (! is_array($filter)) {
                continue;
            }

            $column = $filter['column'] ?? null;
            $operator = strtolower($filter['operator'] ?? '=');
            $value = $filter['value'] ?? null;

            if (! $column) {
                continue;
            }

            $resolvedColumn = $this->resolveColumn($plan['tables'] ?? [$baseTable], $column, $user);

            if (! $resolvedColumn) {
                continue;
            }

            $value = $value === 'current_user.id' ? $user->id : $value;

            if ($operator === 'like' && $this->isEmployeeNameFilter($plan, $resolvedColumn, $value)) {
                $flexibleNamePattern = $this->flexibleNamePattern((string) $value);
                $query->where(function (Builder $nameQuery) use ($resolvedColumn, $value, $flexibleNamePattern) {
                    $nameQuery->where($resolvedColumn, 'like', '%' . $value . '%')
                        ->orWhere($resolvedColumn, 'like', $flexibleNamePattern);
                });

                continue;
            }

            match ($operator) {
                'between' => is_array($value) && count($value) === 2
                    ? $query->whereBetween($resolvedColumn, [$value[0], $value[1]])
                    : null,
                'in' => is_array($value)
                    ? $query->whereIn($resolvedColumn, $value)
                    : null,
                'like' => $query->where($resolvedColumn, 'like', '%' . $value . '%'),
                'not_like', 'not like' => $query->where($resolvedColumn, 'not like', '%' . $value . '%'),
                '>', '>=', '<', '<=', '!=', '<>' => $query->where($resolvedColumn, $operator, $value),
                default => $query->where($resolvedColumn, '=', $value),
            };
        }
    }

    private function isEmployeeNameFilter(array $plan, string $resolvedColumn, mixed $value): bool
    {
        return $resolvedColumn === 'employees.name'
            && in_array('employees', $plan['tables'] ?? [], true)
            && is_scalar($value)
            && trim((string) $value) !== '';
    }

    private function flexibleNamePattern(string $value): string
    {
        $parts = preg_split('/[\s-]+/', trim($value), -1, PREG_SPLIT_NO_EMPTY);

        return '%' . implode('%', $parts ?: [$value]) . '%';
    }

    private function applyRoleScope(Builder $query, User $user, string $baseTable): void
    {
        if ($user->hasAnyRole(['Super Admin', 'Admin']) || $this->aiPermissionService->canAccessFinance($user)) {
            return;
        }

        if ($baseTable === 'projects') {
            if ($user->hasRole('Sales Person')) {
                $query->where('projects.sales_partner_user_id', $user->id);
                return;
            }

            if ($user->hasRole('Sub-Contractor User')) {
                $query->where('projects.sub_contractor_user_id', $user->id);
                return;
            }

            if ($user->hasRole('Employee')) {
                $employeeIds = Employee::where('user_id', $user->id)->select('id');
                $query->whereIn('projects.id', Task::whereIn('employee_id', $employeeIds)->select('project_id'));
                return;
            }

            if ($user->hasRole('Manager')) {
                $departmentIds = EmployeeDepartment::whereIn('employee_id', Employee::where('user_id', $user->id)->select('id'))->select('department_id');
                $query->whereIn('projects.department_id', $departmentIds);
                return;
            }

            if ($user->hasRole('Sales Manager')) {
                $query->join('customers as access_customers', 'access_customers.id', '=', 'projects.customer_id')
                    ->where('access_customers.sales_partner_id', $user->sales_partner_id);
                return;
            }

            if ($user->hasRole('Sub-Contractor Manager')) {
                $query->join('customers as access_customers', 'access_customers.id', '=', 'projects.customer_id')
                    ->where('access_customers.sub_contractor_id', $user->sales_partner_id);
                return;
            }
        }

        if ($baseTable === 'service_tickets') {
            $query->where(function (Builder $ticketQuery) use ($user) {
                $ticketQuery->where('service_tickets.user_id', $user->id)
                    ->orWhere('service_tickets.assigned_to', $user->id);
            });
        }

        if ($baseTable === 'tasks') {
            if ($user->hasRole('Employee')) {
                $employeeIds = Employee::where('user_id', $user->id)->select('id');
                $query->whereIn('tasks.employee_id', $employeeIds);
                return;
            }

            if ($user->hasRole('Manager')) {
                $departmentIds = EmployeeDepartment::whereIn('employee_id', Employee::where('user_id', $user->id)->select('id'))->select('department_id');
                $query->whereIn('tasks.department_id', $departmentIds);
            }
        }
    }

    private function applyJoins(Builder $query, string $baseTable, array $joinTables): void
    {
        $relationships = $this->aiSchemaService->getRelationships($baseTable);

        foreach ($joinTables as $joinTable) {
            $joined = false;

            foreach ($relationships as $relationship) {
                if (($relationship['table'] ?? null) !== $joinTable) {
                    continue;
                }

                $this->ensureJoin(
                    $query,
                    $joinTable,
                    $this->qualify($baseTable, $relationship['local_key']),
                    '=',
                    $this->qualify($joinTable, $relationship['foreign_key'])
                );

                $joined = true;
                break;
            }

            if ($joined) {
                continue;
            }

            foreach ($this->aiSchemaService->getRelationships($joinTable) as $relationship) {
                if (($relationship['table'] ?? null) !== $baseTable) {
                    continue;
                }

                $this->ensureJoin(
                    $query,
                    $joinTable,
                    $this->qualify($joinTable, $relationship['local_key']),
                    '=',
                    $this->qualify($baseTable, $relationship['foreign_key'])
                );

                break;
            }
        }
    }

    private function ensureJoin(Builder $query, string $table, string $first, string $operator, string $second): void
    {
        $alreadyJoined = collect($query->joins ?? [])->contains(fn ($join) => $join->table === $table);

        if (! $alreadyJoined) {
            $query->leftJoin($table, $first, $operator, $second);
        }
    }

    private function qualify(string $table, string $column): string
    {
        return $table . '.' . $column;
    }

    private function selectedColumns(array $plan, string $baseTable): array
    {
        if (($plan['answer_type'] ?? null) === 'count') {
            return ['aggregate'];
        }

        if (($plan['intent'] ?? null) === 'project_status_summary') {
            return ['status', 'aggregate'];
        }

        if (($plan['intent'] ?? null) === 'project_department_summary') {
            return ['department_name', 'sub_department_name', 'aggregate'];
        }

        if (($plan['intent'] ?? null) === 'ticket_status') {
            return ['status', 'aggregate'];
        }

        if (($plan['intent'] ?? null) === 'ticket_creator_status_summary') {
            return ['user_name', 'pending_count', 'resolved_count', 'total_tickets'];
        }

        if (($plan['intent'] ?? null) === 'project_customer') {
            return ['id', 'project_name', 'code', 'first_name', 'last_name', 'email', 'phone', 'city', 'state'];
        }

        if (($plan['intent'] ?? null) === 'finance_summary') {
            return ['project_name', 'code', 'finance_option', 'financing_status', 'contract_amount', 'dealer_fee_amount', 'commission_amount'];
        }

        if (($plan['intent'] ?? null) === 'profitability_report') {
            return ['project_name', 'code', 'total_revenue', 'total_expense', 'gross_profit', 'margin_percent', 'report_date'];
        }

        if (($plan['intent'] ?? null) === 'customer_revenue') {
            return ['first_name', 'last_name', 'revenue_amount'];
        }

        if (
            ($plan['intent'] ?? null) === 'crm_list'
            && $baseTable === 'tasks'
            && in_array('projects', $plan['tables'] ?? [], true)
            && in_array('employees', $plan['tables'] ?? [], true)
        ) {
            return ['project_name', 'code', 'assigned_employee_name'];
        }

        if (($plan['intent'] ?? null) === 'crm_list' && $baseTable === 'tasks' && in_array('projects', $plan['tables'] ?? [], true)) {
            return ['project_name', 'code', 'status', 'department_name', 'sub_department_name'];
        }

        if (($plan['intent'] ?? null) === 'crm_group_summary') {
            $tables = $plan['tables'] ?? [$baseTable];
            $columns = collect($plan['group_by'] ?? $plan['columns'] ?? [])
                ->map(fn ($column) => $this->resolveColumnMeta($tables, $column))
                ->filter()
                ->map(fn (array $meta) => $meta['alias'])
                ->values()
                ->all();

            return array_merge($columns, ['aggregate']);
        }

        return array_values($plan['columns'] ?? ['id']);
    }

    private function resolveColumn(array $tables, string $column, User $user): ?string
    {
        foreach ($tables as $table) {
            if ($this->aiPermissionService->canAccessColumn($user, $table, $column)) {
                return $this->qualify($table, $column);
            }
        }

        return null;
    }

    private function resolveColumnMeta(array $tables, string $column): ?array
    {
        foreach ($tables as $table) {
            if (! $this->aiSchemaService->isColumnAllowed($table, $column)) {
                continue;
            }

            return [
                'table' => $table,
                'column' => $column,
                'qualified' => $this->qualify($table, $column),
                'alias' => $this->columnAlias($table, $column),
            ];
        }

        return null;
    }

    private function columnAlias(string $table, string $column): string
    {
        if ($column === 'name') {
            return match ($table) {
                'users' => 'user_name',
                'departments' => 'department_name',
                'sub_departments' => 'sub_department_name',
                default => $table . '_name',
            };
        }

        return $column;
    }

    private function queryTables(Builder $query, array $plannedTables): array
    {
        $tables = $plannedTables;

        foreach ($query->joins ?? [] as $join) {
            $tables[] = $this->baseTableName((string) $join->table);
        }

        return array_values(array_unique(array_filter($tables)));
    }

    private function baseTableName(string $table): string
    {
        $table = trim($table);
        $parts = preg_split('/\s+as\s+|\s+/i', $table);

        return $parts[0] ?? $table;
    }
}
