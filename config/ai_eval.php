<?php

/*
|--------------------------------------------------------------------------
| AI Chat evaluation cases
|--------------------------------------------------------------------------
|
| A regression + coverage suite for the AI assistant. Run it with:
|     php artisan ai:eval                # as first admin
|     php artisan ai:eval --user=12      # as a specific user
|     php artisan ai:eval --show         # also print a few result rows
|
| A case is EITHER a single question or a multi-turn conversation:
|
|   Single:  ['q' => '...', <expectations>]
|   Chain:   ['name' => '...', 'steps' => [ ['q'=>'...', <exp>], ['q'=>'...', <exp>] ]]
|            (steps run in ONE chat, so follow-ups carry context)
|
| Expectations (all optional — omit for "must return some data"):
|   'min_rows'     => N            answer must contain >= N rows
|   'count_min'    => N            count answer value must be >= N
|   'intent'       => 'substr'     planned intent must contain this
|   'contains'     => 'str'|[...]  assistant text must contain all of these
|   'not_contains' => 'str'|[...]  assistant text must contain none of these
|   'note'         => '...'        human description / the bug it guards
|
| Seed new cases from real questions in ai_query_logs — especially any that
| returned 0/empty in production, and any follow-up the user reports.
|
*/

return [
    'cases' => [

        // =====================================================================
        // MULTI-TURN FOLLOW-UPS  (context must carry between turns)
        // =====================================================================
        [
            'name'  => 'Department count then details of those projects',
            'note'  => 'Reported bug: "details of this projects" said "couldn\'t find a project matching this".',
            'steps' => [
                ['q' => 'How many projects are in the Deal Review department?', 'count_min' => 1],
                ['q' => 'Show me the details of this projects', 'min_rows' => 1, 'not_contains' => ["couldn't find", 'could not find', 'matching "this"']],
            ],
        ],
        [
            'name'  => 'Deal review (no "department" word) then "this N projects"',
            'note'  => 'Real failing logs: "How many total projects in deal review?" + "tell me the details of this 4 projects".',
            'steps' => [
                ['q' => 'How many total projects in deal review?', 'count_min' => 1],
                ['q' => 'tell me the details of this 4 projects', 'min_rows' => 1, 'not_contains' => ["couldn't find", 'could not find', 'matching "this"']],
            ],
        ],
        [
            'name'  => 'Customer-wise projects then show those',
            'steps' => [
                ['q' => 'Customer wise projects', 'min_rows' => 1],
                ['q' => 'show me details of these', 'not_contains' => ["couldn't find", 'could not find']],
            ],
        ],
        [
            'name'  => 'All customers then how many',
            'steps' => [
                ['q' => 'Show me all customers', 'min_rows' => 1],
                ['q' => 'how many are they in total?', 'not_contains' => ["couldn't", 'could not', 'I can plan CRM']],
            ],
        ],
        [
            'name'  => 'In-progress projects then their customers',
            'steps' => [
                ['q' => 'Show in-progress projects', 'min_rows' => 1],
                ['q' => 'who are the customers for these projects?', 'not_contains' => ["couldn't find", 'could not find']],
            ],
        ],
        [
            'name'  => 'Pending tickets then sort/filter follow-up',
            'steps' => [
                ['q' => 'Show pending tickets', 'min_rows' => 1],
                ['q' => 'only the high priority ones', 'not_contains' => ["couldn't", 'could not']],
            ],
        ],

        // --- Project-detail gate must not hijack non-project / bare-code / refs ---
        [
            'name'  => 'Ticket request not hijacked by project-detail gate',
            'note'  => 'Bug: "details of High priority ticket" said "couldn\'t find a project matching...".',
            'steps' => [
                ['q' => 'Show me the details of High priority ticket details', 'min_rows' => 1, 'not_contains' => ["couldn't find", 'could not find', 'matching "', 'check the name or code']],
            ],
        ],
        [
            'name'  => 'Disambiguation then bare code reply resolves the project',
            'note'  => 'Bug: replying "1048" was treated as chit-chat ("what is 1048?").',
            'steps' => [
                ['q' => 'Show me the project details of Yunjiao Guan', 'min_rows' => 1],
                ['q' => '1048', 'min_rows' => 1, 'not_contains' => ['what is', 'could you tell me', 'what you need', "couldn't"]],
            ],
        ],
        [
            'name'  => '"this project" resolves to the project just discussed',
            'note'  => 'Bug: "complete details of this project" dumped raw columns / "No records".',
            'steps' => [
                ['q' => 'Show me the summary of Yunjiao-Guan - 61st Ave', 'min_rows' => 1],
                ['q' => 'complete details of this project', 'min_rows' => 1, 'not_contains' => ['No records', "couldn't find", 'could not find']],
            ],
        ],

        [
            'name'  => 'Multi-intent message is split into separate answers',
            // Bug: "financing details of this project ALSO X" was treated as one query;
            // the LLM now splits it AND resolves "this project" to the named project in
            // BOTH parts. The eval asserts the LAST split answer, so both clauses use a
            // part that has data for this project (financing + tasks) — the previous
            // "logs" clause is legitimately empty for Yunjiao-Guan, which used to mask a
            // false pass (unresolved "this project" returned unfiltered logs).
            'note'  => 'Multi-intent split + "this project" resolved into each part.',
            'steps' => [
                ['q' => 'Show me the summary of Yunjiao-Guan - 61st Ave', 'min_rows' => 1],
                ['q' => 'Show me the financing details of this project and also its tasks', 'min_rows' => 1, 'not_contains' => ['No records', "couldn't find"]],
            ],
        ],

        // --- COMPOUND follow-ups (new constraint added on top of prior query) ---
        [
            'name'  => 'Count then switch to a list of the same',
            'note'  => 'count -> "ab inki list do" must list ONLY those deal-review projects (not all). max_rows guards filter loss.',
            'steps' => [
                ['q' => 'How many projects are in the Deal Review department?', 'count_min' => 1],
                ['q' => 'ab in projects ki list do', 'min_rows' => 1, 'max_rows' => 50, 'not_contains' => ["couldn't find", 'could not find']],
            ],
        ],
        [
            'name'  => 'List then sort follow-up',
            'steps' => [
                ['q' => 'Show all projects', 'min_rows' => 1],
                ['q' => 'sort them by created date', 'min_rows' => 1, 'not_contains' => ["couldn't", 'could not']],
            ],
        ],
        [
            'name'  => 'List then narrow columns follow-up',
            'steps' => [
                ['q' => 'show all employees', 'min_rows' => 1],
                ['q' => 'sirf unke naam aur email dikhao', 'min_rows' => 1, 'not_contains' => ["couldn't", 'could not']],
            ],
        ],
        [
            'name'  => 'Status summary then refine to one status as a list',
            'steps' => [
                ['q' => 'Tickets status wise', 'min_rows' => 1],
                ['q' => 'ab sirf resolved wale tickets ki list do', 'not_contains' => ["couldn't", 'could not']],
            ],
        ],

        // =====================================================================
        // FIELD / TERM EXPLANATIONS  (deterministic, no data needed)
        // =====================================================================
        ['q' => 'What is PTO?', 'contains' => 'Permission', 'note' => 'Glossary term.'],
        ['q' => 'What is NTP?', 'contains' => 'Notice'],
        ['q' => 'meter_spot_result kya hai?', 'contains' => 'meter'],
        ['q' => 'What values can ticket status have?', 'contains' => ['Pending', 'Resolved']],
        ['q' => 'explain the contract amount field', 'contains' => 'contract'],
        ['q' => 'acceptance status ki values kya hain?', 'contains' => ['Approved', 'Rejected']],
        ['q' => 'what does the field solar_install_date mean?', 'contains' => 'install'],

        // =====================================================================
        // CORE DATA QUERIES  (varied phrasings — should always return data)
        // =====================================================================
        ['q' => 'How many projects are there in total?', 'count_min' => 1],
        ['q' => 'Show me all customers', 'min_rows' => 1],
        ['q' => 'list all departments', 'min_rows' => 1],
        ['q' => 'show all sub departments', 'min_rows' => 1],
        ['q' => 'show all employees', 'min_rows' => 1],
        ['q' => 'list sales partners', 'min_rows' => 1],
        ['q' => 'Tickets status wise', 'min_rows' => 1],
        ['q' => 'projects by status', 'min_rows' => 1],

        // --- Phrasing variants that previously mis-routed to count 0 ----------
        ['q' => 'Show the project count department-wise', 'min_rows' => 2, 'note' => 'Was count 0; now Text-to-SQL.'],
        ['q' => 'Show the project count as per department', 'min_rows' => 2],
        ['q' => 'project total numbers department wise', 'min_rows' => 2],
        ['q' => 'How many projects in the Deal Review department?', 'count_min' => 1],
        [
            // Soft-delete guard: list must match the active count (4), not include
            // soft-deleted rows (was 10). max_rows tolerates small data growth.
            'q'        => 'Show me the details of all deal review department projects',
            'min_rows' => 1,
            'max_rows' => 6,
            'note'     => 'Bug: list included soft-deleted projects (10) while count was 4. Now deleted_at filtered.',
        ],

        // =====================================================================
        // NEWLY-EXPOSED MODULES  (added to the schema in this work)
        // =====================================================================
        ['q' => 'list all utility companies', 'min_rows' => 1, 'note' => 'Newly exposed lookup table.'],
        ['q' => 'show all inverter types', 'min_rows' => 1],
        ['q' => 'show all module types', 'min_rows' => 1],
        ['q' => 'list sub contractors', 'intent' => 'sub_contractor', 'note' => 'Routing check (table may be small/empty).'],
        ['q' => 'show scheduled site surveys', 'intent' => 'survey', 'note' => 'Routing check — 0 "scheduled" rows is a valid answer. Intent label is free-form (site_surveys_scheduled_list / survey_scheduled_list), so match the stable "survey" substring.'],

        // =====================================================================
        // NULL / MISSING DATA FILTERS
        // =====================================================================
        [
            'q'    => 'Show me those customers whose phone number data is missing',
            'note' => 'IS NULL filter on customers.phone. Currently 0 results (all customers have phones) — correct answer is an empty result, not an error or wrong data.',
            'not_contains' => ['error', 'exception', 'something went wrong'],
        ],
        [
            'q'    => 'Customers whose phone is missing',
            'note' => 'Short variant — same IS NULL filter. 0 rows is correct.',
            'not_contains' => ['Did you mean', 'error', 'exception'],
        ],
        [
            'q'    => 'Show projects where NTP date is missing',
            'note' => 'IS NULL filter on projects.ntp_approval_date — should return rows (many projects have no NTP date yet).',
            'min_rows' => 1,
        ],

        // =====================================================================
        // FINANCING METHOD GROUPING
        // =====================================================================
        [
            'q'        => 'Show the projects as per Financing methods',
            'min_rows' => 1,
            'note'     => 'Should GROUP BY finance_options.name and return one row per financing method with project count, NOT a flat per-project list.',
        ],

        // =====================================================================
        // NAMED PROJECT DETAIL  (deterministic handler)
        // =====================================================================
        [
            'q'        => 'Show me the details of Yunjiao-Guan - 61st Ave project',
            'min_rows' => 1,
            'note'     => 'Project detail: formatted text + per-department days table. Update name if removed.',
        ],

        // =====================================================================
        // ADDERS ON A PROJECT  (+ loose / token-wise name matching)
        // =====================================================================
        [
            'q'            => 'show me the adders of project Yunjiao Guan 61st Ave',
            'min_rows'     => 1,
            'not_contains' => ['No records found', 'data may not exist'],
            'note'         => 'Reported bug: name typed without hyphens ("Yunjiao Guan 61st Ave") vs stored "Yunjiao-Guan - 61st Ave" made a single-phrase LIKE return 0 rows. Must token-match the name (AND of per-token LIKEs) AND read the deal adders from customer_adders (not the adders price catalogue). Project 272 / customer 282 has 1 customer_adder.',
        ],
    ],
];
