# CLAUDE.md

Guidance for working in this repository. Read this first; it captures what is not obvious from a single file.

## What this project is

A **Laravel 10 CRM for a solar-energy installation company** (PHP 8.1+). It manages
solar installation projects end-to-end: customers, projects moving through department
"lanes", tasks, service tickets, site surveys, finance/profitability, sub-contractors
and sales partners, an equipment catalogue (inverters/modules/batteries), an email/IMAP
module, and reporting.

Stack & key packages:
- Laravel 10, **Livewire 3** (dashboards, reports, charts), Blade + **Tailwind/Alpine** (Vite).
- **spatie/laravel-permission** (roles/permissions), **spatie/laravel-activitylog** (audit/`activity_log`).
- `barryvdh/laravel-dompdf` + `setasign/fpdf` (PDFs), `maatwebsite/excel` (exports),
  `webklex/laravel-imap` (email fetch), `spatie/laravel-google-calendar`, `lab404/laravel-impersonate`.
- Frontend build: `npm run dev` / `npm run build` (Vite). Tests: PHPUnit (`phpunit.xml`), Dusk for browser.

Conventions: PSR-4 `App\` → `app/`. Service classes in `app/Services`, Livewire in
`app/Livewire`, Eloquent models in `app/Models`. Match surrounding code style; run
`./vendor/bin/pint` before finishing PHP changes.

---

## ⭐ The AI Chatbot — "SolenAssist"

This is the centerpiece feature and the most intricate subsystem. It is a **read-only,
role-aware natural-language assistant** over the CRM database. Users ask questions in
English or Roman-Urdu (or mixed) and get back text, tables, cards, or counts. It speaks
the solar domain (NTP, PTO, HOA, AHJ, MPU, COC, meter spot, lanes, etc.).

**Hard security invariants (never violate):**
- **Read-only.** No INSERT/UPDATE/DELETE/DDL ever. Write-intent questions are rejected up front.
- **OpenAI never executes SQL.** The model returns *structured JSON plans* or *candidate SQL strings*; Laravel validates, scopes, and executes them. Generated SQL is always SELECT-only, passed through validators, and `LIMIT`-capped (≤100).
- **Everything is allowlisted** in `config/ai_schema.php` (59 tables) — tables, columns, searchable columns, relationships, sensitive columns, access rules. The model can only see/use what is listed.
- **Role-based row + column scoping** is applied server-side before execution. Never widen a scoped user's visibility.
- Secrets (password, remember_token, api_token, tokens, etc.) must never be added to `allowed_columns`.

### Request flow (entry points)

- Routes: `routes/web.php` lines ~77–84, gated by `can:SolenAssist` + `throttle:ai_chat`; `send`/`retry` also pass `ai.daily.limit` middleware (`App\Http\Middleware\AiDailyLimit`).
- Controller: `app/Http/Controllers/AiChatController.php` — `index/show/send/rename/destroy/retry/feedback`. Thin; delegates to `AiChatService`.
- Persistence: `ai_chats`, `ai_chat_messages`, `ai_query_logs`, `ai_query_feedback`, `ai_query_examples` (migrations `2026_05_26_*`, `2026_05_27_*`). Conversation continuity uses OpenAI's `previous_response_id` stored on the chat (`openai_response_id`).

### Orchestration — `app/Services/AiChatService.php` (the brain)

`send()` → `respondToMessage()` → optional **multi-intent decomposition** (`looksCompound`/`decompose` splits "X and also Y" into self-contained questions via the LLM) → `routeSingle()`.

`routeSingle()` tries cheap deterministic fast-paths first (no/low OpenAI cost), falling through if a gate can't confidently resolve:
1. **Help** request → static capabilities text.
2. **Bare project code** ("1048", "SS-001") → project-detail summary.
3. **Obvious greeting / social** → general chat (skip planner).
4. **Field/term explanation** ("what is PTO?", "meter_spot_result kya hai?") → `AiFieldDictionaryService` (deterministic, permission-aware, no OpenAI cost).
5. **Named project detail** ("summary of <project>") → `AiProjectLaneService::getProjectDetail` + per-department lane table.
6. **"Who moved project X" / move history** → answered from `activity_log` directly (correct `subject_type` + code→id, which AI SQL gets wrong).
7. Everything else → **`handleQueryPlan()`** (the full CRM pipeline).

### The hybrid query pipeline — `handleQueryPlan()`

```
User message
  → AiQueryPlannerService::plan()      (structured JSON plan via OpenAI sql_model)
  → branch on plan.intent / plan.mode:
       • project_lane_movement / _summary → AiProjectLaneService (named query, bypasses SQL pipeline)
       • intent=unknown & mode=unsupported → general chat fallback
       • mode != 'fixed_action' (data_explorer) → AI TEXT-TO-SQL first
       • mode = 'fixed_action' (curated report) → STRUCTURED builder first
  → AiAnswerFormatterService::format()  (LLM turns rows into a friendly answer)
  → persist assistant message + AiQueryLog + AiQueryExample
