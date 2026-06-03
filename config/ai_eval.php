<?php

/*
|--------------------------------------------------------------------------
| AI Chat evaluation cases
|--------------------------------------------------------------------------
|
| A small regression suite for the AI assistant. Each case is a question plus
| the outcome we expect. Run it with:  php artisan ai:eval
|
| Supported expectations (all optional — omit for "must return some data"):
|   'min_rows'   => N   The answer must contain at least N rows.
|   'count_min'  => N   For a count answer, the value must be >= N.
|   'intent'     => 's' The planned intent must contain this substring.
|   'note'       => '…' Human description (e.g. the bug it guards against).
|
| Seed this list from real questions in the ai_query_logs table — especially
| any that returned 0/empty in production.
|
*/

return [
    'cases' => [
        // --- Regressions we have already fixed (guard against re-breaking) -------
        [
            'q'        => 'How many projects are in the Deal Review department?',
            'count_min' => 1,
            'note'     => 'Named department count (returned 4).',
        ],
        [
            'q'        => 'Show the project count department-wise',
            'min_rows' => 2,
            'note'     => 'Bug: "department-wise" extracted a bogus dept name -> count 0. Now via Text-to-SQL.',
        ],
        [
            'q'        => 'Show the project count as per department',
            'min_rows' => 2,
            'note'     => 'Bug: "as per department" -> count 0. Now via Text-to-SQL.',
        ],
        [
            'q'        => 'Show the project total numbers department-wise',
            'min_rows' => 2,
            'note'     => 'Bug: "department-wise" -> count 0. Now via Text-to-SQL.',
        ],

        // --- Common queries that should always return data ----------------------
        [
            'q'        => 'Show me all customers',
            'min_rows' => 1,
            'note'     => 'Basic customer list.',
        ],
        [
            'q'        => 'Tickets status wise',
            'min_rows' => 1,
            'note'     => 'Ticket status grouping.',
        ],
        [
            'q'        => 'Customer wise projects',
            'min_rows' => 1,
            'note'     => 'Projects grouped per customer.',
        ],
        [
            'q'         => 'How many projects are there in total?',
            'count_min' => 1,
            'note'      => 'Total project count.',
        ],
        [
            // Deterministic project-detail handler (no LLM) — update name if removed.
            'q'        => 'Show me the details of Yunjiao-Guan - 61st Ave project',
            'min_rows' => 1,
            'note'     => 'Project detail summary: formatted text + per-department days table.',
        ],
    ],
];
