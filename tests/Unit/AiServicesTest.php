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

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->syncRoles([Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'])]);

        return $user->fresh();
    }
}
