<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class AiDynamicSqlBuilderService
{
    public function __construct(
        private readonly OpenAiService $openAiService,
        private readonly AiSchemaService $aiSchemaService,
        private readonly AiPermissionService $aiPermissionService
    ) {
    }

    public function buildFromPlan(array $plan, User $user): array
    {
        // If plan has specific intent with hardcoded logic, use traditional builder
        $hardcodedIntents = [
            'project_status_summary',
            'project_department_summary',
            'ticket_creator_status_summary',
            'project_customer',
            'finance_summary',
            'profitability_report',
            'customer_revenue',
        ];

        if (in_array($plan['intent'] ?? '', $hardcodedIntents, true)) {
            return ['use_traditional_builder' => true];
        }

        // For flexible queries, let AI generate SQL
        try {
            $schema = $this->buildSchemaContext($plan['tables'] ?? [], $user);
            $sqlGeneration = $this->generateSql($plan, $schema, $user);

            return [
                'use_traditional_builder' => false,
                'sql' => $sqlGeneration['sql'],
                'bindings' => $sqlGeneration['bindings'] ?? [],
                'tables' => $plan['tables'] ?? [],
                'columns' => $plan['columns'] ?? [],
                'limit' => 100,
                'ai_generated' => true,
                'explanation' => $sqlGeneration['explanation'] ?? '',
            ];
        } catch (\Throwable $e) {
            Log::warning('AI SQL generation failed, falling back to traditional builder', [
                'error' => $e->getMessage(),
                'plan' => $plan,
            ]);

            return ['use_traditional_builder' => true];
        }
    }

    private function generateSql(array $plan, array $schema, User $user): array
    {
        $response = $this->openAiService->createJsonResponse(
            instructions: $this->sqlGenerationInstructions($user),
            input: [
                'question' => $plan['original_question'] ?? 'Generate query based on plan',
                'plan' => $plan,
                'schema' => $schema,
                'user_role' => $this->getUserRole($user),
                'user_id' => $user->id,
            ],
            maxOutputTokens: 1000,
            jsonSchema: [
                'type' => 'json_schema',
                'name' => 'sql_generation',
                'strict' => true,
                'schema' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['sql', 'bindings', 'explanation'],
                    'properties' => [
                        'sql' => [
                            'type' => 'string',
                            'description' => 'Complete SELECT query with ? placeholders for bindings',
                        ],
                        'bindings' => [
                            'type' => 'array',
                            'items' => [
                                'type' => ['string', 'number', 'boolean', 'null'],
                            ],
                            'description' => 'Array of values for ? placeholders in order',
                        ],
                        'explanation' => [
                            'type' => 'string',
                            'description' => 'Brief explanation of what the query does',
                        ],
                    ],
                ],
            ]
        );

        return $response['json'];
    }

    private function sqlGenerationInstructions(User $user): string
    {
        $role = $this->getUserRole($user);

        return <<<PROMPT
You are a SQL expert for a Laravel CRM system. Generate a SAFE, READ-ONLY SELECT query.

CRITICAL SECURITY RULES:
1. ONLY generate SELECT queries - NEVER INSERT, UPDATE, DELETE, DROP, ALTER, CREATE, TRUNCATE
2. ALWAYS include "LIMIT 100" at the end
3. Use LEFT JOIN for relationships (never INNER JOIN unless explicitly needed)
4. Use ? placeholders for ALL values (parameterized queries)
5. NEVER use wildcards (*) - list specific columns
6. NEVER include SQL comments (-- or /* */)
7. Table and column names must match the provided schema EXACTLY
8. Apply user role filters based on user_role

USER ROLE: {$role}
USER ID: {$user->id}

IMPORTANT - CURRENT USER FILTERS:
When user asks "my projects", "assigned to me", "my tickets", etc:

For PROJECTS:
- If role is "Employee": Filter by tasks.employee_id via employees table where user_id = {$user->id}
- If role is "Sales Person": Filter by projects.sales_partner_user_id = {$user->id}
- If role is "Sub-Contractor User": Filter by projects.sub_contractor_user_id = {$user->id}
- If role is "Manager": Filter by department_id from user's departments
- If role is "Super Admin" or "Admin": No filter needed

For TICKETS:
- Filter by: service_tickets.user_id = {$user->id} OR service_tickets.assigned_to = {$user->id}

For TASKS:
- If role is "Employee": Filter by tasks.employee_id via employees table where user_id = {$user->id}
- If role is "Manager": Filter by tasks.department_id from user's departments

EXAMPLE QUERIES:

1. "How many projects assigned to me?" (Employee role)
SELECT COUNT(*) as aggregate
FROM projects
LEFT JOIN tasks ON tasks.project_id = projects.id
LEFT JOIN employees ON tasks.employee_id = employees.id
WHERE employees.user_id = ?
AND projects.deleted_at IS NULL
LIMIT 100
Bindings: [{$user->id}]

2. "My tickets" (Any role)
SELECT service_tickets.*
FROM service_tickets
WHERE (service_tickets.user_id = ? OR service_tickets.assigned_to = ?)
AND service_tickets.deleted_at IS NULL
LIMIT 100
Bindings: [{$user->id}, {$user->id}]

3. "Projects assigned to me" (Sales Person)
SELECT projects.*
FROM projects
WHERE projects.sales_partner_user_id = ?
AND projects.deleted_at IS NULL
LIMIT 100
Bindings: [{$user->id}]

QUERY STRUCTURE:
SELECT [specific columns or COUNT(*) as aggregate]
FROM [base_table]
LEFT JOIN [related_table] ON [join_condition]
WHERE [user_filters] AND [other_filters] AND [deleted_at IS NULL]
GROUP BY [if needed]
ORDER BY [if needed]
LIMIT 100

BINDINGS ARRAY:
Return all ? placeholder values in order as an array.
Example: If query has "WHERE status = ? AND user_id = ?", bindings should be ["Pending", {$user->id}]

Return valid JSON only.
PROMPT;
    }

    private function buildSchemaContext(array $tables, User $user): array
    {
        $schema = [];

        foreach ($tables as $table) {
            if (!$this->aiPermissionService->canAccessTable($user, $table)) {
                continue;
            }

            $allowedColumns = array_filter(
                $this->aiSchemaService->getAllowedColumns($table),
                fn($column) => $this->aiPermissionService->canAccessColumn($user, $table, $column)
            );

            $schema[$table] = [
                'columns' => array_values($allowedColumns),
                'relationships' => $this->aiSchemaService->getRelationships($table),
                'searchable_columns' => $this->aiSchemaService->getSearchableColumns($table),
            ];
        }

        return $schema;
    }

    private function getUserRole(User $user): string
    {
        if ($user->hasAnyRole(['Super Admin', 'Admin'])) {
            return 'Super Admin';
        }

        if ($user->hasRole('Manager')) {
            return 'Manager';
        }

        if ($user->hasRole('Employee')) {
            return 'Employee';
        }

        if ($user->hasRole('Sales Person')) {
            return 'Sales Person';
        }

        return 'User';
    }
}
