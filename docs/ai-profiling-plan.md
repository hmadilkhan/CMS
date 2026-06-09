# AI Chat — Measurement / Profiling Plan (Phase 0)

**Status:** ✅ Implemented (Phase 0). Pure observability, gated by `AI_PROFILING` (default off).
Run `php artisan ai:eval --profile` or `php artisan ai:profile-report` after enabling the flag.
**Goal:** Find out *exactly* where time and tokens go per question (per-stage), in both
production and the `ai:eval` benchmark — before optimizing anything.

**Guiding principle (carried from prior discussion):** This phase is **pure observability**.
It must not touch routing, intent-matching, entity resolution, scoping, or data fetching.
Behavior stays byte-for-byte identical. Speed/accuracy trade-offs come in a *later* phase,
gated by this data.

---

## 1. What we measure (per request)

### Per-stage: wall-time + OpenAI tokens + call count

| Stage label | Where (file / method) | Captured |
|---|---|---|
| `decompose` | `AiChatService::decompose` | ms, tokens, ran? |
| `planner` | `AiQueryPlannerService::plan` | ms, tokens, **deterministic (`inferKnownPlan`) vs OpenAI**, 2nd-pass retry? |
| `text_to_sql` | `AiTextToSqlService::generate` | ms, tokens |
| `text_to_sql_retry` | `AiTextToSqlService::regenerate` | ms, tokens, retried? |
| `entity_resolve` | `AiEntityResolverService::resolve` | ms |
| `sql_build` | `AiSafeQueryBuilderService::build` | ms |
| `db_execute` | `AiQueryExecutorService::execute` | ms (pure DB) |
| `formatter` | `AiAnswerFormatterService::format` | ms, tokens |

### Request-level aggregates (the real decision drivers)

- **`openai_calls`** — total OpenAI round-trips for this question (the headline metric).
- **`openai_ms`** / **`db_ms`** / **`php_ms`** — latency split (php_ms = total − openai − db).
- **`engine`** — which engine answered: `text_to_sql | structured | lane | general_chat | dictionary | help | project_detail`.
- **`fallbacks`** — how many times a fallback fired (structured→text-to-sql, empty-result retry, etc.).
- Retry flags: planner 2nd-pass, text-to-sql regenerate.

---

## 2. Implementation design (least-intrusive, additive only)

### 2a. New `App\Services\AiProfiler` (request-scoped)

A small collector with **no-op behavior when disabled** (near-zero overhead):

```
measure(string $stage, callable $fn): mixed     // wrap a stage, record wall time
stage(string $label): void                       // set "current stage" label
recordOpenAi(int $ms, array $usage, int $attempts): void  // tags to current stage
recordDb(int $ms): void
incrementFallback(string $reason): void
setEngine(string $engine): void
toArray(): array                                 // { stages, totals, engine, fallbacks }
```

- Bound as a **scoped singleton** (one instance per HTTP request) in a service provider
  (`$this->app->scoped(AiProfiler::class)`).
- When `config('ai.profiling.enabled') === false`, every method early-returns (the wrapped
  callable in `measure()` still runs normally). Overhead = a boolean check.

### 2b. `OpenAiService` self-reports each call (additive)

- Add a constructor that injects `AiProfiler` (Laravel auto-resolves — **no existing caller changes**).
- In `createResponse()` / `createJsonResponse()`: wrap the `Http::post(...)` with `microtime`,
  then call `$profiler->recordOpenAi($ms, $usage, $attempts)`. `createJsonResponse`'s internal
  retry/model-fallback loop reports `attempts` so retry cost is visible.
- Also add `'duration_ms'` and `'attempts'` keys to the **returned array** — additive, nothing breaks.

### 2c. Orchestrator tags stages

- Inject `AiProfiler` into `AiChatService`.
- Before each sub-call, set the label: `$profiler->stage('planner')` → `plan()`,
  `$profiler->stage('text_to_sql')` → `generate()`, `$profiler->stage('formatter')` → `format()`, etc.
- Call `$profiler->setEngine(...)` / `incrementFallback(...)` at the existing branch points
  (we already know them — `$textToSqlUsed`, the empty-result retry, general-chat fallback).

### 2d. DB time

- Inject `AiProfiler` into `AiQueryExecutorService`; record `recordDb($ms)` around its
  `select()` so pure DB time is isolated from OpenAI/PHP.

