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
        'finance_summary',
        'profitability_report',
        'customer_revenue',
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
                            'filters' => [],
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
                            'filters' => [],
                            'requires_finance_access' => true,
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
        $inferredPlan = $this->inferKnownPlan($question);

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
  "intent": "project_count|project_status_summary|project_department_summary|project_customer|ticket_status|finance_summary|profitability_report|customer_revenue|unknown",
  "tables": [],
  "columns": [],
  "filters": [],
  "requires_finance_access": false,
  "sql": null,
  "fallback_message": null
}

For filters, use simple objects with "column", "operator", and "value".
For current user filters, use "current_user.id" as the value.
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
        $mentionsDepartment = str_contains($normalized, 'department') || str_contains($normalized, 'subdepartment') || str_contains($normalized, 'sub department');
        $mentionsCount = str_contains($normalized, 'count') || str_contains($normalized, 'kitne') || str_contains($normalized, 'kitni') || str_contains($normalized, 'wise');

        if ($mentionsProject && $mentionsDepartment && $mentionsCount && $departmentName = $this->extractDepartmentName($normalized)) {
            return [
                'answer_type' => 'count',
                'intent' => 'project_count',
                'tables' => ['projects', 'departments'],
                'columns' => ['id', 'name'],
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
            return [
                'answer_type' => 'table',
                'intent' => 'project_department_summary',
                'tables' => ['projects', 'departments', 'sub_departments'],
                'columns' => ['department_id', 'sub_department_id', 'name'],
                'filters' => [],
                'requires_finance_access' => false,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        return null;
    }

    private function inferredPlanIsMoreSpecific(array $plan, array $inferredPlan): bool
    {
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

    private function sensitiveAllowedColumns(string $table): array
    {
        return array_values(array_filter(
            $this->aiSchemaService->getAllowedColumns($table),
            fn (string $column) => $this->aiSchemaService->isSensitiveColumn($table, $column)
        ));
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
            'filters' => [],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => 'You do not have permission to access this information.',
        ];
    }
}
