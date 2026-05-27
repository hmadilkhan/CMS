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

    'security' => [
        'max_daily_requests_per_user' => env('AI_MAX_DAILY_REQUESTS_PER_USER', 100),
        'query_timeout_ms' => env('AI_QUERY_TIMEOUT_MS', 5000),
        'query_cache_ttl' => env('AI_QUERY_CACHE_TTL', 300),
    ],
];
