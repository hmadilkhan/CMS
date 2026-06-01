<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Generates safe, validated SQL from a natural-language question.
 *
 * Used as a last-resort fallback when the structured query planner
 * cannot produce a valid plan or the SQL builder cannot handle a
 * complex query pattern. The AI is constrained to the allowed schema
 * and the user's role-based access rules.
 */
class AiTextToSqlService
{
    public function __construct(
        private readonly OpenAiService        $openAiService,
        private readonly AiSchemaService      $aiSchemaService,
        private readonly AiPermissionService  $aiPermissionService,
        private readonly AiSqlParserService   $aiSqlParserService
    ) {}

    /**
     * Generate SQL for the given question and validate it is safe to run.
     *
     * Returns:
     *   ['success' => true,  'sql' => '...', 'bindings' => [], 'tables' => [...], 'limit' => N]
     *   ['success' => false, 'error' => '...']
     */
    public function generate(string $question, User $user): array
    {
        try {
            $allowedSchema = $this->buildAllowedSchemaForUser($user);

            if (empty($allowedSchema)) {
                return $this->fail('No accessible tables found for your role.');
            }

            $response = $this->openAiService->createJsonResponse(
                $this->buildInstructions($allowedSchema, $user),
                $question,
                900,
                $this->jsonSchema()
            );

            $result = $response['json'] ?? [];
            $rawSql = trim((string) ($result['sql'] ?? ''));
            $limit  = max(1, min((int) ($result['limit'] ?? 50), 100));
            $tables = array_values(array_filter((array) ($result['tables'] ?? [])));

            if ($rawSql === '') {
                return $this->fail('AI did not return a SQL query.');
            }

            // Replace current-user placeholders with real values
            $sql = $this->replacePlaceholders($rawSql, $user);

            // Ensure LIMIT is present and matches
            $sql = $this->ensureLimit($sql, $limit);

            // --- Safety validation ---
            $parseResult = $this->aiSqlParserService->validate($sql);
            if (! ($parseResult['valid'] ?? false)) {
                return $this->fail($parseResult['error'] ?? 'SQL validation failed.');
            }

            // Additional checks not in the parser (mirrors AiSqlValidatorService)
            if (preg_match('/\bfrom\s*\(\s*select\b/i', $sql)) {
                return $this->fail('Subqueries in FROM clause are not allowed.');
            }
            if (preg_match('/\bunion\b/i', $sql)) {
                return $this->fail('UNION queries are not allowed.');
            }

            // Verify every referenced table is allowed
            $referencedTables = $parseResult['tables'] ?? $tables;
            foreach ($referencedTables as $table) {
                if (! $this->aiSchemaService->isTableAllowed($table)) {
                    return $this->fail("Table \"{$table}\" is not in the allowed schema.");
                }
                if (! $this->aiPermissionService->canAccessTable($user, $table)) {
                    return $this->fail("You do not have permission to access \"{$table}\".");
                }
            }

            // Finance / profitability guard
            $requiresFinance = collect($referencedTables)->contains(
                fn ($t) => in_array(
                    $this->aiSchemaService->getAccessRule($t),
                    ['finance_access', 'profitability_access'],
                    true
                )
            );

            if ($requiresFinance && ! $this->aiPermissionService->canAccessFinance($user)) {
                return $this->fail('You do not have permission to access financial data.');
            }

            return [
                'success'               => true,
                'sql'                   => $sql,
                'bindings'              => [],
                'tables'                => $referencedTables,
                'limit'                 => $limit,
                'requires_finance_access' => $requiresFinance,
                'error'                 => null,
            ];
        } catch (Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Prompt construction
    // -------------------------------------------------------------------------

    private function buildInstructions(array $allowedSchema, User $user): string
    {
        $roleNames   = $user->roles->pluck('name')->join(', ');
        $schemaJson  = json_encode($allowedSchema, JSON_PRETTY_PRINT);

        return <<<PROMPT
You are a safe SQL generator for a solar-installation company CRM (MySQL).

## Rules
- Return a JSON object only. No markdown, no explanation.
- Generate a single SELECT query only. No INSERT/UPDATE/DELETE/DROP/ALTER/UNION.
- Only use tables and columns listed in the ALLOWED SCHEMA below.
- Include LIMIT N (max 100). Default to LIMIT 50.
- Use parameterized style: write actual values inline (they will be validated).
- For "current user" filters use placeholder __CURRENT_USER_ID__ (will be replaced).
- For "current employee" filters use placeholder __CURRENT_EMPLOYEE_ID__ (will be replaced).
- Apply appropriate WHERE clauses based on the user's role restrictions below.
- Do not use subqueries in FROM clause. Do not use UNION.
- Add table aliases and explicit JOIN ... ON conditions.

## Current User
- Name: {$user->name}
- Role(s): {$roleNames}

## Allowed Schema (tables → allowed columns)
{$schemaJson}

## Output JSON shape
{
  "sql": "SELECT ... FROM ... WHERE ... LIMIT N",
  "tables": ["table1", "table2"],
  "limit": N
}
PROMPT;
    }

    private function jsonSchema(): array
    {
        return [
            'type'   => 'json_schema',
            'name'   => 'text_to_sql_result',
            'strict' => true,
            'schema' => [
                'type'                 => 'object',
                'additionalProperties' => false,
                'required'             => ['sql', 'tables', 'limit'],
                'properties'           => [
                    'sql'    => ['type' => 'string'],
                    'tables' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'limit'  => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100],
                ],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function buildAllowedSchemaForUser(User $user): array
    {
        $schema = [];

        foreach ($this->aiSchemaService->getAllowedTables() as $table) {
            if (! $this->aiPermissionService->canAccessTable($user, $table)) {
                continue;
            }

            $columns = $this->aiSchemaService->getAllowedColumns($table);

            // Strip sensitive columns the user cannot see
            $columns = array_values(array_filter(
                $columns,
                fn ($col) => $this->aiPermissionService->canAccessColumn($user, $table, $col)
            ));

            if (! empty($columns)) {
                $schema[$table] = [
                    'columns'       => $columns,
                    'access_rule'   => $this->aiSchemaService->getAccessRule($table),
                    'relationships' => $this->aiSchemaService->getRelationships($table),
                ];
            }
        }

        return $schema;
    }

    private function replacePlaceholders(string $sql, User $user): string
    {
        $employeeId = \DB::table('employees')
            ->where('user_id', $user->id)
            ->value('id') ?? 0;

        return str_replace(
            ['__CURRENT_USER_ID__', '__CURRENT_EMPLOYEE_ID__'],
            [(string) $user->id, (string) $employeeId],
            $sql
        );
    }

    private function ensureLimit(string $sql, int $limit): string
    {
        // If AI already included a LIMIT clause, normalize it to $limit
        $sql = preg_replace('/\bLIMIT\s+\d+/i', "LIMIT {$limit}", $sql);

        // If no LIMIT clause, append one
        if (! preg_match('/\bLIMIT\s+\d+/i', $sql)) {
            $sql = rtrim($sql, '; ') . " LIMIT {$limit}";
        }

        return $sql;
    }

    private function fail(string $error): array
    {
        return [
            'success' => false,
            'sql'     => null,
            'error'   => $error,
        ];
    }
}
