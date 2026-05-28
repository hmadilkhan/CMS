<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class AiQueryPlannerService
{
    // Removed hardcoded intents - now AI can generate any intent dynamically

    private const ALLOWED_ANSWER_TYPES = [
        'text',
        'table',
        'card',
        'count',
    ];

    public function __construct(
        private readonly OpenAiService $openAiService,
        private readonly AiSchemaService $aiSchemaService,
        private readonly AiPermissionService $aiPermissionService
    ) {
    }

    public function plan(string $question, User $user): array
    {
        if ($this->isWriteOperationQuestion($question)) {
            return [
                'plan' => array_merge($this->unknownPlan(), [
                    'fallback_message' => 'I can only read CRM data. Insert, update, delete, and other write operations are not allowed.',
                ]),
                'openai' => $this->syntheticOpenAiResponse(),
            ];
        }

        $inferredPlan = $this->inferKnownPlan($question);

        if ($inferredPlan) {
            return [
                'plan' => $this->sanitizePlan($this->withHybridMetadata($inferredPlan, 'fixed_action', 1.0), $user),
                'openai' => $this->syntheticOpenAiResponse(),
            ];
        }

        $response = $this->openAiService->createJsonResponse(
            $this->instructions(),
            [
                'question' => $question,
                'user_role' => $this->userRole($user),
                'allowed_schema' => $this->schemaForPlanner(),
                'crm_module_hints' => $this->moduleHints(),
                'required_json_format' => $this->unknownPlan(),
                'examples' => [
                    [
                        'question' => 'Employees ki list show kro or ye bhi btao k kis employee ko kitne departments allowed hain',
                        'expected_plan' => [
                            'answer_type' => 'table',
                            'intent' => 'employee_department_list',
                            'tables' => ['employees', 'employee_departments'],
                            'columns' => ['name', 'email', 'phone'],
                            'group_by' => ['name', 'email', 'phone'],
                            'filters' => [],
                            'requires_finance_access' => false,
                            'sql' => null,
                            'fallback_message' => null,
                        ],
                    ],
                    [
                        'question' => 'Mere assigned projects kitne hain?',
                        'expected_plan' => [
                            'answer_type' => 'count',
                            'intent' => 'project_count',
                            'tables' => ['projects'],
                            'columns' => ['id'],
                            'group_by' => [],
                            'filters' => [],
                            'requires_finance_access' => false,
                            'sql' => null,
                            'fallback_message' => null,
                        ],
                    ],
                    [
                        'question' => 'Deal Review department me is waqt kitne projects hain?',
                        'expected_plan' => [
                            'answer_type' => 'count',
                            'intent' => 'project_count',
                            'tables' => ['projects', 'departments'],
                            'columns' => ['id', 'name'],
                            'group_by' => [],
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
                        ],
                    ],
                    [
                        'question' => 'Mere projects status wise show karo',
                        'expected_plan' => [
                            'answer_type' => 'table',
                            'intent' => 'project_status_summary',
                            'tables' => ['projects', 'tasks'],
                            'columns' => ['id', 'status'],
                            'group_by' => ['status'],
                            'filters' => [],
                            'requires_finance_access' => false,
                            'sql' => null,
                            'fallback_message' => null,
                        ],
                    ],
                    [
                        'question' => 'In-Progress projects show karo',
                        'expected_plan' => [
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
                        ],
                    ],
                    [
                        'question' => 'Total project count department or subdepartment name ke sath show karo',
                        'expected_plan' => [
                            'answer_type' => 'table',
                            'intent' => 'project_department_summary',
                            'tables' => ['projects', 'departments', 'sub_departments'],
                            'columns' => ['department_id', 'sub_department_id', 'name'],
                            'filters' => [],
                            'requires_finance_access' => false,
                            'sql' => null,
                            'fallback_message' => null,
                        ],
                    ],
                    [
                        'question' => 'Project customer info show karo',
                        'expected_plan' => [
                            'answer_type' => 'card',
                            'intent' => 'project_customer',
                            'tables' => ['projects', 'customers'],
                            'columns' => ['project_name', 'code', 'first_name', 'last_name', 'email', 'phone', 'city', 'state'],
                            'group_by' => [],
                            'filters' => [],
                            'requires_finance_access' => false,
                            'sql' => null,
                            'fallback_message' => null,
                        ],
                    ],
                    [
                        'question' => 'Tickets status wise show karo',
                        'expected_plan' => [
                            'answer_type' => 'table',
                            'intent' => 'ticket_status',
                            'tables' => ['service_tickets'],
                            'columns' => ['status'],
                            'group_by' => ['status'],
                            'filters' => [],
                            'requires_finance_access' => false,
                            'sql' => null,
                            'fallback_message' => null,
                        ],
                    ],
                    [
                        'question' => 'Priority wise tickets summary show karo',
                        'expected_plan' => [
                            'answer_type' => 'table',
                            'intent' => 'crm_group_summary',
                            'tables' => ['service_tickets'],
                            'columns' => ['priority'],
                            'group_by' => ['priority'],
                            'filters' => [],
                            'requires_finance_access' => false,
                            'sql' => null,
                            'fallback_message' => null,
                        ],
                    ],
                    [
                        'question' => 'How many tickets created by users? Show user name and count of each status.',
                        'expected_plan' => [
                            'answer_type' => 'table',
                            'intent' => 'ticket_creator_status_summary',
                            'tables' => ['service_tickets', 'users'],
                            'columns' => ['user_id', 'name', 'status'],
                            'group_by' => ['name', 'status'],
                            'filters' => [],
                            'requires_finance_access' => false,
                            'sql' => null,
                            'fallback_message' => null,
                        ],
                    ],
                    [
                        'question' => 'Is project ki financing show karo',
                        'expected_plan' => [
                            'answer_type' => 'card',
                            'intent' => 'finance_summary',
                            'tables' => ['project_finances', 'projects'],
                            'columns' => ['project_id', 'finance_option', 'financing_status', 'contract_amount', 'dealer_fee_amount', 'commission_amount'],
                            'group_by' => [],
                            'filters' => [],
                            'requires_finance_access' => true,
                            'sql' => null,
                            'fallback_message' => null,
                        ],
                    ],
                    [
                        'question' => 'Profitability report dikhao',
                        'expected_plan' => [
                            'answer_type' => 'table',
                            'intent' => 'profitability_report',
                            'tables' => ['customers', 'projects', 'sales_partners', 'customer_finances'],
                            'columns' => ['first_name', 'last_name', 'name', 'solar_install_date', 'contract_amount', 'dealer_fee_amount', 'redline_costs', 'adders', 'actual_material_cost', 'actual_labor_cost', 'actual_permit_fee', 'office_cost'],
                            'group_by' => [],
                            'filters' => [],
                            'requires_finance_access' => true,
                            'sql' => null,
                            'fallback_message' => null,
                        ],
                    ],
                    [
                        'question' => 'Profitability report show kro from 1st April 2026 to 30th April 2026',
                        'expected_plan' => [
                            'answer_type' => 'table',
                            'intent' => 'profitability_report_by_date_range',
                            'tables' => ['customers', 'projects', 'sales_partners', 'customer_finances'],
                            'columns' => ['first_name','last_name', 'name','solar_install_date','contract_amount','dealer_fee_amount','redline_costs','adders','actual_material_cost','actual_labor_cost','actual_permit_fee','office_cost'],
                            'group_by' => [],
                            'filters' => [
                                [
                                    'table' => 'projects',
                                    'column' => 'solar_install_date',
                                    'operator' => '>=',
                                    'value' => '2026-04-01',
                                ],
                                [
                                    'table' => 'projects',
                                    'column' => 'solar_install_date',
                                    'operator' => '<=',
                                    'value' => '2026-04-30',
                                ],
                            ],
                            'requires_finance_access' => true,
                            'sql' => null,
                            'fallback_message' => null,
                        ],
                    ],
                    [
                        'question' => 'Customer wise revenue show karo',
                        'expected_plan' => [
                            'answer_type' => 'table',
                            'intent' => 'customer_revenue',
                            'tables' => ['project_revenue', 'customers'],
                            'columns' => ['customer_id', 'first_name', 'last_name', 'revenue_amount'],
                            'group_by' => ['customer_id', 'first_name', 'last_name'],
                            'filters' => [],
                            'requires_finance_access' => true,
                            'sql' => null,
                            'fallback_message' => null,
                        ],
                    ],
                    [
                        'question' => 'Show customers from California',
                        'expected_plan' => [
                            'answer_type' => 'table',
                            'intent' => 'crm_list',
                            'tables' => ['customers'],
                            'columns' => ['first_name', 'last_name', 'email', 'phone', 'city', 'state'],
                            'group_by' => [],
                            'filters' => [
                                [
                                    'column' => 'state',
                                    'operator' => 'like',
                                    'value' => 'California',
                                ],
                            ],
                            'requires_finance_access' => false,
                            'sql' => null,
                            'fallback_message' => null,
                        ],
                    ],
                    [
                        'question' => 'User wise ticket count by status',
                        'expected_plan' => [
                            'answer_type' => 'table',
                            'intent' => 'crm_group_summary',
                            'tables' => ['service_tickets', 'users'],
                            'columns' => ['name', 'status'],
                            'group_by' => ['name', 'status'],
                            'filters' => [],
                            'requires_finance_access' => false,
                            'sql' => null,
                            'fallback_message' => null,
                        ],
                    ],
                    [
                        'question' => 'Employees ki list show kro or ye bhi btao k kis employee ko kitne departments allowed hain',
                        'expected_plan' => [
                            'answer_type' => 'table',
                            'intent' => 'employee_department_list',
                            'tables' => ['employees', 'employee_departments', 'departments'],
                            'columns' => ['name', 'email', 'phone'],
                            'group_by' => ['name', 'email', 'phone'],
                            'filters' => [],
                            'requires_finance_access' => false,
                            'sql' => null,
                            'fallback_message' => null,
                        ],
                    ],
                ],
            ],
            1200,
            $this->jsonSchema()
        );

        $plan = $this->sanitizePlan($this->legacyPlanFromHybrid($response['json']), $user);

        if (($plan['intent'] ?? 'unknown') === 'unknown' && $this->looksLikeCrmDataQuestion($question)) {
            $retryResponse = $this->openAiService->createJsonResponse(
                $this->instructions() . "\n\nSECOND PASS: The previous plan was unsupported. Re-check allowed_schema and crm_module_hints carefully. If the question is about any allowed CRM module, return mode data_explorer with generic intent crm_list, crm_count, crm_group_summary, or crm_detail. Return unsupported only when no allowed table or column can answer it.",
                [
                    'question' => $question,
                    'user_role' => $this->userRole($user),
                    'allowed_schema' => $this->schemaForPlanner(),
                    'crm_module_hints' => $this->moduleHints(),
                    'previous_plan' => $response['json'],
                    'required_json_format' => $this->unknownPlan(),
                ],
                1200,
                $this->jsonSchema()
            );

            $retryPlan = $this->sanitizePlan($this->legacyPlanFromHybrid($retryResponse['json']), $user);

            if (($retryPlan['intent'] ?? 'unknown') !== 'unknown') {
                $response = $retryResponse;
                $plan = $retryPlan;
            }
        }
        
        // Store original question in plan for AI SQL generation
        $plan['original_question'] = $question;

        if ($inferredPlan && (($plan['intent'] ?? 'unknown') === 'unknown' || $this->inferredPlanIsMoreSpecific($plan, $inferredPlan))) {
            $plan = $this->sanitizePlan($this->withHybridMetadata($inferredPlan, 'fixed_action', 1.0), $user);
        }

        return [
            'plan' => $plan,
            'openai' => $response,
        ];
    }

    public function looksLikeCrmDataQuestion(string $question): bool
    {
        $question = mb_strtolower($question);

        $keywords = [
            'project',
            'projects',
            'customer',
            'customers',
            'client',
            'clients',
            'ticket',
            'tickets',
            'task',
            'tasks',
            'department',
            'departments',
            'role',
            'roles',
            'permission',
            'permissions',
            'subdepartment',
            'sub department',
            'sub_department',
            'assigned',
            'mere',
            'mery',
            'meri',
            'kitne',
            'kitni',
            'count',
            'status',
            'finance',
            'financing',
            'profitability',
            'report',
            'expense',
            'payment',
            'transaction',
            'transactions',
            'remittance',
            'remitted',
            'deduction',
            'milestone',
            'payee',
            'amount',
            'cost',
            'profit',
            'revenue',
            'commission',
            'forecast',
            'override',
            'pending',
            'resolved',
            'pto',
            'ntp',
        ];

        foreach ($keywords as $keyword) {
            if (str_contains($question, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function instructions(): string
    {
        return <<<'PROMPT'
You are a safe CRM query planner for a Laravel CRM.

Return structured JSON only. Do not return markdown. Do not answer the user's CRM question directly.
Do not generate SQL. Never include a raw SQL string.
Use only the supplied allowed_schema tables and allowed columns.

IMPORTANT: Be FLEXIBLE and HELPFUL. If the question can be answered using allowed_schema:
- Use appropriate intent (project_count, crm_list, crm_group_summary, etc.)
- Include ALL relevant tables needed to answer the question
- Include ALL relevant columns from those tables
- Add appropriate filters based on the question
- Do not require a fixed/hardcoded intent. Fixed actions are optional; data_explorer is the normal mode for new CRM questions.
- Before returning unsupported, search allowed_schema and crm_module_hints for related tables, relationships, searchable columns, status fields, name fields, and date fields.
- Prefer generic intents when no exact report exists: crm_list, crm_count, crm_group_summary, crm_detail.
- Prefer real mapped tables over placeholders. For project financing use projects + customers + customer_finances + finance_options when those tables are allowed.

CURRENT USER CONTEXT:
When user asks about "my", "assigned to me", "mine", etc., add filter:
- For projects assigned to current user: use tasks + projects and filter {"table": "tasks", "column": "employee_id", "operator": "=", "value": "current_employee.id"}
- For tickets: Use filter {"column": "user_id", "operator": "=", "value": "current_user.id"} OR {"column": "assigned_to", "operator": "=", "value": "current_user.id"}
- For tasks: Use filter {"column": "user_id", "operator": "=", "value": "current_user.id"}

EXAMPLES:
"How many projects assigned to me?" → Add filter: {"column": "user_id", "operator": "=", "value": "current_user.id"}
"My tickets" → Add filter: {"column": "user_id", "operator": "=", "value": "current_user.id"}
"Show my projects" → Add filter: {"column": "user_id", "operator": "=", "value": "current_user.id"}

For NEW or UNCOMMON questions:
- Use "crm_count" for simple counts
- Use "crm_list" for listing records with filters
- Use "crm_group_summary" for grouped summaries
- Set answer_type to "table" for lists, "count" for counts, "card" for detailed views

If the question cannot be answered from allowed_schema, return intent "unknown".
If the question needs finance, payment, cost, revenue, amount, commission, margin, profit, or profitability data, set requires_finance_access to true.
If any requested table or column is missing from allowed_schema, do not invent it; return intent "unknown".

The preferred output shape is:
{
  "mode": "fixed_action|data_explorer|clarification_required|unsupported",
  "confidence": 0.82,
  "entities": [],
  "selected_columns": [{"table": "projects", "columns": ["id", "project_name"]}],
  "filters": [],
  "relationships": [],
  "sort": [],
  "limit": 20,
  "answer_type": "text|table|card|count",
  "intent": "<any descriptive string like project_count, customer_list, ticket_summary>",
  "needs_clarification": false,
  "clarification_question": null,
  "fallback_message": null
}

For filters, use objects with "table", "column", "operator", and "value".
For current user filters, use "current_user.id" as the value.
Allowed operators are only: =, !=, >, >=, <, <=, like, in, between.

Never include sensitive columns such as password, remember_token, api_token, token, secret unless they are explicitly allowed in allowed_schema and the user role requires finance access.
Reject write requests such as insert, update, delete, create, drop, alter, truncate, restore, approve, move, assign, or change by returning unknown.

EXAMPLES OF FLEXIBILITY:
- "Show me all customers" → intent: "crm_list", tables: ["customers"], columns: ["first_name", "last_name", "email", "phone", "city", "state"]
- "How many users are there?" → intent: "crm_count", tables: ["users"], columns: ["id"]
- "List projects with their customer names" → intent: "crm_list", tables: ["projects", "customers"], columns: ["project_name", "code", "first_name", "last_name"]
- "Show tickets grouped by priority" → intent: "crm_group_summary", tables: ["service_tickets"], columns: ["priority"], group_by: ["priority"]
PROMPT;
    }

    private function jsonSchema(): array
    {
        return [
            'type' => 'json_schema',
            'name' => 'hybrid_ai_query_plan',
            'strict' => true,
            'schema' => [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => [
                    'mode',
                    'confidence',
                    'entities',
                    'selected_columns',
                    'relationships',
                    'sort',
                    'group_by',
                    'limit',
                    'answer_type',
                    'intent',
                    'filters',
                    'needs_clarification',
                    'clarification_question',
                    'fallback_message',
                ],
                'properties' => [
                    'mode' => [
                        'type' => 'string',
                        'enum' => ['fixed_action', 'data_explorer', 'clarification_required', 'unsupported'],
                    ],
                    'confidence' => [
                        'type' => 'number',
                        'minimum' => 0,
                        'maximum' => 1,
                    ],
                    'entities' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'selected_columns' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'required' => ['table', 'columns'],
                            'properties' => [
                                'table' => ['type' => 'string'],
                                'columns' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                ],
                            ],
                        ],
                    ],
                    'relationships' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'required' => ['from', 'to'],
                            'properties' => [
                                'from' => ['type' => 'string'],
                                'to' => ['type' => 'string'],
                            ],
                        ],
                    ],
                    'sort' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'required' => ['table', 'column', 'direction'],
                            'properties' => [
                                'table' => ['type' => 'string'],
                                'column' => ['type' => 'string'],
                                'direction' => ['type' => 'string', 'enum' => ['asc', 'desc']],
                            ],
                        ],
                    ],
                    'group_by' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'required' => ['table', 'column'],
                            'properties' => [
                                'table' => ['type' => 'string'],
                                'column' => ['type' => 'string'],
                            ],
                        ],
                    ],
                    'limit' => [
                        'type' => 'integer',
                        'minimum' => 1,
                        'maximum' => 100,
                    ],
                    'answer_type' => [
                        'type' => 'string',
                        'enum' => self::ALLOWED_ANSWER_TYPES,
                    ],
                    'intent' => [
                        'type' => 'string',
                        'description' => 'Short label describing the query intent (e.g., project_count, customer_list, ticket_summary)',
                    ],
                    'filters' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'required' => ['table', 'column', 'operator', 'value'],
                            'properties' => [
                                'table' => ['type' => 'string'],
                                'column' => ['type' => 'string'],
                                'operator' => [
                                    'type' => 'string',
                                    'enum' => ['=', '!=', '>', '>=', '<', '<=', 'like', 'in', 'between'],
                                ],
                                'value' => [
                                    'anyOf' => [
                                        ['type' => 'string'],
                                        ['type' => 'number'],
                                        ['type' => 'boolean'],
                                        ['type' => 'null'],
                                        ['type' => 'array', 'items' => ['type' => ['string', 'number', 'boolean', 'null']]],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'needs_clarification' => [
                        'type' => 'boolean',
                    ],
                    'clarification_question' => [
                        'type' => ['string', 'null'],
                    ],
                    'fallback_message' => [
                        'type' => ['string', 'null'],
                    ],
                ],
            ],
        ];
    }

    private function sanitizePlan(array $plan, User $user): array
    {
        $safe = array_merge($this->unknownPlan(), Arr::only($plan, [
            'mode',
            'confidence',
            'entities',
            'selected_columns',
            'answer_type',
            'intent',
            'tables',
            'columns',
            'group_by',
            'filters',
            'relationships',
            'sort',
            'limit',
            'needs_clarification',
            'clarification_question',
            'requires_finance_access',
            'sql',
            'fallback_message',
        ]));

        if (! in_array($safe['answer_type'], self::ALLOWED_ANSWER_TYPES, true)) {
            $safe['answer_type'] = 'text';
        }

        // Allow any intent string - no hardcoded restrictions
        if (empty($safe['intent']) || ! is_string($safe['intent'])) {
            $safe['intent'] = 'unknown';
        }

        $safe['tables'] = array_values(array_filter((array) $safe['tables'], fn ($table) => $this->aiSchemaService->isTableAllowed($table)));

        foreach ($safe['tables'] as $table) {
            if (! $this->aiPermissionService->canAccessTable($user, $table)) {
                return $this->permissionDeniedPlan();
            }
        }

        foreach ((array) $safe['columns'] as $column) {
            foreach ($safe['tables'] as $table) {
                if ($this->aiSchemaService->isColumnAllowed($table, $column) && ! $this->aiPermissionService->canAccessColumn($user, $table, $column)) {
                    return $this->permissionDeniedPlan();
                }
            }
        }

        $safe['columns'] = array_values(array_filter((array) $safe['columns'], function ($column) use ($safe, $user) {
            foreach ($safe['tables'] as $table) {
                if ($this->aiPermissionService->canAccessColumn($user, $table, $column)) {
                    return true;
                }
            }

            return false;
        }));

        $safe['group_by'] = array_values(array_filter((array) ($safe['group_by'] ?? []), function ($column) use ($safe, $user) {
            foreach ($safe['tables'] as $table) {
                if ($this->aiPermissionService->canAccessColumn($user, $table, $column)) {
                    return true;
                }
            }

            return false;
        }));

        foreach ((array) $safe['filters'] as $filter) {
            if (! is_array($filter) || ! isset($filter['column'])) {
                continue;
            }

            foreach ($safe['tables'] as $table) {
                if ($this->aiSchemaService->isColumnAllowed($table, $filter['column']) && ! $this->aiPermissionService->canAccessColumn($user, $table, $filter['column'])) {
                    return $this->permissionDeniedPlan();
                }
            }
        }

        $safe['filters'] = is_array($safe['filters']) ? array_values(array_filter($safe['filters'], function ($filter) use ($safe, $user) {
            if (! is_array($filter) || ! isset($filter['column'], $filter['operator'])) {
                return false;
            }

            foreach ($safe['tables'] as $table) {
                if ($this->aiPermissionService->canAccessColumn($user, $table, $filter['column'])) {
                    return true;
                }
            }

            return false;
        })) : [];
        $safe['requires_finance_access'] = (bool) $safe['requires_finance_access'];
        $safe['sql'] = null;
        $safe['limit'] = max(1, min((int) ($safe['limit'] ?? 100), 100));

        if ($safe['requires_finance_access'] && ! $this->aiPermissionService->canAccessFinance($user)) {
            return $this->permissionDeniedPlan();
        }

        foreach ($safe['tables'] as $table) {
            if ($this->aiSchemaService->getAccessRule($table) === 'profitability_access' && ! $this->aiPermissionService->canAccessProfitability($user)) {
                return $this->permissionDeniedPlan();
            }
        }

        if ($safe['intent'] === 'unknown' || empty($safe['tables'])) {
            $safe['answer_type'] = 'text';
            $safe['intent'] = 'unknown';
            $safe['tables'] = [];
            $safe['columns'] = [];
            $safe['group_by'] = [];
            $safe['filters'] = [];
            $safe['requires_finance_access'] = false;
            $safe['mode'] = $safe['mode'] === 'clarification_required' ? 'clarification_required' : 'unsupported';
            $safe['fallback_message'] = $safe['fallback_message'] ?: 'I can plan CRM data queries only for allowed CRM tables and columns. I could not map this question safely yet.';
        }

        return $safe;
    }

    private function legacyPlanFromHybrid(array $plan): array
    {
        if (! array_key_exists('mode', $plan)) {
            return $this->withHybridMetadata($plan, 'data_explorer', (float) ($plan['confidence'] ?? 0.75));
        }

        $mode = (string) ($plan['mode'] ?? 'unsupported');

        if (in_array($mode, ['unsupported', 'clarification_required'], true)) {
            return array_merge($this->unknownPlan(), [
                'mode' => $mode,
                'confidence' => (float) ($plan['confidence'] ?? 0),
                'entities' => array_values((array) ($plan['entities'] ?? [])),
                'selected_columns' => $this->normalizeSelectedColumns($plan['selected_columns'] ?? $plan['select'] ?? []),
                'relationships' => array_values((array) ($plan['relationships'] ?? [])),
                'sort' => array_values((array) ($plan['sort'] ?? [])),
                'limit' => max(1, min((int) ($plan['limit'] ?? 100), 100)),
                'needs_clarification' => (bool) ($plan['needs_clarification'] ?? $mode === 'clarification_required'),
                'clarification_question' => $plan['clarification_question'] ?? null,
                'fallback_message' => $plan['clarification_question']
                    ?? $plan['fallback_message']
                    ?? 'I can answer CRM data questions only from the allowed CRM modules.',
            ]);
        }

        $select = $this->normalizeSelectedColumns($plan['selected_columns'] ?? $plan['select'] ?? []);
        $tables = array_values(array_unique(array_merge(
            array_values((array) ($plan['entities'] ?? [])),
            array_keys($select)
        )));
        $columns = collect($select)->flatMap(fn ($columns) => (array) $columns)->unique()->values()->all();
        $groupBy = collect((array) ($plan['group_by'] ?? []))
            ->map(fn ($item) => is_array($item) ? ($item['column'] ?? null) : $item)
            ->filter()
            ->values()
            ->all();
        $filters = collect((array) ($plan['filters'] ?? []))
            ->filter(fn ($filter) => is_array($filter))
            ->map(fn (array $filter) => Arr::only($filter, ['table', 'column', 'operator', 'value']))
            ->values()
            ->all();

        $requiresFinance = collect($tables)->contains(function (string $table) {
            return in_array($this->aiSchemaService->getAccessRule($table), ['finance_access', 'profitability_access'], true);
        });

        return [
            'mode' => $mode,
            'confidence' => (float) ($plan['confidence'] ?? 0.75),
            'entities' => $tables,
            'selected_columns' => $this->selectedColumnsPayload($select),
            'answer_type' => $plan['answer_type'] ?? 'table',
            'intent' => $plan['intent'] ?? 'crm_list',
            'tables' => $tables,
            'columns' => $columns,
            'group_by' => $groupBy,
            'filters' => $filters,
            'relationships' => array_values((array) ($plan['relationships'] ?? [])),
            'sort' => array_values((array) ($plan['sort'] ?? [])),
            'limit' => max(1, min((int) ($plan['limit'] ?? 100), 100)),
            'needs_clarification' => (bool) ($plan['needs_clarification'] ?? false),
            'clarification_question' => $plan['clarification_question'] ?? null,
            'requires_finance_access' => (bool) ($plan['requires_finance_access'] ?? $requiresFinance),
            'sql' => null,
            'fallback_message' => $plan['fallback_message'] ?? null,
        ];
    }

    private function withHybridMetadata(array $plan, string $mode, float $confidence): array
    {
        $tables = array_values((array) ($plan['tables'] ?? []));

        return array_merge([
            'mode' => $mode,
            'confidence' => $confidence,
            'entities' => $tables,
            'selected_columns' => $this->selectedColumnsPayload(collect($tables)->mapWithKeys(fn (string $table) => [
                $table => array_values((array) ($plan['columns'] ?? [])),
            ])->all()),
            'relationships' => [],
            'sort' => [],
            'limit' => 100,
            'needs_clarification' => false,
            'clarification_question' => null,
        ], $plan);
    }

    private function normalizeSelectedColumns(mixed $selectedColumns): array
    {
        if (! is_array($selectedColumns)) {
            return [];
        }

        $isList = array_is_list($selectedColumns);

        if (! $isList) {
            return collect($selectedColumns)
                ->mapWithKeys(fn ($columns, string $table) => [$table => array_values((array) $columns)])
                ->all();
        }

        return collect($selectedColumns)
            ->filter(fn ($item) => is_array($item) && isset($item['table'], $item['columns']))
            ->mapWithKeys(fn (array $item) => [(string) $item['table'] => array_values((array) $item['columns'])])
            ->all();
    }

    private function selectedColumnsPayload(array $selectedColumns): array
    {
        return collect($selectedColumns)
            ->map(fn (array $columns, string $table) => [
                'table' => $table,
                'columns' => array_values($columns),
            ])
            ->values()
            ->all();
    }

    private function schemaForPlanner(): array
    {
        return collect($this->aiSchemaService->getAllowedTables())
            ->mapWithKeys(function (string $table) {
                return [
                    $table => [
                        'access_rule' => $this->aiSchemaService->getAccessRule($table),
                        'allowed_columns' => $this->aiSchemaService->getAllowedColumns($table),
                        'searchable_columns' => $this->aiSchemaService->getSearchableColumns($table),
                        'relationships' => $this->aiSchemaService->getRelationships($table),
                        'sensitive_columns' => $this->sensitiveAllowedColumns($table),
                    ],
                ];
            })
            ->all();
    }

    private function moduleHints(): array
    {
        return [
            'projects' => [
                'tables' => ['projects', 'customers', 'departments', 'sub_departments', 'tasks', 'employees', 'project_acceptances'],
                'common_columns' => ['project_name', 'code', 'status', 'department_id', 'sub_department_id', 'customer_id', 'created_at', 'updated_at'],
                'common_questions' => ['counts', 'lists', 'status summaries', 'department summaries', 'assigned projects', 'project details'],
            ],
            'tickets' => [
                'tables' => ['service_tickets', 'users', 'projects'],
                'common_columns' => ['subject', 'priority', 'status', 'user_id', 'assigned_to', 'created_at', 'updated_at'],
                'common_questions' => ['ticket lists', 'status counts', 'creator summaries', 'assigned tickets'],
            ],
            'users_and_roles' => [
                'tables' => ['users', 'model_has_roles', 'roles', 'employees'],
                'common_columns' => ['name', 'email', 'username', 'role_id', 'model_id'],
                'common_questions' => ['user lists', 'role lists', 'role counts', 'employee lookup'],
            ],
            'finance' => [
                'tables' => ['projects', 'customers', 'customer_finances', 'finance_options', 'account_transactions'],
                'common_columns' => ['contract_amount', 'dealer_fee_amount', 'commission', 'holdback_amount', 'customer_portion', 'amount'],
                'requires_finance_access' => true,
                'common_questions' => ['project financing', 'customer financing', 'payments', 'amount summaries', 'finance options'],
            ],
            'transactions' => [
                'tables' => ['account_transactions', 'projects'],
                'common_columns' => ['project_id', 'payee', 'milestone', 'amount', 'deduction_amount', 'transaction_date', 'transaction_details'],
                'date_filter_column' => 'account_transactions.transaction_date',
                'requires_finance_access' => true,
                'common_questions' => ['transaction report', 'remitted amount', 'deductions', 'payee summaries', 'milestone payments'],
            ],
            'project_acceptance' => [
                'tables' => ['projects', 'customers', 'project_acceptances', 'users'],
                'common_columns' => ['status', 'approved_date', 'reason', 'panel_qty', 'inverter_name', 'action_by'],
                'status_values' => ['pending' => 0, 'approved' => 1, 'rejected' => 2],
            ],
        ];
    }

    private function inferKnownPlan(string $question): ?array
    {
        $normalized = mb_strtolower($question);
        $mentionsProject = str_contains($normalized, 'project');
        $mentionsTicket = str_contains($normalized, 'ticket');
        $mentionsDepartment = str_contains($normalized, 'department') || str_contains($normalized, 'subdepartment') || str_contains($normalized, 'sub department');
        $mentionsRole = str_contains($normalized, 'role') || str_contains($normalized, 'roles');
        $mentionsCount = str_contains($normalized, 'count') || str_contains($normalized, 'kitne') || str_contains($normalized, 'kitni') || str_contains($normalized, 'wise');
        $mentionsUser = str_contains($normalized, 'user') || str_contains($normalized, 'users') || str_contains($normalized, 'created by') || str_contains($normalized, 'creator');
        $mentionsStatus = str_contains($normalized, 'status') || str_contains($normalized, 'pending') || str_contains($normalized, 'resolved');
        $mentionsSummary = str_contains($normalized, 'summary') || str_contains($normalized, 'summarize') || str_contains($normalized, 'wise');
        $mentionsAcceptance = str_contains($normalized, 'acceptance') || str_contains($normalized, 'approved') || str_contains($normalized, 'approval');
        $mentionsForecast = str_contains($normalized, 'forecast');
        $mentionsOverride = str_contains($normalized, 'override') || str_contains($normalized, 'overrider');
        $mentionsTransaction = str_contains($normalized, 'transaction')
            || str_contains($normalized, 'transactions')
            || str_contains($normalized, 'remittance')
            || str_contains($normalized, 'remitted')
            || str_contains($normalized, 'deduction');
        $mentionsProfitability = str_contains($normalized, 'profitability')
            || str_contains($normalized, 'profitablity')
            || str_contains($normalized, 'profitable')
            || str_contains($normalized, 'profit report')
            || str_contains($normalized, 'profit report');
        $mentionsFinance = str_contains($normalized, 'finance')
            || str_contains($normalized, 'financing')
            || str_contains($normalized, 'contract amount')
            || str_contains($normalized, 'dealer fee')
            || str_contains($normalized, 'commission');
        $status = $this->extractStatus($normalized);
        $assignedEmployeeName = $this->extractAssignedEmployeeName($normalized);
        $ticketUserName = $this->extractTicketUserName($normalized);
        $projectSummaryName = $this->extractProjectSummaryName($normalized);
        $projectAcceptanceName = $this->extractProjectAcceptanceName($normalized);
        $acceptanceStatus = $this->extractAcceptanceStatus($normalized);
        $dateRange = $this->extractDateRange($question);

        if ($mentionsUser && $mentionsRole) {
            $wantsCount = $mentionsCount
                || str_contains($normalized, 'count')
                || str_contains($normalized, 'hisaab')
                || str_contains($normalized, 'hisab')
                || str_contains($normalized, 'kitne')
                || str_contains($normalized, 'kitni');

            return [
                'answer_type' => 'table',
                'intent' => $wantsCount ? 'user_role_count' : 'user_role_list',
                'tables' => ['users', 'model_has_roles', 'roles'],
                'columns' => ['name', 'email', 'username', 'role_id', 'model_id'],
                'group_by' => $wantsCount ? ['name'] : [],
                'filters' => [],
                'requires_finance_access' => false,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        if ($mentionsProject && $mentionsSummary && $projectSummaryName) {
            return [
                'answer_type' => 'card',
                'intent' => 'project_summary',
                'tables' => ['projects', 'customers', 'departments', 'sub_departments', 'tasks', 'employees', 'project_acceptances'],
                'columns' => [
                    'project_name',
                    'code',
                    'first_name',
                    'last_name',
                    'phone',
                    'email',
                    'city',
                    'state',
                    'department_id',
                    'sub_department_id',
                    'name',
                    'status',
                    'approved_date',
                    'reason',
                    'created_at',
                    'updated_at',
                ],
                'group_by' => [],
                'filters' => [
                    [
                        'column' => 'project_name',
                        'operator' => 'like',
                        'value' => $projectSummaryName,
                    ],
                ],
                'requires_finance_access' => false,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        if ($mentionsProject && $mentionsAcceptance && $projectAcceptanceName) {
            return [
                'answer_type' => 'card',
                'intent' => 'project_acceptance_summary',
                'tables' => ['projects', 'customers', 'project_acceptances'],
                'columns' => [
                    'project_name',
                    'code',
                    'first_name',
                    'last_name',
                    'status',
                    'approved_date',
                    'reason',
                    'panel_qty',
                    'inverter_name',
                    'adders_list',
                    'notes',
                    'action_by',
                    'created_at',
                    'updated_at',
                ],
                'group_by' => [],
                'filters' => [
                    [
                        'column' => 'project_name',
                        'operator' => 'like',
                        'value' => $projectAcceptanceName,
                    ],
                ],
                'requires_finance_access' => false,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        if ($mentionsForecast) {
            return [
                'answer_type' => 'table',
                'intent' => $dateRange ? 'forecast_report_by_date_range' : 'forecast_report',
                'tables' => ['customers', 'sales_partners', 'customer_finances'],
                'columns' => [
                    'sold_date',
                    'first_name',
                    'last_name',
                    'name',
                    'contract_amount',
                    'commission',
                    'dealer_fee_amount',
                ],
                'group_by' => [],
                'filters' => $dateRange ? [
                    [
                        'table' => 'customers',
                        'column' => 'sold_date',
                        'operator' => '>=',
                        'value' => $dateRange['from'],
                    ],
                    [
                        'table' => 'customers',
                        'column' => 'sold_date',
                        'operator' => '<=',
                        'value' => $dateRange['to'],
                    ],
                ] : [],
                'requires_finance_access' => true,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        if ($mentionsOverride) {
            return [
                'answer_type' => 'table',
                'intent' => $dateRange ? 'override_report_by_date_range' : 'override_report',
                'tables' => ['customers', 'projects', 'sales_partners', 'users', 'customer_finances'],
                'columns' => [
                    'sold_date',
                    'first_name',
                    'last_name',
                    'name',
                    'sales_partner_user_id',
                    'panel_qty',
                    'redline_costs',
                    'overwrite_base_price',
                    'overwrite_panel_price',
                ],
                'group_by' => [],
                'filters' => $dateRange ? [
                    [
                        'table' => 'customers',
                        'column' => 'sold_date',
                        'operator' => '>=',
                        'value' => $dateRange['from'],
                    ],
                    [
                        'table' => 'customers',
                        'column' => 'sold_date',
                        'operator' => '<=',
                        'value' => $dateRange['to'],
                    ],
                ] : [],
                'requires_finance_access' => true,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        if ($mentionsTransaction) {
            return [
                'answer_type' => 'table',
                'intent' => $dateRange ? 'transaction_report_by_date_range' : 'transaction_report',
                'tables' => ['account_transactions', 'projects'],
                'columns' => [
                    'project_id',
                    'project_name',
                    'payee',
                    'milestone',
                    'amount',
                    'deduction_amount',
                    'transaction_date',
                    'transaction_details',
                ],
                'group_by' => [],
                'filters' => $dateRange ? [
                    [
                        'table' => 'account_transactions',
                        'column' => 'transaction_date',
                        'operator' => '>=',
                        'value' => $dateRange['from'],
                    ],
                    [
                        'table' => 'account_transactions',
                        'column' => 'transaction_date',
                        'operator' => '<=',
                        'value' => $dateRange['to'],
                    ],
                ] : [],
                'requires_finance_access' => true,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        if ($mentionsProfitability) {
            return [
                'answer_type' => 'table',
                'intent' => $dateRange ? 'profitability_report_by_date_range' : 'profitability_report',
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
                'filters' => $dateRange ? [
                    [
                        'table' => 'projects',
                        'column' => 'solar_install_date',
                        'operator' => '>=',
                        'value' => $dateRange['from'],
                    ],
                    [
                        'table' => 'projects',
                        'column' => 'solar_install_date',
                        'operator' => '<=',
                        'value' => $dateRange['to'],
                    ],
                ] : [],
                'requires_finance_access' => true,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        if ($mentionsProject && $mentionsAcceptance && $acceptanceStatus !== null && ! ($mentionsCount || str_contains($normalized, 'how many') || str_contains($normalized, 'kitne'))) {
            return [
                'answer_type' => 'table',
                'intent' => 'project_acceptance_list',
                'tables' => ['projects', 'customers', 'project_acceptances'],
                'columns' => [
                    'project_name',
                    'code',
                    'first_name',
                    'last_name',
                    'status',
                    'approved_date',
                    'reason',
                    'created_at',
                    'updated_at',
                ],
                'group_by' => [],
                'filters' => [
                    [
                        'column' => 'status',
                        'operator' => '=',
                        'value' => $acceptanceStatus,
                    ],
                ],
                'requires_finance_access' => false,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        if ($mentionsProject && $mentionsFinance) {
            return [
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
            ];
        }

        if ($mentionsProject && $mentionsAcceptance && ($mentionsCount || str_contains($normalized, 'how many') || str_contains($normalized, 'kitne')) && $acceptanceStatus !== null) {
            return [
                'answer_type' => 'count',
                'intent' => 'project_acceptance_count',
                'tables' => ['projects', 'project_acceptances'],
                'columns' => ['id', 'project_id', 'status'],
                'group_by' => [],
                'filters' => [
                    [
                        'column' => 'status',
                        'operator' => '=',
                        'value' => $acceptanceStatus,
                    ],
                ],
                'requires_finance_access' => false,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        if ($mentionsTicket && ($mentionsUser || $ticketUserName) && ($mentionsStatus || $mentionsCount || $mentionsSummary)) {
            return [
                'answer_type' => 'table',
                'intent' => 'ticket_creator_status_summary',
                'tables' => ['service_tickets', 'users'],
                'columns' => ['user_id', 'name', 'status'],
                'group_by' => ['name', 'status'],
                'filters' => array_values(array_filter([
                    $ticketUserName
                        ? [
                            'column' => 'name',
                            'operator' => 'like',
                            'value' => $ticketUserName,
                        ]
                        : null,
                ])),
                'requires_finance_access' => false,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        if ($mentionsProject && $this->isAssignedToCurrentUserQuestion($normalized)) {
            return [
                'answer_type' => 'table',
                'intent' => 'crm_list',
                'tables' => ['tasks', 'projects', 'employees'],
                'columns' => ['project_name', 'code', 'name'],
                'group_by' => ['project_name', 'code', 'name'],
                'filters' => [
                    [
                        'column' => 'employee_id',
                        'operator' => '=',
                        'value' => 'current_employee.id',
                    ],
                ],
                'requires_finance_access' => false,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        if ($mentionsProject && $assignedEmployeeName) {
            return [
                'answer_type' => 'table',
                'intent' => 'crm_list',
                'tables' => ['tasks', 'projects', 'employees'],
                'columns' => ['project_name', 'code', 'name'],
                'group_by' => ['project_name', 'code', 'name'],
                'filters' => [
                    [
                        'column' => 'name',
                        'operator' => 'like',
                        'value' => $assignedEmployeeName,
                    ],
                ],
                'requires_finance_access' => false,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        if ($mentionsProject && $status) {
            return [
                'answer_type' => 'table',
                'intent' => 'crm_list',
                'tables' => ['tasks', 'projects', 'departments', 'sub_departments'],
                'columns' => ['project_name', 'code', 'status', 'name'],
                'group_by' => [],
                'filters' => array_values(array_filter([
                    [
                        'column' => 'status',
                        'operator' => 'like',
                        'value' => $status,
                    ],
                    $this->excludesArchivedDepartment($normalized)
                        ? [
                            'column' => 'department_id',
                            'operator' => '!=',
                            'value' => (int) config('ai.schema.archived_department_id', 9),
                        ]
                        : null,
                ])),
                'requires_finance_access' => false,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        if ($mentionsTicket && $status) {
            return [
                'answer_type' => 'table',
                'intent' => 'crm_list',
                'tables' => ['service_tickets', 'users'],
                'columns' => ['subject', 'priority', 'status', 'name', 'created_at'],
                'group_by' => [],
                'filters' => [
                    [
                        'column' => 'status',
                        'operator' => 'like',
                        'value' => $status,
                    ],
                ],
                'requires_finance_access' => false,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        if (str_contains($normalized, 'task') && $status) {
            return [
                'answer_type' => 'table',
                'intent' => 'crm_list',
                'tables' => ['tasks', 'projects'],
                'columns' => ['project_name', 'code', 'notes', 'assign_to_notes', 'status'],
                'group_by' => [],
                'filters' => [
                    [
                        'column' => 'status',
                        'operator' => 'like',
                        'value' => $status,
                    ],
                ],
                'requires_finance_access' => false,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        if ($mentionsProject && $mentionsDepartment && $mentionsCount && $departmentName = $this->extractDepartmentName($normalized)) {
            return [
                'answer_type' => 'count',
                'intent' => 'project_count',
                'tables' => ['projects', 'departments'],
                'columns' => ['id', 'name'],
                'group_by' => [],
                'filters' => [
                    [
                        'column' => 'name',
                        'operator' => 'like',
                        'value' => $departmentName,
                    ],
                ],
                'requires_finance_access' => false,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        if ($mentionsProject && $mentionsDepartment && $mentionsCount) {
            $includeSubDepartment = $this->wantsSubDepartmentSummary($normalized);

            return [
                'answer_type' => 'table',
                'intent' => 'project_department_summary',
                'tables' => $includeSubDepartment
                    ? ['projects', 'departments', 'sub_departments']
                    : ['projects', 'departments'],
                'columns' => $includeSubDepartment
                    ? ['department_id', 'sub_department_id', 'name']
                    : ['department_id', 'name'],
                'group_by' => ['name'],
                'filters' => [],
                'requires_finance_access' => false,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        return null;
    }

    private function wantsSubDepartmentSummary(string $question): bool
    {
        $mentionsSubDepartment = str_contains($question, 'subdepartment')
            || str_contains($question, 'sub department')
            || str_contains($question, 'sub_department');

        if (! $mentionsSubDepartment) {
            return false;
        }

        return ! (
            str_contains($question, 'sub department wise ni')
            || str_contains($question, 'sub department wise nahi')
            || str_contains($question, 'subdepartment wise ni')
            || str_contains($question, 'subdepartment wise nahi')
            || str_contains($question, 'sub department wise not')
            || str_contains($question, 'not sub department')
        );
    }

    private function inferredPlanIsMoreSpecific(array $plan, array $inferredPlan): bool
    {
        if (($plan['intent'] ?? null) === 'ticket_status' && ($inferredPlan['intent'] ?? null) === 'ticket_creator_status_summary') {
            return true;
        }

        if (($inferredPlan['intent'] ?? null) === 'crm_list' && ! empty($inferredPlan['filters'] ?? [])) {
            return in_array(($plan['intent'] ?? null), ['unknown', 'crm_list', 'project_status_summary'], true);
        }

        return ($plan['intent'] ?? null) === ($inferredPlan['intent'] ?? null)
            && empty($plan['filters'] ?? [])
            && ! empty($inferredPlan['filters'] ?? []);
    }

    private function extractDepartmentName(string $question): ?string
    {
        if (! preg_match('/([a-z0-9\s&-]+?)\s+department\b/u', $question, $matches)) {
            return null;
        }

        $name = trim($matches[1]);
        $stopWords = [
            'total',
            'count',
            'project',
            'projects',
            'kitne',
            'kitni',
            'is waqt',
            'currently',
            'show',
            'dikhao',
            'dhikhao',
            'me',
            'main',
            'mein',
        ];

        foreach ($stopWords as $word) {
            $name = trim(preg_replace('/\b' . preg_quote($word, '/') . '\b/u', ' ', $name));
        }

        $name = trim(preg_replace('/\s+/u', ' ', $name));

        return $name !== '' ? $name : null;
    }

    private function extractAssignedEmployeeName(string $question): ?string
    {
        if (! str_contains($question, 'assigned') && ! str_contains($question, 'assign')) {
            return null;
        }

        $patterns = [
            '/assigned\s+to\s+(.+?)\s+employee\b/u',
            '/assign(?:ed)?\s+to\s+(.+?)\s+employee\b/u',
            '/assigned\s+to\s+(.+?)(?:\s+show|\s+project|\s+projects|\s+list|$)/u',
        ];

        $name = null;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $question, $matches)) {
                $name = trim($matches[1]);
                break;
            }
        }

        if (! $name) {
            return null;
        }

        $stopWords = [
            'show',
            'project',
            'projects',
            'list',
            'which',
            'are',
            'assigned',
            'assign',
            'to',
            'employee',
            'name',
            'column',
            'columns',
            'do',
            'not',
            'duplication',
            'duplicate',
            'without',
            'with',
        ];

        foreach ($stopWords as $word) {
            $name = trim(preg_replace('/\b' . preg_quote($word, '/') . '\b/u', ' ', $name));
        }

        $name = trim(preg_replace('/\s+/u', ' ', $name));

        if (in_array($name, ['me', 'my', 'mine', 'mujhe', 'mujhy', 'mere', 'meri', 'mera'], true)) {
            return null;
        }

        return $name !== '' ? $name : null;
    }

    private function isAssignedToCurrentUserQuestion(string $question): bool
    {
        if (! str_contains($question, 'assign')) {
            return false;
        }

        return preg_match('/\b(me|my|mine|mujhe|mujhy|mere|meri|mera)\b/u', $question) === 1;
    }

    private function extractTicketUserName(string $question): ?string
    {
        if (! str_contains($question, 'ticket')) {
            return null;
        }

        $patterns = [
            '/summary\s+of\s+(.+?)\s+tickets?\b/u',
            '/(.+?)\s+tickets?\s+summary\b/u',
            '/tickets?\s+(?:of|for|by|created\s+by)\s+(.+?)(?:\s+summary|$)/u',
        ];

        foreach ($patterns as $pattern) {
            if (! preg_match($pattern, $question, $matches)) {
                continue;
            }

            $name = trim($matches[1]);
            $stopWords = [
                'show',
                'me',
                'the',
                'of',
                'for',
                'by',
                'created',
                'ticket',
                'tickets',
                'summary',
                'status',
                'wise',
            ];

            foreach ($stopWords as $word) {
                $name = trim(preg_replace('/\b' . preg_quote($word, '/') . '\b/u', ' ', $name));
            }

            $name = trim(preg_replace('/\s+/u', ' ', $name));

            if ($name !== '') {
                return $name;
            }
        }

        return null;
    }

    private function extractProjectSummaryName(string $question): ?string
    {
        $patterns = [
            '/project\s+summary\s+of\s+(.+?)\s+project\b/u',
            '/summary\s+of\s+(.+?)\s+project\b/u',
            '/(.+?)\s+project\s+summary\b/u',
        ];

        foreach ($patterns as $pattern) {
            if (! preg_match($pattern, $question, $matches)) {
                continue;
            }

            $name = trim($matches[1]);
            $stopWords = [
                'show',
                'me',
                'the',
                'of',
                'project',
                'summary',
                'details',
                'detail',
            ];

            foreach ($stopWords as $word) {
                $name = trim(preg_replace('/\b' . preg_quote($word, '/') . '\b/u', ' ', $name));
            }

            $name = trim(preg_replace('/\s+/u', ' ', $name));

            if ($name !== '') {
                return $name;
            }
        }

        return null;
    }

    private function extractProjectAcceptanceName(string $question): ?string
    {
        if (! str_contains($question, 'acceptance') && ! str_contains($question, 'approved') && ! str_contains($question, 'approval')) {
            return null;
        }

        $patterns = [
            '/(?:for|of)\s+(.+?)\s+project\b/u',
            '/project\s+acceptance\s+(?:of|for)\s+(.+?)(?:\s+project|$)/u',
            '/(.+?)\s+project\s+acceptance\b/u',
            '/(.+?)\s+project\b/u',
        ];

        foreach ($patterns as $pattern) {
            if (! preg_match($pattern, $question, $matches)) {
                continue;
            }

            $name = trim($matches[1]);
            $stopWords = [
                'is',
                'the',
                'this',
                'project',
                'acceptance',
                'approved',
                'approval',
                'status',
                'for',
                'of',
                'show',
                'me',
                'details',
                'detail',
            ];

            foreach ($stopWords as $word) {
                $name = trim(preg_replace('/\b' . preg_quote($word, '/') . '\b/u', ' ', $name));
            }

            $name = trim(preg_replace('/\s+/u', ' ', $name));

            if ($name !== '') {
                return $name;
            }
        }

        return null;
    }

    private function extractAcceptanceStatus(string $question): ?int
    {
        if (str_contains($question, 'approved') || str_contains($question, 'approve')) {
            return 1;
        }

        if (str_contains($question, 'rejected') || str_contains($question, 'reject')) {
            return 2;
        }

        if (str_contains($question, 'pending')) {
            return 0;
        }

        return null;
    }

    private function extractDateRange(string $question): ?array
    {
        $normalized = preg_replace('/\b(\d{1,2})(st|nd|rd|th)\b/i', '$1', $question) ?: $question;
        $monthNames = 'jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|jun(?:e)?|jul(?:y)?|aug(?:ust)?|sep(?:tember)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?';
        $datePattern = '/\b(?:\d{4}-\d{1,2}-\d{1,2}|\d{1,2}\s+(?:' . $monthNames . ')\s+\d{4}|(?:' . $monthNames . ')\s+\d{1,2},?\s+\d{4})\b/i';

        preg_match_all($datePattern, $normalized, $matches);
        $dates = array_values($matches[0] ?? []);

        if (count($dates) >= 2) {
            $from = $this->parseDate($dates[0]);
            $to = $this->parseDate($dates[1]);

            if ($from && $to) {
                return [
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                ];
            }
        }

        if (preg_match('/\b(' . $monthNames . ')\s+(\d{4})\b/i', $normalized, $monthMatch)) {
            $month = $this->parseDate($monthMatch[0]);

            if ($month) {
                return [
                    'from' => $month->copy()->startOfMonth()->toDateString(),
                    'to' => $month->copy()->endOfMonth()->toDateString(),
                ];
            }
        }

        $lower = mb_strtolower($normalized);

        if (str_contains($lower, 'this month') || str_contains($lower, 'current month')) {
            return [
                'from' => now()->startOfMonth()->toDateString(),
                'to' => now()->endOfMonth()->toDateString(),
            ];
        }

        if (str_contains($lower, 'last month')) {
            return [
                'from' => now()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                'to' => now()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ];
        }

        return null;
    }

    private function parseDate(string $date): ?Carbon
    {
        try {
            return Carbon::parse(trim($date));
        } catch (\Throwable) {
            return null;
        }
    }

    private function extractStatus(string $question): ?string
    {
        $statuses = [
            'in-progress' => 'In-Progress',
            'in progress' => 'In-Progress',
            'completed' => 'Completed',
            'complete' => 'Completed',
            'cancelled' => 'Cancelled',
            'canceled' => 'Cancelled',
            'hold' => 'Hold',
            'pending' => 'Pending',
            'resolved' => 'Resolved',
        ];

        foreach ($statuses as $needle => $status) {
            if (str_contains($question, $needle)) {
                return $status;
            }
        }

        return null;
    }

    private function excludesArchivedDepartment(string $question): bool
    {
        return str_contains($question, 'archived')
            && (
                str_contains($question, 'mat')
                || str_contains($question, 'not')
                || str_contains($question, 'exclude')
                || str_contains($question, 'without')
                || str_contains($question, 'chor')
                || str_contains($question, 'chhor')
            );
    }

    private function sensitiveAllowedColumns(string $table): array
    {
        return array_values(array_filter(
            $this->aiSchemaService->getAllowedColumns($table),
            fn (string $column) => $this->aiSchemaService->isSensitiveColumn($table, $column)
        ));
    }

    private function isWriteOperationQuestion(string $question): bool
    {
        return preg_match('/\b(insert|update|delete|drop|alter|create|truncate|restore|approve|move|change|edit|remove)\b/i', $question) === 1;
    }

    private function syntheticOpenAiResponse(): array
    {
        return [
            'id' => null,
            'model' => config('services.openai.model', 'gpt-4.1-mini'),
            'usage' => [],
            'payload' => [],
            'raw' => [],
        ];
    }

    private function userRole(User $user): string
    {
        if (method_exists($user, 'getRoleNames')) {
            return $user->getRoleNames()->first() ?? 'User';
        }

        return 'User';
    }

    private function unknownPlan(): array
    {
        return [
            'answer_type' => 'text',
            'intent' => 'unknown',
            'tables' => [],
            'columns' => [],
            'group_by' => [],
            'filters' => [],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => null,
            'mode' => 'unsupported',
            'confidence' => 0,
            'entities' => [],
            'selected_columns' => [],
            'relationships' => [],
            'sort' => [],
            'limit' => 100,
            'needs_clarification' => false,
            'clarification_question' => null,
        ];
    }

    private function permissionDeniedPlan(): array
    {
        return [
            'answer_type' => 'text',
            'intent' => 'unknown',
            'tables' => [],
            'columns' => [],
            'group_by' => [],
            'filters' => [],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => 'You do not have permission to access this information.',
            'mode' => 'unsupported',
            'confidence' => 1,
            'entities' => [],
            'selected_columns' => [],
            'relationships' => [],
            'sort' => [],
            'limit' => 100,
            'needs_clarification' => false,
            'clarification_question' => null,
        ];
    }
}
