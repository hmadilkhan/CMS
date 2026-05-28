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
        private readonly AiPermissionService $aiPermissionService,
        private readonly AiGenericQueryBuilderService $aiGenericQueryBuilderService
    ) {
    }

    public function build(array $plan, User $user): array
    {
        $intent = $plan['intent'] ?? 'unknown';
        
        // Known complex intents with hardcoded logic
        $hardcodedIntents = [
            'employee_department_list',
            'ticket_status',
            'ticket_creator_status_summary',
            'user_role_list',
            'user_role_count',
            'project_status_summary',
            'project_department_summary',
            'project_customer',
            'project_summary',
            'project_acceptance_summary',
            'project_acceptance_count',
            'project_acceptance_list',
            'project_movement_summary',
            'finance_summary',
            'project_financing_summary',
            'forecast_report',
            'forecast_report_by_date_range',
            'override_report',
            'override_report_by_date_range',
            'transaction_report',
            'transaction_report_by_date_range',
            'profitability_report',
            'profitability_report_by_date_range',
            'customer_revenue',
            'crm_list',
            'crm_group_summary',
            'crm_count',
        ];
        
        // Use hardcoded logic for known complex intents
        if (in_array($intent, $hardcodedIntents, true)) {
            return $this->buildWithHardcodedLogic($plan, $user);
        }
        
        // For ALL other intents, use GENERIC BUILDER (handles ANY question!)
        try {
            return $this->aiGenericQueryBuilderService->buildFromPlan($plan, $user);
        } catch (\Throwable $e) {
            \Log::warning('Generic query builder failed, using hardcoded fallback', [
                'error' => $e->getMessage(),
                'intent' => $intent,
            ]);
            
            return $this->buildWithHardcodedLogic($plan, $user);
        }
    }
    
    private function buildWithHardcodedLogic(array $plan, User $user): array
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
        $this->applyDefaultFilters($query, $baseTable);
        $this->applyFilters($query, $plan, $baseTable, $user);
        $this->applyIntent($query, $plan, $baseTable);
        $this->applyRoleScope($query, $user, $baseTable);
        $this->applySort($query, $plan, $user);

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
        if (($plan['intent'] ?? null) === 'employee_department_list') {
            $this->ensureJoin($query, 'employee_departments', 'employees.id', '=', 'employee_departments.employee_id');
            $this->ensureJoin($query, 'departments', 'employee_departments.department_id', '=', 'departments.id');
            $query->select(
                'employees.id as employee_id',
                'employees.name',
                'employees.email',
                'employees.phone',
                DB::raw('GROUP_CONCAT(DISTINCT departments.name ORDER BY departments.name SEPARATOR ", ") as department_names'),
                DB::raw('COUNT(DISTINCT employee_departments.department_id) as department_count')
            )->groupBy('employees.id', 'employees.name', 'employees.email', 'employees.phone');
            return;
        }

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

        if (($plan['intent'] ?? null) === 'user_role_list') {
            $this->ensureJoin($query, 'model_has_roles', 'model_has_roles.model_id', '=', 'users.id');
            $this->ensureJoin($query, 'roles', 'model_has_roles.role_id', '=', 'roles.id');
            $query->select(
                'roles.name as role_name',
                'users.name as user_name',
                'users.username',
                'users.email'
            )
                ->where('model_has_roles.model_type', User::class)
                ->orderBy('roles.name')
                ->orderBy('users.name');
        }

        if (($plan['intent'] ?? null) === 'user_role_count') {
            $this->ensureJoin($query, 'model_has_roles', 'model_has_roles.model_id', '=', 'users.id');
            $this->ensureJoin($query, 'roles', 'model_has_roles.role_id', '=', 'roles.id');
            $query->select(
                'roles.name as role_name',
                DB::raw('count(distinct users.id) as user_count')
            )
                ->where('model_has_roles.model_type', User::class)
                ->groupBy('roles.id', 'roles.name')
                ->orderBy('roles.name');
        }

        if (($plan['intent'] ?? null) === 'project_status_summary') {
            $this->ensureJoin($query, 'tasks', 'tasks.project_id', '=', 'projects.id');
            $query->select('tasks.status', DB::raw('count(distinct projects.id) as aggregate'))
                ->groupBy('tasks.status');
        }

        if (($plan['intent'] ?? null) === 'project_department_summary') {
            $this->ensureJoin($query, 'departments', 'projects.department_id', '=', 'departments.id');

            if (in_array('sub_departments', $plan['tables'] ?? [], true)) {
                $this->ensureJoin($query, 'sub_departments', 'projects.sub_department_id', '=', 'sub_departments.id');
                $query->select(
                    'departments.name as department_name',
                    'sub_departments.name as sub_department_name',
                    DB::raw('count(distinct projects.id) as aggregate')
                )->groupBy('departments.name', 'sub_departments.name');

                return;
            }

            $query->select(
                'departments.name as department_name',
                DB::raw('count(distinct projects.id) as aggregate')
            )->groupBy('departments.name');
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

        if (($plan['intent'] ?? null) === 'project_summary') {
            $this->ensureJoin($query, 'customers', 'projects.customer_id', '=', 'customers.id');
            $this->ensureJoin($query, 'departments', 'projects.department_id', '=', 'departments.id');
            $this->ensureJoin($query, 'sub_departments', 'projects.sub_department_id', '=', 'sub_departments.id');
            $this->ensureJoin($query, 'tasks', 'tasks.project_id', '=', 'projects.id');
            $this->ensureJoin($query, 'employees', 'tasks.employee_id', '=', 'employees.id');
            $this->ensureJoin($query, 'project_acceptances', 'project_acceptances.project_id', '=', 'projects.id');

            $query->select(
                'projects.project_name',
                'projects.code',
                DB::raw("concat(coalesce(customers.first_name, ''), ' ', coalesce(customers.last_name, '')) as customer_name"),
                'customers.phone as customer_phone',
                'customers.email as customer_email',
                'customers.city as customer_city',
                'customers.state as customer_state',
                'departments.name as current_department',
                'sub_departments.name as current_sub_department',
                'tasks.status as latest_task_status',
                'employees.name as assigned_employee_name',
                DB::raw("case project_acceptances.status when 1 then 'Approved' when 2 then 'Rejected' when 0 then 'Pending' else 'Not Initiated' end as acceptance_status"),
                'project_acceptances.approved_date as acceptance_approved_date',
                'project_acceptances.reason as acceptance_reason',
                'projects.start_date',
                'projects.end_date',
                'projects.completion_date',
                'projects.ntp_approval_date',
                'projects.solar_install_date',
                'projects.final_inspection_date',
                'projects.pto_approval_date',
                'projects.updated_at as last_updated_at'
            )
                ->where('tasks.id', function ($latestTaskQuery) {
                    $latestTaskQuery->selectRaw('max(latest_tasks.id)')
                        ->from('tasks as latest_tasks')
                        ->whereColumn('latest_tasks.project_id', 'projects.id');
                })
                ->where(function (Builder $acceptanceQuery) {
                    $acceptanceQuery
                        ->whereNull('project_acceptances.id')
                        ->orWhere('project_acceptances.id', function ($latestAcceptanceQuery) {
                            $latestAcceptanceQuery->selectRaw('max(latest_acceptances.id)')
                                ->from('project_acceptances as latest_acceptances')
                                ->whereColumn('latest_acceptances.project_id', 'projects.id');
                        });
                });
        }

        if (($plan['intent'] ?? null) === 'project_acceptance_summary') {
            $this->ensureJoin($query, 'customers', 'projects.customer_id', '=', 'customers.id');
            $this->ensureJoin($query, 'project_acceptances', 'project_acceptances.project_id', '=', 'projects.id');

            $query->select(
                'projects.project_name',
                'projects.code',
                DB::raw("concat(coalesce(customers.first_name, ''), ' ', coalesce(customers.last_name, '')) as customer_name"),
                DB::raw("case project_acceptances.status when 1 then 'Approved' when 2 then 'Rejected' when 0 then 'Pending' else 'Not Initiated' end as acceptance_status"),
                'project_acceptances.approved_date',
                'project_acceptances.reason',
                'project_acceptances.panel_qty',
                'project_acceptances.inverter_name',
                'project_acceptances.adders_list',
                'project_acceptances.notes',
                DB::raw('(select users.name from users where users.id = project_acceptances.action_by limit 1) as action_by_name'),
                'project_acceptances.created_at as acceptance_created_at',
                'project_acceptances.updated_at as acceptance_updated_at'
            )
                ->where(function (Builder $acceptanceQuery) {
                    $acceptanceQuery
                        ->whereNull('project_acceptances.id')
                        ->orWhere('project_acceptances.id', function ($latestAcceptanceQuery) {
                            $latestAcceptanceQuery->selectRaw('max(latest_acceptances.id)')
                                ->from('project_acceptances as latest_acceptances')
                                ->whereColumn('latest_acceptances.project_id', 'projects.id');
                        });
                });
        }

        if (($plan['intent'] ?? null) === 'project_acceptance_count') {
            $this->ensureJoin($query, 'project_acceptances', 'project_acceptances.project_id', '=', 'projects.id');
            $query->select(DB::raw('count(distinct projects.id) as aggregate'))
                ->where('project_acceptances.id', function ($latestAcceptanceQuery) {
                    $latestAcceptanceQuery->selectRaw('max(latest_acceptances.id)')
                        ->from('project_acceptances as latest_acceptances')
                        ->whereColumn('latest_acceptances.project_id', 'projects.id');
                });
        }

        if (($plan['intent'] ?? null) === 'project_acceptance_list') {
            $this->ensureJoin($query, 'customers', 'projects.customer_id', '=', 'customers.id');
            $this->ensureJoin($query, 'project_acceptances', 'project_acceptances.project_id', '=', 'projects.id');
            $query->select(
                'projects.project_name',
                'projects.code',
                DB::raw("concat(coalesce(customers.first_name, ''), ' ', coalesce(customers.last_name, '')) as customer_name"),
                DB::raw("case project_acceptances.status when 1 then 'Approved' when 2 then 'Rejected' when 0 then 'Pending' else 'Not Initiated' end as acceptance_status"),
                'project_acceptances.approved_date',
                'project_acceptances.reason',
                'project_acceptances.created_at as acceptance_created_at',
                'project_acceptances.updated_at as acceptance_updated_at'
            )
                ->where('project_acceptances.id', function ($latestAcceptanceQuery) {
                    $latestAcceptanceQuery->selectRaw('max(latest_acceptances.id)')
                        ->from('project_acceptances as latest_acceptances')
                        ->whereColumn('latest_acceptances.project_id', 'projects.id');
                })
                ->orderBy('projects.project_name');
        }

        if (($plan['intent'] ?? null) === 'project_movement_summary') {
            $this->ensureJoin($query, 'departments', 'tasks.department_id', '=', 'departments.id');
            $this->ensureJoin($query, 'sub_departments', 'tasks.sub_department_id', '=', 'sub_departments.id');
            $query->select(
                'tasks.project_id',
                'departments.name as department_name',
                'sub_departments.name as sub_department_name',
                'tasks.status',
                'tasks.created_at',
                'tasks.updated_at'
            )->orderBy('tasks.id', 'desc');
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

        if (($plan['intent'] ?? null) === 'project_financing_summary') {
            $this->ensureJoin($query, 'customers', 'projects.customer_id', '=', 'customers.id');
            $this->ensureJoin($query, 'customer_finances', 'customers.id', '=', 'customer_finances.customer_id');
            $this->ensureJoin($query, 'finance_options', 'customer_finances.finance_option_id', '=', 'finance_options.id');

            $query->select(
                'projects.project_name',
                'projects.code',
                DB::raw("concat(coalesce(customers.first_name, ''), ' ', coalesce(customers.last_name, '')) as customer_name"),
                'finance_options.name as finance_option',
                'customer_finances.contract_amount',
                'customer_finances.dealer_fee_amount',
                'customer_finances.commission',
                'customer_finances.holdback_amount',
                'customer_finances.customer_portion',
                'customer_finances.updated_at as finance_updated_at'
            )
                ->whereNotNull('customer_finances.id')
                ->whereNull('customer_finances.deleted_at')
                ->orderByDesc('customer_finances.updated_at');
        }

        if (in_array($plan['intent'] ?? null, ['profitability_report', 'profitability_report_by_date_range'], true)) {
            $this->ensureJoin($query, 'projects', 'customers.id', '=', 'projects.customer_id');
            $this->ensureJoin($query, 'sales_partners', 'customers.sales_partner_id', '=', 'sales_partners.id');
            $this->ensureJoin($query, 'customer_finances', 'customers.id', '=', 'customer_finances.customer_id');

            $query->select(
                'customers.first_name',
                'customers.last_name',
                'sales_partners.name as sales_partner_name',
                'projects.project_name',
                'projects.code',
                'projects.solar_install_date',
                'customer_finances.contract_amount',
                'customer_finances.dealer_fee_amount',
                'customer_finances.redline_costs',
                'customer_finances.adders',
                'customer_finances.commission',
                'projects.actual_material_cost',
                'projects.actual_labor_cost',
                'projects.actual_permit_fee',
                'projects.office_cost',
                DB::raw('(coalesce(projects.actual_permit_fee, 0) + coalesce(projects.actual_labor_cost, 0) + coalesce(projects.actual_material_cost, 0) + coalesce(projects.office_cost, 0)) as actual_job_cost'),
                DB::raw('(coalesce(customer_finances.redline_costs, 0) + (coalesce(cast(customer_finances.adders as decimal(12,2)), 0) - (coalesce(projects.actual_permit_fee, 0) + coalesce(projects.actual_labor_cost, 0) + coalesce(projects.actual_material_cost, 0) + coalesce(projects.office_cost, 0)))) as profit_amount'),
                DB::raw('round(((coalesce(customer_finances.redline_costs, 0) + (coalesce(cast(customer_finances.adders as decimal(12,2)), 0) - (coalesce(projects.actual_permit_fee, 0) + coalesce(projects.actual_labor_cost, 0) + coalesce(projects.actual_material_cost, 0) + coalesce(projects.office_cost, 0)))) / nullif((coalesce(customer_finances.redline_costs, 0) + coalesce(cast(customer_finances.adders as decimal(12,2)), 0)), 0)) * 100, 2) as profit_margin_percent')
            )
                ->whereNotNull('projects.solar_install_date')
                ->whereNotNull('customer_finances.id')
                ->whereNull('customer_finances.deleted_at')
                ->orderByDesc('projects.solar_install_date');
        }

        if (in_array($plan['intent'] ?? null, ['forecast_report', 'forecast_report_by_date_range'], true)) {
            $this->ensureJoin($query, 'sales_partners', 'customers.sales_partner_id', '=', 'sales_partners.id');
            $this->ensureJoin($query, 'customer_finances', 'customers.id', '=', 'customer_finances.customer_id');

            $query->select(
                'customers.sold_date',
                DB::raw("concat(coalesce(customers.first_name, ''), ' ', coalesce(customers.last_name, '')) as customer_name"),
                'sales_partners.name as sales_partner_name',
                'customer_finances.contract_amount',
                'customer_finances.commission',
                'customer_finances.dealer_fee_amount',
                DB::raw('(coalesce(customer_finances.contract_amount, 0) - coalesce(customer_finances.commission, 0) - coalesce(customer_finances.dealer_fee_amount, 0)) as net_sales')
            )
                ->whereNotNull('customers.sold_date')
                ->whereNotNull('customer_finances.id')
                ->whereNull('customer_finances.deleted_at')
                ->orderBy('customers.sold_date');
        }

        if (in_array($plan['intent'] ?? null, ['override_report', 'override_report_by_date_range'], true)) {
            $this->ensureJoin($query, 'projects', 'customers.id', '=', 'projects.customer_id');
            $this->ensureJoin($query, 'sales_partners', 'customers.sales_partner_id', '=', 'sales_partners.id');
            $this->ensureJoin($query, 'users', 'projects.sales_partner_user_id', '=', 'users.id');
            $this->ensureJoin($query, 'customer_finances', 'customers.id', '=', 'customer_finances.customer_id');

            $query->select(
                'customers.sold_date',
                DB::raw("concat(coalesce(customers.first_name, ''), ' ', coalesce(customers.last_name, '')) as customer_name"),
                'sales_partners.name as sales_partner_name',
                'users.name as sales_person_name',
                'customer_finances.redline_costs',
                'customers.panel_qty',
                'projects.overwrite_base_price',
                'projects.overwrite_panel_price',
                DB::raw('(coalesce(customers.panel_qty, 0) * coalesce(projects.overwrite_panel_price, 0)) as total_override_panel_cost'),
                DB::raw('((coalesce(customers.panel_qty, 0) * coalesce(projects.overwrite_panel_price, 0)) + coalesce(projects.overwrite_base_price, 0)) as total_override_cost'),
                DB::raw('(coalesce(customer_finances.redline_costs, 0) - ((coalesce(customers.panel_qty, 0) * coalesce(projects.overwrite_panel_price, 0)) + coalesce(projects.overwrite_base_price, 0))) as actual_redline_cost')
            )
                ->whereNotNull('customers.sold_date')
                ->whereNotNull('customer_finances.id')
                ->whereNull('customer_finances.deleted_at')
                ->orderBy('customers.sold_date');
        }

        if (in_array($plan['intent'] ?? null, ['transaction_report', 'transaction_report_by_date_range'], true)) {
            $this->ensureJoin($query, 'projects', 'account_transactions.project_id', '=', 'projects.id');

            $query->select(
                'projects.project_name',
                DB::raw("case account_transactions.payee when 'sales_partner' then 'Sales Partner' when 'sub_contractor' then 'Sub-Contractor' when 'others' then 'Others' else account_transactions.payee end as payee_label"),
                'account_transactions.milestone',
                'account_transactions.amount',
                'account_transactions.deduction_amount',
                DB::raw('(coalesce(account_transactions.amount, 0) - coalesce(account_transactions.deduction_amount, 0)) as remitted_amount'),
                'account_transactions.transaction_date',
                'account_transactions.transaction_details'
            )
                ->whereNull('account_transactions.deleted_at')
                ->orderByDesc('account_transactions.created_at');
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
            $statusFilter = $this->statusFilterValue($plan);
            $this->ensureJoin($query, 'departments', 'tasks.department_id', '=', 'departments.id');
            $this->ensureJoin($query, 'sub_departments', 'tasks.sub_department_id', '=', 'sub_departments.id');
            $query->select(
                'projects.project_name',
                'projects.code',
                'tasks.status',
                'departments.name as department_name',
                'sub_departments.name as sub_department_name'
            );

            if ($statusFilter && strcasecmp($statusFilter, 'Completed') === 0) {
                $query->where('tasks.id', function ($latestTaskQuery) use ($statusFilter) {
                    $latestTaskQuery->selectRaw('max(latest_tasks.id)')
                        ->from('tasks as latest_tasks')
                        ->whereColumn('latest_tasks.project_id', 'tasks.project_id')
                        ->where('latest_tasks.status', 'like', '%' . $statusFilter . '%');
                });
            } else {
                $query->where('tasks.id', function ($latestTaskQuery) {
                    $latestTaskQuery->selectRaw('max(latest_tasks.id)')
                        ->from('tasks as latest_tasks')
                        ->whereColumn('latest_tasks.project_id', 'tasks.project_id');
                });
            }

            $query
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

            $resolvedColumn = $this->resolveFilterColumn($plan, $filter, $baseTable, $user);

            if (! $resolvedColumn) {
                continue;
            }

            // Handle current_user.id placeholder
            if ($value === 'current_user.id' || $value === 'current_user') {
                $value = $user->id;
            }

            if ($value === 'current_employee.id' || $value === 'current_employee') {
                $query->whereIn($resolvedColumn, Employee::where('user_id', $user->id)->select('id'));
                continue;
            }

            if ($operator === 'like' && $this->isEmployeeNameFilter($plan, $resolvedColumn, $value)) {
                $flexibleNamePattern = $this->flexibleNamePattern((string) $value);
                $query->where(function (Builder $nameQuery) use ($resolvedColumn, $value, $flexibleNamePattern) {
                    $nameQuery->where($resolvedColumn, 'like', '%' . $value . '%')
                        ->orWhere($resolvedColumn, 'like', $flexibleNamePattern);
                });

                continue;
            }

            if ($operator === 'like' && $this->isProjectNameFilter($resolvedColumn, $value)) {
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

    private function applySort(Builder $query, array $plan, User $user): void
    {
        foreach ($plan['sort'] ?? [] as $sort) {
            if (! is_array($sort)) {
                continue;
            }

            $table = (string) ($sort['table'] ?? '');
            $column = (string) ($sort['column'] ?? '');
            $direction = strtolower((string) ($sort['direction'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

            if (! in_array($table, $plan['tables'] ?? [], true) || ! $this->aiPermissionService->canAccessColumn($user, $table, $column)) {
                continue;
            }

            $query->orderBy($this->qualify($table, $column), $direction);
        }
    }

    private function applyDefaultFilters(Builder $query, string $baseTable): void
    {
        foreach ($this->aiSchemaService->getDefaultFilters($baseTable) as $filter) {
            if (! is_array($filter)) {
                continue;
            }

            $column = $filter['column'] ?? null;
            $operator = strtolower($filter['operator'] ?? '=');

            if (! is_string($column) || ! $this->aiSchemaService->isColumnAllowed($baseTable, $column)) {
                continue;
            }

            $qualified = $this->qualify($baseTable, $column);
            $value = $filter['value'] ?? null;

            if ($operator === '=' && is_null($value)) {
                $query->whereNull($qualified);
                continue;
            }

            if ($operator === '!=' && is_null($value)) {
                $query->whereNotNull($qualified);
                continue;
            }

            $query->where($qualified, $operator, $value);
        }
    }

    private function isEmployeeNameFilter(array $plan, string $resolvedColumn, mixed $value): bool
    {
        return $resolvedColumn === 'employees.name'
            && in_array('employees', $plan['tables'] ?? [], true)
            && is_scalar($value)
            && trim((string) $value) !== '';
    }

    private function isProjectNameFilter(string $resolvedColumn, mixed $value): bool
    {
        return $resolvedColumn === 'projects.project_name'
            && is_scalar($value)
            && trim((string) $value) !== '';
    }

    private function statusFilterValue(array $plan): ?string
    {
        foreach ($plan['filters'] ?? [] as $filter) {
            if (! is_array($filter) || ($filter['column'] ?? null) !== 'status') {
                continue;
            }

            $value = $filter['value'] ?? null;

            return is_scalar($value) ? (string) $value : null;
        }

        return null;
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
        if (($plan['intent'] ?? null) === 'employee_department_list') {
            return ['employee_id', 'name', 'email', 'phone', 'department_names', 'department_count'];
        }

        if (($plan['answer_type'] ?? null) === 'count') {
            return ['aggregate'];
        }

        if (($plan['intent'] ?? null) === 'project_status_summary') {
            return ['status', 'aggregate'];
        }

        if (($plan['intent'] ?? null) === 'project_department_summary') {
            return in_array('sub_departments', $plan['tables'] ?? [], true)
                ? ['department_name', 'sub_department_name', 'aggregate']
                : ['department_name', 'aggregate'];
        }

        if (($plan['intent'] ?? null) === 'ticket_status') {
            return ['status', 'aggregate'];
        }

        if (($plan['intent'] ?? null) === 'ticket_creator_status_summary') {
            return ['user_name', 'pending_count', 'resolved_count', 'total_tickets'];
        }

        if (($plan['intent'] ?? null) === 'user_role_list') {
            return ['role_name', 'user_name', 'username', 'email'];
        }

        if (($plan['intent'] ?? null) === 'user_role_count') {
            return ['role_name', 'user_count'];
        }

        if (($plan['intent'] ?? null) === 'project_customer') {
            return ['id', 'project_name', 'code', 'first_name', 'last_name', 'email', 'phone', 'city', 'state'];
        }

        if (($plan['intent'] ?? null) === 'project_summary') {
            return [
                'project_name',
                'code',
                'customer_name',
                'customer_phone',
                'customer_email',
                'customer_city',
                'customer_state',
                'current_department',
                'current_sub_department',
                'latest_task_status',
                'assigned_employee_name',
                'acceptance_status',
                'acceptance_approved_date',
                'acceptance_reason',
                'start_date',
                'end_date',
                'completion_date',
                'ntp_approval_date',
                'solar_install_date',
                'final_inspection_date',
                'pto_approval_date',
                'last_updated_at',
            ];
        }

        if (($plan['intent'] ?? null) === 'project_acceptance_summary') {
            return [
                'project_name',
                'code',
                'customer_name',
                'acceptance_status',
                'approved_date',
                'reason',
                'panel_qty',
                'inverter_name',
                'adders_list',
                'notes',
                'action_by_name',
                'acceptance_created_at',
                'acceptance_updated_at',
            ];
        }

        if (($plan['intent'] ?? null) === 'project_acceptance_count') {
            return ['aggregate'];
        }

        if (($plan['intent'] ?? null) === 'project_acceptance_list') {
            return [
                'project_name',
                'code',
                'customer_name',
                'acceptance_status',
                'approved_date',
                'reason',
                'acceptance_created_at',
                'acceptance_updated_at',
            ];
        }

        if (($plan['intent'] ?? null) === 'finance_summary') {
            return ['project_name', 'code', 'finance_option', 'financing_status', 'contract_amount', 'dealer_fee_amount', 'commission_amount'];
        }

        if (($plan['intent'] ?? null) === 'project_financing_summary') {
            return [
                'project_name',
                'code',
                'customer_name',
                'finance_option',
                'contract_amount',
                'dealer_fee_amount',
                'commission',
                'holdback_amount',
                'customer_portion',
                'finance_updated_at',
            ];
        }

        if (in_array($plan['intent'] ?? null, ['profitability_report', 'profitability_report_by_date_range'], true)) {
            return [
                'first_name',
                'last_name',
                'sales_partner_name',
                'project_name',
                'code',
                'solar_install_date',
                'contract_amount',
                'dealer_fee_amount',
                'redline_costs',
                'adders',
                'commission',
                'actual_material_cost',
                'actual_labor_cost',
                'actual_permit_fee',
                'office_cost',
                'actual_job_cost',
                'profit_amount',
                'profit_margin_percent',
            ];
        }

        if (in_array($plan['intent'] ?? null, ['forecast_report', 'forecast_report_by_date_range'], true)) {
            return [
                'sold_date',
                'customer_name',
                'sales_partner_name',
                'contract_amount',
                'commission',
                'dealer_fee_amount',
                'net_sales',
            ];
        }

        if (in_array($plan['intent'] ?? null, ['override_report', 'override_report_by_date_range'], true)) {
            return [
                'sold_date',
                'customer_name',
                'sales_partner_name',
                'sales_person_name',
                'redline_costs',
                'panel_qty',
                'overwrite_base_price',
                'overwrite_panel_price',
                'total_override_panel_cost',
                'total_override_cost',
                'actual_redline_cost',
            ];
        }

        if (in_array($plan['intent'] ?? null, ['transaction_report', 'transaction_report_by_date_range'], true)) {
            return [
                'project_name',
                'payee_label',
                'milestone',
                'amount',
                'deduction_amount',
                'remitted_amount',
                'transaction_date',
                'transaction_details',
            ];
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

    private function resolveFilterColumn(array $plan, array $filter, string $baseTable, User $user): ?string
    {
        $column = (string) ($filter['column'] ?? '');
        $table = (string) ($filter['table'] ?? '');

        if ($table !== ''
            && in_array($table, $plan['tables'] ?? [$baseTable], true)
            && $this->aiPermissionService->canAccessColumn($user, $table, $column)) {
            return $this->qualify($table, $column);
        }

        return $this->resolveColumn($plan['tables'] ?? [$baseTable], $column, $user);
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