### 2e. Persist

- At the end of each handler (where `ai_query_logs` is already updated), write
  `$profiler->toArray()` into the new columns + `stage_timings` JSON.

---

## 3. Migration (additive, nullable — zero risk to existing rows)

New migration adding to **`ai_query_logs`**:

| Column | Type | Purpose |
|---|---|---|
| `openai_calls` | unsignedTinyInteger, null | round-trips per question |
| `openai_ms` | unsignedInteger, null | time in OpenAI |
| `db_ms` | unsignedInteger, null | time in DB |
| `engine` | string(32), null, index | which engine answered |
| `fallbacks` | unsignedTinyInteger, default 0 | fallback count |
| `stage_timings` | json, null | full per-stage breakdown |
| `question_hash` | char(32), null, index | normalized-question hash (repeat-rate) |

(`duration_ms` total + `prompt/completion/total_tokens` already exist — reused.)

---

## 4. Config flag (default OFF in production)

`config/ai.php`:

```php
'profiling' => [
    'enabled' => env('AI_PROFILING', false),
],
```

Turn `AI_PROFILING=true` only during a measurement window. Per earlier agreement: flag-gated,
controlled, instantly reversible.

---

## 5. Reporting (read-only)

### 5a. `ai:eval` — add `--profile`

Prints a per-case stage table (ms, calls, tokens) + per-run summary. `ai:eval` is the
controlled, reproducible benchmark, so this becomes our baseline.

### 5b. New `php artisan ai:profile-report {--days=7} {--json}`

Read-only aggregation over `ai_query_logs` (profiled rows only):

- count, **p50 / p95 `duration_ms`**
- **avg / p95 `openai_calls`**
- latency split: avg `openai_ms` vs `db_ms` vs `php_ms` (and %)
- per-stage p50/p95 (from `stage_timings`)
- **engine distribution** (group by `engine`)
- **fallback rate** (% requests with `fallbacks > 0`)
- retry rate (planner 2nd-pass, text-to-sql regenerate)
- **repeat-question rate** (duplicate `question_hash`) → caching potential

---

## 6. What the data unlocks (decision thresholds)

| If the report shows… | …then this optimization is justified (later phase) |
|---|---|
| avg `openai_calls` > 2 | merge planner+text-to-sql / caching |
| `formatter` = large share of latency | deterministic formatting (skip formatter LLM) |
| high repeat-question rate | cache validated SQL in `ai_query_examples` |
| structured→text-to-sql fallback fires often | route data_explorer directly |
| planner input tokens dominate | (carefully) trim prompt — **not** brittle keyword schema-pruning |

Every later optimization will be picked by **proof, not guess**, and validated by `ai:eval`
before/after. Anything that risks re-introducing keyword/intent mismatch (schema pruning,
model downgrade) stays out unless data + eval prove it's safe.

---

## 7. Files touched (this phase)

**New:** `app/Services/AiProfiler.php`, migration `*_add_profiling_to_ai_query_logs.php`,
`app/Console/Commands/AiProfileReportCommand.php`.
**Edited (additive only):** `OpenAiService.php`, `AiChatService.php`,
`AiQueryExecutorService.php`, a service provider (scoped binding), `config/ai.php`,
`AiEvalCommand.php` (optional `--profile`).

No routing/planning/scoping logic is modified.

---

## 8. Safety & acceptance

- **No behavior change:** run `ai:eval` with profiling OFF vs ON — identical answers/intents.
- **Overhead:** microtime is nanoseconds; one JSON encode per request; disabled = boolean check.
- **Reversible:** `AI_PROFILING=false` disables everything; columns stay nullable/unused.
- **Done when:** profiled `ai:eval` run + a few days of real traffic give us the §5b report,
  i.e. we can state avg round-trips, latency split, engine mix, and repeat rate with numbers.

---

## 9. Suggested rollout order

1. Migration + `config/ai.php` flag.
2. `AiProfiler` + scoped binding.
3. `OpenAiService` self-reporting.
4. Orchestrator stage tags + persistence (`AiChatService`, `AiQueryExecutorService`).
5. `ai:profile-report` + `ai:eval --profile`.
6. Run `ai:eval` (OFF vs ON) to prove zero behavior change → enable in prod for a window → collect.
</content>
