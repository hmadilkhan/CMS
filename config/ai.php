<?php

return [
    'enable_write_block' => env('AI_ENABLE_WRITE_BLOCK', true),

    'schema' => [
        'archived_department_id' => env('AI_ARCHIVED_DEPT_ID', 9),
        'max_query_limit' => env('AI_MAX_QUERY_LIMIT', 100),
        'integrity_tables' => [
            'projects',
            'customers',
            'users',
            'employees',
            'tasks',
            'service_tickets',
        ],
    ],

    'project_detail' => [
        // A single department lane taking more than this many days is flagged as
        // the likely bottleneck in a project-detail summary.
        'lane_delay_days' => env('AI_LANE_DELAY_DAYS', 30),
    ],

    // Phase 0 observability. When enabled, the AI pipeline records per-stage
    // latency, OpenAI round-trips/tokens, and DB time into ai_query_logs. Pure
    // measurement — it never changes routing or results. Keep OFF in production
    // except during a measurement window.
    'profiling' => [
        'enabled' => env('AI_PROFILING', false),
    ],

    'planner' => [
        // Cache the planner's structured plan for an identical (normalized)
        // question + permission signature, so repeated questions skip the
        // expensive gpt-4.1 planner call. The plan carries no user-specific row
        // data (row scoping is applied downstream), so this never widens access.
        // Set to 0 to disable.
        'cache_ttl' => env('AI_PLANNER_CACHE_TTL', 21600), // 6 hours
    ],

    'text_to_sql' => [
        // Cache generated SQL for an identical question — ONLY for unscoped
        // (admin/finance) users, whose SQL carries no row-scope and is identical
        // for the same permission signature. Scoped users splice user-specific
        // project IDs into the SQL and are never cached. Set to 0 to disable.
        'cache_ttl' => env('AI_TEXT_TO_SQL_CACHE_TTL', 21600), // 6 hours
    ],

    'security' => [
        'max_daily_requests_per_user' => env('AI_MAX_DAILY_REQUESTS_PER_USER', 100),
        'per_minute_requests_per_user' => env('AI_MAX_REQUESTS_PER_MINUTE_PER_USER', 60),
        'query_timeout_ms' => env('AI_QUERY_TIMEOUT_MS', 5000),
        'query_cache_ttl' => env('AI_QUERY_CACHE_TTL', 300),
    ],
];
