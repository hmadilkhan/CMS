<?php

namespace Tests\Feature;

use App\Models\AiChat;
use App\Models\AiChatMessage;
use App\Models\User;
use App\Services\AiAnswerFormatterService;
use App\Services\AiQueryExecutorService;
use App\Services\AiSqlBuilderService;
use App\Services\OpenAiService;
use App\Http\Middleware\VerifyCsrfToken;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreatesAiChatTestSchema;
use Tests\TestCase;

class AiChatModuleTest extends TestCase
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

        $this->withoutMiddleware(VerifyCsrfToken::class);

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'permission.cache.store' => 'array',
        ]);

        $this->createAiChatTestSchema();
    }

    public function test_user_can_send_message(): void
    {
        $user = $this->userWithRole('Employee');
        $this->mockNormalOpenAiReply('Hello from AI.');

        $response = $this->actingAs($user)->postJson(route('ai-chat.send'), [
            'message' => 'Write a short greeting',
        ]);

        $response->assertOk()
            ->assertJsonPath('messages.0.role', 'user')
            ->assertJsonPath('messages.1.content', 'Hello from AI.');
    }

    public function test_chat_history_saved(): void
    {
        $user = $this->userWithRole('Employee');
        $this->mockNormalOpenAiReply('Saved response.');

        $this->actingAs($user)->postJson(route('ai-chat.send'), [
            'message' => 'Write a note',
        ])->assertOk();

        $this->assertDatabaseHas('ai_chats', ['user_id' => $user->id]);
        $this->assertDatabaseHas('ai_chat_messages', ['role' => 'user', 'content' => 'Write a note']);
        $this->assertDatabaseHas('ai_chat_messages', ['role' => 'assistant', 'content' => 'Saved response.']);
    }

    public function test_employee_cannot_access_finance(): void
    {
        $user = $this->userWithRole('Employee');
        $this->mockPlannerResponse([
            'answer_type' => 'card',
            'intent' => 'finance_summary',
            'tables' => ['project_finances'],
            'columns' => ['contract_amount'],
            'filters' => [],
            'requires_finance_access' => true,
            'sql' => null,
            'fallback_message' => null,
        ]);

        $response = $this->actingAs($user)->postJson(route('ai-chat.send'), [
            'message' => 'Is project ki financing show karo',
        ]);

        $response->assertOk()
            ->assertJsonPath('messages.1.content', 'You do not have permission to access this information.');
    }

    public function test_manager_only_sees_department_data(): void
    {
        $user = $this->userWithRole('Manager');
        $builder = app(AiSqlBuilderService::class);

        $preview = $builder->build([
            'answer_type' => 'table',
            'intent' => 'project_status_summary',
            'tables' => ['projects', 'tasks'],
            'columns' => ['id', 'status'],
            'filters' => [],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => null,
        ], $user);

        $this->assertStringContainsString('department_id', $preview['sql']);
        $this->assertStringContainsString('employee_departments', $preview['sql']);
    }

    public function test_admin_can_access_allowed_finance(): void
    {
        $user = $this->userWithRole('Admin');
        $this->mockPlannerResponse([
            'answer_type' => 'card',
            'intent' => 'finance_summary',
            'tables' => ['project_finances', 'projects'],
            'columns' => ['project_id', 'contract_amount'],
            'filters' => [],
            'requires_finance_access' => true,
            'sql' => null,
            'fallback_message' => null,
        ]);

        $this->mock(AiQueryExecutorService::class, function ($mock) {
            $mock->shouldReceive('execute')->once()->andReturn([
                'success' => true,
                'rows' => [['project_id' => 1, 'contract_amount' => 1000]],
                'row_count' => 1,
                'connection' => 'testing',
                'error_message' => null,
            ]);
        });

        $this->mock(AiAnswerFormatterService::class, function ($mock) {
            $mock->shouldReceive('format')->once()->andReturn([
                'type' => 'card',
                'message' => 'Finance summary ready.',
                'columns' => [],
                'rows' => [],
                'cards' => [['label' => 'Contract Amount', 'value' => 1000]],
            ]);
        });

        $this->actingAs($user)->postJson(route('ai-chat.send'), [
            'message' => 'Is project ki financing show karo',
        ])->assertOk()->assertJsonPath('messages.1.content', 'Finance summary ready.');
    }

    public function test_unsafe_query_is_rejected(): void
    {
        $user = $this->userWithRole('Admin');
        $this->mockPlannerResponse([
            'answer_type' => 'table',
            'intent' => 'project_count',
            'tables' => ['projects'],
            'columns' => ['id'],
            'filters' => [],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => null,
        ]);

        $this->mock(AiSqlBuilderService::class, function ($mock) {
            $mock->shouldReceive('build')->once()->andReturn([
                'sql' => 'delete from projects limit 100',
                'bindings' => [],
                'tables' => ['projects'],
                'columns' => ['id'],
                'limit' => 100,
            ]);
        });

        $this->actingAs($user)->postJson(route('ai-chat.send'), [
            'message' => 'Total active projects',
        ])->assertOk()->assertJsonPath('messages.1.metadata.status', 'unsafe_query_rejected');
    }

    public function test_unknown_question_returns_fallback(): void
    {
        $user = $this->userWithRole('Employee');
        $this->mockPlannerResponse([
            'answer_type' => 'text',
            'intent' => 'unknown',
            'tables' => [],
            'columns' => [],
            'filters' => [],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => 'I cannot map this question safely.',
        ]);

        $this->actingAs($user)->postJson(route('ai-chat.send'), [
            'message' => 'project magic unknown',
        ])->assertOk()->assertJsonPath('messages.1.content', 'I cannot map this question safely.');
    }

    public function test_department_subdepartment_project_count_question_is_mapped_safely(): void
    {
        $user = $this->userWithRole('Admin');
        $this->mockPlannerResponse([
            'answer_type' => 'text',
            'intent' => 'unknown',
            'tables' => [],
            'columns' => [],
            'filters' => [],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => 'I cannot map this question safely.',
        ]);

        $this->mock(AiQueryExecutorService::class, function ($mock) {
            $mock->shouldReceive('execute')->once()->andReturn([
                'success' => true,
                'rows' => [
                    ['department_name' => 'Permitting', 'sub_department_name' => 'Review', 'aggregate' => 3],
                ],
                'row_count' => 1,
                'connection' => 'testing',
                'error_message' => null,
            ]);
        });

        $this->mock(AiAnswerFormatterService::class, function ($mock) {
            $mock->shouldReceive('format')->once()->andReturn([
                'type' => 'table',
                'message' => 'Here is the project count by department and subdepartment.',
                'columns' => ['department_name', 'sub_department_name', 'aggregate'],
                'rows' => [
                    ['department_name' => 'Permitting', 'sub_department_name' => 'Review', 'aggregate' => 3],
                ],
                'cards' => [],
            ]);
        });

        $this->actingAs($user)->postJson(route('ai-chat.send'), [
            'message' => 'total project count department or subdepartment name ke sath show karo',
        ])->assertOk()
            ->assertJsonPath('messages.1.content', 'Here is the project count by department and subdepartment.')
            ->assertJsonPath('messages.1.metadata.query_plan.intent', 'project_department_summary');
    }

    public function test_named_department_project_count_adds_department_filter(): void
    {
        $user = $this->userWithRole('Admin');
        $this->mockPlannerResponse([
            'answer_type' => 'count',
            'intent' => 'project_count',
            'tables' => ['projects'],
            'columns' => ['id'],
            'filters' => [],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => null,
        ]);

        $this->mock(AiQueryExecutorService::class, function ($mock) {
            $mock->shouldReceive('execute')->once()->with(Mockery::on(function (array $preview) {
                return str_contains($preview['sql'], 'departments')
                    && in_array('%deal review%', $preview['bindings'], true);
            }))->andReturn([
                'success' => true,
                'rows' => [['aggregate' => 12]],
                'row_count' => 1,
                'connection' => 'testing',
                'error_message' => null,
            ]);
        });

        $this->mock(AiAnswerFormatterService::class, function ($mock) {
            $mock->shouldReceive('format')->once()->andReturn([
                'type' => 'count',
                'message' => 'Deal Review department has 12 projects.',
                'columns' => ['label', 'value'],
                'rows' => [['label' => 'Deal Review Projects', 'value' => 12]],
                'cards' => [['label' => 'Deal Review Projects', 'value' => 12]],
            ]);
        });

        $this->actingAs($user)->postJson(route('ai-chat.send'), [
            'message' => 'Deal review department me is waqt kitne projects hain?',
        ])->assertOk()
            ->assertJsonPath('messages.1.content', 'Deal Review department has 12 projects.')
            ->assertJsonPath('messages.1.metadata.query_plan.tables.1', 'departments')
            ->assertJsonPath('messages.1.metadata.query_plan.filters.0.value', 'deal review');
    }

    public function test_query_limit_is_enforced(): void
    {
        $user = $this->userWithRole('Admin');
        $builder = app(AiSqlBuilderService::class);

        $preview = $builder->build([
            'answer_type' => 'table',
            'intent' => 'project_customer',
            'tables' => ['projects', 'customers'],
            'columns' => ['id', 'project_name'],
            'filters' => [],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => null,
        ], $user);

        $this->assertSame(100, $preview['limit']);
        $this->assertStringContainsString('limit 100', strtolower($preview['sql']));
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->syncRoles([Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'])]);

        return $user->fresh();
    }

    private function mockNormalOpenAiReply(string $text): void
    {
        $this->mock(OpenAiService::class, function ($mock) use ($text) {
            $mock->shouldReceive('createResponse')->once()->andReturn([
                'id' => 'resp_test',
                'model' => 'gpt-test',
                'text' => $text,
                'usage' => [],
                'payload' => [],
                'raw' => [],
            ]);
        });
    }

    private function mockPlannerResponse(array $plan): void
    {
        $this->mock(OpenAiService::class, function ($mock) use ($plan) {
            $mock->shouldReceive('createJsonResponse')->once()->andReturn([
                'id' => 'resp_plan',
                'model' => 'gpt-test',
                'json' => $plan,
                'text' => json_encode($plan),
                'usage' => [],
                'payload' => [],
                'raw' => [],
            ]);
        });
    }
}
