<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class AiQueryPlannerService
{
    // Removed hardcoded intents - now AI can generate any intent dynamically

    private const ALLOWED_ANSWER_TYPES = [
        'text',
        'table',
        'card',
        'count',
    ];

    public function __construct(
        private readonly OpenAiService $openAiService,
        private readonly AiSchemaService $aiSchemaService,
        private readonly AiPermissionService $aiPermissionService,
        private readonly AiProfiler $profiler
    ) {
    }

    public function plan(string $question, User $user, ?array $previousContext = null, string $conversationMemory = ''): array
    {
        $this->profiler->stage('planner');

        if ($this->isWriteOperationQuestion($question)) {
            return [
                'plan' => array_merge($this->unknownPlan(), [
                    'fallback_message' => 'I can only read CRM data. Insert, update, delete, and other write operations are not allowed.',
                ]),
                'openai' => $this->syntheticOpenAiResponse(),
            ];
        }

        // Conversational follow-up: "details of those projects", "list them",
        // "in me se sirf hold wale", "sort by date". We mark it data_explorer so
        // the pipeline routes to Text-to-SQL, which — given the conversation
        // memory — resolves the reference AND applies any new constraint the user
        // adds. The structured plan below remains as a fallback if SQL fails.
        if ($previousContext && $this->isFollowUpReference($question)) {
            $followUp = $this->buildFollowUpPlan($question, $previousContext);

            if ($followUp) {
                return [
                    'plan' => $this->sanitizePlan($this->withHybridMetadata($followUp, 'data_explorer', 1.0), $user),
                    'openai' => $this->syntheticOpenAiResponse(),
                ];
            }
        }

        $inferredPlan = $this->inferKnownPlan($question);

        if ($inferredPlan) {
            return [
                'plan' => $this->sanitizePlan($this->withHybridMetadata($inferredPlan, 'fixed_action', 1.0), $user),
                'openai' => $this->syntheticOpenAiResponse(),
            ];
        }

        // Plan cache: for a self-contained question (no conversation memory) an
        // identical question from a user with the same permissions yields the same
        // sanitized plan. Reuse it to skip the expensive gpt-4.1 planner call. The
        // plan carries no user-specific row data — row scoping is applied later —
        // so this never widens access. Keyed by permission signature so scoped
        // roles never see another permission class's plan.
        $cacheKey = $conversationMemory === '' ? $this->planCacheKey($question, $user) : null;

        if ($cacheKey !== null && ($cachedPlan = $this->cachedPlan($cacheKey)) !== null) {
            $cachedPlan['original_question'] = $question;

            return [
                'plan' => $cachedPlan,
                'openai' => $this->syntheticOpenAiResponse(),
            ];
        }

        $response = $this->openAiService->createJsonResponse(
            $this->instructions(),
            [
                'question' => $question,
                'conversation_memory' => $conversationMemory !== '' ? $conversationMemory : '(no prior conversation)',
                'user_role' => $this->userRole($user),
                'allowed_schema' => $this->schemaForPlanner(),
                'crm_module_hints' => $this->moduleHints(),
                'required_json_format' => $this->unknownPlan(),
                'examples' => [
                    [
                        'question' => 'Mere assigned projects kitne hain?',
                        'expected_plan' => [
                            'answer_type' => 'count',
                            'intent' => 'project_count',
                            'tables' => ['projects'],
                            'columns' => ['id'],
                            'group_by' => [],
                            'filters' => [],
                            'requires_finance_access' => false,
                            'sql' => null,
                            'fallback_message' => null,
                        ],
                    ],
                    [
                        'question' => 'Deal Review department me is waqt kitne projects hain?',
                        'expected_plan' => [
                            'answer_type' => 'count',
                            'intent' => 'project_count',
                            'tables' => ['projects', 'departments'],
                            'columns' => ['id', 'name'],
                            'group_by' => [],
                            'filters' => [
                                [
                                    'column' => 'name',
                                    'operator' => 'like',
                                    'value' => 'deal review',
                                ],
                            ],
                            'requires_finance_access' => false,
                            'sql' => null,
                            'fallback_message' => null,
                        ],
                    ],
                    [
                        'question' => 'Mere projects status wise show karo',
                        'expected_plan' => [
                            'answer_type' => 'table',
                            'intent' => 'project_status_summary',
                            'tables' => ['projects', 'tasks'],
                            'columns' => ['id', 'status'],
                            'group_by' => ['status'],
                            'filters' => [],
                            'requires_finance_access' => false,
                            'sql' => null,
                            'fallback_message' => null,
                        ],
                    ],
                    [
                        'question' => 'In-Progress projects show karo',
                        'expected_plan' => [
                            'answer_type' => 'table',
                            'intent' => 'crm_list',
                            'tables' => ['tasks', 'projects', 'departments', 'sub_departments'],
                            'columns' => ['project_name', 'code', 'status', 'name'],
                            'group_by' => [],
                            'filters' => [
                                [
                                    'column' => 'status',
                                    'operator' => 'like',
                                    'value' => 'In-Progress',
                                ],
                            ],
                            'requires_finance_access' => false,
                            'sql' => null,
                            'fallback_message' => null,
                        ],
                    ],
                    [
                        'question' => 'Total project count department or subdepartment name ke sath show karo',
                        'expected_plan' => [
                            'answer_type' => 'table',
                            'intent' => 'project_department_summary',
                            'tables' => ['projects', 'departments', 'sub_departments'],
                            'columns' => ['department_id', 'sub_department_id', 'name'],
                            'filters' => [],
                            'requires_finance_access' => false,
                            'sql' => null,
                            'fallback_message' => null,
                        ],
                    ],
                    [
                        'question' => 'Project customer info show karo',
                        'expected_plan' => [
                            'answer_type' => 'card',
                            'intent' => 'project_customer',
                            'tables' => ['projects', 'customers'],
                            'columns' => ['project_name', 'code', 'first_name', 'last_name', 'email', 'phone', 'city', 'state'],
                            'group_by' => [],
                            'filters' => [],
                            'requires_finance_access' => false,
                            'sql' => null,
                            'fallback_message' => null,
                        ],
                    ],
                    [
                        'question' => 'Tickets status wise show karo',
                        'expected_plan' => [
                            'answer_type' => 'table',
                            'intent' => 'ticket_status',
                            'tables' => ['service_tickets'],
                            'columns' => ['status'],
                            'group_by' => ['status'],
                            'filters' => [],
                            'requires_finance_access' => false,
                            'sql' => null,
                            'fallback_message' => null,
                        ],
                    ],
                    [
                        'question' => 'Priority wise tickets summary show karo',
                        'expected_plan' => [
                            'answer_type' => 'table',
                            'intent' => 'crm_group_summary',
                            'tables' => ['service_tickets'],
                            'columns' => ['priority'],
                            'group_by' => ['priority'],
                            'filters' => [],
                            'requires_finance_access' => false,
                            'sql' => null,
                            'fallback_message' => null,
                        ],
                    ],
                    [
                        'question' => 'How many tickets created by users? Show user name and count of each status.',
                        'expected_plan' => [
                            'answer_type' => 'table',
                            'intent' => 'ticket_creator_status_summary',
                            'tables' => ['service_tickets', 'users'],
                            'columns' => ['user_id', 'name', 'status'],
                            'group_by' => ['name', 'status'],
                            'filters' => [],
                            'requires_finance_access' => false,
                            'sql' => null,
                            'fallback_message' => null,
                        ],
                    ],
                    [
                        'question' => 'Is project ki financing show karo',
                        'expected_plan' => [
                            'answer_type' => 'card',
                            'intent' => 'finance_summary',
                            'tables' => ['projects', 'customers', 'customer_finances', 'finance_options'],
                            'columns' => ['project_name', 'code', 'finance_option_id', 'contract_amount', 'dealer_fee_amount', 'commission'],
                            'group_by' => [],
                            'filters' => [],
                            'requires_finance_access' => true,
                            'sql' => null,
                            'fallback_message' => null,
                        ],
                    ],
                    [
                        'question' => 'Profitability report dikhao',
                        'expected_plan' => [
                            'answer_type' => 'table',
                            'intent' => 'profitability_report',
                            'tables' => ['customers', 'projects', 'sales_partners', 'customer_finances'],
                            'columns' => ['first_name', 'last_name', 'name', 'solar_install_date', 'contract_amount', 'dealer_fee_amount', 'redline_costs', 'adders', 'actual_material_cost', 'actual_labor_cost', 'actual_permit_fee', 'office_cost'],
                            'group_by' => [],
                            'filters' => [],
                            'requires_finance_access' => true,
                            'sql' => null,
                            'fallback_message' => null,
                        ],
                    ],
                    [
                        'question' => 'Profitability report show kro from 1st April 2026 to 30th April 2026',
                        'expected_plan' => [
                            'answer_type' => 'table',
                            'intent' => 'profitability_report_by_date_range',
                            'tables' => ['customers', 'projects', 'sales_partners', 'customer_finances'],
                            'columns' => ['first_name','last_name', 'name','solar_install_date','contract_amount','dealer_fee_amount','redline_costs','adders','actual_material_cost','actual_labor_cost','actual_permit_fee','office_cost'],
                            'group_by' => [],
                            'filters' => [
                                [
                                    'table' => 'projects',
                                    'column' => 'solar_install_date',
                                    'operator' => '>=',
                                    'value' => '2026-04-01',
                                ],
                                [
                                    'table' => 'projects',
                                    'column' => 'solar_install_date',
                                    'operator' => '<=',
                                    'value' => '2026-04-30',
                                ],
                            ],
                            'requires_finance_access' => true,
                            'sql' => null,
                            'fallback_message' => null,
                        ],
                    ],
                    [
                        'question' => 'Customer wise revenue show karo',
                        'expected_plan' => [
                            'answer_type' => 'table',
                            'intent' => 'customer_revenue',
                            'tables' => ['projects', 'customers', 'customer_finances'],
                            'columns' => ['customer_id', 'first_name', 'last_name', 'contract_amount'],
                            'group_by' => ['customer_id', 'first_name', 'last_name'],
                            'filters' => [],
                            'requires_finance_access' => true,
                            'sql' => null,
                            'fallback_message' => null,
                        ],
                    ],
                    [
                        'question' => 'Show customers from California',
                        'expected_plan' => [
                            'answer_type' => 'table',
                            'intent' => 'crm_list',
                            'tables' => ['customers'],
                            'columns' => ['first_name', 'last_name', 'email', 'phone', 'city', 'state'],
                            'group_by' => [],
                            'filters' => [
                                [
                                    'column' => 'state',
                                    'operator' => 'like',
                                    'value' => 'California',
                                ],
                            ],
                            'requires_finance_access' => false,
                            'sql' => null,
                            'fallback_message' => null,
                        ],
                    ],
                    [
                        'question' => 'User wise ticket count by status',
                        'expected_plan' => [
                            'answer_type' => 'table',
                            'intent' => 'crm_group_summary',
                            'tables' => ['service_tickets', 'users'],
                            'columns' => ['name', 'status'],
                            'group_by' => ['name', 'status'],
                            'filters' => [],
                            'requires_finance_access' => false,
                            'sql' => null,
                            'fallback_message' => null,
                        ],
                    ],
                    [
                        'question' => 'Employees ki list show kro or ye bhi btao k kis employee ko kitne departments allowed hain',
                        'expected_plan' => [
                            'answer_type' => 'table',
                            'intent' => 'employee_department_list',
                            'tables' => ['employees', 'employee_departments', 'departments'],
                            'columns' => ['name', 'email', 'phone'],
                            'group_by' => ['name', 'email', 'phone'],
                            'filters' => [],
                            'requires_finance_access' => false,
                            'sql' => null,
                            'fallback_message' => null,
                        ],
                    ],
                ],
            ],
            1200,
            $this->jsonSchema(),
            $this->openAiService->sqlModel()
        );

        $plan = $this->sanitizePlan($this->legacyPlanFromHybrid($response['json']), $user);

        if (($plan['intent'] ?? 'unknown') === 'unknown' && $this->looksLikeCrmDataQuestion($question)) {
            $retryResponse = $this->openAiService->createJsonResponse(
                $this->instructions() . "\n\nSECOND PASS: The previous plan was unsupported. Re-check allowed_schema and crm_module_hints carefully. If the question is about any allowed CRM module, return mode data_explorer with generic intent crm_list, crm_count, crm_group_summary, or crm_detail. Return unsupported only when no allowed table or column can answer it.",
                [
                    'question' => $question,
                    'conversation_memory' => $conversationMemory !== '' ? $conversationMemory : '(no prior conversation)',
                    'user_role' => $this->userRole($user),
                    'allowed_schema' => $this->schemaForPlanner(),
                    'crm_module_hints' => $this->moduleHints(),
                    'previous_plan' => $response['json'],
                    'required_json_format' => $this->unknownPlan(),
                ],
                1200,
                $this->jsonSchema(),
                $this->openAiService->sqlModel()
            );

            $retryPlan = $this->sanitizePlan($this->legacyPlanFromHybrid($retryResponse['json']), $user);

            if (($retryPlan['intent'] ?? 'unknown') !== 'unknown') {
                $response = $retryResponse;
                $plan = $retryPlan;
            }
        }
        
        // Store original question in plan for AI SQL generation
        $plan['original_question'] = $question;

        if ($inferredPlan && (($plan['intent'] ?? 'unknown') === 'unknown' || $this->inferredPlanIsMoreSpecific($plan, $inferredPlan))) {
            $plan = $this->sanitizePlan($this->withHybridMetadata($inferredPlan, 'fixed_action', 1.0), $user);
        }

        // Only cache a usable plan (a real intent). Never cache unknown /
        // clarification / permission-denied outcomes, so a transient miss is not
        // frozen in for everyone with the same permissions.
        if ($cacheKey !== null && ($plan['intent'] ?? 'unknown') !== 'unknown') {
            $this->storePlan($cacheKey, $plan);
        }

        return [
            'plan' => $plan,
            'openai' => $response,
        ];
    }

    /**
     * Cache key for a planned question: the normalized question plus a signature of
     * everything sanitizePlan() depends on (roles + finance/profitability access).
     * Users with the same signature share a sanitized plan; row-level scoping is
     * applied downstream, so no user-specific data is ever shared via the cache.
     */
    private function planCacheKey(string $question, User $user): string
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', mb_strtolower($question)));

        $signature = implode(',', $user->getRoleNames()->sort()->values()->all())
            .'|fin:'.($this->aiPermissionService->canAccessFinance($user) ? 1 : 0)
            .'|prof:'.($this->aiPermissionService->canAccessProfitability($user) ? 1 : 0);

        return 'ai_plan:'.md5($normalized.'||'.$signature);
    }

    private function cachedPlan(string $cacheKey): ?array
    {
        if ((int) config('ai.planner.cache_ttl', 0) <= 0) {
            return null;
        }

        $plan = Cache::get($cacheKey);

        return is_array($plan) ? $plan : null;
    }

    private function storePlan(string $cacheKey, array $plan): void
    {
        $ttl = (int) config('ai.planner.cache_ttl', 0);

        if ($ttl > 0) {
            Cache::put($cacheKey, $plan, $ttl);
        }
    }

    /**
     * True when the question refers back to the previous result instead of naming
     * its own subject — "details of those projects", "list them", "the same", etc.
     * Kept conservative (requires a clear back-reference) so it never hijacks a new,
     * self-contained question.
     */
    private function isFollowUpReference(string $question): bool
    {
        $q = mb_strtolower($question);

        return (bool) preg_match(
            // Bare back-reference pronouns (NOT "this"/"that" — those are only a
            // back-reference when followed by a record noun, handled below, so we
            // don't hijack phrases like "created this month").
            '/\b(those|these|them|they|their)\b'
            . '|\bthe same\b'
            . '|\b(above|previous|last)\s+(result|results|query|one|ones|projects|records|data|list|report)\b'
            // "this/those + (optional one word/number) + record noun":
            // matches "those projects", "this 4 projects", "these top tickets",
            // but not "this month" (month is not a record noun) or longer phrases.
            . '|\b(this|that|these|those)\s+(?:\w+\s+){0,1}(project|projects|record|records|customer|customers|ticket|tickets|data|result|results|list|report|ones)\b'
            // Roman-Urdu demonstratives "in/un/inn/unn + (optional word) + noun":
            // "in projects", "un tickets", "inn 4 customers".
            . '|\b(in|un|inn|unn)\s+(?:\w+\s+){0,1}(project|projects|record|records|customer|customers|ticket|tickets|log|logs)\b'
            . '|\b(in|un)\s*(mein|me)\s+se\b'
            // Roman-Urdu pronouns: inka/inki/inko/inhe/inhein (+ un- variants).
            . '|\b(inka|inki|inko|inhe|inhein|unka|unki|unko|unhe|unhein)\b/u',
            $q
        );
    }

    /**
     * Build a plan for a follow-up by inheriting the previous turn's tables and
     * filters, converting a count/summary into a detailed list. Returns null when
     * there is nothing usable to inherit (caller then plans normally).
     *
     * @param  array{tables?:array,filters?:array,requires_finance_access?:bool}  $previousContext
     */
    private function buildFollowUpPlan(string $question, array $previousContext): ?array
    {
        $tables  = array_values(array_filter((array) ($previousContext['tables'] ?? [])));
        $filters = array_values(array_filter((array) ($previousContext['filters'] ?? []), 'is_array'));

        if ($tables === []) {
            return null;
        }

        $requiresFinance = (bool) ($previousContext['requires_finance_access'] ?? false);

        // Project-centric follow-up → list the matching projects with their customer
        // details. We keep the previous tables (so the inherited filters still resolve
        // to the same columns) and only add `customers`, which has no `name` column
        // and therefore cannot make a department/sub-department name filter ambiguous.
        if (in_array('projects', $tables, true)) {
            return [
                'answer_type'             => 'table',
                'intent'                  => 'crm_list',
                'tables'                  => array_values(array_unique(array_merge($tables, ['customers']))),
                'columns'                 => ['project_name', 'code', 'first_name', 'last_name'],
                'group_by'                => [],
                'filters'                 => $filters,
                'requires_finance_access' => $requiresFinance,
                'sql'                     => null,
                'fallback_message'        => null,
            ];
        }

        // Generic follow-up → re-list the same entity with the same filters.
        return [
            'answer_type'             => 'table',
            'intent'                  => 'crm_list',
            'tables'                  => $tables,
            'columns'                 => array_values(array_filter((array) ($previousContext['columns'] ?? []))),
            'group_by'                => [],
            'filters'                 => $filters,
            'requires_finance_access' => $requiresFinance,
            'sql'                     => null,
            'fallback_message'        => null,
        ];
    }

    public function looksLikeCrmDataQuestion(string $question): bool
    {
        $question = mb_strtolower($question);

        // Clearly non-CRM: pure greetings or thanks — skip pipeline
        $greetingsOnly = ['hello', 'hi', 'hey', 'thanks', 'thank you', 'shukriya', 'ok', 'okay', 'bye', 'goodbye'];
        $trimmed = trim($question, " \t\n\r.,!?");
        if (in_array($trimmed, $greetingsOnly, true)) {
            return false;
        }

        $keywords = [
            // Core CRM entities
            'project', 'projects',
            'customer', 'customers',
            'client', 'clients',
            'ticket', 'tickets',
            'task', 'tasks',
            'department', 'departments',
            'role', 'roles',
            'permission', 'permissions',
            'subdepartment', 'sub department', 'sub_department',
            'employee', 'employees',
            'user', 'users',
            'sales partner', 'salespartner',
            'sub-contractor', 'subcontractor',
            'sub contractor',

            // Solar installation domain
            'solar', 'install', 'installation', 'installations',
            'panel', 'panels', 'battery', 'batteries',
            'inverter', 'inverters',
            'monitoring', 'monitor',
            'utility', 'utility company',
            'adder', 'adders',
            'production', 'production value',
            'system', 'systems',

            // Project milestone / date fields
            'permit', 'permitting', 'permited',
            'inspection', 'inspections',
            'survey', 'site survey',
            'ntp', 'pto',
            'hoa', 'ahj', 'mpu',
            'meter spot', 'meter',
            'placards', 'placard',
            'rough inspection', 'final inspection', 'fire inspection',
            'fire review',
            'coc', 'packet',

            // Project modules
            'follow up', 'followup', 'follow-up',
            'call log', 'calllog', 'call-log',
            'design', 'design detail',
            'acceptance', 'project acceptance',
            'file', 'files', 'attachment',

            // Finance / payments
            'finance', 'financing',
            'profitability', 'profitablity',
            'report', 'reports',
            'expense', 'expenses',
            'payment', 'payments',
            'transaction', 'transactions',
            'remittance', 'remitted',
            'deduction', 'deductions',
            'milestone', 'milestones',
            'payee', 'amount', 'amounts',
            'cost', 'costs',
            'profit', 'revenue',
            'commission', 'commissions',
            'contract', 'contract amount',
            'dealer fee', 'holdback',
            'loan', 'loan term', 'apr',
            'redline', 'adders',

            // Status and metrics
            'status', 'statuses',
            'pending', 'resolved', 'active',
            'count', 'total', 'summary',
            'forecast', 'override',
            'overwrite', 'overrider',
            'priority', 'priorities',
            'ghost', 'lane',
            'pre-inspection', 'pre inspection',

            // Activity / audit log
            'activity', 'activity log', 'audit', 'audit log',
            'change log', 'changelog', 'action log',
            'who moved', 'who changed', 'who approved',
            'history log', 'move log',

            // Urdu / mixed-language CRM queries
            'mere', 'mery', 'meri', 'mera',
            'kitne', 'kitni', 'kitna',
            'dikhao', 'dikha', 'dikhai',
            'batao', 'bata', 'batana',
            'list karo', 'list kare', 'list karta',
            'kaunse', 'kaun se',
            'assigned', 'assign',
            'kab', 'kb',
            'sab', 'sabka', 'sab ka',
            'show karo', 'show kare',
            'report karo', 'report nikalo',
            'nikalo', 'nikaal',
        ];

        foreach ($keywords as $keyword) {
            if (str_contains($question, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function instructions(): string
    {
        return <<<'PROMPT'
You are a safe CRM query planner for a solar installation company's Laravel CRM.

Return structured JSON only. Do not return markdown. Do not answer the user's question directly.
Do not generate SQL. Never include a raw SQL string.
Use only the supplied allowed_schema tables and allowed columns.

## CONVERSATION MEMORY
- The input includes `conversation_memory`: the recent turns with the intent, filters, SQL and result size the assistant used.
- If the new question is a follow-up (e.g. "those projects", "in me se", "show their customers", "only the hold ones", "sort by date"), resolve the reference against that memory and KEEP the previous filters/tables, adding any new constraint the user now asks for. Do not ask the user to repeat what they already said.

## CORE RULES
- Be FLEXIBLE and HELPFUL. If the question can be answered using allowed_schema, answer it.
- Before returning unsupported/unknown, carefully check allowed_schema and crm_module_hints for related tables, relationships, searchable columns, and date fields.
- Prefer generic intents when no exact report exists: crm_list, crm_count, crm_group_summary, crm_detail.
- For project financing use: projects + customers + customer_finances + finance_options.
- Never invent tables or columns not in allowed_schema.

## SOLAR CRM DOMAIN KNOWLEDGE — Column Mappings
This is a solar energy installation company CRM. Use these term-to-column mappings:

### Project Milestone Dates (table: projects)
- "solar installation" / "solar install" / "installed" → solar_install_date
- "battery installation" / "battery install" → battery_install_date
- "NTP" / "notice to proceed" / "ntp approval" → ntp_approval_date
- "PTO" / "permission to operate" / "pto submission" → pto_submission_date, pto_approval_date
- "permitting" / "permit submitted" → permitting_submittion_date
- "permit approved" / "permit approval" → permitting_approval_date
- "site survey" / "survey" → site_survey_link (or project date context)
- "HOA" / "hoa approval" / "homeowners association" → hoa_approval_date, hoa_approval_request_date, hoa
- "AHJ" / "authority having jurisdiction" → ahj
- "MPU" / "main panel upgrade" → mpu_required, mpu_install_date
- "meter spot" / "meter" → meter_spot_requestd_date, meter_spot_result
- "rough inspection" → rough_inspection_date
- "final inspection" → final_inspection_date
- "fire inspection" / "fire review" → fire_inspection_date, fire_review_required
- "COC" / "coc packet" → coc_packet_mailed_out_date
- "placards" → placards_ordered, placards_note
- "monitoring" → monitoring_link
- "production value" → production_value_achieved

### Project Status Terms
- "ghost project" / "pre-inspection lane" / "ghost" → projects where sub_department is "Pre-Inspection Lane"
- "active projects" → filter status = 'active' or check tasks/sub_departments

### Finance / Cost Columns (table: customer_finances, projects)
- "contract amount" / "deal amount" → contract_amount (customer_finances)
- "dealer fee" → dealer_fee, dealer_fee_amount
- "commission" → commission
- "redline" / "redline cost" → redline_costs
- "adders" → adders
- "holdback" → holdback_amount
- "office cost" → office_cost (projects, sensitive)
- "labor cost" → actual_labor_cost (projects, sensitive)
- "material cost" → actual_material_cost (projects, sensitive)
- "permit fee" → actual_permit_fee (projects, sensitive)

### Related Modules (separate tables)
- "follow up" / "followup" / "follow-up" → table: project_follow_ups
- "call log" / "call logs" / "calls" → table: project_call_logs
- "project design" / "design details" → table: project_design_details
- "acceptance" / "project acceptance" → table: project_acceptances (status: 0=pending, 1=approved, 2=rejected)
- "project files" / "documents" → table: project_files
- "loan" / "loan terms" → table: loan_terms
- "APR" / "interest rate" → table: loan_aprs
- "transactions" / "remittance" / "payments" → table: account_transactions

### Customer Fields
- "customer name" → customers.first_name + customers.last_name
- "customer location" / "city" / "state" → customers.city, customers.state
- "solar system size" / "panel count" → customers.panel_qty
- "inverter" → customers.inverter_type_id, customers.inverter_qty
- "module" → customers.module_type_id, customers.module_value
- "sold date" / "sale date" → customers.sold_date
- "language" → customers.preferred_language

### People / Assignments
- "sales partner" / "sales rep" → projects.sales_partner_user_id → users table
- "sub-contractor" / "subcontractor" → projects.sub_contractor_user_id → users table
- "assigned employee" → tasks.employee_id → employees table
- "ticket creator" → service_tickets.user_id → users table
- "ticket assigned to" → service_tickets.assigned_to → users table

## CURRENT USER CONTEXT
When user says "my", "mere", "meri", "mera", "mine", "assigned to me":
- Projects: filter tasks.employee_id = current_employee.id (join tasks + projects)
- Tickets: filter service_tickets.user_id = current_user.id OR assigned_to = current_user.id
- Tasks: filter tasks.employee_id = current_employee.id

## ANSWER TYPE GUIDE
- Single number result → answer_type: "count"
- List of records → answer_type: "table"
- Grouped/summarized data → answer_type: "table" with group_by
- Single project/customer details → answer_type: "card"

## OUTPUT SHAPE
{
  "mode": "fixed_action|data_explorer|clarification_required|unsupported",
  "confidence": 0.85,
  "entities": [],
  "selected_columns": [{"table": "projects", "columns": ["id", "project_name"]}],
  "filters": [],
  "relationships": [],
  "sort": [],
  "limit": 50,
  "answer_type": "text|table|card|count",
  "intent": "<descriptive string: project_count, installation_list, permit_status, etc.>",
  "needs_clarification": false,
  "clarification_question": null,
  "fallback_message": null
}

Allowed filter operators: =, !=, >, >=, <, <=, like, in, between
For current user: use "current_user.id" or "current_employee.id" as filter value.

## SECURITY
- Never include: password, remember_token, api_token, token, secret
- Reject write operations (insert, update, delete, create, drop, alter, truncate, restore, approve, move, assign, change) → return unknown
- Finance columns (cost, amount, commission, dealer_fee, redline, holdback) → set requires_finance_access: true

## ACTIVITY LOG MODULE
The `activity_log` table records all project activity and department moves.
- Use for: "who moved project X", "project activity", "department change history", "action log"
- Key columns: event, description, subject_id (= project id), causer_id (= user id), properties (JSON), created_at
- For lane moves: filter event = 'move' — properties JSON has old_lane and new_lane
- Always join: activity_log.subject_id = projects.id, activity_log.causer_id = users.id

## UNSUPPORTED QUESTIONS
If the question cannot be answered from any table in allowed_schema AND it is not a CRM data question
(e.g., "what is solar energy?", "tell me a joke"), set:
  mode: "unsupported", intent: "unknown", fallback_message: null
The system will then route the question to the general AI assistant automatically.
Do NOT set a fallback_message for unsupported — leave it null so the router can fall back cleanly.

## QUICK EXAMPLES
- "Solar installations this month" → tables: ["projects"], columns: ["project_name", "solar_install_date"], filter: solar_install_date this month
- "How many permits submitted?" → tables: ["projects"], columns: ["id", "permitting_submittion_date"], intent: "permit_count", answer_type: "count"
- "PTO pending projects" → tables: ["projects"], columns: ["project_name", "pto_submission_date", "pto_approval_date"], filter: pto_approval_date IS NULL
- "HOA pending list" → tables: ["projects"], columns: ["project_name", "hoa", "hoa_approval_date"], filter: hoa = 'yes' AND hoa_approval_date IS NULL. IMPORTANT: only projects that REQUIRE HOA (hoa = 'yes') can be HOA-pending; projects with hoa = 'no' are NOT pending and must be excluded.
- "Follow ups this week" → tables: ["project_follow_ups", "projects"], columns: ["project_id", "project_name", "created_at"]
- "Call logs today" → tables: ["project_call_logs", "projects"], columns: ["project_id", "project_name", "created_at"]
- "Acceptance pending" → tables: ["project_acceptances", "projects"], columns: ["project_id", "status", "approved_date"], filter: status = 0
- "Customers from California" → tables: ["customers"], columns: ["first_name", "last_name", "city", "state"], filter: state like "California"
- "Show me all customers" → tables: ["customers"], columns: ["first_name", "last_name", "email", "phone", "city", "state"]
- "Tickets grouped by priority" → tables: ["service_tickets"], columns: ["priority"], group_by: ["priority"], answer_type: "table"
- "Battery installations last month" → tables: ["projects"], columns: ["project_name", "battery_install_date"], filter: battery_install_date last month
- "Who moved project Annie-Ewing last?" → tables: ["activity_log", "projects", "users"], columns: ["description", "created_at", "name"], filter: event = "move", subject matches project
- "What happened on project X yesterday?" → tables: ["activity_log", "projects"], columns: ["description", "event", "created_at"], filter: subject_id = projectId, created_at yesterday
PROMPT;
    }

    private function jsonSchema(): array
    {
        return [
            'type' => 'json_schema',
            'name' => 'hybrid_ai_query_plan',
            'strict' => true,
            'schema' => [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => [
                    'mode',
                    'confidence',
                    'entities',
                    'selected_columns',
                    'relationships',
                    'sort',
                    'group_by',
                    'limit',
                    'answer_type',
                    'intent',
                    'filters',
                    'needs_clarification',
                    'clarification_question',
                    'fallback_message',
                ],
                'properties' => [
                    'mode' => [
                        'type' => 'string',
                        'enum' => ['fixed_action', 'data_explorer', 'clarification_required', 'unsupported'],
                    ],
                    'confidence' => [
                        'type' => 'number',
                        'minimum' => 0,
                        'maximum' => 1,
                    ],
                    'entities' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'selected_columns' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'required' => ['table', 'columns'],
                            'properties' => [
                                'table' => ['type' => 'string'],
                                'columns' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                ],
                            ],
                        ],
                    ],
                    'relationships' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'required' => ['from', 'to'],
                            'properties' => [
                                'from' => ['type' => 'string'],
                                'to' => ['type' => 'string'],
                            ],
                        ],
                    ],
                    'sort' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'required' => ['table', 'column', 'direction'],
                            'properties' => [
                                'table' => ['type' => 'string'],
                                'column' => ['type' => 'string'],
                                'direction' => ['type' => 'string', 'enum' => ['asc', 'desc']],
                            ],
                        ],
                    ],
                    'group_by' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'required' => ['table', 'column'],
                            'properties' => [
                                'table' => ['type' => 'string'],
                                'column' => ['type' => 'string'],
                            ],
                        ],
                    ],
                    'limit' => [
                        'type' => 'integer',
                        'minimum' => 1,
                        'maximum' => 100,
                    ],
                    'answer_type' => [
                        'type' => 'string',
                        'enum' => self::ALLOWED_ANSWER_TYPES,
                    ],
                    'intent' => [
                        'type' => 'string',
                        'description' => 'Short label describing the query intent (e.g., project_count, customer_list, ticket_summary)',
                    ],
                    'filters' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'required' => ['table', 'column', 'operator', 'value'],
                            'properties' => [
                                'table' => ['type' => 'string'],
                                'column' => ['type' => 'string'],
                                'operator' => [
                                    'type' => 'string',
                                    'enum' => ['=', '!=', '>', '>=', '<', '<=', 'like', 'in', 'between'],
                                ],
                                'value' => [
                                    'anyOf' => [
                                        ['type' => 'string'],
                                        ['type' => 'number'],
                                        ['type' => 'boolean'],
                                        ['type' => 'null'],
                                        ['type' => 'array', 'items' => ['type' => ['string', 'number', 'boolean', 'null']]],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'needs_clarification' => [
                        'type' => 'boolean',
                    ],
                    'clarification_question' => [
                        'type' => ['string', 'null'],
                    ],
                    'fallback_message' => [
                        'type' => ['string', 'null'],
                    ],
                ],
            ],
        ];
    }

    private function sanitizePlan(array $plan, User $user): array
    {
        $safe = array_merge($this->unknownPlan(), Arr::only($plan, [
            'mode',
            'confidence',
            'entities',
            'selected_columns',
            'answer_type',
            'intent',
            'tables',
            'columns',
            'group_by',
            'filters',
            'relationships',
            'sort',
            'limit',
            'needs_clarification',
            'clarification_question',
            'requires_finance_access',
            'sql',
            'fallback_message',
        ]));

        if (! in_array($safe['answer_type'], self::ALLOWED_ANSWER_TYPES, true)) {
            $safe['answer_type'] = 'text';
        }

        // Allow any intent string - no hardcoded restrictions
        if (empty($safe['intent']) || ! is_string($safe['intent'])) {
            $safe['intent'] = 'unknown';
        }

        $safe['tables'] = array_values(array_filter((array) $safe['tables'], fn ($table) => $this->aiSchemaService->isTableAllowed($table)));

        foreach ($safe['tables'] as $table) {
            if (! $this->aiPermissionService->canAccessTable($user, $table)) {
                return $this->permissionDeniedPlan();
            }
        }

        foreach ((array) $safe['columns'] as $column) {
            foreach ($safe['tables'] as $table) {
                if ($this->aiSchemaService->isColumnAllowed($table, $column) && ! $this->aiPermissionService->canAccessColumn($user, $table, $column)) {
                    return $this->permissionDeniedPlan();
                }
            }
        }

        $safe['columns'] = array_values(array_filter((array) $safe['columns'], function ($column) use ($safe, $user) {
            foreach ($safe['tables'] as $table) {
                if ($this->aiPermissionService->canAccessColumn($user, $table, $column)) {
                    return true;
                }
            }

            return false;
        }));

        $safe['group_by'] = array_values(array_filter((array) ($safe['group_by'] ?? []), function ($column) use ($safe, $user) {
            foreach ($safe['tables'] as $table) {
                if ($this->aiPermissionService->canAccessColumn($user, $table, $column)) {
                    return true;
                }
            }

            return false;
        }));

        foreach ((array) $safe['filters'] as $filter) {
            if (! is_array($filter) || ! isset($filter['column'])) {
                continue;
            }

            foreach ($safe['tables'] as $table) {
                if ($this->aiSchemaService->isColumnAllowed($table, $filter['column']) && ! $this->aiPermissionService->canAccessColumn($user, $table, $filter['column'])) {
                    return $this->permissionDeniedPlan();
                }
            }
        }

        $safe['filters'] = is_array($safe['filters']) ? array_values(array_filter($safe['filters'], function ($filter) use ($safe, $user) {
            if (! is_array($filter) || ! isset($filter['column'], $filter['operator'])) {
                return false;
            }

            foreach ($safe['tables'] as $table) {
                if ($this->aiPermissionService->canAccessColumn($user, $table, $filter['column'])) {
                    return true;
                }
            }

            return false;
        })) : [];
        $safe['requires_finance_access'] = (bool) $safe['requires_finance_access'];
        $safe['sql'] = null;
        $safe['limit'] = max(1, min((int) ($safe['limit'] ?? 100), 100));

        if ($safe['requires_finance_access'] && ! $this->aiPermissionService->canAccessFinance($user)) {
            return $this->permissionDeniedPlan();
        }

        foreach ($safe['tables'] as $table) {
            if ($this->aiSchemaService->getAccessRule($table) === 'profitability_access' && ! $this->aiPermissionService->canAccessProfitability($user)) {
                return $this->permissionDeniedPlan();
            }
        }

        if ($safe['intent'] === 'unknown' || empty($safe['tables'])) {
            $safe['answer_type'] = 'text';
            $safe['intent'] = 'unknown';
            $safe['tables'] = [];
            $safe['columns'] = [];
            $safe['group_by'] = [];
            $safe['filters'] = [];
            $safe['requires_finance_access'] = false;
            $safe['mode'] = $safe['mode'] === 'clarification_required' ? 'clarification_required' : 'unsupported';
            $safe['fallback_message'] = $safe['fallback_message'] ?: 'I can plan CRM data queries only for allowed CRM tables and columns. I could not map this question safely yet.';
        }

        return $safe;
    }

    private function legacyPlanFromHybrid(array $plan): array
    {
        if (! array_key_exists('mode', $plan)) {
            return $this->withHybridMetadata($plan, 'data_explorer', (float) ($plan['confidence'] ?? 0.75));
        }

        $mode = (string) ($plan['mode'] ?? 'unsupported');

        if (in_array($mode, ['unsupported', 'clarification_required'], true)) {
            return array_merge($this->unknownPlan(), [
                'mode' => $mode,
                'confidence' => (float) ($plan['confidence'] ?? 0),
                'entities' => array_values((array) ($plan['entities'] ?? [])),
                'selected_columns' => $this->normalizeSelectedColumns($plan['selected_columns'] ?? $plan['select'] ?? []),
                'relationships' => array_values((array) ($plan['relationships'] ?? [])),
                'sort' => array_values((array) ($plan['sort'] ?? [])),
                'limit' => max(1, min((int) ($plan['limit'] ?? 100), 100)),
                'needs_clarification' => (bool) ($plan['needs_clarification'] ?? $mode === 'clarification_required'),
                'clarification_question' => $plan['clarification_question'] ?? null,
                'fallback_message' => $plan['clarification_question']
                    ?? $plan['fallback_message']
                    ?? 'I can answer CRM data questions only from the allowed CRM modules.',
            ]);
        }

        $select = $this->normalizeSelectedColumns($plan['selected_columns'] ?? $plan['select'] ?? []);
        $tables = array_values(array_unique(array_merge(
            array_values((array) ($plan['entities'] ?? [])),
            array_keys($select)
        )));
        $columns = collect($select)->flatMap(fn ($columns) => (array) $columns)->unique()->values()->all();
        $groupBy = collect((array) ($plan['group_by'] ?? []))
            ->map(fn ($item) => is_array($item) ? ($item['column'] ?? null) : $item)
            ->filter()
            ->values()
            ->all();
        $filters = collect((array) ($plan['filters'] ?? []))
            ->filter(fn ($filter) => is_array($filter))
            ->map(fn (array $filter) => Arr::only($filter, ['table', 'column', 'operator', 'value']))
            ->values()
            ->all();

        $requiresFinance = collect($tables)->contains(function (string $table) {
            return in_array($this->aiSchemaService->getAccessRule($table), ['finance_access', 'profitability_access'], true);
        });

        return [
            // LLM-planned (free-form) questions always run Text-to-SQL first: it
            // reads the raw question and applies every filter, whereas the
            // structured builder only sees plan['filters'] — which the LLM planner
            // sometimes leaves empty (e.g. "deal review department projects" → no
            // filter → all rows). Curated inferKnownPlan reports keep fixed_action
            // (tagged separately in withHybridMetadata), so this only affects the
            // generic LLM path; the structured builder stays as the fallback if
            // Text-to-SQL can't answer. This removes the non-deterministic
            // fixed_action↔data_explorer routing that caused dropped filters.
            'mode' => 'data_explorer',
            'confidence' => (float) ($plan['confidence'] ?? 0.75),
            'entities' => $tables,
            'selected_columns' => $this->selectedColumnsPayload($select),
            'answer_type' => $plan['answer_type'] ?? 'table',
            'intent' => $plan['intent'] ?? 'crm_list',
            'tables' => $tables,
            'columns' => $columns,
            'group_by' => $groupBy,
            'filters' => $filters,
            'relationships' => array_values((array) ($plan['relationships'] ?? [])),
            'sort' => array_values((array) ($plan['sort'] ?? [])),
            'limit' => max(1, min((int) ($plan['limit'] ?? 100), 100)),
            'needs_clarification' => (bool) ($plan['needs_clarification'] ?? false),
            'clarification_question' => $plan['clarification_question'] ?? null,
            'requires_finance_access' => (bool) ($plan['requires_finance_access'] ?? $requiresFinance),
            'sql' => null,
            'fallback_message' => $plan['fallback_message'] ?? null,
        ];
    }

    private function withHybridMetadata(array $plan, string $mode, float $confidence): array
    {
        $tables = array_values((array) ($plan['tables'] ?? []));

        return array_merge([
            'mode' => $mode,
            'confidence' => $confidence,
            'entities' => $tables,
            'selected_columns' => $this->selectedColumnsPayload(collect($tables)->mapWithKeys(fn (string $table) => [
                $table => array_values((array) ($plan['columns'] ?? [])),
            ])->all()),
            'relationships' => [],
            'sort' => [],
            'limit' => 100,
            'needs_clarification' => false,
            'clarification_question' => null,
        ], $plan);
    }

    private function normalizeSelectedColumns(mixed $selectedColumns): array
    {
        if (! is_array($selectedColumns)) {
            return [];
        }

        $isList = array_is_list($selectedColumns);

        if (! $isList) {
            return collect($selectedColumns)
                ->mapWithKeys(fn ($columns, string $table) => [$table => array_values((array) $columns)])
                ->all();
        }

        return collect($selectedColumns)
            ->filter(fn ($item) => is_array($item) && isset($item['table'], $item['columns']))
            ->mapWithKeys(fn (array $item) => [(string) $item['table'] => array_values((array) $item['columns'])])
            ->all();
    }

    private function selectedColumnsPayload(array $selectedColumns): array
    {
        return collect($selectedColumns)
            ->map(fn (array $columns, string $table) => [
                'table' => $table,
                'columns' => array_values($columns),
            ])
            ->values()
            ->all();
    }

    private function schemaForPlanner(): array
    {
        return collect($this->aiSchemaService->getAllowedTables())
            ->mapWithKeys(function (string $table) {
                return [
                    $table => [
                        'access_rule' => $this->aiSchemaService->getAccessRule($table),
                        'allowed_columns' => $this->aiSchemaService->getAllowedColumns($table),
                        'searchable_columns' => $this->aiSchemaService->getSearchableColumns($table),
                        'relationships' => $this->aiSchemaService->getRelationships($table),
                        'sensitive_columns' => $this->sensitiveAllowedColumns($table),
                    ],
                ];
            })
            ->all();
    }

    private function moduleHints(): array
    {
        return [
            'projects' => [
                'tables' => ['projects', 'customers', 'departments', 'sub_departments', 'tasks', 'employees', 'project_acceptances'],
                'common_columns' => ['project_name', 'code', 'status', 'department_id', 'sub_department_id', 'customer_id', 'created_at', 'updated_at'],
                'common_questions' => ['counts', 'lists', 'status summaries', 'department summaries', 'assigned projects', 'project details'],
            ],
            'tickets' => [
                'tables' => ['service_tickets', 'users', 'projects'],
                'common_columns' => ['subject', 'priority', 'status', 'user_id', 'assigned_to', 'created_at', 'updated_at'],
                'common_questions' => ['ticket lists', 'status counts', 'creator summaries', 'assigned tickets'],
            ],
            'users_and_roles' => [
                'tables' => ['users', 'model_has_roles', 'roles', 'employees'],
                'common_columns' => ['name', 'email', 'username', 'role_id', 'model_id'],
                'common_questions' => ['user lists', 'role lists', 'role counts', 'employee lookup'],
            ],
            'finance' => [
                'tables' => ['projects', 'customers', 'customer_finances', 'finance_options', 'account_transactions'],
                'common_columns' => ['contract_amount', 'dealer_fee_amount', 'commission', 'holdback_amount', 'customer_portion', 'amount'],
                'requires_finance_access' => true,
                'common_questions' => ['project financing', 'customer financing', 'payments', 'amount summaries', 'finance options'],
            ],
            'transactions' => [
                'tables' => ['account_transactions', 'projects'],
                'common_columns' => ['project_id', 'payee', 'milestone', 'amount', 'deduction_amount', 'transaction_date', 'transaction_details'],
                'date_filter_column' => 'account_transactions.transaction_date',
                'requires_finance_access' => true,
                'common_questions' => ['transaction report', 'remitted amount', 'deductions', 'payee summaries', 'milestone payments'],
            ],
            'project_acceptance' => [
                'tables' => ['projects', 'customers', 'project_acceptances', 'users'],
                'common_columns' => ['status', 'approved_date', 'reason', 'panel_qty', 'inverter_name', 'action_by'],
                'status_values' => ['pending' => 0, 'approved' => 1, 'rejected' => 2],
            ],
            'activity_history' => [
                'tables' => ['activity_log', 'projects', 'users'],
                'common_columns' => ['log_name', 'description', 'event', 'subject_id', 'causer_id', 'properties', 'created_at'],
                'common_questions' => ['project activity log', 'who moved project', 'department move history', 'project changes', 'action history'],
                'note' => 'Use event="move" for lane/department movement. properties JSON has old_lane and new_lane.',
            ],
            'site_surveys' => [
                'tables' => ['site_surveys', 'projects', 'users', 'technician_schedules'],
                'common_columns' => ['project_id', 'technician_id', 'survey_date', 'status', 'customer_address'],
                'common_questions' => ['scheduled surveys', 'surveys by technician', 'pending site surveys', 'technician availability'],
            ],
            'adders_catalogue' => [
                'tables' => ['adder_types', 'adder_sub_types', 'adder_units', 'adders', 'customer_adders', 'customers'],
                'common_columns' => ['name', 'tag', 'price', 'amount', 'adder_type_id'],
                'common_questions' => ['adder types', 'adder prices', 'adders on a customer deal'],
            ],
            'product_catalogue' => [
                'tables' => ['inverter_types', 'module_types', 'battery_types', 'utility_companies', 'inverter_type_rates'],
                'common_columns' => ['name', 'value', 'tags', 'base_cost'],
                'common_questions' => ['inverter types', 'module types', 'battery types', 'utility companies', 'equipment catalogue'],
            ],
            'partners' => [
                'tables' => ['sales_partners', 'sub_contractors', 'customers', 'projects'],
                'common_columns' => ['name', 'email', 'phone'],
                'common_questions' => ['sales partners list', 'sub-contractors list', 'partner contact details'],
            ],
            'project_notes' => [
                'tables' => ['department_notes', 'project_call_logs', 'projects', 'departments', 'users'],
                'common_columns' => ['project_id', 'department_id', 'notes', 'call_no', 'show_to_customer'],
                'common_questions' => ['project notes', 'department notes', 'call logs on a project'],
            ],
        ];
    }

    private function inferKnownPlan(string $question): ?array
    {
        $normalized = mb_strtolower($question);

        // Detect project lane movement / days-in-lane queries
        $mentionsLane = str_contains($normalized, 'lane')
            || str_contains($normalized, 'lanes');
        $mentionsMovement = str_contains($normalized, 'movement')
            || str_contains($normalized, 'moved')
            || str_contains($normalized, 'history')
            || str_contains($normalized, 'transition')
            || str_contains($normalized, 'journey')
            || str_contains($normalized, 'move log')
            || str_contains($normalized, 'move history')
            // "progress" alone — but NOT inside "in-progress" (project status)
            || preg_match('/(?<!in-)progress\b/i', $normalized);
        $mentionsDaysInLane = (str_contains($normalized, 'days') || str_contains($normalized, 'how long') || str_contains($normalized, 'time') || str_contains($normalized, 'duration') || str_contains($normalized, 'kitne din'))
            && (str_contains($normalized, 'lane') || str_contains($normalized, 'department'));

        // "Ghost" / "Pre-Inspection Lane" questions are a project LIST (handled by the
        // dedicated pre-inspection/ghost detection below), not a lane-movement report.
        // Without this guard the bare word "lane" in "Pre-Inspection Lane" would wrongly
        // route them here.
        $isGhostOrPreInspectionList = str_contains($normalized, 'ghost')
            || str_contains($normalized, 'pre-inspection')
            || str_contains($normalized, 'pre inspection')
            || str_contains($normalized, 'preinspection');

        if (! $isGhostOrPreInspectionList
            && ($mentionsLane || ($mentionsMovement && str_contains($normalized, 'department')) || $mentionsDaysInLane)) {
            // Summary only when user explicitly asks for aggregate/overview
            $wantsSummary = str_contains($normalized, 'summary')
                || str_contains($normalized, 'average')
                || str_contains($normalized, 'avg')
                || str_contains($normalized, 'all projects')
                || str_contains($normalized, 'overview')
                || str_contains($normalized, 'sab projects')
                || str_contains($normalized, 'tamam projects');

            return [
                'answer_type' => 'table',
                'intent'      => $wantsSummary ? 'project_lane_summary' : 'project_lane_movement',
                'tables'      => ['tasks', 'projects', 'departments'],
                'columns'     => ['project_name', 'code', 'department', 'created_at', 'updated_at'],
                'group_by'    => [],
                'filters'     => [],
                'requires_finance_access' => false,
                'sql'             => null,
                'fallback_message' => null,
            ];
        }
        // NOTE: Simple total-count questions (projects/tickets/customers) are
        // intentionally NOT fast-pathed here. Those blind counts ignored any
        // filter in the question ("how many projects in California",
        // "tickets created this month"), so every variation returned the same
        // number. They now flow to the AI planner + Text-to-SQL, which respect
        // the filters the user actually asked for.

        $mentionsProject = str_contains($normalized, 'project');
        $mentionsTicket = str_contains($normalized, 'ticket');
        $mentionsDepartment = str_contains($normalized, 'department') || str_contains($normalized, 'subdepartment') || str_contains($normalized, 'sub department');
        $mentionsRole = str_contains($normalized, 'role') || str_contains($normalized, 'roles');
        $mentionsCount = str_contains($normalized, 'count') || str_contains($normalized, 'kitne') || str_contains($normalized, 'kitni') || str_contains($normalized, 'wise');
        $mentionsUser = str_contains($normalized, 'user') || str_contains($normalized, 'users') || str_contains($normalized, 'created by') || str_contains($normalized, 'creator');
        $mentionsStatus = str_contains($normalized, 'status') || str_contains($normalized, 'pending') || str_contains($normalized, 'resolved');
        $mentionsSummary = str_contains($normalized, 'summary') || str_contains($normalized, 'summarize') || str_contains($normalized, 'wise');
        // "approval" alone should not trigger acceptance routing when it refers to domain milestones
        // (NTP approval, HOA approval, PTO approval, permit approval, etc.)
        $mentionsAcceptance = str_contains($normalized, 'acceptance')
            || (
                preg_match('/\b(approved|approval)\b/i', $normalized)
                && ! preg_match('/\b(ntp|pto|hoa|ahj|mpu|permit|permitting|inspection|survey|fire|meter|finance|financing|loan)\b/i', $normalized)
            );
        $mentionsForecast = str_contains($normalized, 'forecast');
        $mentionsOverride = str_contains($normalized, 'override') || str_contains($normalized, 'overrider');
        $mentionsTransaction = str_contains($normalized, 'transaction')
            || str_contains($normalized, 'transactions')
            || str_contains($normalized, 'remittance')
            || str_contains($normalized, 'remitted')
            || str_contains($normalized, 'deduction');
        $mentionsProfitability = str_contains($normalized, 'profitability')
            || str_contains($normalized, 'profitablity')
            || str_contains($normalized, 'profitable')
            || str_contains($normalized, 'profit report')
            || str_contains($normalized, 'profit report');
        $mentionsFinance = str_contains($normalized, 'finance')
            || str_contains($normalized, 'financing')
            || str_contains($normalized, 'contract amount')
            || str_contains($normalized, 'dealer fee')
            || str_contains($normalized, 'commission');
        $status = $this->extractStatus($normalized);
        $assignedEmployeeName = $this->extractAssignedEmployeeName($normalized);
        $ticketUserName = $this->extractTicketUserName($normalized);
        $projectSummaryName = $this->extractProjectSummaryName($normalized);
        $projectAcceptanceName = $this->extractProjectAcceptanceName($normalized);
        $acceptanceCondition = $this->extractAcceptanceCondition($normalized);
        $dateRange = $this->extractDateRange($question);

        // NOTE: Generic group-summary questions (customer-wise / city-wise /
        // state-wise / status-wise / department top projects / project count by
        // department) are intentionally NOT keyword-routed here anymore. They flow
        // to the AI planner + Text-to-SQL, which read the actual phrasing and
        // respect every filter — removing the brittle keyword matching that caused
        // mis-routing bugs (e.g. "department-wise" returning 0). The financial /
        // named reports below stay curated. The empty/count-0 safety net in
        // AiChatService backstops any mis-route for full-access users.

        // "How many projects are in progress / on hold / completed / cancelled" →
        // a COUNT of projects by their CURRENT (latest-task) status. Previously this
        // fell through to a capped list and the formatter reported the LIMIT (e.g.
        // "100") as if it were the real total. We handle ONLY the simple form here;
        // any question carrying an extra qualifier (date, department, location,
        // milestone) is deferred to the LLM planner / Text-to-SQL, which read the
        // full question. The structured builder applies the latest-task pin so the
        // count reflects each project exactly once.
        $isCountQuestion = $mentionsCount
            || str_contains($normalized, 'how many')
            || str_contains($normalized, 'kitne')
            || str_contains($normalized, 'kitni')
            || str_contains($normalized, 'number of');

        $taskStatus = in_array($status, ['In-Progress', 'Completed', 'Cancelled', 'Hold'], true) ? $status : null;

        $hasExtraQualifier = $dateRange !== null
            || $this->extractDepartmentName($normalized) !== null
            || (bool) preg_match('/\b(department|sub[\s_-]?department|state|city|customer|installed?|permit|permitting|pto|hoa|ntp|survey|inspection|battery|mpu)\b/i', $normalized);

        if ($mentionsProject && $isCountQuestion && $taskStatus !== null
            && ! $mentionsTicket && ! $mentionsAcceptance && ! $hasExtraQualifier) {
            return [
                'answer_type'             => 'count',
                'intent'                  => 'project_status_filter_count',
                'tables'                  => ['projects', 'tasks'],
                'columns'                 => ['id'],
                'group_by'                => [],
                'filters'                 => [
                    ['table' => 'tasks', 'column' => 'status', 'operator' => '=', 'value' => $taskStatus],
                ],
                'requires_finance_access' => false,
                'sql'                     => null,
                'fallback_message'        => null,
            ];
        }

        if ($mentionsUser && $mentionsRole) {
            $wantsCount = $mentionsCount
                || str_contains($normalized, 'count')
                || str_contains($normalized, 'hisaab')
                || str_contains($normalized, 'hisab')
                || str_contains($normalized, 'kitne')
                || str_contains($normalized, 'kitni');

            return [
                'answer_type' => 'table',
                'intent' => $wantsCount ? 'user_role_count' : 'user_role_list',
                'tables' => ['users', 'model_has_roles', 'roles'],
                'columns' => ['name', 'email', 'username', 'role_id', 'model_id'],
                'group_by' => $wantsCount ? ['name'] : [],
                'filters' => [],
                'requires_finance_access' => false,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        if ($mentionsProject && $mentionsSummary && $projectSummaryName) {
            return [
                'answer_type' => 'card',
                'intent' => 'project_summary',
                'tables' => ['projects', 'customers', 'departments', 'sub_departments', 'tasks', 'employees', 'project_acceptances'],
                'columns' => [
                    'project_name',
                    'code',
                    'first_name',
                    'last_name',
                    'phone',
                    'email',
                    'city',
                    'state',
                    'department_id',
                    'sub_department_id',
                    'name',
                    'status',
                    'approved_date',
                    'reason',
                    'created_at',
                    'updated_at',
                ],
                'group_by' => [],
                'filters' => [
                    [
                        'column' => 'project_name',
                        'operator' => 'like',
                        'value' => $projectSummaryName,
                    ],
                ],
                'requires_finance_access' => false,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        // COUNT must be checked BEFORE project-name lookup to avoid false matches
        if ($mentionsProject && $mentionsAcceptance && ($mentionsCount || str_contains($normalized, 'how many') || str_contains($normalized, 'kitne') || str_contains($normalized, 'total') || str_contains($normalized, 'kitni'))) {
            // Unknown/unsupported status (e.g. "cancelled") — return a helpful fallback
            if (isset($acceptanceCondition['fallback'])) {
                return array_merge($this->unknownPlan(), ['fallback_message' => $acceptanceCondition['fallback']]);
            }

            $hasCondition = $acceptanceCondition !== null && ! isset($acceptanceCondition['fallback']);
            $statusFilter = $hasCondition ? [$acceptanceCondition] : [];

            // "not in Archived department" / "exclude archived" → drop archived-dept projects.
            if (preg_match('/\bnot\s+(?:in\s+)?(?:the\s+)?archived?\b|exclude[ds]?\s+archived|without\s+archived|except\s+archived|non[- ]?archived/i', $normalized)) {
                $statusFilter[] = [
                    'table'    => 'projects',
                    'column'   => 'department_id',
                    'operator' => '!=',
                    'value'    => (int) config('ai.schema.archived_department_id', 9),
                ];
            }

            return [
                'answer_type' => $hasCondition ? 'count' : 'table',
                'intent'      => 'project_acceptance_count',
                'tables'      => ['projects', 'project_acceptances'],
                'columns'     => ['id', 'project_id', 'status'],
                'group_by'    => $hasCondition ? [] : ['status'],
                'filters'     => $statusFilter,
                'requires_finance_access' => false,
                'sql'          => null,
                'fallback_message' => null,
            ];
        }

        if ($mentionsProject && $mentionsAcceptance && $projectAcceptanceName) {
            return [
                'answer_type' => 'card',
                'intent' => 'project_acceptance_summary',
                'tables' => ['projects', 'customers', 'project_acceptances'],
                'columns' => [
                    'project_name',
                    'code',
                    'first_name',
                    'last_name',
                    'status',
                    'approved_date',
                    'reason',
                    'panel_qty',
                    'inverter_name',
                    'adders_list',
                    'notes',
                    'action_by',
                    'created_at',
                    'updated_at',
                ],
                'group_by' => [],
                'filters' => [
                    [
                        'column' => 'project_name',
                        'operator' => 'like',
                        'value' => $projectAcceptanceName,
                    ],
                ],
                'requires_finance_access' => false,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        if ($mentionsForecast) {
            return [
                'answer_type' => 'table',
                'intent' => $dateRange ? 'forecast_report_by_date_range' : 'forecast_report',
                'tables' => ['customers', 'sales_partners', 'customer_finances'],
                'columns' => [
                    'sold_date',
                    'first_name',
                    'last_name',
                    'name',
                    'contract_amount',
                    'commission',
                    'dealer_fee_amount',
                ],
                'group_by' => [],
                'filters' => $dateRange ? [
                    [
                        'table' => 'customers',
                        'column' => 'sold_date',
                        'operator' => '>=',
                        'value' => $dateRange['from'],
                    ],
                    [
                        'table' => 'customers',
                        'column' => 'sold_date',
                        'operator' => '<=',
                        'value' => $dateRange['to'],
                    ],
                ] : [],
                'requires_finance_access' => true,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        if ($mentionsOverride) {
            return [
                'answer_type' => 'table',
                'intent' => $dateRange ? 'override_report_by_date_range' : 'override_report',
                'tables' => ['customers', 'projects', 'sales_partners', 'users', 'customer_finances'],
                'columns' => [
                    'sold_date',
                    'first_name',
                    'last_name',
                    'name',
                    'sales_partner_user_id',
                    'panel_qty',
                    'redline_costs',
                    'overwrite_base_price',
                    'overwrite_panel_price',
                ],
                'group_by' => [],
                'filters' => $dateRange ? [
                    [
                        'table' => 'customers',
                        'column' => 'sold_date',
                        'operator' => '>=',
                        'value' => $dateRange['from'],
                    ],
                    [
                        'table' => 'customers',
                        'column' => 'sold_date',
                        'operator' => '<=',
                        'value' => $dateRange['to'],
                    ],
                ] : [],
                'requires_finance_access' => true,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        if ($mentionsTransaction) {
            $txFilters = $dateRange ? [
                ['table' => 'account_transactions', 'column' => 'transaction_date', 'operator' => '>=', 'value' => $dateRange['from']],
                ['table' => 'account_transactions', 'column' => 'transaction_date', 'operator' => '<=', 'value' => $dateRange['to']],
            ] : [];

            // Payee filter: "only Sales Partner" / "sub-contractor" / "others".
            $payee = null;
            if (str_contains($normalized, 'sales partner')) {
                $payee = 'sales_partner';
            } elseif (str_contains($normalized, 'sub-contractor') || str_contains($normalized, 'sub contractor') || str_contains($normalized, 'subcontractor')) {
                $payee = 'sub_contractor';
            } elseif (str_contains($normalized, 'others') || str_contains($normalized, 'other payee')) {
                $payee = 'others';
            }
            if ($payee) {
                $txFilters[] = ['table' => 'account_transactions', 'column' => 'payee', 'operator' => '=', 'value' => $payee];
            }

            return [
                'answer_type' => 'table',
                'intent' => $dateRange ? 'transaction_report_by_date_range' : 'transaction_report',
                'tables' => ['account_transactions', 'projects'],
                'columns' => [
                    'project_id',
                    'project_name',
                    'payee',
                    'milestone',
                    'amount',
                    'deduction_amount',
                    'transaction_date',
                    'transaction_details',
                ],
                'group_by' => [],
                'filters' => $txFilters,
                'requires_finance_access' => true,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        if ($mentionsProfitability) {
            // Meta-question about HOW the report is filtered → explain, don't run it.
            foreach (['kis column', 'which column', 'konsa column', 'konse column', 'kaunsa column', 'kaun se column', 'column se filter', 'filter kis', 'filter kaise', 'how is it filtered', 'how does the filter'] as $metaCue) {
                if (str_contains($normalized, $metaCue)) {
                    return array_merge($this->unknownPlan(), [
                        'fallback_message' => 'The profitability report filters by the project solar install date (projects.solar_install_date) — give a date range like "from 1 April 2026 to 30 April 2026". You can also scope it to a sales partner, e.g. "profitability report of Sales Partner <name>". Profit is calculated as (redline costs + adders) minus actual material, labor, permit and office costs.',
                    ]);
                }
            }

            $profitFilters = $dateRange ? [
                ['table' => 'projects', 'column' => 'solar_install_date', 'operator' => '>=', 'value' => $dateRange['from']],
                ['table' => 'projects', 'column' => 'solar_install_date', 'operator' => '<=', 'value' => $dateRange['to']],
            ] : [];

            // Scope to a named sales partner when the question asks for one.
            if ($partner = $this->extractSalesPartnerName($question)) {
                $profitFilters[] = ['table' => 'sales_partners', 'column' => 'name', 'operator' => 'like', 'value' => $partner];
            }

            return [
                'answer_type' => 'table',
                'intent' => $dateRange ? 'profitability_report_by_date_range' : 'profitability_report',
                'tables' => ['customers', 'projects', 'sales_partners', 'customer_finances'],
                'columns' => [
                    'first_name',
                    'last_name',
                    'name',
                    'solar_install_date',
                    'contract_amount',
                    'dealer_fee_amount',
                    'redline_costs',
                    'adders',
                    'actual_material_cost',
                    'actual_labor_cost',
                    'actual_permit_fee',
                    'office_cost',
                ],
                'group_by' => [],
                'filters' => $profitFilters,
                'requires_finance_access' => true,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        if ($mentionsProject && $mentionsAcceptance && $acceptanceCondition !== null && ! ($mentionsCount || str_contains($normalized, 'how many') || str_contains($normalized, 'kitne'))) {
            if (isset($acceptanceCondition['fallback'])) {
                return array_merge($this->unknownPlan(), ['fallback_message' => $acceptanceCondition['fallback']]);
            }

            return [
                'answer_type' => 'table',
                'intent'      => 'project_acceptance_list',
                'tables'      => ['projects', 'customers', 'project_acceptances'],
                'columns'     => ['project_name', 'code', 'first_name', 'last_name', 'status', 'approved_date', 'reason', 'created_at', 'updated_at'],
                'group_by'    => [],
                'filters'     => [$acceptanceCondition],
                'requires_finance_access' => false,
                'sql'          => null,
                'fallback_message' => null,
            ];
        }

        // Ranking/superlative finance questions ("top 5 by highest contract amount",
        // "lowest commission projects") need an ORDER BY + small LIMIT the curated
        // financing summary does not apply. Let these fall through to the LLM planner
        // → Text-to-SQL, which can sort and limit. Plain "financing details" (no
        // ranking word) still uses the curated report below.
        $wantsFinanceRanking = (bool) preg_match('/\b(highest|lowest|top|bottom|most|least|largest|smallest|maximum|minimum|max|min|biggest|expensive|cheapest)\b/i', $normalized);

        if ($mentionsProject && $mentionsFinance && ! $wantsFinanceRanking) {
            // Scope to a specific project when the question names one ("code 1149",
            // "SS-1149", a quoted/hyphenated name). Without this the curated finance
            // report ignored the reference and listed EVERY project's financing.
            $projectFilter = $this->extractProjectReferenceFilter($question);

            return [
                'answer_type' => 'table',
                'intent' => 'project_financing_summary',
                'tables' => ['projects', 'customers', 'customer_finances', 'finance_options'],
                'columns' => [
                    'project_name',
                    'code',
                    'first_name',
                    'last_name',
                    'finance_option_id',
                    'contract_amount',
                    'dealer_fee_amount',
                    'commission',
                    'holdback_amount',
                    'customer_portion',
                    'name',
                    'created_at',
                    'updated_at',
                ],
                'group_by' => [],
                'filters' => $projectFilter ? [$projectFilter] : [],
                'requires_finance_access' => true,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        // Tickets by priority. If the user names a specific priority ("High priority
        // tickets", "details of urgent tickets") return the actual LIST (or a count
        // for "how many"); only fall back to the grouped summary for "priority wise".
        if ($mentionsTicket && str_contains($normalized, 'priorit')) {
            $priorityValue = $this->extractTicketPriority($normalized);
            $wantsSummary  = str_contains($normalized, 'wise')
                || str_contains($normalized, 'summary')
                || str_contains($normalized, 'group')
                || str_contains($normalized, 'breakdown');

            if ($priorityValue && ! $wantsSummary) {
                $isCount = $mentionsCount
                    || str_contains($normalized, 'how many')
                    || str_contains($normalized, 'kitne')
                    || str_contains($normalized, 'kitni')
                    || str_contains($normalized, 'count');

                return [
                    'answer_type' => $isCount ? 'count' : 'table',
                    'intent'      => $isCount ? 'crm_count' : 'crm_list',
                    'tables'      => ['service_tickets'],
                    'columns'     => $isCount ? ['id'] : ['subject', 'priority', 'status', 'created_at'],
                    'group_by'    => [],
                    'filters'     => [[
                        'table'    => 'service_tickets',
                        'column'   => 'priority',
                        'operator' => '=',
                        'value'    => $priorityValue,
                    ]],
                    'requires_finance_access' => false,
                    'sql'          => null,
                    'fallback_message' => null,
                ];
            }

            return [
                'answer_type' => 'table',
                'intent'      => 'crm_group_summary',
                'tables'      => ['service_tickets'],
                'columns'     => ['priority'],
                'group_by'    => ['priority'],
                'filters'     => [],
                'requires_finance_access' => false,
                'sql'          => null,
                'fallback_message' => null,
            ];
        }

        // Status-wise ticket summary
        if ($mentionsTicket && str_contains($normalized, 'status') && $mentionsSummary && ! $mentionsUser && ! $ticketUserName) {
            return [
                'answer_type' => 'table',
                'intent'      => 'crm_group_summary',
                'tables'      => ['service_tickets'],
                'columns'     => ['status'],
                'group_by'    => ['status'],
                'filters'     => [],
                'requires_finance_access' => false,
                'sql'          => null,
                'fallback_message' => null,
            ];
        }

        if ($mentionsTicket && ($mentionsUser || $ticketUserName) && ($mentionsStatus || $mentionsCount || $mentionsSummary)) {
            return [
                'answer_type' => 'table',
                'intent' => 'ticket_creator_status_summary',
                'tables' => ['service_tickets', 'users'],
                'columns' => ['user_id', 'name', 'status'],
                'group_by' => ['name', 'status'],
                'filters' => array_values(array_filter([
                    $ticketUserName
                        ? [
                            'column' => 'name',
                            'operator' => 'like',
                            'value' => $ticketUserName,
                        ]
                        : null,
                ])),
                'requires_finance_access' => false,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        if ($mentionsProject && $this->isAssignedToCurrentUserQuestion($normalized)) {
            return [
                'answer_type' => 'table',
                'intent' => 'crm_list',
                'tables' => ['tasks', 'projects', 'employees'],
                'columns' => ['project_name', 'code', 'name'],
                'group_by' => ['project_name', 'code', 'name'],
                'filters' => [
                    [
                        'column' => 'employee_id',
                        'operator' => '=',
                        'value' => 'current_employee.id',
                    ],
                ],
                'requires_finance_access' => false,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        if ($mentionsProject && $assignedEmployeeName) {
            return [
                'answer_type' => 'table',
                'intent' => 'crm_list',
                'tables' => ['tasks', 'projects', 'employees'],
                'columns' => ['project_name', 'code', 'name'],
                'group_by' => ['project_name', 'code', 'name'],
                'filters' => [
                    [
                        'column' => 'name',
                        'operator' => 'like',
                        'value' => $assignedEmployeeName,
                    ],
                ],
                'requires_finance_access' => false,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        if ($mentionsProject && $this->isPreInspectionOrGhostQuestion($normalized)) {
            return [
                'answer_type' => 'table',
                'intent' => 'project_pre_inspection_or_ghost_list',
                'tables' => ['projects', 'customers', 'departments', 'sub_departments', 'tasks', 'employees'],
                'columns' => [
                    'project_name',
                    'code',
                    'first_name',
                    'last_name',
                    'sold_date',
                    'department_id',
                    'sub_department_id',
                    'name',
                    'status',
                    'employee_id',
                ],
                'group_by' => [],
                'filters' => [],
                'sort' => [
                    [
                        'table' => 'customers',
                        'column' => 'sold_date',
                        'direction' => 'asc',
                    ],
                ],
                'limit' => 100,
                'requires_finance_access' => false,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        if ($mentionsProject && $status) {
            return [
                'answer_type' => 'table',
                'intent' => 'crm_list',
                'tables' => ['tasks', 'projects', 'departments', 'sub_departments'],
                'columns' => ['project_name', 'code', 'status', 'name'],
                'group_by' => [],
                'filters' => array_values(array_filter([
                    [
                        'column' => 'status',
                        'operator' => 'like',
                        'value' => $status,
                    ],
                    $this->excludesArchivedDepartment($normalized)
                        ? [
                            'column' => 'department_id',
                            'operator' => '!=',
                            'value' => (int) config('ai.schema.archived_department_id', 9),
                        ]
                        : null,
                ])),
                'requires_finance_access' => false,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        if ($mentionsTicket && $status) {
            return [
                'answer_type' => 'table',
                'intent' => 'crm_list',
                'tables' => ['service_tickets', 'users'],
                'columns' => ['subject', 'priority', 'status', 'name', 'created_at'],
                'group_by' => [],
                'filters' => [
                    [
                        'column' => 'status',
                        'operator' => 'like',
                        'value' => $status,
                    ],
                ],
                'requires_finance_access' => false,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        if (str_contains($normalized, 'task') && $status) {
            return [
                'answer_type' => 'table',
                'intent' => 'crm_list',
                'tables' => ['tasks', 'projects'],
                'columns' => ['project_name', 'code', 'notes', 'assign_to_notes', 'status'],
                'group_by' => [],
                'filters' => [
                    [
                        'column' => 'status',
                        'operator' => 'like',
                        'value' => $status,
                    ],
                ],
                'requires_finance_access' => false,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        if ($mentionsProject && $mentionsDepartment && $mentionsCount && $departmentName = $this->extractDepartmentName($normalized)) {
            return [
                'answer_type' => 'count',
                'intent' => 'project_count',
                'tables' => ['projects', 'departments'],
                'columns' => ['id', 'name'],
                'group_by' => [],
                'filters' => [
                    [
                        'column' => 'name',
                        'operator' => 'like',
                        'value' => $departmentName,
                    ],
                ],
                'requires_finance_access' => false,
                'sql' => null,
                'fallback_message' => null,
            ];
        }

        // "Project count/numbers by department" (no specific department named) is a
        // generic group-summary — handled by the AI planner + Text-to-SQL, not a
        // keyword fast-path.

        return null;
    }

    private function isPreInspectionOrGhostQuestion(string $question): bool
    {
        $mentionsPreInspection = str_contains($question, 'pre-inspection')
            || str_contains($question, 'pre inspection')
            || str_contains($question, 'preinspection');
        $mentionsGhost = str_contains($question, 'ghost');
        $mentionsLane = str_contains($question, 'lane');

        return $mentionsGhost || ($mentionsPreInspection && $mentionsLane);
    }

    private function inferredPlanIsMoreSpecific(array $plan, array $inferredPlan): bool
    {
        if (($plan['intent'] ?? null) === 'ticket_status' && ($inferredPlan['intent'] ?? null) === 'ticket_creator_status_summary') {
            return true;
        }

        if (($inferredPlan['intent'] ?? null) === 'crm_list' && ! empty($inferredPlan['filters'] ?? [])) {
            return in_array(($plan['intent'] ?? null), ['unknown', 'crm_list', 'project_status_summary'], true);
        }

        return ($plan['intent'] ?? null) === ($inferredPlan['intent'] ?? null)
            && empty($plan['filters'] ?? [])
            && ! empty($inferredPlan['filters'] ?? []);
    }

    private function extractDepartmentName(string $question): ?string
    {
        // Group-by phrasings ("department-wise", "by/per/each/as per department",
        // "department breakdown") are NOT a specific named department — bail out so
        // the question falls through to the per-department summary handler instead
        // of becoming a single count filtered by a bogus department name.
        if (preg_match('/\b(?:department[-\s]?wise|department\s+breakdown|(?:by|per|each|every|as\s+per)\s+(?:sub[\s_-]?)?departments?)\b/u', $question)) {
            return null;
        }

        if (! preg_match('/([a-z0-9\s&-]+?)\s+department\b/u', $question, $matches)) {
            return null;
        }

        $name = trim($matches[1]);
        $stopWords = [
            'total',
            'count',
            'project',
            'projects',
            'kitne',
            'kitni',
            'is waqt',
            'currently',
            'show',
            'dikhao',
            'dhikhao',
            'me',
            'main',
            'mein',
            'the',
            'as',
            'per',
            'wise',
            'each',
            'every',
            'by',
            'number',
            'numbers',
        ];

        foreach ($stopWords as $word) {
            $name = trim(preg_replace('/\b' . preg_quote($word, '/') . '\b/u', ' ', $name));
        }

        $name = trim(preg_replace('/\s+/u', ' ', $name));

        return $name !== '' ? $name : null;
    }

    private function extractAssignedEmployeeName(string $question): ?string
    {
        if (! str_contains($question, 'assigned') && ! str_contains($question, 'assign')) {
            return null;
        }

        // Group-by / "all employees" phrasings are aggregation requests, not a
        // specific person — bail out so they are not turned into a name filter
        // (e.g. "assigned to each employee" must not become name LIKE '%each%').
        if (preg_match('/\b(?:employee[-\s]?wise|(?:each|every|all|any|per)\s+employees?|employees?\s+wise)\b/u', $question)) {
            return null;
        }

        $patterns = [
            '/assigned\s+to\s+(.+?)\s+employee\b/u',
            '/assign(?:ed)?\s+to\s+(.+?)\s+employee\b/u',
            '/assigned\s+to\s+(.+?)(?:\s+show|\s+project|\s+projects|\s+list|$)/u',
        ];

        $name = null;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $question, $matches)) {
                $name = trim($matches[1]);
                break;
            }
        }

        if (! $name) {
            return null;
        }

        $stopWords = [
            'show',
            'project',
            'projects',
            'list',
            'which',
            'are',
            'assigned',
            'assign',
            'to',
            'employee',
            'name',
            'column',
            'columns',
            'do',
            'not',
            'duplication',
            'duplicate',
            'without',
            'with',
        ];

        foreach ($stopWords as $word) {
            $name = trim(preg_replace('/\b' . preg_quote($word, '/') . '\b/u', ' ', $name));
        }

        $name = trim(preg_replace('/\s+/u', ' ', $name));

        if (in_array($name, ['me', 'my', 'mine', 'mujhe', 'mujhy', 'mere', 'meri', 'mera', 'each', 'every', 'all', 'any', 'per', 'wise'], true)) {
            return null;
        }

        // Must look like a real name: short, few words, starts with a letter.
        if (mb_strlen($name) > 40 || str_word_count($name) > 4) {
            return null;
        }

        return $name !== '' ? $name : null;
    }

    private function isAssignedToCurrentUserQuestion(string $question): bool
    {
        if (! str_contains($question, 'assign')) {
            return false;
        }

        return preg_match('/\b(me|my|mine|mujhe|mujhy|mere|meri|mera)\b/u', $question) === 1;
    }

    private function extractTicketUserName(string $question): ?string
    {
        if (! str_contains($question, 'ticket')) {
            return null;
        }

        // These phrases indicate aggregate/grouping queries — no specific user intended
        $aggregatePatterns = ['user wise', 'priority wise', 'status wise', 'assigned wise', 'all users', 'all tickets', 'created by users'];
        foreach ($aggregatePatterns as $agg) {
            if (str_contains($question, $agg)) {
                return null;
            }
        }

        $patterns = [
            '/tickets?\s+(?:of|for|by|created\s+by)\s+([A-Z][a-zA-Z\s\-]{2,40})(?:\s+summary|$)/u',
            '/summary\s+of\s+([A-Z][a-zA-Z\s\-]{2,40})\s+tickets?\b/u',
        ];

        foreach ($patterns as $pattern) {
            if (! preg_match($pattern, $question, $matches)) {
                continue;
            }

            $name = trim($matches[1]);
            $stopWords = ['show', 'me', 'the', 'of', 'for', 'by', 'created', 'ticket', 'tickets', 'summary', 'status', 'wise'];

            foreach ($stopWords as $word) {
                $name = trim(preg_replace('/\b' . preg_quote($word, '/') . '\b/u', ' ', $name));
            }

            $name = trim(preg_replace('/\s+/u', ' ', $name));

            // Must look like a real name: 2–40 chars, 1–4 words, starts uppercase
            if ($name !== '' && mb_strlen($name) <= 40 && str_word_count($name) <= 4 && ctype_upper(mb_substr($name, 0, 1))) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Detect a concrete project reference in the question and return it as a plan
     * filter. Codes (explicit "code 1149", "SS-1149", or a bare 3-6 digit number)
     * match `projects.code` exactly; a quoted or hyphenated proper name matches
     * `projects.project_name`. Returns null when no specific project is named, so
     * the caller keeps its existing "list all" behaviour.
     *
     * @return array{column:string,operator:string,value:string}|null
     */
    private function extractProjectReferenceFilter(string $question): ?array
    {
        // Explicit "code <X>" / "project code <X>" — most reliable signal.
        if (preg_match('/\bcode\s*#?\s*([A-Za-z]{0,4}-?\d{2,6})\b/i', $question, $m)) {
            return ['column' => 'code', 'operator' => '=', 'value' => trim($m[1])];
        }

        // Prefixed code anywhere (e.g. SS-001, AB-12345).
        if (preg_match('/\b([A-Za-z]{1,4}-\d{3,6})\b/', $question, $m)) {
            return ['column' => 'code', 'operator' => '=', 'value' => $m[1]];
        }

        // Quoted name (e.g. "Annie Ewing").
        if (preg_match('/["\']([A-Za-z][A-Za-z0-9\s\-]{1,40})["\']/', $question, $m)) {
            return ['column' => 'project_name', 'operator' => 'like', 'value' => trim($m[1])];
        }

        // Hyphenated proper name (e.g. Annie-Ewing), optionally followed by a
        // " - <address/unit>" segment. Capturing that suffix targets the SPECIFIC
        // project ("Yunjiao-Guan - 61st Ave") instead of every sibling project that
        // shares the customer name ("Yunjiao-Guan - Burlwood", "… - Amador St").
        if (preg_match('/\b([A-Z][a-zA-Z]+-[A-Z][a-zA-Z]+)\b/', $question, $m)) {
            $name = $m[1];

            if (preg_match('/' . preg_quote($name, '/') . '\s*-\s*([A-Za-z0-9][A-Za-z0-9. ]*?)(?=\s+(?:and|also|plus|or|with|task|tasks|finance|financing|detail|details|summary|project)\b|[,.?!]|$)/iu', $question, $m2)) {
                $suffix = trim($m2[1], " \t.,?!");

                if ($suffix !== '') {
                    $name .= ' - ' . $suffix;
                }
            }

            return ['column' => 'project_name', 'operator' => 'like', 'value' => $name];
        }

        // Bare 3-6 digit number as a last resort (a lone code in the question).
        if (preg_match('/\b(\d{3,6})\b/', $question, $m)) {
            return ['column' => 'code', 'operator' => '=', 'value' => $m[1]];
        }

        return null;
    }

    /**
     * Map a ticket-priority word in the question to its stored value, or null.
     */
    private function extractTicketPriority(string $normalized): ?string
    {
        foreach (['urgent' => 'Urgent', 'critical' => 'Critical', 'high' => 'High', 'medium' => 'Medium', 'low' => 'Low'] as $needle => $value) {
            if (preg_match('/\b' . $needle . '\b/', $normalized)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Extract a sales-partner name when a report question scopes to one
     * ("... of Sales Partner Solen Energy Construction"). Returns null when none.
     */
    private function extractSalesPartnerName(string $question): ?string
    {
        if (! preg_match('/\b(?:sales\s+partner|partner)\b[:\s]+(.+)/i', $question, $m)) {
            return null;
        }

        $name = $m[1];

        // Cut at trailing clauses/noise so we keep just the partner name.
        $name = preg_split('/\b(?:from|between|for the|report|profitability|profit|transaction|forecast|override|in\s+\d|of\s+\w+\s+20\d\d)\b/i', $name)[0] ?? $name;
        $name = trim(preg_replace('/\s+/', ' ', trim($name, " \t\n\r.,:;\"'")));

        return ($name !== '' && mb_strlen($name) >= 3 && mb_strlen($name) <= 60) ? $name : null;
    }

    private function extractProjectSummaryName(string $question): ?string
    {
        // Aggregate phrases — these mean "group by", not "filter by project name"
        $aggregatePatterns = ['department wise', 'subdepartment wise', 'sub department wise', 'status wise', 'customer wise', 'user wise', 'all projects'];
        foreach ($aggregatePatterns as $agg) {
            if (str_contains(mb_strtolower($question), $agg)) {
                return null;
            }
        }

        $patterns = [
            '/project\s+summary\s+of\s+([A-Z][a-zA-Z\s\-]{2,40})\s+project\b/u',
            '/summary\s+of\s+([A-Z][a-zA-Z\s\-]{2,40})\s+project\b/u',
            '/([A-Z][a-zA-Z\s\-]{2,40})\s+project\s+summary\b/u',
        ];

        foreach ($patterns as $pattern) {
            if (! preg_match($pattern, $question, $matches)) {
                continue;
            }

            $name = trim($matches[1]);
            $stopWords = ['show', 'me', 'the', 'of', 'project', 'summary', 'details', 'detail'];

            foreach ($stopWords as $word) {
                $name = trim(preg_replace('/\b' . preg_quote($word, '/') . '\b/u', ' ', $name));
            }

            $name = trim(preg_replace('/\s+/u', ' ', $name));

            if ($name !== '' && mb_strlen($name) <= 40 && str_word_count($name) <= 4) {
                return $name;
            }
        }

        return null;
    }

    private function extractProjectAcceptanceName(string $question): ?string
    {
        if (! str_contains($question, 'acceptance') && ! str_contains($question, 'approved') && ! str_contains($question, 'approval')) {
            return null;
        }

        // If question sounds like a count/list query, don't try to extract a project name
        $questionWords = ['how many', 'how much', 'kitne', 'kitni', 'total', 'count', 'list', 'show all', 'all projects', 'tamam', 'sab'];
        foreach ($questionWords as $qw) {
            if (str_contains($question, $qw)) {
                return null;
            }
        }

        $patterns = [
            '/(?:for|of)\s+(.+?)\s+project\b/u',
            '/project\s+acceptance\s+(?:of|for)\s+(.+?)(?:\s+project|$)/u',
            '/(.+?)\s+project\s+acceptance\b/u',
            '/(.+?)\s+project\b/u',
        ];

        foreach ($patterns as $pattern) {
            if (! preg_match($pattern, $question, $matches)) {
                continue;
            }

            $name = trim($matches[1]);
            $stopWords = [
                'is', 'the', 'this', 'that', 'a', 'an',
                'project', 'acceptance', 'approved', 'approval',
                'status', 'for', 'of', 'show', 'me', 'details',
                'detail', 'tell', 'what', 'which', 'where',
                'whose', 'there', 'are', 'in', 'list',
            ];

            foreach ($stopWords as $word) {
                $name = trim(preg_replace('/\b' . preg_quote($word, '/') . '\b/u', ' ', $name));
            }

            $name = trim(preg_replace('/\s+/u', ' ', $name));

            // Reject if extracted name is too long (>40 chars) or has too many words (>4)
            // — that means we captured a sentence fragment, not a project name
            if ($name === '' || mb_strlen($name) > 40 || str_word_count($name) > 4) {
                return null;
            }

            if ($name !== '') {
                return $name;
            }
        }

        return null;
    }

    /**
     * Returns a filter array ['column', 'operator', 'value'] for known acceptance
     * statuses, or ['fallback' => 'message'] for statuses that don't exist in the DB,
     * or null when no status can be identified.
     *
     * DB values: 0 = pending, 1 = approved, 2 = rejected
     */
    private function extractAcceptanceCondition(string $question): ?array
    {
        $q = mb_strtolower($question);

        // "not approved" / "unapproved" — must check BEFORE plain "approved"
        if (preg_match('/\bnot\s+approved\b|\bunapproved\b/i', $question)) {
            return ['column' => 'status', 'operator' => '!=', 'value' => 1];
        }

        // "not rejected"
        if (preg_match('/\bnot\s+rejected\b/i', $question)) {
            return ['column' => 'status', 'operator' => '!=', 'value' => 2];
        }

        // "approved" — exact word match only; "approval" must NOT match (it's a domain milestone term)
        if (preg_match('/\bapproved?\b/i', $question)) {
            return ['column' => 'status', 'operator' => '=', 'value' => 1];
        }

        // "rejected"
        if (str_contains($q, 'rejected') || str_contains($q, 'reject')) {
            return ['column' => 'status', 'operator' => '=', 'value' => 2];
        }

        // "pending" / "not initiated" / "awaiting"
        if (
            str_contains($q, 'pending')
            || str_contains($q, 'not initiated')
            || str_contains($q, 'awaiting')
            || str_contains($q, 'initiate nahi')
            || str_contains($q, 'shuru nahi')
        ) {
            return ['column' => 'status', 'operator' => '=', 'value' => 0];
        }

        // "cancelled" / "canceled" — no such status in this CRM
        if (str_contains($q, 'cancelled') || str_contains($q, 'canceled')) {
            return [
                'fallback' => 'Project Acceptance does not have a "cancelled" status. '
                    . 'The available statuses are: **Pending** (not yet reviewed), **Approved**, or **Rejected**. '
                    . 'Please try asking about one of those.',
            ];
        }

        return null;
    }

    private function extractDateRange(string $question): ?array
    {
        $normalized = preg_replace('/\b(\d{1,2})(st|nd|rd|th)\b/i', '$1', $question) ?: $question;
        $monthNames = 'jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|jun(?:e)?|jul(?:y)?|aug(?:ust)?|sep(?:tember)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?';
        $datePattern = '/\b(?:\d{4}-\d{1,2}-\d{1,2}|\d{1,2}\s+(?:' . $monthNames . ')\s+\d{4}|(?:' . $monthNames . ')\s+\d{1,2},?\s+\d{4})\b/i';

        preg_match_all($datePattern, $normalized, $matches);
        $dates = array_values($matches[0] ?? []);

        if (count($dates) >= 2) {
            $from = $this->parseDate($dates[0]);
            $to = $this->parseDate($dates[1]);

            if ($from && $to) {
                return [
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                ];
            }
        }

        if (preg_match('/\b(' . $monthNames . ')\s+(\d{4})\b/i', $normalized, $monthMatch)) {
            $month = $this->parseDate($monthMatch[0]);

            if ($month) {
                return [
                    'from' => $month->copy()->startOfMonth()->toDateString(),
                    'to' => $month->copy()->endOfMonth()->toDateString(),
                ];
            }
        }

        $lower = mb_strtolower($normalized);

        if (str_contains($lower, 'this month') || str_contains($lower, 'current month')) {
            return [
                'from' => now()->startOfMonth()->toDateString(),
                'to' => now()->endOfMonth()->toDateString(),
            ];
        }

        if (str_contains($lower, 'last month')) {
            return [
                'from' => now()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                'to' => now()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ];
        }

        return null;
    }

    private function parseDate(string $date): ?Carbon
    {
        try {
            return Carbon::parse(trim($date));
        } catch (\Throwable) {
            return null;
        }
    }

    private function extractStatus(string $question): ?string
    {
        $statuses = [
            'in-progress' => 'In-Progress',
            'in progress' => 'In-Progress',
            'completed' => 'Completed',
            'complete' => 'Completed',
            'cancelled' => 'Cancelled',
            'canceled' => 'Cancelled',
            'hold' => 'Hold',
            'pending' => 'Pending',
            'resolved' => 'Resolved',
        ];

        foreach ($statuses as $needle => $status) {
            if (str_contains($question, $needle)) {
                return $status;
            }
        }

        return null;
    }

    private function excludesArchivedDepartment(string $question): bool
    {
        return str_contains($question, 'archived')
            && (
                str_contains($question, 'mat')
                || str_contains($question, 'not')
                || str_contains($question, 'exclude')
                || str_contains($question, 'without')
                || str_contains($question, 'chor')
                || str_contains($question, 'chhor')
            );
    }

    private function sensitiveAllowedColumns(string $table): array
    {
        return array_values(array_filter(
            $this->aiSchemaService->getAllowedColumns($table),
            fn (string $column) => $this->aiSchemaService->isSensitiveColumn($table, $column)
        ));
    }

    private function isWriteOperationQuestion(string $question): bool
    {
        // "move" is intentionally excluded — "project move history" is a valid read query
        return preg_match('/\b(insert|update|delete|drop|alter|create|truncate|restore|approve|change|edit|remove)\b/i', $question) === 1;
    }

    private function syntheticOpenAiResponse(): array
    {
        return [
            'id' => null,
            'model' => config('services.openai.model', 'gpt-4.1-mini'),
            'usage' => [],
            'payload' => [],
            'raw' => [],
        ];
    }

    private function userRole(User $user): string
    {
        if (method_exists($user, 'getRoleNames')) {
            return $user->getRoleNames()->first() ?? 'User';
        }

        return 'User';
    }

    private function unknownPlan(): array
    {
        return [
            'answer_type' => 'text',
            'intent' => 'unknown',
            'tables' => [],
            'columns' => [],
            'group_by' => [],
            'filters' => [],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => null,
            'mode' => 'unsupported',
            'confidence' => 0,
            'entities' => [],
            'selected_columns' => [],
            'relationships' => [],
            'sort' => [],
            'limit' => 100,
            'needs_clarification' => false,
            'clarification_question' => null,
        ];
    }

    private function permissionDeniedPlan(): array
    {
        return [
            'answer_type' => 'text',
            'intent' => 'unknown',
            'tables' => [],
            'columns' => [],
            'group_by' => [],
            'filters' => [],
            'requires_finance_access' => false,
            'sql' => null,
            'fallback_message' => 'You do not have permission to access this information.',
            'mode' => 'unsupported',
            'confidence' => 1,
            'entities' => [],
            'selected_columns' => [],
            'relationships' => [],
            'sort' => [],
            'limit' => 100,
            'needs_clarification' => false,
            'clarification_question' => null,
        ];
    }
}
