<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\AiPermissionService;
use App\Services\AiSchemaService;
use App\Services\AiSqlBuilderService;
use App\Services\AiSqlValidatorService;
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

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->syncRoles([Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'])]);

        return $user->fresh();
    }
}
