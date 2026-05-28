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
use Spatie\Permission\Models\Permission;
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

    public function test_super_admin_can_request_project_financing_summary(): void
    {
        $user = $this->userWithRole('Super Admin');
        $this->mockPlannerResponse([
            'answer_type' => 'text',
            'intent' => 'unknown',
            'tables' => [],
            'columns' => [],
            'group_by' => [],
            'filters' => [],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => 'OpenAI fallback should not be used.',
        ]);

        $this->mock(AiQueryExecutorService::class, function ($mock) {
            $mock->shouldReceive('execute')->once()->with(Mockery::on(function (array $preview) {
                $sql = strtolower($preview['sql']);

                return str_contains($sql, '"customer_finances"')
                    && str_contains($sql, '"finance_options"')
                    && $preview['columns'] === [
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
            }), Mockery::type('int'))->andReturn([
                'success' => true,
                'rows' => [
                    [
                        'project_name' => 'Solar Project',
                        'code' => 'P-100',
                        'customer_name' => 'Jane Doe',
                        'finance_option' => 'Cash',
                        'contract_amount' => 25000,
                        'dealer_fee_amount' => 0,
                        'commission' => 1000,
                        'holdback_amount' => 500,
                        'customer_portion' => 25000,
                        'finance_updated_at' => '2026-05-28 10:00:00',
                    ],
                ],
                'row_count' => 1,
                'connection' => 'testing',
                'error_message' => null,
            ]);
        });

        $this->mock(AiAnswerFormatterService::class, function ($mock) {
            $mock->shouldReceive('format')->once()->andReturn([
                'type' => 'table',
                'message' => 'Here is the project financing summary.',
                'columns' => ['project_name', 'code', 'customer_name', 'finance_option', 'contract_amount'],
                'rows' => [],
                'cards' => [],
            ]);
        });

        $this->actingAs($user)->postJson(route('ai-chat.send'), [
            'message' => 'Project financing summary show karo',
        ])->assertOk()
            ->assertJsonPath('messages.1.metadata.query_plan.intent', 'project_financing_summary')
            ->assertJsonPath('messages.1.content', 'Here is the project financing summary.');
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

    public function test_crm_question_retries_openai_planning_before_marking_unknown(): void
    {
        $user = $this->userWithRole('Super Admin');

        $this->mock(OpenAiService::class, function ($mock) {
            $mock->shouldReceive('createJsonResponse')->twice()->andReturn(
                [
                    'id' => 'resp_unsupported',
                    'model' => 'gpt-test',
                    'json' => [
                        'mode' => 'unsupported',
                        'confidence' => 0.4,
                        'entities' => [],
                        'selected_columns' => [],
                        'relationships' => [],
                        'sort' => [],
                        'group_by' => [],
                        'limit' => 20,
                        'answer_type' => 'text',
                        'intent' => 'unknown',
                        'filters' => [],
                        'needs_clarification' => false,
                        'clarification_question' => null,
                        'fallback_message' => 'Unsupported first pass.',
                    ],
                    'text' => '{}',
                    'usage' => [],
                    'payload' => [],
                    'raw' => [],
                ],
                [
                    'id' => 'resp_dynamic',
                    'model' => 'gpt-test',
                    'json' => [
                        'mode' => 'data_explorer',
                        'confidence' => 0.86,
                        'entities' => ['project_files'],
                        'selected_columns' => [
                            ['table' => 'project_files', 'columns' => ['filename']],
                        ],
                        'relationships' => [],
                        'sort' => [],
                        'group_by' => [
                            ['table' => 'project_files', 'column' => 'filename'],
                        ],
                        'limit' => 20,
                        'answer_type' => 'table',
                        'intent' => 'crm_group_summary',
                        'filters' => [],
                        'needs_clarification' => false,
                        'clarification_question' => null,
                        'fallback_message' => null,
                    ],
                    'text' => '{}',
                    'usage' => [],
                    'payload' => [],
                    'raw' => [],
                ],
            );
        });

        $this->mock(AiQueryExecutorService::class, function ($mock) {
            $mock->shouldReceive('execute')->once()->with(Mockery::on(function (array $preview) {
                $sql = strtolower($preview['sql']);

                return str_contains($sql, '"project_files"')
                    && str_contains($sql, 'count(*) as aggregate')
                    && $preview['columns'] === ['filename', 'aggregate'];
            }), Mockery::type('int'))->andReturn([
                'success' => true,
                'rows' => [
                    ['filename' => 'permit.pdf', 'aggregate' => 12],
                ],
                'row_count' => 1,
                'connection' => 'testing',
                'error_message' => null,
            ]);
        });

        $this->mock(AiAnswerFormatterService::class, function ($mock) {
            $mock->shouldReceive('format')->once()->andReturn([
                'type' => 'table',
                'message' => 'Here is the dynamic CRM summary.',
                'columns' => ['filename', 'aggregate'],
                'rows' => [['filename' => 'permit.pdf', 'aggregate' => 12]],
                'cards' => [],
            ]);
        });

        $this->actingAs($user)->postJson(route('ai-chat.send'), [
            'message' => 'Show project files count by filename',
        ])->assertOk()
            ->assertJsonPath('messages.1.metadata.query_plan.mode', 'data_explorer')
            ->assertJsonPath('messages.1.metadata.query_plan.intent', 'crm_group_summary')
            ->assertJsonPath('messages.1.content', 'Here is the dynamic CRM summary.');
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
            }), Mockery::type('int'))->andReturn([
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

    public function test_project_summary_question_maps_to_project_summary_report(): void
    {
        $user = $this->userWithRole('Super Admin');
        $this->mockPlannerResponse([
            'answer_type' => 'text',
            'intent' => 'unknown',
            'tables' => [],
            'columns' => [],
            'filters' => [],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => 'OpenAI fallback should not be used.',
        ]);

        $this->mock(AiQueryExecutorService::class, function ($mock) {
            $mock->shouldReceive('execute')->once()->with(Mockery::on(function (array $preview) {
                $sql = strtolower($preview['sql']);

                return str_contains($sql, 'project_acceptances')
                    && str_contains($sql, 'latest_tasks')
                    && in_array('%susan stauffer%', $preview['bindings'], true)
                    && in_array('%susan%stauffer%', $preview['bindings'], true)
                    && in_array('current_department', $preview['columns'], true)
                    && in_array('acceptance_status', $preview['columns'], true);
            }), Mockery::type('int'))->andReturn([
                'success' => true,
                'rows' => [
                    [
                        'project_name' => 'Susan-Stauffer',
                        'code' => '1115',
                        'customer_name' => 'Susan Stauffer',
                        'current_department' => 'Design',
                        'current_sub_department' => 'Review',
                        'latest_task_status' => 'In-Progress',
                        'assigned_employee_name' => 'Jereme Silva',
                        'acceptance_status' => 'Approved',
                    ],
                ],
                'row_count' => 1,
                'connection' => 'testing',
                'error_message' => null,
            ]);
        });

        $this->mock(AiAnswerFormatterService::class, function ($mock) {
            $mock->shouldReceive('format')->once()->andReturn([
                'type' => 'card',
                'message' => 'Here is the project summary.',
                'columns' => [],
                'rows' => [],
                'cards' => [
                    ['label' => 'Project', 'value' => 'Susan-Stauffer'],
                    ['label' => 'Current Department', 'value' => 'Design'],
                ],
            ]);
        });

        $this->actingAs($user)->postJson(route('ai-chat.send'), [
            'message' => 'Show me the project summary of Susan Stauffer project',
        ])->assertOk()
            ->assertJsonPath('messages.1.metadata.query_plan.intent', 'project_summary')
            ->assertJsonPath('messages.1.metadata.query_plan.filters.0.value', 'susan stauffer')
            ->assertJsonPath('messages.1.content', 'Here is the project summary.');
    }

    public function test_project_acceptance_question_maps_to_acceptance_summary(): void
    {
        $user = $this->userWithRole('Super Admin');
        $this->mockPlannerResponse([
            'answer_type' => 'text',
            'intent' => 'unknown',
            'tables' => [],
            'columns' => [],
            'filters' => [],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => 'OpenAI fallback should not be used.',
        ]);

        $this->mock(AiQueryExecutorService::class, function ($mock) {
            $mock->shouldReceive('execute')->once()->with(Mockery::on(function (array $preview) {
                $sql = strtolower($preview['sql']);

                return str_contains($sql, 'project_acceptances')
                    && str_contains($sql, 'latest_acceptances')
                    && str_contains($sql, 'project_acceptances.action_by')
                    && in_array('%susan%', $preview['bindings'], true)
                    && in_array('acceptance_status', $preview['columns'], true)
                    && in_array('action_by_name', $preview['columns'], true);
            }), Mockery::type('int'))->andReturn([
                'success' => true,
                'rows' => [
                    [
                        'project_name' => 'Susan-Stauffer',
                        'code' => '1115',
                        'customer_name' => 'Susan Stauffer',
                        'acceptance_status' => 'Approved',
                        'approved_date' => '2025-02-24 19:48:34',
                    ],
                ],
                'row_count' => 1,
                'connection' => 'testing',
                'error_message' => null,
            ]);
        });

        $this->mock(AiAnswerFormatterService::class, function ($mock) {
            $mock->shouldReceive('format')->once()->andReturn([
                'type' => 'card',
                'message' => 'Here is the project acceptance summary.',
                'columns' => [],
                'rows' => [],
                'cards' => [
                    ['label' => 'Acceptance Status', 'value' => 'Approved'],
                ],
            ]);
        });

        $this->actingAs($user)->postJson(route('ai-chat.send'), [
            'message' => 'Is project acceptance is approved for Susan project',
        ])->assertOk()
            ->assertJsonPath('messages.1.metadata.query_plan.intent', 'project_acceptance_summary')
            ->assertJsonPath('messages.1.metadata.query_plan.filters.0.value', 'susan')
            ->assertJsonPath('messages.1.content', 'Here is the project acceptance summary.');
    }

    public function test_project_acceptance_pending_count_maps_to_acceptance_count(): void
    {
        $user = $this->userWithRole('Super Admin');
        $this->mockPlannerResponse([
            'answer_type' => 'text',
            'intent' => 'unknown',
            'tables' => [],
            'columns' => [],
            'filters' => [],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => 'OpenAI fallback should not be used.',
        ]);

        $this->mock(AiQueryExecutorService::class, function ($mock) {
            $mock->shouldReceive('execute')->once()->with(Mockery::on(function (array $preview) {
                $sql = strtolower($preview['sql']);

                return str_contains($sql, 'count(distinct projects.id)')
                    && str_contains($sql, 'latest_acceptances')
                    && in_array(0, $preview['bindings'], true);
            }), Mockery::type('int'))->andReturn([
                'success' => true,
                'rows' => [['aggregate' => 4]],
                'row_count' => 1,
                'connection' => 'testing',
                'error_message' => null,
            ]);
        });

        $this->mock(AiAnswerFormatterService::class, function ($mock) {
            $mock->shouldReceive('format')->once()->andReturn([
                'type' => 'count',
                'message' => 'Here is the project acceptance count.',
                'columns' => ['Count'],
                'rows' => [['label' => 'Count', 'value' => 4]],
                'cards' => [['label' => 'Count', 'value' => 4]],
            ]);
        });

        $this->actingAs($user)->postJson(route('ai-chat.send'), [
            'message' => 'How many projects are there whose acceptance is in pending',
        ])->assertOk()
            ->assertJsonPath('messages.1.metadata.query_plan.intent', 'project_acceptance_count')
            ->assertJsonPath('messages.1.metadata.query_plan.filters.0.value', 0)
            ->assertJsonPath('messages.1.content', 'Here is the project acceptance count.');
    }

    public function test_project_acceptance_pending_list_maps_to_acceptance_list(): void
    {
        $user = $this->userWithRole('Super Admin');
        $this->mockPlannerResponse([
            'answer_type' => 'text',
            'intent' => 'unknown',
            'tables' => [],
            'columns' => [],
            'filters' => [],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => 'OpenAI fallback should not be used.',
        ]);

        $this->mock(AiQueryExecutorService::class, function ($mock) {
            $mock->shouldReceive('execute')->once()->with(Mockery::on(function (array $preview) {
                $sql = strtolower($preview['sql']);

                return str_contains($sql, 'project_acceptances')
                    && str_contains($sql, 'latest_acceptances')
                    && in_array(0, $preview['bindings'], true)
                    && in_array('acceptance_status', $preview['columns'], true);
            }), Mockery::type('int'))->andReturn([
                'success' => true,
                'rows' => [
                    ['project_name' => 'Project A', 'code' => 'P-1', 'customer_name' => 'Customer A', 'acceptance_status' => 'Pending'],
                ],
                'row_count' => 1,
                'connection' => 'testing',
                'error_message' => null,
            ]);
        });

        $this->mock(AiAnswerFormatterService::class, function ($mock) {
            $mock->shouldReceive('format')->once()->andReturn([
                'type' => 'table',
                'message' => 'Here are the matching projects by acceptance status.',
                'columns' => ['project_name', 'code', 'customer_name', 'acceptance_status'],
                'rows' => [
                    ['project_name' => 'Project A', 'code' => 'P-1', 'customer_name' => 'Customer A', 'acceptance_status' => 'Pending'],
                ],
                'cards' => [],
            ]);
        });

        $this->actingAs($user)->postJson(route('ai-chat.send'), [
            'message' => 'Show the projects list whose acceptance is in pending',
        ])->assertOk()
            ->assertJsonPath('messages.1.metadata.query_plan.intent', 'project_acceptance_list')
            ->assertJsonPath('messages.1.metadata.query_plan.filters.0.value', 0)
            ->assertJsonPath('messages.1.content', 'Here are the matching projects by acceptance status.');
    }

    public function test_ticket_status_summary_by_creator_is_mapped_safely(): void
    {
        $user = $this->userWithRole('Admin');
        $this->mockPlannerResponse([
            'answer_type' => 'table',
            'intent' => 'ticket_status',
            'tables' => ['service_tickets'],
            'columns' => ['status'],
            'filters' => [],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => null,
        ]);

        $this->mock(AiQueryExecutorService::class, function ($mock) {
            $mock->shouldReceive('execute')->once()->with(Mockery::on(function (array $preview) {
                return str_contains($preview['sql'], 'users')
                    && str_contains($preview['sql'], 'pending_count')
                    && str_contains($preview['sql'], 'resolved_count');
            }), Mockery::type('int'))->andReturn([
                'success' => true,
                'rows' => [
                    ['user_name' => 'Admin User', 'pending_count' => 2, 'resolved_count' => 5, 'total_tickets' => 7],
                ],
                'row_count' => 1,
                'connection' => 'testing',
                'error_message' => null,
            ]);
        });

        $this->mock(AiAnswerFormatterService::class, function ($mock) {
            $mock->shouldReceive('format')->once()->andReturn([
                'type' => 'table',
                'message' => 'Here is the ticket status summary by user.',
                'columns' => ['user_name', 'pending_count', 'resolved_count', 'total_tickets'],
                'rows' => [
                    ['user_name' => 'Admin User', 'pending_count' => 2, 'resolved_count' => 5, 'total_tickets' => 7],
                ],
                'cards' => [],
            ]);
        });

        $this->actingAs($user)->postJson(route('ai-chat.send'), [
            'message' => 'How many tickets created by users? Give me summary thats show user name, count of status',
        ])->assertOk()
            ->assertJsonPath('messages.1.content', 'Here is the ticket status summary by user.')
            ->assertJsonPath('messages.1.metadata.query_plan.intent', 'ticket_creator_status_summary')
            ->assertJsonPath('messages.1.metadata.query_plan.tables.1', 'users');
    }

    public function test_named_user_ticket_summary_maps_without_openai(): void
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
            'fallback_message' => 'OpenAI fallback should not be used.',
        ]);

        $this->mock(AiQueryExecutorService::class, function ($mock) {
            $mock->shouldReceive('execute')->once()->with(Mockery::on(function (array $preview) {
                return str_contains(strtolower($preview['sql']), 'users')
                    && in_array('%super admin%', $preview['bindings'], true);
            }), Mockery::type('int'))->andReturn([
                'success' => true,
                'rows' => [
                    ['user_name' => 'Super Admin', 'pending_count' => 1, 'resolved_count' => 2, 'total_tickets' => 3],
                ],
                'row_count' => 1,
                'connection' => 'testing',
                'error_message' => null,
            ]);
        });

        $this->mock(AiAnswerFormatterService::class, function ($mock) {
            $mock->shouldReceive('format')->once()->andReturn([
                'type' => 'table',
                'message' => 'Here is the ticket status summary by user.',
                'columns' => ['user_name', 'pending_count', 'resolved_count', 'total_tickets'],
                'rows' => [
                    ['user_name' => 'Super Admin', 'pending_count' => 1, 'resolved_count' => 2, 'total_tickets' => 3],
                ],
                'cards' => [],
            ]);
        });

        $this->actingAs($user)->postJson(route('ai-chat.send'), [
            'message' => 'Show me the summary of Super Admin tickets',
        ])->assertOk()
            ->assertJsonPath('messages.1.metadata.query_plan.intent', 'ticket_creator_status_summary')
            ->assertJsonPath('messages.1.metadata.query_plan.filters.0.value', 'super admin')
            ->assertJsonPath('messages.1.content', 'Here is the ticket status summary by user.');
    }

    public function test_user_role_list_question_maps_to_safe_report(): void
    {
        $user = $this->userWithRole('Super Admin');
        $this->mockPlannerResponse([
            'answer_type' => 'text',
            'intent' => 'unknown',
            'tables' => [],
            'columns' => [],
            'filters' => [],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => 'OpenAI fallback should not be used.',
        ]);

        $this->mock(AiQueryExecutorService::class, function ($mock) {
            $mock->shouldReceive('execute')->once()->with(Mockery::on(function (array $preview) {
                $sql = strtolower($preview['sql']);

                return str_contains($sql, 'model_has_roles')
                    && str_contains($sql, 'roles')
                    && $preview['columns'] === ['role_name', 'user_name', 'username', 'email'];
            }), Mockery::type('int'))->andReturn([
                'success' => true,
                'rows' => [
                    ['role_name' => 'Super Admin', 'user_name' => 'Super Admin', 'username' => 'hmadilkhan', 'email' => 'admin@example.test'],
                ],
                'row_count' => 1,
                'connection' => 'testing',
                'error_message' => null,
            ]);
        });

        $this->mock(AiAnswerFormatterService::class, function ($mock) {
            $mock->shouldReceive('format')->once()->andReturn([
                'type' => 'table',
                'message' => 'Here is the user list by role.',
                'columns' => ['role_name', 'user_name', 'username', 'email'],
                'rows' => [
                    ['role_name' => 'Super Admin', 'user_name' => 'Super Admin', 'username' => 'hmadilkhan', 'email' => 'admin@example.test'],
                ],
                'cards' => [],
            ]);
        });

        $this->actingAs($user)->postJson(route('ai-chat.send'), [
            'message' => 'Full list of users according to roles',
        ])->assertOk()
            ->assertJsonPath('messages.1.metadata.query_plan.intent', 'user_role_list')
            ->assertJsonPath('messages.1.content', 'Here is the user list by role.');
    }

    public function test_user_role_count_question_maps_to_count_summary(): void
    {
        $user = $this->userWithRole('Super Admin');
        $this->mockPlannerResponse([
            'answer_type' => 'text',
            'intent' => 'unknown',
            'tables' => [],
            'columns' => [],
            'filters' => [],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => 'OpenAI fallback should not be used.',
        ]);

        $this->mock(AiQueryExecutorService::class, function ($mock) {
            $mock->shouldReceive('execute')->once()->with(Mockery::on(function (array $preview) {
                $sql = strtolower($preview['sql']);

                return str_contains($sql, 'count(distinct users.id)')
                    && str_contains($sql, 'group by')
                    && $preview['columns'] === ['role_name', 'user_count'];
            }), Mockery::type('int'))->andReturn([
                'success' => true,
                'rows' => [
                    ['role_name' => 'Employee', 'user_count' => 14],
                ],
                'row_count' => 1,
                'connection' => 'testing',
                'error_message' => null,
            ]);
        });

        $this->mock(AiAnswerFormatterService::class, function ($mock) {
            $mock->shouldReceive('format')->once()->andReturn([
                'type' => 'table',
                'message' => 'Here is the user count by role.',
                'columns' => ['role_name', 'user_count'],
                'rows' => [
                    ['role_name' => 'Employee', 'user_count' => 14],
                ],
                'cards' => [],
            ]);
        });

        $this->actingAs($user)->postJson(route('ai-chat.send'), [
            'message' => 'Mjhe users ka count chaiye roles k hisaab sa',
        ])->assertOk()
            ->assertJsonPath('messages.1.metadata.query_plan.intent', 'user_role_count')
            ->assertJsonPath('messages.1.content', 'Here is the user count by role.');
    }

    public function test_in_progress_projects_question_maps_to_project_list_filtered_by_task_status(): void
    {
        $user = $this->userWithRole('Admin');
        $this->mockPlannerResponse([
            'answer_type' => 'text',
            'intent' => 'unknown',
            'tables' => [],
            'columns' => [],
            'group_by' => [],
            'filters' => [],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => 'I cannot map this question safely.',
        ]);

        $this->mock(AiQueryExecutorService::class, function ($mock) {
            $mock->shouldReceive('execute')->once()->with(Mockery::on(function (array $preview) {
                return str_contains($preview['sql'], 'projects')
                    && in_array('%In-Progress%', $preview['bindings'], true);
            }), Mockery::type('int'))->andReturn([
                'success' => true,
                'rows' => [
                    ['project_name' => 'Solar A', 'code' => 'P-001', 'status' => 'In-Progress'],
                ],
                'row_count' => 1,
                'connection' => 'testing',
                'error_message' => null,
            ]);
        });

        $this->mock(AiAnswerFormatterService::class, function ($mock) {
            $mock->shouldReceive('format')->once()->andReturn([
                'type' => 'table',
                'message' => 'Here are the In-Progress projects.',
                'columns' => ['project_name', 'code', 'status'],
                'rows' => [
                    ['project_name' => 'Solar A', 'code' => 'P-001', 'status' => 'In-Progress'],
                ],
                'cards' => [],
            ]);
        });

        $this->actingAs($user)->postJson(route('ai-chat.send'), [
            'message' => 'In-Progress projects show karo',
        ])->assertOk()
            ->assertJsonPath('messages.1.content', 'Here are the In-Progress projects.')
            ->assertJsonPath('messages.1.metadata.query_plan.intent', 'crm_list')
            ->assertJsonPath('messages.1.metadata.query_plan.filters.0.value', 'In-Progress')
            ->assertJsonPath('messages.1.metadata.query_plan.tables.2', 'departments')
            ->assertJsonPath('messages.1.metadata.query_plan.tables.3', 'sub_departments');
    }

    public function test_in_progress_projects_can_exclude_archived_department(): void
    {
        $user = $this->userWithRole('Admin');
        $this->mockPlannerResponse([
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
        ]);

        $this->mock(AiQueryExecutorService::class, function ($mock) {
            $mock->shouldReceive('execute')->once()->with(Mockery::on(function (array $preview) {
                $sql = strtolower($preview['sql']);

                return str_contains($sql, 'department_id')
                    && in_array(9, $preview['bindings'], true);
            }), Mockery::type('int'))->andReturn([
                'success' => true,
                'rows' => [
                    ['project_name' => 'Solar A', 'code' => 'P-001', 'status' => 'In-Progress', 'department_name' => 'Permitting'],
                ],
                'row_count' => 1,
                'connection' => 'testing',
                'error_message' => null,
            ]);
        });

        $this->mock(AiAnswerFormatterService::class, function ($mock) {
            $mock->shouldReceive('format')->once()->andReturn([
                'type' => 'table',
                'message' => 'Here are the In-Progress projects excluding archived department.',
                'columns' => ['project_name', 'code', 'status', 'department_name'],
                'rows' => [
                    ['project_name' => 'Solar A', 'code' => 'P-001', 'status' => 'In-Progress', 'department_name' => 'Permitting'],
                ],
                'cards' => [],
            ]);
        });

        $this->actingAs($user)->postJson(route('ai-chat.send'), [
            'message' => 'In-Progress projects show karo magar archived department k mat show krna',
        ])->assertOk()
            ->assertJsonPath('messages.1.metadata.query_plan.filters.1.column', 'department_id')
            ->assertJsonPath('messages.1.metadata.query_plan.filters.1.value', 9);
    }

    public function test_pending_tickets_question_maps_to_ticket_list_filtered_by_status(): void
    {
        $user = $this->userWithRole('Admin');
        $this->mockPlannerResponse([
            'answer_type' => 'text',
            'intent' => 'unknown',
            'tables' => [],
            'columns' => [],
            'group_by' => [],
            'filters' => [],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => 'I cannot map this question safely.',
        ]);

        $this->mock(AiQueryExecutorService::class, function ($mock) {
            $mock->shouldReceive('execute')->once()->with(Mockery::on(function (array $preview) {
                return str_contains($preview['sql'], 'service_tickets')
                    && in_array('%Pending%', $preview['bindings'], true);
            }), Mockery::type('int'))->andReturn([
                'success' => true,
                'rows' => [
                    ['subject' => 'Panel issue', 'priority' => 'High', 'status' => 'Pending', 'name' => 'Admin User'],
                ],
                'row_count' => 1,
                'connection' => 'testing',
                'error_message' => null,
            ]);
        });

        $this->mock(AiAnswerFormatterService::class, function ($mock) {
            $mock->shouldReceive('format')->once()->andReturn([
                'type' => 'table',
                'message' => 'Here are the pending tickets.',
                'columns' => ['subject', 'priority', 'status', 'name'],
                'rows' => [
                    ['subject' => 'Panel issue', 'priority' => 'High', 'status' => 'Pending', 'name' => 'Admin User'],
                ],
                'cards' => [],
            ]);
        });

        $this->actingAs($user)->postJson(route('ai-chat.send'), [
            'message' => 'Pending tickets show karo',
        ])->assertOk()
            ->assertJsonPath('messages.1.metadata.query_plan.intent', 'crm_list')
            ->assertJsonPath('messages.1.metadata.query_plan.filters.0.value', 'Pending');
    }

    public function test_assigned_employee_project_question_filters_employee_name_and_hides_employee_id(): void
    {
        $user = $this->userWithRole('Admin');
        $this->mockPlannerResponse([
            'answer_type' => 'table',
            'intent' => 'crm_list',
            'tables' => ['tasks', 'projects'],
            'columns' => ['project_name', 'employee_id'],
            'group_by' => [],
            'filters' => [],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => null,
        ]);

        $this->mock(AiQueryExecutorService::class, function ($mock) {
            $mock->shouldReceive('execute')->once()->with(Mockery::on(function (array $preview) {
                $sql = strtolower($preview['sql']);

                return str_contains($sql, 'join "employees"')
                    && str_contains($sql, '"employees"."name" like')
                    && str_contains($sql, 'assigned_employee_name')
                    && str_contains($sql, 'max(latest_tasks.id)')
                    && str_contains($sql, 'group by "projects"."id"')
                    && in_array('%ibad dawood%', $preview['bindings'], true)
                    && in_array('%ibad%dawood%', $preview['bindings'], true)
                    && in_array('In-Progress', $preview['bindings'], true)
                    && $preview['columns'] === ['project_name', 'code', 'assigned_employee_name'];
            }), Mockery::type('int'))->andReturn([
                'success' => true,
                'rows' => [
                    ['project_name' => 'Solar House', 'code' => 'P-100', 'assigned_employee_name' => 'Ibad Dawood'],
                ],
                'row_count' => 1,
                'connection' => 'testing',
                'error_message' => null,
            ]);
        });

        $this->mock(AiAnswerFormatterService::class, function ($mock) {
            $mock->shouldReceive('format')->once()->andReturn([
                'type' => 'table',
                'message' => 'Here are Ibad Dawood assigned projects.',
                'columns' => ['project_name', 'code', 'assigned_employee_name'],
                'rows' => [
                    ['project_name' => 'Solar House', 'code' => 'P-100', 'assigned_employee_name' => 'Ibad Dawood'],
                ],
                'cards' => [],
            ]);
        });

        $this->actingAs($user)->postJson(route('ai-chat.send'), [
            'message' => 'Show the project list which are assigned to Ibad Dawood employee show the project name and assigned column name do not show duplication projects',
        ])->assertOk()
            ->assertJsonPath('messages.1.metadata.query_plan.intent', 'crm_list')
            ->assertJsonPath('messages.1.metadata.query_plan.tables.2', 'employees')
            ->assertJsonPath('messages.1.metadata.query_plan.filters.0.column', 'name')
            ->assertJsonPath('messages.1.metadata.query_plan.filters.0.value', 'ibad dawood')
            ->assertJsonMissing(['employee_id']);
    }

    public function test_assigned_to_me_project_question_uses_current_employee_scope(): void
    {
        $user = $this->userWithRole('Super Admin');
        $this->mockPlannerResponse([
            'answer_type' => 'text',
            'intent' => 'unknown',
            'tables' => [],
            'columns' => [],
            'group_by' => [],
            'filters' => [],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => 'OpenAI fallback should not be used.',
        ]);

        $this->mock(AiQueryExecutorService::class, function ($mock) {
            $mock->shouldReceive('execute')->once()->with(Mockery::on(function (array $preview) {
                $sql = strtolower($preview['sql']);

                return str_contains($sql, '"tasks"."employee_id" in')
                    && str_contains($sql, 'select "id" from "employees" where "user_id" = ?')
                    && ! in_array('%me%', $preview['bindings'], true)
                    && $preview['columns'] === ['project_name', 'code', 'assigned_employee_name'];
            }), Mockery::type('int'))->andReturn([
                'success' => true,
                'rows' => [
                    ['project_name' => 'Solar House', 'code' => 'P-100', 'assigned_employee_name' => 'Super Admin'],
                ],
                'row_count' => 1,
                'connection' => 'testing',
                'error_message' => null,
            ]);
        });

        $this->mock(AiAnswerFormatterService::class, function ($mock) {
            $mock->shouldReceive('format')->once()->andReturn([
                'type' => 'table',
                'message' => 'Here are your assigned projects.',
                'columns' => ['project_name', 'code', 'assigned_employee_name'],
                'rows' => [
                    ['project_name' => 'Solar House', 'code' => 'P-100', 'assigned_employee_name' => 'Super Admin'],
                ],
                'cards' => [],
            ]);
        });

        $this->actingAs($user)->postJson(route('ai-chat.send'), [
            'message' => 'Show projects assigned to me',
        ])->assertOk()
            ->assertJsonPath('messages.1.metadata.query_plan.filters.0.column', 'employee_id')
            ->assertJsonPath('messages.1.metadata.query_plan.filters.0.value', 'current_employee.id')
            ->assertJsonPath('messages.1.content', 'Here are your assigned projects.');
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
        $roleModel = Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        $roleModel->givePermissionTo(Permission::firstOrCreate(['name' => 'SolenAssist', 'guard_name' => 'web']));
        $user->syncRoles([$roleModel]);

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
            $mock->shouldReceive('createJsonResponse')->zeroOrMoreTimes()->andReturn([
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
