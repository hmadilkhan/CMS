<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        private readonly AiRowScopeService    $aiRowScopeService,
        private readonly AiProfiler           $profiler
    ) {}

    /**
     * Placeholder the model must place in the WHERE clause for scoped users.
     * Replaced server-side with a trusted `projects.id IN (...)` predicate so the
     * model can never widen a scoped user's row visibility.
     */
    private const SCOPE_TOKEN = '__PROJECT_ACCESS_SCOPE__';

    /**
     * Credential columns that must never appear in a generated query, even
     * unqualified (e.g. a bare `SELECT password`). These are never in any
     * table's allowed_columns, so the qualified-reference check below already
     * blocks `users.password`; this denylist is the backstop for an unqualified
     * reference that carries no table prefix.
     */
    private const SECRET_COLUMN_DENYLIST = [
        'password',
        'remember_token',
        'api_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'access_token',
        'refresh_token',
    ];

    /**
     * Generate SQL for the given question and validate it is safe to run.
     *
     * Returns:
     *   ['success' => true,  'sql' => '...', 'bindings' => [], 'tables' => [...], 'limit' => N]
     *   ['success' => false, 'error' => '...']
     */
    public function generate(string $question, User $user, string $conversationMemory = ''): array
    {
        $this->profiler->stage('text_to_sql');

        $cacheKey = $this->sqlCacheKey($question, $user, $conversationMemory);

        if ($cacheKey !== null) {
            $cached = Cache::get($cacheKey);

            if (is_array($cached) && ($cached['success'] ?? false)) {
                return $cached;
            }
        }

        $result = $this->run($question, $user, null, $conversationMemory);

        if ($cacheKey !== null && ($result['success'] ?? false)) {
            Cache::put($cacheKey, $result, (int) config('ai.text_to_sql.cache_ttl', 21600));
        }

        return $result;
    }

    /**
     * Cache key for generated SQL — only for UNSCOPED users (admin/finance),
     * whose SQL carries no row-scope predicate and is identical for the same
     * permission signature. Scoped users splice user-specific project IDs into the
     * SQL, so their SQL is never cached. Gated to empty conversation memory so a
     * follow-up's context can never be served a stale standalone query. Returns
     * null (no caching) when disabled, scoped, or in a follow-up.
     */
    private function sqlCacheKey(string $question, User $user, string $conversationMemory): ?string
    {
        if ((int) config('ai.text_to_sql.cache_ttl', 0) <= 0 || $conversationMemory !== '') {
            return null;
        }

        // Scoped users get inline, user-specific project IDs in their SQL — never cache.
        if ($this->aiRowScopeService->projectScopeSql($user) !== null) {
            return null;
        }

        $normalized = trim(preg_replace('/\s+/u', ' ', mb_strtolower($question)));
        $signature = implode(',', $user->getRoleNames()->sort()->values()->all())
            .'|fin:'.($this->aiPermissionService->canAccessFinance($user) ? 1 : 0)
            .'|prof:'.($this->aiPermissionService->canAccessProfitability($user) ? 1 : 0);

        return 'ai_tts_sql:'.md5($normalized.'||'.$signature);
    }

    /**
     * Re-generate SQL after a previous attempt failed at execution time.
     *
     * The failed SQL and the real database error are fed back to the model so it
     * can correct itself (wrong column/table name, bad join, type mismatch, etc.).
     * This single retry recovers the large majority of "query failed" cases.
     */
    public function regenerate(string $question, User $user, string $failedSql, string $dbError, string $conversationMemory = ''): array
    {
        $this->profiler->stage('text_to_sql_retry');

        return $this->run($question, $user, [
            'failed_sql' => $failedSql,
            'db_error'   => $dbError,
        ], $conversationMemory);
    }

    /**
     * @param  array{failed_sql:string,db_error:string}|null  $correction
     */
    private function run(string $question, User $user, ?array $correction, string $conversationMemory = ''): array
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
                $this->buildInput($question, $correction, $isScoped, $conversationMemory),
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

            // SECURITY: enforce the COLUMN allowlist. The structured engine validates
            // columns via AiSqlValidatorService; the Text-to-SQL path must do the same
            // so the model can never select a column outside the user's permitted set
            // — secrets (password/token), or finance/profitability columns a non-finance
            // user cannot see. On a violation we refuse and the caller falls back to the
            // structured pipeline.
            if ($columnError = $this->validateColumns($sql, $referencedTables, $user)) {
                return $this->fail($columnError);
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

            // GUARANTEE soft-delete filtering. The structured builder always adds
            // `deleted_at IS NULL`; an AI-written query often forgets it, which
            // leaks soft-deleted rows (e.g. a department "project count" of 4 but a
            // "project list" of 10). We enforce it server-side so the model can't.
            $sql = $this->enforceSoftDeletes($sql, $referencedTables);

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

        // Tables that use soft deletes — the model must exclude deleted rows.
        $softDeleteTables = collect($allowedSchema)
            ->filter(fn ($cfg) => in_array('deleted_at', (array) ($cfg['columns'] ?? []), true))
            ->keys()
            ->implode(', ');
        $softDeleteRule = $softDeleteTables !== ''
            ? "\n- SOFT DELETES: these tables hide deleted rows via deleted_at — ALWAYS add `<table>.deleted_at IS NULL` for each one you query: {$softDeleteTables}."
            : '';
        $scopeRules  = $scoped ? $this->scopeRules() : '';
        $scopeNote   = $scoped
            ? "\n- Today's date and row-access scope rules in the ROW ACCESS section are MANDATORY."
            : '';
        $deptGrounding = $this->domainGrounding();

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
- Do NOT alias the main/outer query tables — reference them by full name and qualify every column as `table_name.column` (e.g. `customers.name`, `tasks.status` — not `c.name` or `t.status`). This is required for the server-side row-access and soft-delete guards. You MAY alias a table ONLY inside a correlated subquery (e.g. `(SELECT MAX(lt.id) FROM tasks lt WHERE lt.project_id = projects.id)`).
- For name searches prefer LIKE '%term%' (case-insensitive) rather than exact '='. IMPORTANT: a stored project/customer name often contains hyphens, an address suffix, or different spacing than the user typed (the user writes "Yunjiao Guan 61st Ave" but the row is "Yunjiao-Guan - 61st Ave"). Do NOT match the user's whole phrase as one literal `LIKE '%whole phrase%'` — it misses on any punctuation/spacing difference. Instead split the name into its distinctive tokens and AND one LIKE per token, e.g. `projects.project_name LIKE '%Yunjiao%' AND projects.project_name LIKE '%61st%'`. Skip generic filler tokens (the, of, project, for, ave, st, street).
- When the question implies counting/grouping ("how many", "count", "X wise", "per X"), use COUNT(...) with GROUP BY and give aggregates a clear alias.
- Resolve relative dates against today ({$today}). e.g. "this month", "last 30 days", "in April 2026".
- Read the FULL question: apply every filter the user mentions (location, status, date range, person, etc.). Do not return a generic list when the user asked for a filtered/aggregated result.{$softDeleteRule}
- If a "Conversation so far" block is present, treat the new question as a follow-up: reuse the previous query's filters/joins to resolve references like "those", "them", "their", "in me se", and ADD any new constraint the user now asks for (e.g. "only the hold ones", "sort by install date", "just California"). Switch a count into a list (or vice-versa) when the user now asks for the other.{$scopeNote}
{$scopeRules}
## Current User
- Name: {$user->name}
- Role(s): {$roleNames}
- Today's date: {$today}

## Allowed Schema (tables → allowed columns, access rule, relationships)
{$schemaJson}
{$deptGrounding}

## Output JSON shape
{
  "sql": "SELECT ... FROM ... WHERE ... LIMIT N",
  "tables": ["table1", "table2"],
  "limit": N
}
PROMPT;
    }

    /**
     * Grounds the model with real values for the CRM's two most ambiguous query
     * terms so it stops guessing:
     *  - "department"/"lane" → departments.name vs sub_departments.name (e.g. it
     *    looked for the "Deal Review" DEPARTMENT inside sub_departments → 0).
     *  - "status" → tasks.status (In-Progress/Hold/…) and NOT the department/lane
     *    (e.g. "projects by status" was grouping by departments.name).
     * Cached because these reference tables are tiny and rarely change.
     */
    private function domainGrounding(): string
    {
        return Cache::remember('ai_tts_domain_grounding_v5', 1800, function () {
            $departments = $this->distinctColumn('departments', 'name');
            $subDepartments = $this->distinctColumn('sub_departments', 'name');
            $statuses = $this->distinctColumn('tasks', 'status');
            $financeOptions = $this->distinctColumn('finance_options', 'name');
            $adderTypes = $this->distinctColumn('adder_types', 'name');

            $lines = [];

            if ($departments->isNotEmpty() || $subDepartments->isNotEmpty()) {
                $lines[] = '';
                $lines[] = '## Department vs sub-department names (ground stage/lane filters against these)';
                $lines[] = "- A project's main workflow stage/\"lane\" is `projects.department_id` → `departments.name`; finer stages are `projects.sub_department_id` → `sub_departments.name`.";

                if ($departments->isNotEmpty()) {
                    $lines[] = '- departments.name values: '.$departments->implode(', ').'.';
                }

                if ($subDepartments->isNotEmpty()) {
                    $lines[] = '- sub_departments.name values: '.$subDepartments->implode(', ').'.';
                }

                $lines[] = '- When a question names a stage/lane (e.g. "Deal Review", "Permitting") without saying which table, match it against the lists above and filter whichever table actually contains that value. These lane names live in `departments` (join on `projects.department_id = departments.id`), NOT `sub_departments`, unless the value only appears in the sub-department list.';
                $lines[] = '- To LIST or COUNT projects in a given lane/stage, filter the project\'s own `projects.department_id` → `departments.name` directly. Do NOT join `tasks` for this — a project has MANY tasks, so joining them produces DUPLICATE project rows. Only join `tasks` when you actually need a work status, and then use `SELECT DISTINCT` / `COUNT(DISTINCT projects.id)`.';
            }

            if ($statuses->isNotEmpty()) {
                $lines[] = '';
                $lines[] = '## Project status (the WORK status — never the department/lane)';
                $lines[] = '- A project has no status column of its own. Its status is the status of its LATEST task: `tasks.status`, with values: '.$statuses->implode(', ').'.';
                $lines[] = "- A project has MANY tasks, so a plain JOIN to tasks OVER-COUNTS (one project lands in several status buckets and the totals exceed the real project count). Pin to exactly ONE (the latest) task per project: `JOIN tasks ON tasks.project_id = projects.id AND tasks.deleted_at IS NULL` AND in the WHERE add `tasks.id = (SELECT MAX(lt.id) FROM tasks lt WHERE lt.project_id = projects.id AND lt.deleted_at IS NULL)`.";
                $lines[] = '- NEVER treat a department/lane name as a status and NEVER alias a department as "status". For "projects by status": `SELECT tasks.status, COUNT(DISTINCT projects.id) AS project_count FROM projects JOIN tasks ON tasks.project_id = projects.id AND tasks.deleted_at IS NULL WHERE tasks.id = (SELECT MAX(lt.id) FROM tasks lt WHERE lt.project_id = projects.id AND lt.deleted_at IS NULL) AND projects.deleted_at IS NULL GROUP BY tasks.status` — the latest-task pin makes the counts sum to the real project total.';
            }

            if ($financeOptions->isNotEmpty()) {
                $lines[] = '';
                $lines[] = '## Financing method / finance option grouping';
                $lines[] = '- "Financing method", "finance option", "finance type", "financing type" all refer to `finance_options.name`.';
                $lines[] = '- finance_options.name values: '.$financeOptions->implode(', ').'.';
                $lines[] = '- Join path to reach the financing option from projects: `JOIN customers ON customers.id = projects.customer_id JOIN customer_finances ON customer_finances.customer_id = customers.id AND customer_finances.deleted_at IS NULL JOIN finance_options ON finance_options.id = customer_finances.finance_option_id AND finance_options.deleted_at IS NULL`.';
                $lines[] = '- For "projects per/by financing method" or "projects financing-method-wise": GROUP BY `finance_options.name` and COUNT(DISTINCT projects.id). Example: `SELECT finance_options.name AS financing_method, COUNT(DISTINCT projects.id) AS project_count FROM projects JOIN customers ON customers.id = projects.customer_id JOIN customer_finances ON customer_finances.customer_id = customers.id AND customer_finances.deleted_at IS NULL JOIN finance_options ON finance_options.id = customer_finances.finance_option_id AND finance_options.deleted_at IS NULL WHERE projects.deleted_at IS NULL GROUP BY finance_options.name ORDER BY project_count DESC LIMIT 100`.';
                $lines[] = '- A customer may have more than one customer_finances record; always use COUNT(DISTINCT projects.id) to avoid inflating counts.';
            }

            if ($adderTypes->isNotEmpty()) {
                $lines[] = '';
                $lines[] = '## Adders on a project / customer deal';
                $lines[] = "- A project's (or customer's) adders are the rows in `customer_adders` (the deal's chosen adders + their `amount`). Do NOT use the `adders` table for this — `adders` is the generic price CATALOGUE, not a specific deal's adders.";
                $lines[] = '- A project reaches its adders through the customer: `projects.customer_id = customers.id`, then `customer_adders.customer_id = customers.id`. The adders are NOT linked to projects directly.';
                $lines[] = '- Join path: `JOIN customers ON customers.id = projects.customer_id AND customers.deleted_at IS NULL JOIN customer_adders ON customer_adders.customer_id = customers.id AND customer_adders.deleted_at IS NULL LEFT JOIN adder_types ON adder_types.id = customer_adders.adder_type_id LEFT JOIN adder_sub_types ON adder_sub_types.id = customer_adders.adder_sub_type_id LEFT JOIN adder_units ON adder_units.id = customer_adders.adder_unit_id`.';
                $lines[] = '- Select readable names + amount: `adder_types.name`, `adder_sub_types.name`, `adder_units.name`, `customer_adders.amount`. adder_types.name values: '.$adderTypes->implode(', ').'.';
                $lines[] = "- Example \"adders of project X\": `SELECT adder_types.name AS adder_type, adder_sub_types.name AS sub_type, adder_units.name AS unit, customer_adders.amount FROM projects JOIN customers ON customers.id = projects.customer_id AND customers.deleted_at IS NULL JOIN customer_adders ON customer_adders.customer_id = customers.id AND customer_adders.deleted_at IS NULL LEFT JOIN adder_types ON adder_types.id = customer_adders.adder_type_id LEFT JOIN adder_sub_types ON adder_sub_types.id = customer_adders.adder_sub_type_id LEFT JOIN adder_units ON adder_units.id = customer_adders.adder_unit_id WHERE projects.project_name LIKE '%X%' AND projects.deleted_at IS NULL LIMIT 50`.";
            }

            return $lines === [] ? '' : implode("\n", $lines);
        });
    }

    /**
     * Distinct, non-deleted values of a column for a small reference table.
     * Defensive: skips the soft-delete filter when the column is absent and returns
     * an empty collection on any error, so grounding never breaks SQL generation.
     */
    private function distinctColumn(string $table, string $column): Collection
    {
        try {
            $query = DB::table($table);

            if (Schema::hasColumn($table, 'deleted_at')) {
                $query->whereNull('deleted_at');
            }

            return $query->whereNotNull($column)->orderBy($column)->pluck($column)->filter()->unique()->values();
        } catch (Throwable) {
            return collect();
        }
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
    private function buildInput(string $question, ?array $correction, bool $scoped = false, string $conversationMemory = ''): string
    {
        $scopeReminder = $scoped
            ? "\n\nReminder: keep the query project-centric and keep the " . self::SCOPE_TOKEN . ' token in the WHERE clause.'
            : '';

        // Prior turns let the model resolve follow-up references ("those", "them",
        // "inko", "their customers") and compound the previous query with any new
        // constraint the user now adds.
        $memoryBlock = $conversationMemory !== ''
            ? "Conversation so far (resolve references against this; keep previous filters unless the user changes them, and add any new constraint they ask for):\n{$conversationMemory}\n\n"
            : '';

        if ($correction === null) {
            return $memoryBlock . 'New question: ' . $question . $scopeReminder;
        }

        return $memoryBlock . implode("\n", [
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

    /**
     * Reject any column the user is not permitted to read, mirroring the
     * structured engine's column allowlist for the Text-to-SQL path.
     *
     *  1. Every qualified `table.column` reference whose table is one of the
     *     query's referenced allowed tables must pass canAccessColumn(). This
     *     blocks non-allowlisted columns AND finance/profitability-gated ones in
     *     a single check (`table.*` is ignored — it is not a column).
     *  2. A small credential denylist backstops an UNqualified secret column
     *     (e.g. a bare `SELECT password`) that carries no table prefix and so is
     *     not caught by step 1.
     *
     * Returns an error string on violation, or null when every column is allowed.
     * Aliased references (`c.email`) are intentionally not resolved here — the
     * prompt forbids aliases (so real table names are used), and the denylist
     * still catches aliased credentials.
     *
     * @param  array<int,string>  $tables
     */
    private function validateColumns(string $sql, array $tables, User $user): ?string
    {
        // Drop string literals so values (e.g. an address containing a column-like
        // word) are never mistaken for column references.
        $stripped = preg_replace("/'([^'\\\\]|\\\\.)*'/", "''", $sql) ?? $sql;

        // 1. Qualified column references: table.column (table.* is skipped because
        //    `*` is not matched by the identifier pattern).
        if (preg_match_all('/\b([a-zA-Z_][a-zA-Z0-9_]*)\s*\.\s*([a-zA-Z_][a-zA-Z0-9_]*)\b/', $stripped, $matches, PREG_SET_ORDER)) {
            foreach ($matches as [, $table, $column]) {
                // Only enforce against the query's real, allowed tables. Unknown
                // qualifiers (aliases, derived tables) fall to the denylist below.
                if (! in_array($table, $tables, true) || ! $this->aiSchemaService->isTableAllowed($table)) {
                    continue;
                }

                if (! $this->aiPermissionService->canAccessColumn($user, $table, $column)) {
                    return "Generated query references a column you cannot access: {$table}.{$column}.";
                }
            }
        }

        // 2. Credential backstop for unqualified secret columns.
        foreach (self::SECRET_COLUMN_DENYLIST as $secret) {
            if (preg_match('/\b' . preg_quote($secret, '/') . '\b/i', $stripped)) {
                return 'Generated query references a restricted column.';
            }
        }

        return null;
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

    /**
     * Inject `<table>.deleted_at IS NULL` for every referenced soft-delete table
     * the model did not already filter, so AI-written SQL can never return
     * soft-deleted rows. Inserts into the existing WHERE (or starts one) just
     * before any GROUP BY / HAVING / ORDER BY / LIMIT clause.
     *
     * @param array<int,string> $tables
     */
    private function enforceSoftDeletes(string $sql, array $tables): string
    {
        $needed = [];

        foreach (array_unique($tables) as $table) {
            // Only tables that actually have a soft-delete column.
            if (! in_array('deleted_at', $this->aiSchemaService->getAllowedColumns($table), true)) {
                continue;
            }

            // Respect a filter the model already wrote for this table.
            if (preg_match('/\b' . preg_quote($table, '/') . '\.deleted_at\b/i', $sql)) {
                continue;
            }

            $needed[] = $table . '.deleted_at is null';
        }

        if ($needed === []) {
            return $sql;
        }

        $predicate = implode(' and ', $needed);

        // Insert before the first trailing clause (GROUP BY/HAVING/ORDER BY/LIMIT).
        $boundary = strlen($sql);
        foreach (['group\s+by', 'having', 'order\s+by', 'limit'] as $kw) {
            if (preg_match('/\b' . $kw . '\b/i', $sql, $m, PREG_OFFSET_CAPTURE)) {
                $boundary = min($boundary, $m[0][1]);
            }
        }

        $head = rtrim(substr($sql, 0, $boundary));
        $tail = substr($sql, $boundary);

        $head .= preg_match('/\bwhere\b/i', $head)
            ? ' and ' . $predicate
            : ' where ' . $predicate;

        return rtrim($head . ' ' . $tail);
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