```

Two execution engines, chosen by plan mode, with **fallbacks between them**:

**A. Structured pipeline (curated / fixed_action reports):**
`AiPlanValidatorService::validate` → `AiEntityResolverService::resolve` (resolves
customer/project/department/user names; asks for clarification on multiple matches,
not-found on none) → `AiSafeQueryBuilderService::build` (wraps `AiSqlBuilderService`)
→ `AiSqlValidatorService::validate` (SELECT-only, blocked keywords) →
`AiQueryExecutorService::execute` (runs on read-only connection).

**B. Text-to-SQL (open-ended / data_explorer):** `AiTextToSqlService::generate` asks the
strong model to write SQL constrained to the per-user allowed schema, then **one
self-correcting retry** feeds the real DB error back (`regenerate`). Server enforces:
soft-delete `deleted_at IS NULL`, `LIMIT`, SELECT-only, table/column allowlist, finance guard.

**Cross-fallbacks (important for "why did it still answer" reasoning):**
- data_explorer tries Text-to-SQL first; if it fails, falls to the structured builder.
- structured pipeline falls to Text-to-SQL when: plan can't validate, entity resolution needs clarification, execution fails, **or** a curated query returns empty/`count=0` *for a full-access user* (likely a mis-route — gated to unscoped users so scoped roles never gain visibility).
- If everything fails → graceful **general chat** (`handleGeneralChat`) using OpenAI with assistant instructions.

### The planner — `app/Services/AiQueryPlannerService.php`

- Big file (~2500 lines). Produces a **hybrid plan**: `mode` (`fixed_action | data_explorer | clarification_required | unsupported`), `confidence`, `intent`, `tables`, `columns`, `filters`, `group_by`, `requires_finance_access`, etc. JSON-schema-constrained (`jsonSchema()`).
- Pre-OpenAI shortcuts: `isWriteOperationQuestion` (block writes), `isFollowUpReference` + `buildFollowUpPlan` (resolve "those projects", "in me se" against previous context), `inferKnownPlan` (keyword router for lane queries, user+role lists, named project/acceptance summaries, finance/profitability/transaction reports, date-range extraction).
- `instructions()` holds the **domain prompt**: solar term→column mappings (e.g. "PTO" → `pto_submission_date`/`pto_approval_date`, "ghost" → Pre-Inspection Lane), module hints, activity-log rules, answer-type guide, security rules. If you change schema/columns, update these mappings too.
- `sanitizePlan()` re-checks every table/column/filter against `AiSchemaService` + `AiPermissionService` and returns a permission-denied plan on violation. Confidence < 0.65 → rejected/clarification.
- A second pass retries when the first plan is `unknown` but the question `looksLikeCrmDataQuestion`.

### OpenAI client — `app/Services/OpenAiService.php`

- Calls the **OpenAI Responses API** (`/v1/responses`), not chat completions.
- `createResponse()` = free-form assistant (uses `buildAssistantInstructions` — the SolenAssist persona + full solar domain knowledge + per-role capability description). `createJsonResponse()` = JSON-schema-constrained output for planner/Text-to-SQL, with a retry-on-invalid-JSON loop and a model-downgrade fallback.
- **Two models**: `services.openai.model` (default `gpt-4.1-mini`, cheap path) and `services.openai.sql_model` (`gpt-4.1`, used by planner + Text-to-SQL where accuracy matters). `sqlModel()` returns the latter.

### Row scoping & permissions (security core)

- `AiRowScopeService` — single source of truth for project row-access; **mirrors `App\Services\ProjectService::projectQuery()`** so the assistant scopes exactly like the rest of the CRM. `hasUnscopedAccess()` = Super Admin / Admin / finance-capable. Provides `applyProjectScope()` (lazy subqueries for the structured builder) and `projectScopeSql()`/`allowedProjectIds()` (inline IDs spliced into Text-to-SQL via the `__PROJECT_ACCESS_SCOPE__` token — scoped Text-to-SQL is refused if it isn't project-centric or omits the token).
- `AiPermissionService` / `AiAccessPolicyService` — table/column/finance/profitability access by role, delegating to the CRM permission service.
- Role tiers: **Super Admin/Admin** = all; **Finance** = finance + project/customer context; **Manager** (+ Sales/Sub-Contractor Manager) = department/team; **Employee/Sales Person/Sub-Contractor User** = own assigned projects/tickets/tasks.

### Supporting services (`app/Services/Ai*`)

- `AiSchemaService` — reads `config/ai_schema.php` (allowed tables/columns/relationships/access rules).
- `AiPlanValidatorService`, `AiSqlValidatorService`, `AiSqlParserService` — validation layers (modes, blocked keywords, SELECT-only, no UNION/subquery-in-FROM).
- `AiSafeQueryBuilderService` → `AiSqlBuilderService` / `AiGenericQueryBuilderService` / `AiDynamicSqlBuilderService` — build SQL from structured plans with scoping + soft-deletes.
- `AiEntityResolverService` — name → record resolution + clarification/not-found.
- `AiQueryExecutorService` — executes on the read-only connection, returns rows/row_count/errors.
- `AiAnswerFormatterService` — LLM formats result rows into the user-facing message (text/table/card).
- `AiFieldDictionaryService` — deterministic field/term explanations from `config/ai_field_dictionary.php`; also supplies `guidanceFor()`/`contextFor()` grounding.
- `AiProjectLaneService` — named queries for lane movement, per-department totals, project detail, and move/activity history.

### Config & env (AI)

- `config/ai.php` — write-block toggle, schema knobs (`archived_department_id`, `max_query_limit`), `project_detail.lane_delay_days`, security limits (daily/per-minute caps, query timeout, cache TTL).
- `config/ai_schema.php` — the table/column allowlist (security-critical; review on every migration).
- `config/ai_field_dictionary.php` — human explanations of fields/terms.
- `config/ai_eval.php` — evaluation cases.
- `config/services.php` → `openai` block. Env: `OPENAI_API_KEY`, `OPENAI_MODEL`, `OPENAI_SQL_MODEL`, `OPENAI_MAX_OUTPUT_TOKENS`, `OPENAI_TIMEOUT`, `AI_READONLY_DB_CONNECTION`, plus `AI_*` limits. Production should use a **SELECT-only DB user** for `database.connections.ai_readonly`.

### Tooling & docs

- `php artisan ai:eval [--user=ID] [--show] [--keep]` — regression/coverage suite (`AiEvalCommand`, cases in `config/ai_eval.php`). Supports multi-turn chains so follow-up context is tested. **Run this after changing AI routing/planning.**
- `php artisan ai:schema-audit [--json]` — audits `ai_schema` + `ai_field_dictionary` against the live DB (catches drift after migrations).
- `php artisan ai:profile-report [--days=7] [--json]` — aggregates the Phase-0 profiling data (latency split, OpenAI round-trips/question, engine mix, fallback rate, repeat-question rate) from `ai_query_logs`. `ai:eval --profile` prints per-case timing. Profiling is pure observability, gated by `AI_PROFILING` (default off) → `config('ai.profiling.enabled')`; collected by `App\Services\AiProfiler` (request-scoped), recorded in `OpenAiService` (OpenAI ms/tokens) + `AiQueryExecutorService` (DB ms) + stage labels in planner/text-to-sql/formatter, persisted to `ai_query_logs` (`openai_calls`, `openai_ms`, `db_ms`, `engine`, `fallbacks`, `stage_timings`, `question_hash`). See `docs/ai-profiling-plan.md`.
- Docs: `docs/ai-chat-configuration.md`, `docs/ai-query-planner.md`, `docs/ai-profiling-plan.md`. Frontend view: `resources/views/ai-chat/index.blade.php`.

### When modifying the AI subsystem

1. Adding a table/module → edit `config/ai_schema.php` (model, table, allowed/searchable columns, relationships, sensitive_columns, access_rule), then update planner `instructions()`/`moduleHints()` mappings, run `ai:schema-audit`, add an `ai_eval` case, run `ai:eval`.
2. Never add credential columns to `allowed_columns`; mark finance/profitability columns sensitive.
3. Preserve the two invariants: *OpenAI never executes SQL* and *scoping is applied server-side*. Any new SQL path must go through the validators + `AiRowScopeService`.
4. Keep English + Roman-Urdu handling — keyword lists and prompts are bilingual by design.

### Performance, profiling & ongoing optimization work

A **measure-first** optimization effort is underway. Read this before touching the AI latency path.

**Phase 0 — Profiling (DONE, keep it).** Pure observability, gated by `AI_PROFILING` (default off) → `config('ai.profiling.enabled')`.
- `App\Services\AiProfiler` (request-scoped, bound in `AppServiceProvider::register`) collects per-stage wall time, OpenAI round-trips/tokens, DB time, engine, fallbacks. No-op when the flag is off (≈ a boolean check).
- Recorded by `OpenAiService` (OpenAI ms/tokens + `attempts`/`duration_ms` in its return), `AiQueryExecutorService` (DB ms), and one-line `stage('…')` labels in planner / text-to-sql / formatter. `AiChatService::routeSingle` resets per question, tags engine/fallbacks, and persists via `profileColumns()`.
- Persisted to `ai_query_logs` columns: `openai_calls`, `openai_ms`, `db_ms`, `engine`, `fallbacks`, `stage_timings` (json), `question_hash`.
- Inspect: `php artisan ai:eval --profile` (per-case timing) and `php artisan ai:profile-report [--days=N] [--limit=N]` (aggregate: p50/p95 latency, avg round-trips, OpenAI/DB/PHP split, engine mix, fallback + repeat-question rate, per-stage p50/p95). See `docs/ai-profiling-plan.md`.

**Formatter fix + optimization (DONE).** Two linked changes — do not regress them:
1. `OpenAiService::createJsonResponse` now guarantees the literal word "json" appears in the **input** whenever it falls back to `text.format` = `json_object` (no `json_schema` given). The Responses API 400s otherwise, and the `instructions` field does not satisfy it. This bug had made the answer formatter throw on *every* call, silently returning canned fallback text (e.g. "Here are the matching CRM results.") instead of real summaries.
2. `AiAnswerFormatterService::format` is now **deterministic-first + trimmed**: rows/columns/cards always come from the executed query (`fallbackAnswer`, the same logic that passes eval); report intents (profitability/forecast/override/transaction) and empty results skip the LLM entirely; everything else gets ONE friendly sentence via `generateMessage`, which sends only `row_count` + ≤5 sample rows (never the full result set) under a strict `json_schema`. **Never send full result rows to the formatter again.** Removed dead methods: `instructions`, `normalizeAnswer`, `emptyAnswer`, `examples`.
3. **Count value extraction is alias-agnostic** (`AiAnswerFormatterService::extractCountValue`). Count answers previously read the fixed key `$rows[0]['aggregate']` — fine for the structured builder (which aliases counts `aggregate`) but Text-to-SQL aliases them arbitrarily (`project_count`, `total_projects`, …), so the value came back 0 and the assistant wrongly said "no projects" for a non-zero count. The helper now reads known aliases, then any `*count*/*total*` column, then the first numeric value. This surfaced when the routing fix sent count questions to Text-to-SQL; counts now work regardless of engine/alias.

**Measured baseline after the formatter optimization** (admin user, profiled eval, ~54 questions):
- Total latency p50 ≈ 5.6s, p95 ≈ 18s. Avg OpenAI calls ≈ 1.4 (max 3) — *low*, so "merge planner+text-to-sql" is NOT justified.
- Formatter p50 ≈ 2.0s / p95 ≈ 3.8s (was 9.3s / 81s before trimming). Reports skip it (0 calls).
- **The bottleneck is now the planner** (`AiQueryPlannerService::plan`, gpt-4.1): p50 ≈ 3.65s, **p95 ≈ 11.7s**, big variance. Repeat-question rate ≈ 9%.

**Guiding principle:** speed never at the cost of accuracy. Do NOT reintroduce brittle keyword→intent matching, keyword-based schema pruning, or a planner model downgrade unless `ai:eval` proves it safe. Every change: run `ai:eval` before/after.

**Planner latency (DONE).** Was the top bottleneck after the formatter work (gpt-4.1, p50 ≈ 3.65s, p95 ≈ 11.7s).
- **A — Plan cache (implemented):** `AiQueryPlannerService::plan` caches the sanitized plan for an identical normalized question, keyed by a permission signature (roles + finance + profitability access), gated to empty conversation memory and usable intents only. On a hit it skips the gpt-4.1 planner call and returns `syntheticOpenAiResponse()`. The plan holds no user-specific row data (scoping is applied downstream), so it never widens access. TTL: `config('ai.planner.cache_ttl')` (`AI_PLANNER_CACHE_TTL`, default 6h; 0 disables). Verified: repeated question planner ms ≈ 6000 → ≈ 10 (call skipped), 40/40 eval still pass.
- **B — Few-shot trim (minimal):** removed one exact-duplicate `employee_department_list` example. Aggressive trimming was **declined** — the few-shot examples are a minority of the planner prompt (the 59-table schema dominates) and they aid accuracy; trimming them is not worth the accuracy risk, and schema pruning is off-limits.

**Routing flake fix (DONE).** Root cause: for "details of all deal review department projects" the LLM planner returned `filters: []` (it never captured the department filter into the structured plan) AND non-deterministically set `mode` to `fixed_action` vs `data_explorer`. On `fixed_action` the question went structured-first, which only sees `plan['filters']` (empty) → returned all rows (100). On `data_explorer` it went Text-to-SQL first, which reads the raw question, writes the `WHERE departments.name='Deal Review'` itself → correct 4 rows.
- **Fix:** in `AiQueryPlannerService::legacyPlanFromHybrid` the LLM-planner success branch now forces `'mode' => 'data_explorer'` (was the LLM's own mode). Every free-form LLM-planned question runs Text-to-SQL first; the structured builder remains the fallback. Curated `inferKnownPlan` reports keep `fixed_action` (tagged separately in `withHybridMetadata`), so curated reports are unaffected. This removes the non-determinism at its source and matches the project's principle (Text-to-SQL for free-form, structured/keyword only for curated). Verified: 6/6 runs now return 4 rows via text_to_sql (was intermittently 100).

**Text-to-SQL domain grounding (DONE).** `AiTextToSqlService::domainGrounding()` injects real values for the CRM's two most ambiguous query terms (cached 30 min via `distinctColumn()`, non-sensitive reference data):
- **department vs sub_department** — text-to-sql looked for the "Deal Review" DEPARTMENT inside `sub_departments` → 0 (or ignored it → 372). Grounding lists `departments.name` / `sub_departments.name` values and says to match a named lane against whichever table contains it. "Deal Review" is a department. Verified: returns 4 consistently.
- **status** — "projects by status" was grouping by `departments.name` aliased as "status" (showing lanes, not statuses). Grounding states a project has no status column; its status is `tasks.status` (In-Progress/Hold/Completed/Cancelled) joined `tasks.project_id = projects.id AND tasks.department_id = projects.department_id` (current-department task = one per project), and to NEVER alias a department as status. Verified: "Projects by status" / "projects status wise" now return real statuses.

These groundings are needed because the routing fix sends free-form questions to Text-to-SQL, which must know domain nuances the structured planner examples used to encode. Add new grounding here when a domain term is consistently misread.

**UX + caching enhancements (DONE).** Four additive features, each verified:
1. **Text-to-SQL result caching** — `AiTextToSqlService::generate` caches generated SQL keyed by question_hash + permission signature, **only for unscoped (admin/finance) users** (scoped users splice user-specific project IDs into SQL → never cached), gated to empty conversation memory. `config('ai.text_to_sql.cache_ttl')` (`AI_TEXT_TO_SQL_CACHE_TTL`, 6h). Combined with the plan cache, a repeated admin question can run with 0 OpenAI calls. Verified.
2. **CSV / PDF export** — `AiChatController::export` (route `ai-chat.export`, `GET /ai-chat/messages/{message}/export/{format}` where format∈csv,pdf). Reads the already-stored, already-scoped answer rows from message metadata (no query re-run → can't leak more than the user saw). CSV via `streamDownload`+`fputcsv` (quoting safe), PDF via dompdf `ai-chat.export` view. Buttons in the assistant action bar (Blade + JS) when the answer is a table with rows.
3. **Follow-up chips** — `AiAnswerFormatterService::generateMessage` now returns `{message, suggestions[]}` from the SAME single LLM call (no extra round-trip); suggestions stored on `answer['suggestions']`, rendered as clickable `.followup-chip` buttons (Blade + JS, delegated click → fills input + submits). Only on non-empty, non-report answers.
4. **Inline charts** — grouped/aggregate table answers (one numeric + one label column, 2–30 rows, "Total" row excluded) render an ApexCharts bar chart. ApexCharts bundle added to `layouts.ai-chat`. Chart data passed via a nested `<script type="application/json">` (JSON_HEX_* flags) to avoid attribute-quoting bugs; `initCharts()` is fully defensive (removes the mount on any non-chartable/malformed/error case, never breaks the page). Works for both server-rendered (on load) and freshly-appended messages.

Note: the ai-chat view renders messages in BOTH Blade (initial load) and JS (`appendMessage`/`renderAnswer`, new messages) — any answer-rendering change must be made in both, kept consistent. No Tailwind rebuild needed (reused existing classes).

**All optimization + enhancement items are DONE** (profiling, formatter fix+optimize+count-extraction, planner plan-cache, routing flake, Text-to-SQL department grounding + result caching, CSV/PDF export, follow-up chips, inline charts). Deliberately NOT done (by choice): streaming responses, and planner schema-pruning / model-downgrade (accuracy risk). Targeted fixes were verified individually; run a full `php artisan ai:eval` after OpenAI quota resets to confirm 40/40. Re-run `ai:eval` after any AI change to confirm no regression.

### QA round — bug fixes & hardening (DONE, keep these)

A QA pass (scan + live probing as different roles) found and fixed the following. All verified with `php artisan ai:eval` → **40/40**, and individually re-probed.

**Security (the big one):**
- **Text-to-SQL column allowlist (was missing).** `AiTextToSqlService::run()` validated tables but NOT columns — the "everything is allowlisted: columns" invariant held only for the structured engine, yet Text-to-SQL is the *primary* engine after the routing flake fix. Added `AiTextToSqlService::validateColumns()`: every qualified `table.column` ref must pass `AiPermissionService::canAccessColumn` (blocks secrets + finance/profitability cols a user can't see), plus a `SECRET_COLUMN_DENYLIST` backstop for unqualified credential columns. Verified the existing schema keeps `id`/FK columns in `allowed_columns`, so the check never false-rejects valid joins.
- **No table aliases in Text-to-SQL** (`buildInstructions` rule). Aliases broke the server-side soft-delete + row-scope injection (which use real table names). Outer/main tables must use full names; aliases allowed ONLY inside correlated subqueries (needed for the latest-task pattern below). `AI_READONLY_DB_CONNECTION=ai_readonly` is already set in `.env` (write-protection is real, not just regex).

**Accuracy bugs (all rooted in project↔task one-to-many + count-vs-list routing):**
- **Status counts inflated** ("projects by status" summed 419 > 372 total). A project has many tasks, so a plain `JOIN tasks` over-counts. Fixed BOTH engines to pin to each project's LATEST task: structured `AiSqlBuilderService` (`project_status_count` + `project_status_summary`) adds `whereRaw('tasks.id = (select max(lt.id) from tasks as lt where lt.project_id = projects.id and lt.deleted_at is null)')`; Text-to-SQL `domainGrounding()` (status section, cache key bumped to `_v3`) instructs the same latest-task subquery. **Canonical rule: a project's status = the status of its latest (`MAX(id)`) task.**
- **Duplicate rows in stage lists** ("projects in permitting stage" showed each project twice). Model spuriously joined `tasks`. Grounding now says: to LIST/COUNT projects in a lane/stage, filter `projects.department_id → departments.name` directly, do NOT join `tasks` (use `DISTINCT`/`COUNT(DISTINCT projects.id)` if you must).
- **"Top N highest/lowest" ignored sort+limit** (curated `project_financing_summary` returned 100 unsorted). Added a ranking guard in `AiQueryPlannerService::inferKnownPlan`: finance questions containing a superlative (`highest|lowest|top|most|…`) skip the curated report and fall to Text-to-SQL (which writes `ORDER BY … LIMIT N`).
- **"How many <status> projects" returned a capped list framed as a count** (said "100" when real = 319). Added `project_status_filter_count` intent: a tightly-guarded `inferKnownPlan` branch (only fires on a bare "how many/kitne + project + task-status"; defers anything with a date/department/location/milestone qualifier to the LLM/TTS) → structured builder does `count(*)` with the latest-task pin + `tasks.status` filter.

**Multi-intent decompose (flaky → deterministic):** `AiChatService::decompose()` got NO conversation context, so split parts kept an unresolved "this project" and relied on probabilistic downstream resolution (≈50% flaky). Now `respondToMessage` passes `buildConversationMemory(...)` into `decompose()`, and the prompt resolves references to the concrete subject (real project name) up front → each sub-question is self-contained → deterministic.

**Sibling-project precision (named follow-up leaked siblings):** "financing details of this project" for "Yunjiao-Guan - 61st Ave" returned 3 rows — the customer "Yunjiao-Guan" has 3 projects (`… - 61st Ave`, `… - Burlwood`, `… - Amador St`) and the filter was a loose `project_name LIKE '%Yunjiao-Guan%'`. Two truncation points fixed: (1) `AiQueryPlannerService::extractProjectReferenceFilter` now also captures a trailing ` - <address/unit>` segment after the hyphenated name (so the filter targets the SPECIFIC project, not every sibling), trimming trailing punctuation; (2) `decompose()`'s prompt now keeps the EXACT, COMPLETE name including the address/unit suffix (never shortens "Yunjiao-Guan - 61st Ave" to "Yunjiao-Guan"). Verified: financing follow-up now returns the single asked project; 40/40 eval. (A bare customer name like just "Yunjiao-Guan" still legitimately matches all siblings — that's intended.)

**UI rendering (`resources/views/ai-chat/index.blade.php`):**
- **Multi-intent answers dropped in the UI.** The send (and retry) JS rendered only `data.messages[last]`, so a compound message's earlier reply (e.g. financing) was in the DB but never shown — only the last part (tasks). Added `appendNewAssistantMessages()` which renders EVERY assistant reply after the latest user message. (Blade initial-load already rendered all messages, so this only affected live append.)
- **Wide single-entity results made readable.** A 1–3 row × >5 column result (e.g. one project's financing) rendered as a horizontally-scrolling table. Now rendered as stacked label/value cards via `renderRecordCards()` (JS) + matching Blade branch; the auto-chart is suppressed for these. Kept JS + Blade consistent (per the note above).

**Gotchas / learnings (important):**
- **`php artisan view:cache` does NOT lint the compiled PHP** — it only does the Blade→PHP transform, so a structural bug that produces invalid PHP still reports "cached successfully". To actually validate a Blade change, compile then `php -l` the files in `storage/framework/views/*.php`.
- **Avoid `@php … @endphp` blocks containing a `//` comment** — it breaks Blade's `@php`→`<?php` conversion (opening `@php` stays literal), cascading into "unexpected endif". Prefer inline `@if(<expr>)` conditions and `{{-- --}}` Blade comments.
- **Role reality:** `Employee` (and other scoped roles) have NO finance access, so financing/contract-amount answers are correctly blocked for them — "only tasks showed" is by-design scoping, not a bug. Finance data needs Admin/Super Admin/Finance.
- **The eval asserts the LAST assistant message of a step**, so a multi-intent test's final clause must reference data that exists for the test project (Yunjiao-Guan has financing + tasks but no logs).
- Added **`php artisan ai:eval --filter="<text>"`** to run only cases whose label contains the text (fast iteration on one case).

---

## General development notes

- Email/IMAP: `EmailFetchService`, `app/Console/Commands/FetchEmails.php` / `FetchAllEmails.php`, jobs in `app/Jobs`.
- Reporting/dashboards: `app/Livewire/*` (DynamicReportBuilder, charts, role dashboards).
- Deploy helpers: `deploy.sh` / `rollback.sh`. Activity audit via spatie activitylog (`activity_log` table).
- Tests live in `tests/` (PHPUnit + Dusk). The repo is on branch `main`.
</content>
</invoke>
