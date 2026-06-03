<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\AiPermissionService;
use App\Services\AiAnswerFormatterService;
use App\Services\AiEntityResolverService;
use App\Services\AiPlanValidatorService;
use App\Services\AiQueryPlannerService;
use App\Services\AiSchemaService;
use App\Services\AiSqlBuilderService;
use App\Services\AiSqlValidatorService;
use App\Services\OpenAiService;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreatesAiChatTestSchema;
use Tests\TestCase;

class AiServicesTest extends TestCase
{
    use CreatesAiChatTestSchema;

    protected function setUp(): void
    {
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';
        $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_DATABASE'] = ':memory:';

        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'permission.cache.store' => 'array',
        ]);

        $this->createAiChatTestSchema();
    }

    public function test_ai_schema_service_returns_safe_defaults(): void
    {
        $schema = app(AiSchemaService::class);

        $this->assertTrue($schema->isTableAllowed('projects'));
        $this->assertFalse($schema->isTableAllowed('missing_table'));
        $this->assertContains('id', $schema->getAllowedColumns('projects'));
        $this->assertSame([], $schema->getAllowedColumns('missing_table'));
        $this->assertSame('admin_only', $schema->getAccessRule('missing_table'));
    }

    public function test_ai_permission_service_blocks_employee_finance(): void
    {
        $employee = $this->userWithRole('Employee');
        $finance = $this->userWithRole('Finance');
        $service = app(AiPermissionService::class);

        $this->assertFalse($service->canAccessFinance($employee));
        $this->assertFalse($service->canAccessTable($employee, 'project_finances'));
        $this->assertTrue($service->canAccessFinance($finance));
        $this->assertTrue($service->canAccessTable($finance, 'project_finances'));
    }

    public function test_ai_sql_validator_rejects_unsafe_sql(): void
    {
        $user = $this->userWithRole('Admin');
        $validator = app(AiSqlValidatorService::class);

        $result = $validator->validate([
            'sql' => 'select * from projects; drop table users',
            'bindings' => [],
            'tables' => ['projects'],
            'columns' => ['id'],
            'limit' => 100,
        ], [
            'tables' => ['projects'],
            'columns' => ['id'],
            'filters' => [],
            'requires_finance_access' => false,
        ], $user);

        $this->assertFalse($result['approved']);
    }

    public function test_ai_sql_validator_approves_safe_select(): void
    {
        $user = $this->userWithRole('Admin');
        $validator = app(AiSqlValidatorService::class);

        $result = $validator->validate([
            'sql' => 'select `projects`.`id` from `projects` limit 100',
            'bindings' => [],
            'tables' => ['projects'],
            'columns' => ['id'],
            'limit' => 100,
        ], [
            'tables' => ['projects'],
            'columns' => ['id'],
            'filters' => [],
            'requires_finance_access' => false,
        ], $user);

        $this->assertTrue($result['approved']);
    }

    public function test_ai_plan_validator_blocks_unknown_table(): void
    {
        $user = $this->userWithRole('Admin');
        $validator = app(AiPlanValidatorService::class);

        $result = $validator->validate([
            'mode' => 'data_explorer',
            'confidence' => 0.9,
            'intent' => 'crm_list',
            'tables' => ['secret_table'],
            'columns' => ['id'],
            'filters' => [],
            'relationships' => [],
            'sort' => [],
            'limit' => 20,
            'requires_finance_access' => false,
            'sql' => null,
        ], $user);

        $this->assertFalse($result['approved']);
    }

    public function test_ai_plan_validator_blocks_sensitive_column_without_permission(): void
    {
        $user = $this->userWithRole('Employee');
        $validator = app(AiPlanValidatorService::class);

        $result = $validator->validate([
            'mode' => 'data_explorer',
            'confidence' => 0.9,
            'intent' => 'finance_summary',
            'tables' => ['customer_finances'],
            'columns' => ['contract_amount'],
            'filters' => [],
            'relationships' => [],
            'sort' => [],
            'limit' => 20,
            'requires_finance_access' => true,
            'sql' => null,
        ], $user);

        $this->assertFalse($result['approved']);
    }

    public function test_ai_plan_validator_blocks_raw_sql(): void
    {
        $user = $this->userWithRole('Admin');
        $validator = app(AiPlanValidatorService::class);

        $result = $validator->validate([
            'mode' => 'data_explorer',
            'confidence' => 0.9,
            'intent' => 'crm_list',
            'tables' => ['projects'],
            'columns' => ['id'],
            'filters' => [],
            'relationships' => [],
            'sort' => [],
            'limit' => 20,
            'requires_finance_access' => false,
            'sql' => 'select * from projects',
        ], $user);

        $this->assertFalse($result['approved']);
    }

    public function test_ai_plan_validator_rejects_low_confidence_for_clarification(): void
    {
        $user = $this->userWithRole('Admin');
        $validator = app(AiPlanValidatorService::class);

        $result = $validator->validate([
            'mode' => 'data_explorer',
            'confidence' => 0.4,
            'intent' => 'crm_list',
            'tables' => ['projects'],
            'columns' => ['id'],
            'filters' => [],
            'relationships' => [],
            'sort' => [],
            'limit' => 20,
            'requires_finance_access' => false,
            'sql' => null,
        ], $user);

        $this->assertFalse($result['approved']);
        $this->assertStringContainsString('clarification', strtolower($result['reason']));
    }

    public function test_ai_plan_validator_rejects_limit_above_100(): void
    {
        $user = $this->userWithRole('Admin');
        $validator = app(AiPlanValidatorService::class);

        $result = $validator->validate([
            'mode' => 'data_explorer',
            'confidence' => 0.9,
            'intent' => 'crm_list',
            'tables' => ['projects'],
            'columns' => ['id'],
            'filters' => [],
            'relationships' => [],
            'sort' => [],
            'limit' => 500,
            'requires_finance_access' => false,
            'sql' => null,
        ], $user);

        $this->assertFalse($result['approved']);
    }

    public function test_ai_entity_resolver_asks_clarification_for_ambiguous_project_name(): void
    {
        \Illuminate\Support\Facades\Schema::create('projects', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('project_name')->nullable();
        });

        \Illuminate\Support\Facades\DB::table('projects')->insert([
            ['project_name' => 'Solar Project A'],
            ['project_name' => 'Solar Project B'],
        ]);

        $resolver = app(AiEntityResolverService::class);
        $result = $resolver->resolve([
            'mode' => 'data_explorer',
            'intent' => 'crm_list',
            'tables' => ['projects'],
            'columns' => ['id', 'project_name'],
            'filters' => [
                ['table' => 'projects', 'column' => 'project_name', 'operator' => '=', 'value' => 'Solar Project'],
            ],
        ]);

        $this->assertSame('clarification_required', $result['status']);
        $this->assertCount(2, $result['options']);
    }

    public function test_ai_sql_builder_creates_select_with_limit(): void
    {
        $user = $this->userWithRole('Admin');
        $builder = app(AiSqlBuilderService::class);

        $preview = $builder->build([
            'answer_type' => 'count',
            'intent' => 'project_count',
            'tables' => ['projects'],
            'columns' => ['id'],
            'filters' => [],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => null,
        ], $user);

        $this->assertStringStartsWith('select', strtolower($preview['sql']));
        $this->assertStringContainsString('limit 100', strtolower($preview['sql']));
        $this->assertSame(['projects'], $preview['tables']);
    }

    public function test_ai_sql_builder_groups_projects_by_department_and_subdepartment(): void
    {
        $user = $this->userWithRole('Admin');
        $builder = app(AiSqlBuilderService::class);

        $preview = $builder->build([
            'answer_type' => 'table',
            'intent' => 'project_department_summary',
            'tables' => ['projects', 'departments', 'sub_departments'],
            'columns' => ['department_id', 'sub_department_id', 'name'],
            'filters' => [],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => null,
        ], $user);

        $this->assertStringContainsString('departments', $preview['sql']);
        $this->assertStringContainsString('sub_departments', $preview['sql']);
        $this->assertStringContainsString('count(distinct projects.id)', strtolower($preview['sql']));
        $this->assertSame(['department_name', 'sub_department_name', 'aggregate'], $preview['columns']);
    }

    public function test_ai_sql_builder_groups_projects_by_department_only(): void
    {
        $user = $this->userWithRole('Admin');
        $builder = app(AiSqlBuilderService::class);

        $preview = $builder->build([
            'answer_type' => 'table',
            'intent' => 'project_department_summary',
            'tables' => ['projects', 'departments'],
            'columns' => ['department_id', 'name'],
            'group_by' => ['name'],
            'filters' => [],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => null,
        ], $user);

        $sql = strtolower($preview['sql']);

        $this->assertStringContainsString('departments', $sql);
        $this->assertStringNotContainsString('sub_departments', $sql);
        $this->assertStringContainsString('group by "departments"."name"', $sql);
        $this->assertSame(['department_name', 'aggregate'], $preview['columns']);
    }

    public function test_ai_sql_builder_counts_projects_for_named_department(): void
    {
        $user = $this->userWithRole('Admin');
        $builder = app(AiSqlBuilderService::class);

        $preview = $builder->build([
            'answer_type' => 'count',
            'intent' => 'project_count',
            'tables' => ['projects', 'departments'],
            'columns' => ['id', 'name'],
            'filters' => [
                [
                    'column' => 'name',
                    'operator' => 'like',
                    'value' => 'deal review',
                ],
            ],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => null,
        ], $user);

        $this->assertStringContainsString('departments', $preview['sql']);
        $this->assertStringContainsString('name', strtolower($preview['sql']));
        $this->assertStringContainsString('like', strtolower($preview['sql']));
        $this->assertContains('%deal review%', $preview['bindings']);
    }

    public function test_ai_sql_builder_creates_project_summary_with_names_and_acceptance(): void
    {
        $user = $this->userWithRole('Super Admin');
        $builder = app(AiSqlBuilderService::class);

        $preview = $builder->build([
            'answer_type' => 'card',
            'intent' => 'project_summary',
            'tables' => ['projects', 'customers', 'departments', 'sub_departments', 'tasks', 'employees', 'project_acceptances'],
            'columns' => ['project_name', 'code', 'first_name', 'last_name', 'name', 'status', 'approved_date', 'reason'],
            'filters' => [
                [
                    'column' => 'project_name',
                    'operator' => 'like',
                    'value' => 'susan stauffer',
                ],
            ],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => null,
        ], $user);

        $sql = strtolower($preview['sql']);

        $this->assertStringContainsString('customers', $sql);
        $this->assertStringContainsString('departments', $sql);
        $this->assertStringContainsString('sub_departments', $sql);
        $this->assertStringContainsString('project_acceptances', $sql);
        $this->assertStringContainsString('latest_tasks', $sql);
        $this->assertStringContainsString('latest_acceptances', $sql);
        $this->assertContains('%susan stauffer%', $preview['bindings']);
        $this->assertContains('%susan%stauffer%', $preview['bindings']);
        $this->assertSame('project_name', $preview['columns'][0]);
        $this->assertContains('current_department', $preview['columns']);
        $this->assertContains('acceptance_status', $preview['columns']);
    }

    public function test_ai_sql_builder_creates_project_acceptance_summary(): void
    {
        $user = $this->userWithRole('Super Admin');
        $builder = app(AiSqlBuilderService::class);

        $preview = $builder->build([
            'answer_type' => 'card',
            'intent' => 'project_acceptance_summary',
            'tables' => ['projects', 'customers', 'project_acceptances'],
            'columns' => ['project_name', 'code', 'first_name', 'last_name', 'status', 'approved_date', 'reason'],
            'filters' => [
                [
                    'column' => 'project_name',
                    'operator' => 'like',
                    'value' => 'susan',
                ],
            ],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => null,
        ], $user);

        $sql = strtolower($preview['sql']);

        $this->assertStringContainsString('project_acceptances', $sql);
        $this->assertStringContainsString('case project_acceptances.status', $sql);
        $this->assertStringContainsString('project_acceptances.action_by', $sql);
        $this->assertStringContainsString('latest_acceptances', $sql);
        $this->assertContains('%susan%', $preview['bindings']);
        $this->assertSame('acceptance_status', $preview['columns'][3]);
        $this->assertContains('action_by_name', $preview['columns']);
    }

    public function test_ai_sql_builder_counts_projects_by_acceptance_status(): void
    {
        $user = $this->userWithRole('Super Admin');
        $builder = app(AiSqlBuilderService::class);

        $preview = $builder->build([
            'answer_type' => 'count',
            'intent' => 'project_acceptance_count',
            'tables' => ['projects', 'project_acceptances'],
            'columns' => ['id', 'project_id', 'status'],
            'filters' => [
                [
                    'column' => 'status',
                    'operator' => '=',
                    'value' => 0,
                ],
            ],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => null,
        ], $user);

        $sql = strtolower($preview['sql']);

        $this->assertStringContainsString('count(distinct projects.id) as aggregate', $sql);
        $this->assertStringContainsString('project_acceptances', $sql);
        $this->assertStringContainsString('latest_acceptances', $sql);
        $this->assertContains(0, $preview['bindings']);
        $this->assertSame(['aggregate'], $preview['columns']);
    }

    public function test_ai_sql_builder_lists_projects_by_acceptance_status(): void
    {
        $user = $this->userWithRole('Super Admin');
        $builder = app(AiSqlBuilderService::class);

        $preview = $builder->build([
            'answer_type' => 'table',
            'intent' => 'project_acceptance_list',
            'tables' => ['projects', 'customers', 'project_acceptances'],
            'columns' => ['project_name', 'code', 'first_name', 'last_name', 'status', 'approved_date', 'reason'],
            'filters' => [
                [
                    'column' => 'status',
                    'operator' => '=',
                    'value' => 0,
                ],
            ],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => null,
        ], $user);

        $sql = strtolower($preview['sql']);

        $this->assertStringContainsString('project_acceptances', $sql);
        $this->assertStringContainsString('latest_acceptances', $sql);
        $this->assertStringContainsString('acceptance_status', $sql);
        $this->assertContains(0, $preview['bindings']);
        $this->assertSame('acceptance_status', $preview['columns'][3]);
    }

    public function test_ai_sql_builder_groups_ticket_status_counts_by_creator(): void
    {
        $user = $this->userWithRole('Admin');
        $builder = app(AiSqlBuilderService::class);

        $preview = $builder->build([
            'answer_type' => 'table',
            'intent' => 'ticket_creator_status_summary',
            'tables' => ['service_tickets', 'users'],
            'columns' => ['user_id', 'name', 'status'],
            'filters' => [],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => null,
        ], $user);

        $sql = strtolower($preview['sql']);

        $this->assertStringContainsString('users', $sql);
        $this->assertStringContainsString('pending_count', $sql);
        $this->assertStringContainsString('resolved_count', $sql);
        $this->assertStringContainsString('group by', $sql);
        $this->assertSame(['user_name', 'pending_count', 'resolved_count', 'total_tickets'], $preview['columns']);
    }

    public function test_ai_sql_builder_lists_users_by_roles(): void
    {
        $user = $this->userWithRole('Super Admin');
        $builder = app(AiSqlBuilderService::class);

        $preview = $builder->build([
            'answer_type' => 'table',
            'intent' => 'user_role_list',
            'tables' => ['users', 'model_has_roles', 'roles'],
            'columns' => ['name', 'email', 'username', 'role_id', 'model_id'],
            'filters' => [],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => null,
        ], $user);

        $sql = strtolower($preview['sql']);

        $this->assertStringContainsString('model_has_roles', $sql);
        $this->assertStringContainsString('join "roles"', $sql);
        $this->assertStringContainsString('roles"."name" as "role_name"', $sql);
        $this->assertContains(User::class, $preview['bindings']);
        $this->assertSame(['role_name', 'user_name', 'username', 'email'], $preview['columns']);
    }

    public function test_ai_sql_builder_counts_users_by_roles(): void
    {
        $user = $this->userWithRole('Super Admin');
        $builder = app(AiSqlBuilderService::class);

        $preview = $builder->build([
            'answer_type' => 'table',
            'intent' => 'user_role_count',
            'tables' => ['users', 'model_has_roles', 'roles'],
            'columns' => ['name', 'role_id', 'model_id'],
            'group_by' => ['name'],
            'filters' => [],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => null,
        ], $user);

        $sql = strtolower($preview['sql']);

        $this->assertStringContainsString('count(distinct users.id)', $sql);
        $this->assertStringContainsString('group by', $sql);
        $this->assertSame(['role_name', 'user_count'], $preview['columns']);
    }

    public function test_ai_sql_builder_supports_generic_group_summary(): void
    {
        $user = $this->userWithRole('Admin');
        $builder = app(AiSqlBuilderService::class);

        $preview = $builder->build([
            'answer_type' => 'table',
            'intent' => 'crm_group_summary',
            'tables' => ['service_tickets', 'users'],
            'columns' => ['name', 'status'],
            'group_by' => ['name', 'status'],
            'filters' => [],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => null,
        ], $user);

        $sql = strtolower($preview['sql']);

        $this->assertStringContainsString('users.name as user_name', $sql);
        $this->assertStringContainsString('service_tickets.status as status', $sql);
        $this->assertStringContainsString('count(*) as aggregate', $sql);
        $this->assertSame(['user_name', 'status', 'aggregate'], $preview['columns']);
    }

    public function test_ai_sql_builder_lists_projects_by_task_status(): void
    {
        $user = $this->userWithRole('Admin');
        $builder = app(AiSqlBuilderService::class);

        $preview = $builder->build([
            'answer_type' => 'table',
            'intent' => 'crm_list',
            'tables' => ['tasks', 'projects', 'departments', 'sub_departments'],
            'columns' => ['project_name', 'code', 'status', 'name'],
            'group_by' => [],
            'filters' => [
                [
                    'column' => 'status',
                    'operator' => 'like',
                    'value' => 'In-Progress',
                ],
            ],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => null,
        ], $user);

        $sql = strtolower($preview['sql']);

        $this->assertStringContainsString('join "projects"', $sql);
        $this->assertStringContainsString('join "departments"', $sql);
        $this->assertStringContainsString('join "sub_departments"', $sql);
        $this->assertStringContainsString('tasks"."status" like', $sql);
        $this->assertStringContainsString('max(latest_tasks.id)', $sql);
        $this->assertStringContainsString('latest_tasks"."project_id" = "tasks"."project_id"', $sql);
        $this->assertStringContainsString('"projects"."project_name" is not null', $sql);
        $this->assertStringContainsString('"projects"."code" is not null', $sql);
        $this->assertContains('%In-Progress%', $preview['bindings']);
        $this->assertSame(['project_name', 'code', 'status', 'department_name', 'sub_department_name'], $preview['columns']);
    }

    public function test_ai_sql_builder_lists_distinct_projects_assigned_to_employee_name(): void
    {
        $user = $this->userWithRole('Admin');
        $builder = app(AiSqlBuilderService::class);

        $preview = $builder->build([
            'answer_type' => 'table',
            'intent' => 'crm_list',
            'tables' => ['tasks', 'projects', 'employees'],
            'columns' => ['project_name', 'code', 'name'],
            'group_by' => ['project_name', 'code', 'name'],
            'filters' => [
                [
                    'column' => 'name',
                    'operator' => 'like',
                    'value' => 'ibad dawood',
                ],
            ],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => null,
        ], $user);

        $sql = strtolower($preview['sql']);

        $this->assertStringContainsString('join "projects"', $sql);
        $this->assertStringContainsString('join "employees"', $sql);
        $this->assertStringContainsString('"employees"."name" like', $sql);
        $this->assertStringContainsString('employees"."name" as "assigned_employee_name"', $sql);
        $this->assertStringContainsString('"tasks"."status" in', $sql);
        $this->assertStringContainsString('max(latest_tasks.id)', $sql);
        $this->assertStringContainsString('latest_tasks"."project_id" = "tasks"."project_id"', $sql);
        $this->assertStringContainsString('group by "projects"."id"', $sql);
        $this->assertContains('%ibad dawood%', $preview['bindings']);
        $this->assertContains('%ibad%dawood%', $preview['bindings']);
        $this->assertContains('In-Progress', $preview['bindings']);
        $this->assertContains('Hold', $preview['bindings']);
        $this->assertContains('Cancelled', $preview['bindings']);
        $this->assertSame(['project_name', 'code', 'assigned_employee_name'], $preview['columns']);
    }

    public function test_ai_sql_builder_lists_completed_projects_by_latest_completed_task(): void
    {
        $user = $this->userWithRole('Admin');
        $builder = app(AiSqlBuilderService::class);

        $preview = $builder->build([
            'answer_type' => 'table',
            'intent' => 'crm_list',
            'tables' => ['tasks', 'projects', 'departments', 'sub_departments'],
            'columns' => ['project_name', 'code', 'status', 'name'],
            'group_by' => [],
            'filters' => [
                [
                    'column' => 'status',
                    'operator' => 'like',
                    'value' => 'Completed',
                ],
                [
                    'column' => 'name',
                    'operator' => 'not_like',
                    'value' => 'archived',
                ],
                [
                    'column' => 'department_id',
                    'operator' => '!=',
                    'value' => 9,
                ],
            ],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => null,
        ], $user);

        $sql = strtolower($preview['sql']);

        $this->assertStringContainsString('latest_tasks"."status" like', $sql);
        $this->assertContains('%Completed%', $preview['bindings']);
        $this->assertContains('%archived%', $preview['bindings']);
        $this->assertContains(9, $preview['bindings']);
        $this->assertSame(['project_name', 'code', 'status', 'department_name', 'sub_department_name'], $preview['columns']);
    }

    public function test_ai_sql_builder_builds_project_financing_summary_from_real_finance_tables(): void
    {
        $user = $this->userWithRole('Super Admin');
        $builder = app(AiSqlBuilderService::class);

        $preview = $builder->build([
            'answer_type' => 'table',
            'intent' => 'project_financing_summary',
            'tables' => ['projects', 'customers', 'customer_finances', 'finance_options'],
            'columns' => [
                'project_name',
                'code',
                'first_name',
                'last_name',
                'finance_option_id',
                'contract_amount',
                'dealer_fee_amount',
                'commission',
                'holdback_amount',
                'customer_portion',
                'name',
                'created_at',
                'updated_at',
            ],
            'group_by' => [],
            'filters' => [],
            'requires_finance_access' => true,
            'sql' => null,
            'fallback_message' => null,
        ], $user);

        $sql = strtolower($preview['sql']);

        $this->assertStringContainsString('join "customers"', $sql);
        $this->assertStringContainsString('join "customer_finances"', $sql);
        $this->assertStringContainsString('join "finance_options"', $sql);
        $this->assertStringContainsString('"customer_finances"."contract_amount"', $sql);
        $this->assertStringContainsString('"finance_options"."name" as "finance_option"', $sql);
        $this->assertStringContainsString('"customer_finances"."deleted_at" is null', $sql);
        $this->assertSame([
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
        ], $preview['columns']);
    }

    public function test_ai_sql_builder_builds_profitability_report_like_existing_crm_report(): void
    {
        $user = $this->userWithRole('Super Admin');
        $builder = app(AiSqlBuilderService::class);

        $preview = $builder->build([
            'answer_type' => 'table',
            'intent' => 'profitability_report',
            'tables' => ['customers', 'projects', 'sales_partners', 'customer_finances'],
            'columns' => [
                'first_name',
                'last_name',
                'name',
                'solar_install_date',
                'contract_amount',
                'dealer_fee_amount',
                'redline_costs',
                'adders',
                'actual_material_cost',
                'actual_labor_cost',
                'actual_permit_fee',
                'office_cost',
            ],
            'group_by' => [],
            'filters' => [],
            'requires_finance_access' => true,
            'sql' => null,
            'fallback_message' => null,
        ], $user);

        $sql = strtolower($preview['sql']);

        $this->assertStringContainsString('join "projects"', $sql);
        $this->assertStringContainsString('join "sales_partners"', $sql);
        $this->assertStringContainsString('join "customer_finances"', $sql);
        $this->assertStringContainsString('"projects"."solar_install_date" is not null', $sql);
        $this->assertStringContainsString('actual_job_cost', $sql);
        $this->assertStringContainsString('profit_amount', $sql);
        $this->assertStringContainsString('profit_margin_percent', $sql);
        $this->assertSame([
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
        ], $preview['columns']);
    }

    public function test_ai_profitability_planner_filters_date_range_by_solar_install_date(): void
    {
        $user = $this->userWithRole('Super Admin');
        $planner = app(AiQueryPlannerService::class);
        $builder = app(AiSqlBuilderService::class);

        $plan = $planner->plan('Profitability report show kro from 1st April 2026 to 30th April 2026', $user)['plan'];
        $preview = $builder->build($plan, $user);
        $sql = strtolower($preview['sql']);

        $this->assertSame('profitability_report_by_date_range', $plan['intent']);
        $this->assertSame('solar_install_date', $plan['filters'][0]['column']);
        $this->assertSame('2026-04-01', $plan['filters'][0]['value']);
        $this->assertSame('solar_install_date', $plan['filters'][1]['column']);
        $this->assertSame('2026-04-30', $plan['filters'][1]['value']);
        $this->assertStringContainsString('"projects"."solar_install_date" >=', $sql);
        $this->assertStringContainsString('"projects"."solar_install_date" <=', $sql);
        $this->assertContains('2026-04-01', $preview['bindings']);
        $this->assertContains('2026-04-30', $preview['bindings']);
    }

    public function test_ai_forecast_planner_filters_date_range_by_sold_date(): void
    {
        $user = $this->userWithRole('Super Admin');
        $planner = app(AiQueryPlannerService::class);
        $builder = app(AiSqlBuilderService::class);

        $plan = $planner->plan('Forecast report show kro from 1st April 2026 to 30th April 2026', $user)['plan'];
        $preview = $builder->build($plan, $user);
        $sql = strtolower($preview['sql']);

        $this->assertSame('forecast_report_by_date_range', $plan['intent']);
        $this->assertSame('sold_date', $plan['filters'][0]['column']);
        $this->assertSame('2026-04-01', $plan['filters'][0]['value']);
        $this->assertSame('sold_date', $plan['filters'][1]['column']);
        $this->assertSame('2026-04-30', $plan['filters'][1]['value']);
        $this->assertStringContainsString('"customers"."sold_date" >=', $sql);
        $this->assertStringContainsString('"customers"."sold_date" <=', $sql);
        $this->assertStringContainsString('net_sales', $sql);
        $this->assertSame([
            'sold_date',
            'customer_name',
            'sales_partner_name',
            'contract_amount',
            'commission',
            'dealer_fee_amount',
            'net_sales',
        ], $preview['columns']);
    }

    public function test_ai_override_planner_filters_date_range_by_sold_date(): void
    {
        $user = $this->userWithRole('Super Admin');
        $planner = app(AiQueryPlannerService::class);
        $builder = app(AiSqlBuilderService::class);

        $plan = $planner->plan('Overrider report show kro from 1st April 2026 to 30th April 2026', $user)['plan'];
        $preview = $builder->build($plan, $user);
        $sql = strtolower($preview['sql']);

        $this->assertSame('override_report_by_date_range', $plan['intent']);
        $this->assertSame('sold_date', $plan['filters'][0]['column']);
        $this->assertSame('2026-04-01', $plan['filters'][0]['value']);
        $this->assertSame('sold_date', $plan['filters'][1]['column']);
        $this->assertSame('2026-04-30', $plan['filters'][1]['value']);
        $this->assertStringContainsString('"customers"."sold_date" >=', $sql);
        $this->assertStringContainsString('"customers"."sold_date" <=', $sql);
        $this->assertStringContainsString('total_override_panel_cost', $sql);
        $this->assertStringContainsString('total_override_cost', $sql);
        $this->assertStringContainsString('actual_redline_cost', $sql);
        $this->assertSame([
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
        ], $preview['columns']);
    }

    public function test_ai_transaction_planner_filters_date_range_by_transaction_date(): void
    {
        $user = $this->userWithRole('Super Admin');
        $planner = app(AiQueryPlannerService::class);
        $builder = app(AiSqlBuilderService::class);

        $plan = $planner->plan('Transaction report show kro from 1st April 2026 to 30th April 2026', $user)['plan'];
        $preview = $builder->build($plan, $user);
        $sql = strtolower($preview['sql']);

        $this->assertSame('transaction_report_by_date_range', $plan['intent']);
        $this->assertSame('transaction_date', $plan['filters'][0]['column']);
        $this->assertSame('2026-04-01', $plan['filters'][0]['value']);
        $this->assertSame('transaction_date', $plan['filters'][1]['column']);
        $this->assertSame('2026-04-30', $plan['filters'][1]['value']);
        $this->assertStringContainsString('"account_transactions"."transaction_date" >=', $sql);
        $this->assertStringContainsString('"account_transactions"."transaction_date" <=', $sql);
        $this->assertStringContainsString('payee_label', $sql);
        $this->assertStringContainsString('remitted_amount', $sql);
        $this->assertStringContainsString('"account_transactions"."deleted_at" is null', $sql);
        $this->assertSame([
            'project_name',
            'payee_label',
            'milestone',
            'amount',
            'deduction_amount',
            'remitted_amount',
            'transaction_date',
            'transaction_details',
        ], $preview['columns']);
    }

    public function test_ai_answer_formatter_appends_forecast_total_row(): void
    {
        $this->mock(OpenAiService::class, function ($mock) {
            $mock->shouldReceive('createJsonResponse')->once()->andThrow(new \RuntimeException('OpenAI unavailable'));
        });

        $formatter = app(AiAnswerFormatterService::class);
        $answer = $formatter->format('Forecast report dikhao', [
            'answer_type' => 'table',
            'intent' => 'forecast_report',
        ], [
            'success' => true,
            'rows' => [
                [
                    'sold_date' => '2026-04-01',
                    'customer_name' => 'A Customer',
                    'sales_partner_name' => 'Partner A',
                    'contract_amount' => 1000,
                    'commission' => 100,
                    'dealer_fee_amount' => 50,
                    'net_sales' => 850,
                ],
                [
                    'sold_date' => '2026-04-02',
                    'customer_name' => 'B Customer',
                    'sales_partner_name' => 'Partner B',
                    'contract_amount' => 2000,
                    'commission' => 200,
                    'dealer_fee_amount' => 100,
                    'net_sales' => 1700,
                ],
            ],
            'row_count' => 2,
        ]);

        $total = $answer['rows'][2] ?? [];

        $this->assertStringContainsString('total net sales is $2,550.00', $answer['message']);
        $this->assertSame('Total', $total['sold_date']);
        $this->assertSame('$3,000.00', $total['contract_amount']);
        $this->assertSame('$300.00', $total['commission']);
        $this->assertSame('$150.00', $total['dealer_fee_amount']);
        $this->assertSame('$2,550.00', $total['net_sales']);
    }

    public function test_ai_answer_formatter_appends_override_total_row(): void
    {
        $this->mock(OpenAiService::class, function ($mock) {
            $mock->shouldReceive('createJsonResponse')->once()->andThrow(new \RuntimeException('OpenAI unavailable'));
        });

        $formatter = app(AiAnswerFormatterService::class);
        $answer = $formatter->format('Override report dikhao', [
            'answer_type' => 'table',
            'intent' => 'override_report',
        ], [
            'success' => true,
            'rows' => [
                [
                    'sold_date' => '2026-04-01',
                    'customer_name' => 'A Customer',
                    'sales_partner_name' => 'Partner A',
                    'sales_person_name' => 'Sales A',
                    'redline_costs' => 1000,
                    'panel_qty' => 5,
                    'overwrite_base_price' => 100,
                    'overwrite_panel_price' => 20,
                    'total_override_panel_cost' => 100,
                    'total_override_cost' => 200,
                    'actual_redline_cost' => 800,
                ],
                [
                    'sold_date' => '2026-04-02',
                    'customer_name' => 'B Customer',
                    'sales_partner_name' => 'Partner B',
                    'sales_person_name' => 'Sales B',
                    'redline_costs' => 2000,
                    'panel_qty' => 10,
                    'overwrite_base_price' => 200,
                    'overwrite_panel_price' => 30,
                    'total_override_panel_cost' => 300,
                    'total_override_cost' => 500,
                    'actual_redline_cost' => 1500,
                ],
            ],
            'row_count' => 2,
        ]);

        $total = $answer['rows'][2] ?? [];

        $this->assertStringContainsString('total override cost is $700.00', $answer['message']);
        $this->assertStringContainsString('total actual redline cost is $2,300.00', $answer['message']);
        $this->assertSame('Total', $total['sold_date']);
        $this->assertSame('$3,000.00', $total['redline_costs']);
        $this->assertSame(15.0, $total['panel_qty']);
        $this->assertSame('$300.00', $total['overwrite_base_price']);
        $this->assertSame('$50.00', $total['overwrite_panel_price']);
        $this->assertSame('$400.00', $total['total_override_panel_cost']);
        $this->assertSame('$700.00', $total['total_override_cost']);
        $this->assertSame('$2,300.00', $total['actual_redline_cost']);
    }

    public function test_ai_answer_formatter_appends_transaction_total_row(): void
    {
        $this->mock(OpenAiService::class, function ($mock) {
            $mock->shouldReceive('createJsonResponse')->once()->andThrow(new \RuntimeException('OpenAI unavailable'));
        });

        $formatter = app(AiAnswerFormatterService::class);
        $answer = $formatter->format('Transaction report dikhao', [
            'answer_type' => 'table',
            'intent' => 'transaction_report',
        ], [
            'success' => true,
            'rows' => [
                [
                    'project_name' => 'Project A',
                    'payee_label' => 'Sales Partner',
                    'milestone' => 'NTP',
                    'amount' => 1000,
                    'deduction_amount' => 100,
                    'remitted_amount' => 900,
                    'transaction_date' => '2026-04-01',
                    'transaction_details' => 'Sales partner payment.',
                ],
                [
                    'project_name' => 'Project B',
                    'payee_label' => 'Others',
                    'milestone' => 'Other',
                    'amount' => 500,
                    'deduction_amount' => 25,
                    'remitted_amount' => 475,
                    'transaction_date' => '2026-04-02',
                    'transaction_details' => 'Other payment.',
                ],
            ],
            'row_count' => 2,
        ]);

        $total = $answer['rows'][2] ?? [];

        $this->assertStringContainsString('total remitted amount is $1,375.00', $answer['message']);
        $this->assertSame('Total', $total['project_name']);
        $this->assertSame('$1,500.00', $total['amount']);
        $this->assertSame('$125.00', $total['deduction_amount']);
        $this->assertSame('$1,375.00', $total['remitted_amount']);
    }

    public function test_ai_answer_formatter_appends_profitability_total_row(): void
    {
        $this->mock(OpenAiService::class, function ($mock) {
            $mock->shouldReceive('createJsonResponse')->once()->andThrow(new \RuntimeException('OpenAI unavailable'));
        });

        $formatter = app(AiAnswerFormatterService::class);
        $answer = $formatter->format('Profitability report dikhao', [
            'answer_type' => 'table',
            'intent' => 'profitability_report',
        ], [
            'success' => true,
            'rows' => [
                [
                    'first_name' => 'A',
                    'last_name' => 'Customer',
                    'contract_amount' => 1000,
                    'dealer_fee_amount' => 10,
                    'redline_costs' => 300,
                    'adders' => '100',
                    'commission' => 50,
                    'actual_material_cost' => 20,
                    'actual_labor_cost' => 30,
                    'actual_permit_fee' => 40,
                    'office_cost' => 50,
                    'actual_job_cost' => 140,
                    'profit_amount' => 260,
                    'profit_margin_percent' => 65,
                ],
                [
                    'first_name' => 'B',
                    'last_name' => 'Customer',
                    'contract_amount' => 2000,
                    'dealer_fee_amount' => 20,
                    'redline_costs' => 700,
                    'adders' => '300',
                    'commission' => 100,
                    'actual_material_cost' => 40,
                    'actual_labor_cost' => 60,
                    'actual_permit_fee' => 80,
                    'office_cost' => 100,
                    'actual_job_cost' => 280,
                    'profit_amount' => 720,
                    'profit_margin_percent' => 72,
                ],
            ],
            'row_count' => 2,
        ]);

        $total = $answer['rows'][2] ?? [];

        $this->assertStringContainsString('full table below', $answer['message']);
        $this->assertStringContainsString('total profit is $980.00', $answer['message']);
        $this->assertStringContainsString('Highest profit project is B Customer', $answer['message']);
        $this->assertSame('Total', $total['first_name']);
        $this->assertSame('$3,000.00', $total['contract_amount']);
        $this->assertSame('$1,000.00', $total['redline_costs']);
        $this->assertSame('$400.00', $total['adders']);
        $this->assertSame('$420.00', $total['actual_job_cost']);
        $this->assertSame('$980.00', $total['profit_amount']);
        $this->assertSame('70.00%', $total['profit_margin_percent']);
    }

    public function test_ai_sql_builder_joins_related_tables_when_order_is_reversed(): void
    {
        $user = $this->userWithRole('Admin');
        $builder = app(AiSqlBuilderService::class);

        $preview = $builder->build([
            'answer_type' => 'table',
            'intent' => 'crm_list',
            'tables' => ['users', 'service_tickets'],
            'columns' => ['name', 'subject', 'status'],
            'group_by' => [],
            'filters' => [
                [
                    'column' => 'status',
                    'operator' => 'like',
                    'value' => 'Pending',
                ],
            ],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => null,
        ], $user);

        $sql = strtolower($preview['sql']);

        $this->assertStringContainsString('join "service_tickets"', $sql);
        $this->assertStringContainsString('"service_tickets"."user_id" = "users"."id"', $sql);
        $this->assertContains('%Pending%', $preview['bindings']);
    }

    public function test_ai_planner_maps_pre_inspection_or_ghost_projects_question(): void
    {
        $user = $this->userWithRole('Admin');
        $planner = app(AiQueryPlannerService::class);

        $result = $planner->plan('Show me maximum old projects currently in Pre-Inspection Lane or Ghost projects', $user);

        $this->assertSame('project_pre_inspection_or_ghost_list', $result['plan']['intent']);
        $this->assertSame(['projects', 'customers', 'departments', 'sub_departments', 'tasks', 'employees'], $result['plan']['tables']);
        $this->assertSame(100, $result['plan']['limit']);
    }

    public function test_ai_sql_builder_lists_pre_inspection_or_ghost_projects_by_age(): void
    {
        $user = $this->userWithRole('Admin');
        $builder = app(AiSqlBuilderService::class);

        $preview = $builder->build([
            'answer_type' => 'table',
            'intent' => 'project_pre_inspection_or_ghost_list',
            'tables' => ['projects', 'customers', 'departments', 'sub_departments', 'tasks', 'employees'],
            'columns' => ['project_name', 'code', 'first_name', 'last_name', 'sold_date', 'department_id', 'sub_department_id', 'name', 'status', 'employee_id'],
            'group_by' => [],
            'filters' => [],
            'sort' => [],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => null,
        ], $user);
        $validation = app(AiSqlValidatorService::class)->validate($preview, [
            'tables' => ['projects', 'customers', 'departments', 'sub_departments', 'tasks', 'employees'],
            'columns' => ['project_name', 'code', 'first_name', 'last_name', 'sold_date', 'department_id', 'sub_department_id', 'name', 'status', 'employee_id'],
            'group_by' => [],
            'filters' => [],
            'requires_finance_access' => false,
        ], $user);

        $sql = strtolower($preview['sql']);

        $this->assertTrue($validation['approved'], $validation['reason'] ?? '');
        $this->assertStringContainsString('"projects"."sub_department_id" = ?', $sql);
        $this->assertStringContainsString('ghost_tasks', $sql);
        $this->assertStringContainsString('"later_tasks"."department_id" >= ?', $sql);
        $this->assertStringContainsString('project_age_days', $sql);
        $this->assertStringContainsString('order by julianday', $sql);
        $this->assertContains(21, $preview['bindings']);
        $this->assertSame([
            'project_name',
            'code',
            'customer_name',
            'sold_date',
            'department_name',
            'lane_name',
            'latest_task_status',
            'assigned_employee_name',
            'project_age_days',
        ], $preview['columns']);
    }

    public function test_planner_resolves_followup_from_previous_context(): void
    {
        $user    = $this->userWithRole('Admin');
        $planner = app(AiQueryPlannerService::class);

        $previous = [
            'intent'                  => 'project_count',
            'tables'                  => ['projects', 'departments'],
            'columns'                 => ['id', 'name'],
            'filters'                 => [['column' => 'name', 'operator' => 'like', 'value' => 'deal review']],
            'requires_finance_access' => false,
        ];

        $plan = $planner->plan('can you share the details of those projects?', $user, $previous)['plan'];

        // "those projects" inherits the previous department filter as a project list.
        $this->assertSame('crm_list', $plan['intent']);
        $this->assertSame('table', $plan['answer_type']);
        $this->assertContains('projects', $plan['tables']);
        $this->assertNotEmpty($plan['filters']);
        $this->assertSame('deal review', $plan['filters'][0]['value']);
    }

    public function test_planner_ignores_followup_without_previous_context(): void
    {
        $user    = $this->userWithRole('Admin');
        $planner = app(AiQueryPlannerService::class);

        // A self-contained question must NOT be treated as a follow-up even when a
        // previous context exists (no back-reference words).
        $previous = [
            'intent'  => 'project_count',
            'tables'  => ['projects'],
            'filters' => [],
        ];

        $plan = $planner->plan('How many projects are there in total?', $user, $previous)['plan'];

        $this->assertNotSame('crm_list', $plan['intent']);
    }

    public function test_ai_row_scope_service_scopes_projects_per_role(): void
    {
        \Illuminate\Support\Facades\Schema::create('customers', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_partner_id')->nullable();
            $table->unsignedBigInteger('sub_contractor_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        \Illuminate\Support\Facades\Schema::create('projects', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('sub_contractor_user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        \Illuminate\Support\Facades\Schema::create('tasks', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $scope = app(\App\Services\AiRowScopeService::class);

        // --- Admin: fully unscoped -------------------------------------------------
        $admin = $this->userWithRole('Admin');
        $this->assertNull($scope->allowedProjectIds($admin));
        $this->assertNull($scope->projectScopeSql($admin));

        // --- Sales Person: projects of customers they own --------------------------
        $sales = $this->userWithRole('Sales Person');
        \Illuminate\Support\Facades\DB::table('customers')->insert([
            ['id' => 1, 'sales_partner_id' => $sales->id, 'sub_contractor_id' => 77],
            ['id' => 2, 'sales_partner_id' => 999, 'sub_contractor_id' => null],
        ]);
        \Illuminate\Support\Facades\DB::table('projects')->insert([
            ['id' => 10, 'customer_id' => 1, 'department_id' => 5, 'sub_contractor_user_id' => 555],
            ['id' => 20, 'customer_id' => 2, 'department_id' => 6, 'sub_contractor_user_id' => null],
        ]);
        $this->assertSame([10], $scope->allowedProjectIds($sales));
        $this->assertSame('projects.id in (10)', $scope->projectScopeSql($sales));

        // --- Manager: projects in their employee departments -----------------------
        $manager = $this->userWithRole('Manager');
        \Illuminate\Support\Facades\DB::table('employees')->insert(['id' => 100, 'user_id' => $manager->id]);
        \Illuminate\Support\Facades\DB::table('employee_departments')->insert(['employee_id' => 100, 'department_id' => 6]);
        $this->assertSame([20], $scope->allowedProjectIds($manager));

        // --- Employee: projects whose latest active task is theirs ------------------
        $employee = $this->userWithRole('Employee');
        \Illuminate\Support\Facades\DB::table('employees')->insert(['id' => 200, 'user_id' => $employee->id]);
        \Illuminate\Support\Facades\DB::table('tasks')->insert([
            ['id' => 1, 'project_id' => 10, 'employee_id' => 200, 'status' => 'In-Progress'],
            ['id' => 2, 'project_id' => 20, 'employee_id' => 999, 'status' => 'In-Progress'],
        ]);
        $this->assertSame([10], $scope->allowedProjectIds($employee));

        // --- Sub-Contractor User: projects directly assigned to them ----------------
        $subUser = $this->userWithRole('Sub-Contractor User');
        \Illuminate\Support\Facades\DB::table('projects')->where('id', 10)->update(['sub_contractor_user_id' => $subUser->id]);
        $this->assertSame([10], $scope->allowedProjectIds($subUser));

        // --- Sub-Contractor Manager: projects of their sub-contracted customers -----
        $subManager = $this->userWithRole('Sub-Contractor Manager');
        $subManager->forceFill(['sales_partner_id' => 77])->save();
        $this->assertSame([10], $scope->allowedProjectIds($subManager->fresh()));

        // --- Unknown scoped role: deny all -----------------------------------------
        $viewer = $this->userWithRole('Viewer');
        $this->assertSame([], $scope->allowedProjectIds($viewer));
        $this->assertSame('projects.id in (0)', $scope->projectScopeSql($viewer));
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->syncRoles([Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'])]);

        return $user->fresh();
    }
}
