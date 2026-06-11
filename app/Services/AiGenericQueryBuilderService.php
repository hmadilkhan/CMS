<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AiGenericQueryBuilderService
{
    public function __construct(
        private readonly AiSchemaService $aiSchemaService,
        private readonly AiPermissionService $aiPermissionService
    ) {
    }

    /**
     * Build query from ANY intent using Laravel Query Builder
     * This is SAFE - no direct SQL from OpenAI
     */
    public function buildFromPlan(array $plan, User $user): array
    {
        $tables = $plan['tables'] ?? [];
        $baseTable = $tables[0] ?? null;

        if (!$baseTable || !$this->aiPermissionService->canAccessTable($user, $baseTable)) {
            throw new InvalidArgumentException('The requested table is not allowed.');
        }

        // Start query
        $query = DB::table($baseTable);

        // Apply JOINs for related tables
        $this->applyJoins($query, $baseTable, array_slice($tables, 1));

        // Determine query type
        $answerType = $plan['answer_type'] ?? 'table';
        $groupBy = $plan['group_by'] ?? [];

        // Apply SELECT based on answer type
        if ($answerType === 'count' && empty($groupBy)) {
            // Simple count
            $query->selectRaw('COUNT(*) as aggregate');
        } elseif (!empty($groupBy)) {
            // Grouped query
            $this->applyGroupedSelect($query, $plan, $baseTable, $user);
        } else {
            // Regular select
            $this->applyRegularSelect($query, $plan, $baseTable, $user);
        }

        // Apply filters from plan
        $this->applyFilters($query, $plan, $baseTable, $user);

        // Apply role-based scope (IMPORTANT!)
        $this->applyRoleScope($query, $user, $baseTable);

        // Apply soft delete filter
        if ($this->aiSchemaService->isColumnAllowed($baseTable, 'deleted_at')) {
            $query->whereNull("{$baseTable}.deleted_at");
        }

        // Apply limit
        $limit = min((int) config('ai.schema.max_query_limit', 100), 100);
        $query->limit($limit);

        return [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'tables' => $this->extractTables($query, $tables),
            'columns' => $plan['columns'] ?? [],
            'limit' => $limit,
            'ai_generated' => false,
            'builder_type' => 'generic',
        ];
    }

    private function applyJoins(Builder $query, string $baseTable, array $joinTables): void
    {
        $relationships = $this->aiSchemaService->getRelationships($baseTable);

        foreach ($joinTables as $joinTable) {
            $joined = false;

            // Try to find relationship from base table
            foreach ($relationships as $relationship) {
                if (($relationship['table'] ?? null) === $joinTable) {
                    $this->ensureJoin(
                        $query,
                        $joinTable,
                        "{$baseTable}.{$relationship['local_key']}",
                        '=',
                        "{$joinTable}.{$relationship['foreign_key']}"
                    );
                    $joined = true;
                    break;
                }
            }

            // Try reverse relationship
            if (!$joined) {
                $reverseRelationships = $this->aiSchemaService->getRelationships($joinTable);
                foreach ($reverseRelationships as $relationship) {
                    if (($relationship['table'] ?? null) === $baseTable) {
                        $this->ensureJoin(
                            $query,
                            $joinTable,
                            "{$joinTable}.{$relationship['local_key']}",
                            '=',
                            "{$baseTable}.{$relationship['foreign_key']}"
                        );
                        $joined = true;
                        break;
                    }
                }
            }
            
            // Special case: If employee_departments is already joined and we need departments
            if (!$joined && $joinTable === 'departments' && in_array('employee_departments', $joinTables)) {
                $this->ensureJoin(
                    $query,
                    'departments',
                    'employee_departments.department_id',
                    '=',
                    'departments.id'
                );
            }
        }
    }

    private function applyRegularSelect(Builder $query, array $plan, string $baseTable, User $user): void
    {
        $columns = [];
        $tables = $plan['tables'] ?? [$baseTable];

        foreach ($plan['columns'] ?? [] as $column) {
            $resolved = $this->resolveColumn($tables, $column, $user);
            if ($resolved) {
                $columns[] = $resolved;
            }
        }

        // If no columns specified, select ID
        if (empty($columns)) {
            $columns[] = "{$baseTable}.id";
        }

        $query->select($columns);
    }

    private function applyGroupedSelect(Builder $query, array $plan, string $baseTable, User $user): void
    {
        $selects = [];
        $groupByColumns = [];
        $tables = $plan['tables'] ?? [$baseTable];
        $intent = $plan['intent'] ?? '';

        // Special handling for employee-department many-to-many
        if ($intent === 'employee_department_list' && in_array('employee_departments', $tables)) {
            // Ensure departments table is joined
            $this->ensureJoin(
                $query,
                'departments',
                'employee_departments.department_id',
                '=',
                'departments.id'
            );
            
            // Select employee columns
            $selects[] = DB::raw('employees.id as employee_id');
            $selects[] = DB::raw('employees.name as name');
            $selects[] = DB::raw('employees.email as email');
            $selects[] = DB::raw('employees.phone as phone');
            
            // Aggregate department names using GROUP_CONCAT
            $selects[] = DB::raw('GROUP_CONCAT(DISTINCT departments.name SEPARATOR ", ") as department_names');
            $selects[] = DB::raw('COUNT(DISTINCT employee_departments.department_id) as department_count');
            
            $groupByColumns = ['employees.id', 'employees.name', 'employees.email', 'employees.phone'];
        } else {
            // Regular grouped query
            // Add group by columns to select
            foreach ($plan['group_by'] ?? [] as $column) {
                $resolved = $this->resolveColumn($tables, $column, $user);
                if ($resolved) {
                    $alias = $this->getColumnAlias($column);
                    $selects[] = DB::raw("{$resolved} as {$alias}");
                    $groupByColumns[] = $resolved;
                }
            }

            // Add aggregate
            $selects[] = DB::raw('COUNT(*) as aggregate');
        }

        $query->select($selects);

        if (!empty($groupByColumns)) {
            $query->groupBy($groupByColumns);
        }
    }

    private function applyFilters(Builder $query, array $plan, string $baseTable, User $user): void
    {
        $tables = $plan['tables'] ?? [$baseTable];

        foreach ($plan['filters'] ?? [] as $filter) {
            if (!is_array($filter)) {
                continue;
            }

            $column = $filter['column'] ?? null;
            $operator = strtolower($filter['operator'] ?? '=');
            $value = $filter['value'] ?? null;

            if (!$column) {
                continue;
            }

            // Handle current_user placeholder
            if ($value === 'current_user.id' || $value === 'current_user') {
                $value = $user->id;
            }

            $resolvedColumn = $this->resolveColumn($tables, $column, $user);
            if (!$resolvedColumn) {
                continue;
            }

            // IS NULL / IS NOT NULL — explicit operator or implicit (= / != with null value)
            if ($operator === 'is_null' || ($operator === '=' && is_null($value))) {
                $query->whereNull($resolvedColumn);
                continue;
            }

            if ($operator === 'is_not_null' || (in_array($operator, ['!=', '<>'], true) && is_null($value))) {
                $query->whereNotNull($resolvedColumn);
                continue;
            }

            // Apply filter based on operator
            match ($operator) {
                'like' => $query->where($resolvedColumn, 'like', '%' . $value . '%'),
                'not_like', 'not like' => $query->where($resolvedColumn, 'not like', '%' . $value . '%'),
                'in' => is_array($value) ? $query->whereIn($resolvedColumn, $value) : null,
                'between' => is_array($value) && count($value) === 2
                    ? $query->whereBetween($resolvedColumn, [$value[0], $value[1]])
                    : null,
                '>', '>=', '<', '<=', '!=', '<>' => $query->where($resolvedColumn, $operator, $value),
                default => $query->where($resolvedColumn, '=', $value),
            };
        }
    }

    private function applyRoleScope(Builder $query, User $user, string $baseTable): void
    {
        // Admin sees everything
        if ($user->hasAnyRole(['Super Admin', 'Admin'])) {
            return;
        }

        // Apply role-based filters
        if ($baseTable === 'projects') {
            if ($user->hasRole('Sales Person')) {
                $query->where('projects.sales_partner_user_id', $user->id);
            } elseif ($user->hasRole('Sub-Contractor User')) {
                $query->where('projects.sub_contractor_user_id', $user->id);
            } elseif ($user->hasRole('Employee')) {
                // Join with tasks and employees
                $this->ensureJoin($query, 'tasks', 'tasks.project_id', '=', 'projects.id');
                $this->ensureJoin($query, 'employees', 'tasks.employee_id', '=', 'employees.id');
                $query->where('employees.user_id', $user->id);
            } elseif ($user->hasRole('Manager')) {
                // Filter by department
                $employee = \App\Models\Employee::where('user_id', $user->id)->first();
                if ($employee) {
                    $departmentIds = \App\Models\EmployeeDepartment::where('employee_id', $employee->id)
                        ->pluck('department_id');
                    $query->whereIn('projects.department_id', $departmentIds);
                }
            }
        } elseif ($baseTable === 'service_tickets') {
            if (!$user->hasAnyRole(['Super Admin', 'Admin'])) {
                $query->where(function ($q) use ($user) {
                    $q->where('service_tickets.user_id', $user->id)
                      ->orWhere('service_tickets.assigned_to', $user->id);
                });
            }
        } elseif ($baseTable === 'tasks') {
            if ($user->hasRole('Employee')) {
                $this->ensureJoin($query, 'employees', 'tasks.employee_id', '=', 'employees.id');
                $query->where('employees.user_id', $user->id);
            }
        }
    }

    private function resolveColumn(array $tables, string $column, User $user): ?string
    {
        foreach ($tables as $table) {
            if ($this->aiPermissionService->canAccessColumn($user, $table, $column)) {
                return "{$table}.{$column}";
            }
        }
        return null;
    }

    private function getColumnAlias(string $column): string
    {
        return match ($column) {
            'name' => 'name',
            'status' => 'status',
            'priority' => 'priority',
            default => $column,
        };
    }

    private function ensureJoin(Builder $query, string $table, string $first, string $operator, string $second): void
    {
        $alreadyJoined = collect($query->joins ?? [])->contains(fn($join) => $join->table === $table);
        
        if (!$alreadyJoined) {
            $query->leftJoin($table, $first, $operator, $second);
        }
    }

    private function extractTables(Builder $query, array $plannedTables): array
    {
        $tables = $plannedTables;
        
        foreach ($query->joins ?? [] as $join) {
            $tables[] = $join->table;
        }
        
        return array_values(array_unique(array_filter($tables)));
    }
}
