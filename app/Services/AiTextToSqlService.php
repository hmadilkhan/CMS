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
        private readonly AiSqlParserService   $aiSqlParserService,
        private readonly AiRowScopeService    $aiRowScopeService
    ) {}

    /**
     * Placeholder the model must place in the WHERE clause for scoped users.
     * Replaced server-side with a trusted `projects.id IN (...)` predicate so the
     * model can never widen a scoped user's row visibility.
     */
    private const SCOPE_TOKEN = '__PROJECT_ACCESS_SCOPE__';

    /**
     * Generate SQL for the given question and validate it is safe to run.
     *
     * Returns:
     *   ['success' => true,  'sql' => '...', 'bindings' => [], 'tables' => [...], 'limit' => N]
     *   ['success' => false, 'error' => '...']
     */
    public function generate(string $question, User $user): array
    {
        return $this->run($question, $user, null);
    }

    /**
     * Re-generate SQL after a previous attempt failed at execution time.
     *
     * The failed SQL and the real database error are fed back to the model so it
     * can correct itself (wrong column/table name, bad join, type mismatch, etc.).
     * This single retry recovers the large majority of "query failed" cases.
     */
    public function regenerate(string $question, User $user, string $failedSql, string $dbError): array
    {
        return $this->run($question, $user, [
            'failed_sql' => $failedSql,
            'db_error'   => $dbError,
        ]);
    }

    /**
     * @param  array{failed_sql:string,db_error:string}|null  $correction
     */
    private function run(string $question, User $user, ?array $correction): array
    {
        try {
            $allowedSchema = $this->buildAllowedSchemaForUser($user);

            if (empty($allowedSchema)) {
                return $this->fail('No accessible tables found for your role.');
            }

            // Row-level project scope (null when the user is unscoped/admin/finance).
            $projectScope = $this->aiRowScopeService->projectScopeSql($user);
            $isScoped     = $projectScope !== null;

            $response = $this->openAiService->createJsonResponse(
                $this->buildInstructions($allowedSchema, $user, $isScoped),
                $this->buildInput($question, $correction, $isScoped),
                900,
                $this->jsonSchema(),
                $this->openAiService->sqlModel()
            );

            $result = $response['json'] ?? [];
            $rawSql = trim((string) ($result['sql'] ?? ''));
            $limit  = max(1, min((int) ($result['limit'] ?? 50), 100));
            $tables = array_values(array_filter((array) ($result['tables'] ?? [])));

            if ($rawSql === '') {
                return $this->fail('AI did not return a SQL query.');
            }

            // SECURITY: for scoped users the model MUST place the access-scope
            // token. If it didn't, refuse the query (the caller then falls back to
            // the structured pipeline, which applies scope itself). This guarantees
            // a scoped user's Text-to-SQL result can never exceed their row access.
            if ($isScoped && ! str_contains($rawSql, self::SCOPE_TOKEN)) {
                return $this->fail('Generated query is missing the required row-access scope.');
            }

            // Replace current-user placeholders with real values
            $sql = $this->replacePlaceholders($rawSql, $user);

            // Splice in the trusted scope predicate (or neutralise a stray token).
            $sql = str_replace(
                self::SCOPE_TOKEN,
                $isScoped ? '(' . $projectScope . ')' : '1=1',
                $sql
            );

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

            // SECURITY: a scoped user's query must actually be constrained by the
            // projects table; otherwise the scope predicate cannot bind. Refuse and
            // fall back to the structured pipeline for non-project questions.
            if ($isScoped && ! in_array('projects', $referencedTables, true)) {
                return $this->fail('Scoped Text-to-SQL is limited to project-related questions.');
            }

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

    private function buildInstructions(array $allowedSchema, User $user, bool $scoped = false): string
    {
        $roleNames   = $user->roles->pluck('name')->join(', ');
        $schemaJson  = json_encode($allowedSchema, JSON_PRETTY_PRINT);
        $today       = now()->toDateString();
        $scopeRules  = $scoped ? $this->scopeRules() : '';
        $scopeNote   = $scoped
            ? "\n- Today's date and row-access scope rules in the ROW ACCESS section are MANDATORY."
            : '';

        return <<<PROMPT
You are a safe SQL generator for a solar-installation company CRM (MySQL).

## Rules
- Return a JSON object only. No markdown, no explanation.
- Generate a single SELECT query only. No INSERT/UPDATE/DELETE/DROP/ALTER/UNION.
- Only use tables and columns listed in the ALLOWED SCHEMA below. Never invent a table or column name — if a needed column is not listed, pick the closest allowed one or omit it.
- Include LIMIT N (max 100). Default to LIMIT 50.
- Write literal values inline in the SQL (they are validated before execution).
- For "current user" filters use placeholder __CURRENT_USER_ID__ (will be replaced).
- For "current employee" filters use placeholder __CURRENT_EMPLOYEE_ID__ (will be replaced).
- Apply appropriate WHERE clauses based on the user's role restrictions below.
- Do not use subqueries in FROM clause. Do not use UNION. Do not use SQL comments.
- Always add explicit JOIN ... ON conditions using the relationships listed in the schema. Never rely on implicit/comma joins.
- Use the "relationships" entry on each table to choose correct join keys.
- For name searches prefer LIKE '%term%' (case-insensitive) rather than exact '=' so partial names match.
- When the question implies counting/grouping ("how many", "count", "X wise", "per X"), use COUNT(...) with GROUP BY and give aggregates a clear alias.
- Resolve relative dates against today ({$today}). e.g. "this month", "last 30 days", "in April 2026".
- Read the FULL question: apply every filter the user mentions (location, status, date range, person, etc.). Do not return a generic list when the user asked for a filtered/aggregated result.{$scopeNote}
{$scopeRules}
## Current User
- Name: {$user->name}
- Role(s): {$roleNames}
- Today's date: {$today}

## Allowed Schema (tables → allowed columns, access rule, relationships)
{$schemaJson}

## Output JSON shape
{
  "sql": "SELECT ... FROM ... WHERE ... LIMIT N",
  "tables": ["table1", "table2"],
  "limit": N
}
PROMPT;
    }

    /**
     * Mandatory row-access instructions for scoped (non-admin/non-finance) users.
     * The model must constrain every query to the projects table and place the
     * scope token, which the server replaces with the user's allowed project IDs.
     */
    private function scopeRules(): string
    {
        $token = self::SCOPE_TOKEN;

        return <<<PROMPT

## ROW ACCESS (MANDATORY — this user only sees their permitted projects)
- Every query MUST be project-centric: include the `projects` table in FROM or a JOIN, referenced as `projects` (do NOT alias it).
- You MUST add the exact token {$token} to the WHERE clause, combined with AND. It is a server-managed predicate that limits results to the projects this user may access. Do not modify it, quote it, or wrap it.
- Example: SELECT departments.name, count(distinct projects.id) as total FROM projects LEFT JOIN departments ON projects.department_id = departments.id WHERE {$token} GROUP BY departments.name LIMIT 50
- If the question cannot be answered using the projects table, return an empty string for "sql".

PROMPT;
    }

    /**
     * Build the user-input payload for the model, optionally including a
     * self-correction block when retrying after a database error.
     *
     * @param  array{failed_sql:string,db_error:string}|null  $correction
     */
    private function buildInput(string $question, ?array $correction, bool $scoped = false): string
    {
        $scopeReminder = $scoped
            ? "\n\nReminder: keep the query project-centric and keep the " . self::SCOPE_TOKEN . ' token in the WHERE clause.'
            : '';

        if ($correction === null) {
            return $question . $scopeReminder;
        }

        return implode("\n", [
            'Your previous SQL for this question failed when executed. Fix it and return corrected JSON.',
            '',
            'Question: ' . $question,
            '',
            'Previous SQL:',
            $correction['failed_sql'],
            '',
            'Database error:',
            $correction['db_error'],
            '',
            'Common causes: a column or table name that does not exist in the allowed schema, an ambiguous column needing a table prefix, a wrong join key, or a type mismatch. Re-check the allowed schema and relationships, then return a corrected single SELECT query.' . $scopeReminder,
        ]);
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
        $employeeId = DB::table('employees')
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
