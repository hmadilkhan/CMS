<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Arr;

class AiQueryPlannerService
{
    private const ALLOWED_INTENTS = [
        'project_count',
        'project_status_summary',
        'project_department_summary',
        'project_customer',
        'ticket_status',
        'ticket_creator_status_summary',
        'finance_summary',
        'profitability_report',
        'customer_revenue',
        'crm_count',
        'crm_list',
        'crm_group_summary',
        'unknown',
    ];

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
                'plan' => $this->sanitizePlan($inferredPlan, $user),
                'openai' => $this->syntheticOpenAiResponse(),
            ];
        }

        $response = $this->openAiService->createJsonResponse(
            $this->instructions(),
            [
                'question' => $question,
                'user_role' => $this->userRole($user),
                'allowed_schema' => $this->schemaForPlanner(),
                'required_json_format' => $this->unknownPlan(),
                'examples' => [
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
                            'tables' => ['profitability_reports', 'projects'],
                            'columns' => ['project_id', 'total_revenue', 'total_expense', 'gross_profit', 'margin_percent', 'report_date'],
                            'group_by' => [],
                            'filters' => [],
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
                ],
            ],
            1200,
            $this->jsonSchema()
        );

        $plan = $this->sanitizePlan($response['json'], $user);

        if ($inferredPlan && (($plan['intent'] ?? 'unknown') === 'unknown' || $this->inferredPlanIsMoreSpecific($plan, $inferredPlan))) {
            $plan = $this->sanitizePlan($inferredPlan, $user);
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
            'amount',
            'cost',
            'profit',
            'revenue',
            'commission',
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
Do not generate SQL. The "sql" field must always be null.
Use only the supplied allowed_schema tables and allowed columns.
If the question cannot be answered from allowed_schema, return intent "unknown".
If the question needs finance, payment, cost, revenue, amount, commission, margin, profit, or profitability data, set requires_finance_access to true.
If any requested table or column is missing from allowed_schema, do not invent it; return intent "unknown".

The only valid output shape is:
{
  "answer_type": "text|table|card|count",
  "intent": "project_count|project_status_summary|project_department_summary|project_customer|ticket_status|ticket_creator_status_summary|finance_summary|profitability_report|customer_revenue|crm_count|crm_list|crm_group_summary|unknown",
  "tables": [],
  "columns": [],
  "group_by": [],
  "filters": [],
  "requires_finance_access": false,
  "sql": null,
  "fallback_message": null
}

For filters, use simple objects with "column", "operator", and "value".
For current user filters, use "current_user.id" as the value.
If the question can be answered using allowed_schema but does not match a specific intent, use:
- crm_count for counts on one allowed table
- crm_list for simple result lists
- crm_group_summary for grouped count summaries
Never include sensitive columns such as password, remember_token, api_token, token, secret, salary, cost, profit, margin, revenue, payment, amount, expense, or commission unless they are explicitly allowed in allowed_schema and the user role requires finance access.
Reject write requests such as insert, update, delete, create, drop, alter, truncate, restore, approve, move, assign, or change by returning unknown.
PROMPT;
    }

    private function jsonSchema(): array
    {
        return [
            'type' => 'json_schema',
            'name' => 'ai_query_plan',
            'strict' => true,
            'schema' => [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => [
                    'answer_type',
                    'intent',
                    'tables',
                    'columns',
                    'group_by',
                    'filters',
                    'requires_finance_access',
                    'sql',
                    'fallback_message',
                ],
                'properties' => [
                    'answer_type' => [
                        'type' => 'string',
                        'enum' => self::ALLOWED_ANSWER_TYPES,
                    ],
                    'intent' => [
                        'type' => 'string',
                        'enum' => self::ALLOWED_INTENTS,
                    ],
                    'tables' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'columns' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'group_by' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'filters' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'required' => ['column', 'operator', 'value'],
                            'properties' => [
                                'column' => ['type' => 'string'],
                                'operator' => ['type' => 'string'],
                                'value' => ['type' => ['string', 'number', 'boolean', 'null']],
                            ],
                        ],
                    ],
                    'requires_finance_access' => [
                        'type' => 'boolean',
                    ],
                    'sql' => [
                        'type' => 'null',
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
            'answer_type',
            'intent',
            'tables',
            'columns',
            'group_by',
            'filters',
            'requires_finance_access',
            'sql',
            'fallback_message',
        ]));

        if (! in_array($safe['answer_type'], self::ALLOWED_ANSWER_TYPES, true)) {
            $safe['answer_type'] = 'text';
        }

        if (! in_array($safe['intent'], self::ALLOWED_INTENTS, true)) {
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
            $safe['fallback_message'] = $safe['fallback_message'] ?: 'I can plan CRM data queries only for allowed CRM tables and columns. I could not map this question safely yet.';
        }

        return $safe;
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

    private function inferKnownPlan(string $question): ?array
    {
        $normalized = mb_strtolower($question);
        $mentionsProject = str_contains($normalized, 'project');
        $mentionsTicket = str_contains($normalized, 'ticket');
        $mentionsDepartment = str_contains($normalized, 'department') || str_contains($normalized, 'subdepartment') || str_contains($normalized, 'sub department');
        $mentionsCount = str_contains($normalized, 'count') || str_contains($normalized, 'kitne') || str_contains($normalized, 'kitni') || str_contains($normalized, 'wise');
        $mentionsUser = str_contains($normalized, 'user') || str_contains($normalized, 'users') || str_contains($normalized, 'created by') || str_contains($normalized, 'creator');
        $mentionsStatus = str_contains($normalized, 'status') || str_contains($normalized, 'pending') || str_contains($normalized, 'resolved');
        $status = $this->extractStatus($normalized);
        $assignedEmployeeName = $this->extractAssignedEmployeeName($normalized);

        if ($mentionsTicket && $mentionsUser && ($mentionsStatus || $mentionsCount)) {
            return [
                'answer_type' => 'table',
                'intent' => 'ticket_creator_status_summary',
                'tables' => ['service_tickets', 'users'],
                'columns' => ['user_id', 'name', 'status'],
                'group_by' => ['name', 'status'],
                'filters' => [],
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
                            'column' => 'name',
                            'operator' => 'not_like',
                            'value' => 'archived',
                        ]
                        : null,
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

        return $name !== '' ? $name : null;
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
        ];
    }
}
