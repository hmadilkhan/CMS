<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Arr;

class AiQueryPlannerService
{
    private const ALLOWED_INTENTS = [
        'project_count',
        'project_customer',
        'ticket_status',
        'finance_summary',
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
        private readonly AiSchemaService $aiSchemaService
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
                            'columns' => ['id', 'sales_partner_user_id', 'sub_contractor_user_id'],
                            'filters' => [
                                [
                                    'column' => 'sales_partner_user_id',
                                    'operator' => '=',
                                    'value' => 'current_user.id',
                                ],
                            ],
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

        return [
            'plan' => $this->sanitizePlan($response['json']),
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
            'assigned',
            'mere',
            'mery',
            'meri',
            'kitne',
            'kitni',
            'count',
            'status',
            'finance',
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
  "intent": "project_count|project_customer|ticket_status|finance_summary|unknown",
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

    private function sanitizePlan(array $plan): array
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

        $safe['columns'] = array_values(array_filter((array) $safe['columns'], function ($column) use ($safe) {
            foreach ($safe['tables'] as $table) {
                if ($this->aiSchemaService->isColumnAllowed($table, $column)) {
                    return true;
                }
            }

            return false;
        }));

        $safe['filters'] = is_array($safe['filters']) ? array_values(array_filter($safe['filters'], function ($filter) use ($safe) {
            if (! is_array($filter) || ! isset($filter['column'], $filter['operator'])) {
                return false;
            }

            foreach ($safe['tables'] as $table) {
                if ($this->aiSchemaService->isColumnAllowed($table, $filter['column'])) {
                    return true;
                }
            }

            return false;
        })) : [];
        $safe['requires_finance_access'] = (bool) $safe['requires_finance_access'];
        $safe['sql'] = null;

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
}
