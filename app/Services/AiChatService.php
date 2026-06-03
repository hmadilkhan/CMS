<?php

namespace App\Services;

use App\Models\AiChat;
use App\Models\AiQueryLog;
use App\Models\AiQueryExample;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class AiChatService
{
    public function __construct(
        private readonly OpenAiService $openAiService,
        private readonly AiQueryPlannerService $aiQueryPlannerService,
        private readonly AiPlanValidatorService $aiPlanValidatorService,
        private readonly AiEntityResolverService $aiEntityResolverService,
        private readonly AiSafeQueryBuilderService $aiSafeQueryBuilderService,
        private readonly AiSqlValidatorService $aiSqlValidatorService,
        private readonly AiQueryExecutorService $aiQueryExecutorService,
        private readonly AiAnswerFormatterService $aiAnswerFormatterService,
        private readonly AiProjectLaneService $aiProjectLaneService,
        private readonly AiTextToSqlService $aiTextToSqlService,
        private readonly AiPermissionService $aiPermissionService,
    ) {
    }

    public function chatsFor(User $user)
    {
        return AiChat::query()
            ->where('user_id', $user->id)
            ->withCount('messages')
            ->latest('last_message_at')
            ->latest('id')
            ->get();
    }

    public function messagePayload(AiChat $chat)
    {
        return $chat->messages->map(fn ($message) => [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $message->content,
            'metadata' => $message->metadata,
            'created_at' => optional($message->created_at)->diffForHumans(),
        ]);
    }

    public function suggestedQuestionsFor(User $user): array
    {
        if ($user->hasAnyRole(['Super Admin', 'Admin'])) {
            return [
                'Total active projects',
                'Profitability report dikhao',
                'Tickets pending by department',
                'Customer wise projects',
            ];
        }

        if ($user->hasAnyRole(['Manager', 'Sales Manager', 'Sub-Contractor Manager'])) {
            return [
                'Department projects',
                'Team pending tickets',
                'Project delays',
            ];
        }

        return [
            'My assigned projects',
            'My pending tickets',
            'Today tasks',
        ];
    }

    public function rename(AiChat $chat, string $title): AiChat
    {
        $chat->update(['title' => trim($title)]);

        return $chat->fresh();
    }

    public function delete(AiChat $chat): void
    {
        $chat->delete();
    }

    public function retryLastUserMessage(User $user, AiChat $chat): AiChat
    {
        $message = $chat->messages()
            ->where('role', 'user')
            ->latest()
            ->value('content');

        if (blank($message)) {
            $chat->messages()->create([
                'role' => 'assistant',
                'content' => 'There is no previous question to retry.',
                'metadata' => [
                    'status' => 'failed',
                    'error_type' => 'invalid_question',
                    'retryable' => false,
                ],
            ]);

            return $chat->fresh('messages');
        }

        return $this->respondToMessage($chat, $user, $message, false);
    }

    public function findUserChat(User $user, int $chatId): AiChat
    {
        return AiChat::query()
            ->where('user_id', $user->id)
            ->with('messages')
            ->findOrFail($chatId);
    }

    public function send(User $user, string $message, ?int $chatId = null): AiChat
    {
        $chat = $chatId ? $this->findUserChat($user, $chatId) : null;

        if (! $chat) {
            $chat = AiChat::create([
                'user_id' => $user->id,
                'title' => Str::limit($message, 48, ''),
                'last_message_at' => now(),
            ]);
        }

        return $this->respondToMessage($chat, $user, $message, true);
    }

    private function respondToMessage(AiChat $chat, User $user, string $message, bool $storeUserMessage): AiChat
    {
        $startedAt = microtime(true);
        $log       = $this->createQueryLog($chat, $user, $message);

        if ($storeUserMessage) {
            $chat->messages()->create(['role' => 'user', 'content' => $message]);
        }

        $chat->update(['last_message_at' => now()]);

        try {
            // 1. Help request — always fast-path
            if ($this->isHelpRequest($message)) {
                return $this->handleHelpRequest($chat, $user, $log, $startedAt, $message);
            }

            // 2. Obvious greetings / one-word non-CRM — skip planner to save cost
            if ($this->isObviouslyGeneralChat($message)) {
                return $this->handleGeneralChat($chat, $user, $message, $log, $startedAt);
            }

            // 2.5 Named project detail — "details/summary of <project>" → a formatted
            //      text summary + per-department table (assignment, age, days in each
            //      lane, status, delay reason, notes). Only when a project is named or
            //      carried from the previous turn; otherwise fall through.
            if ($this->isProjectDetailRequest($message)) {
                $search = $this->resolveProjectSearch($message, $chat);

                if ($search !== '') {
                    return $this->handleProjectDetailSummary($chat, $user, $message, $search, $log, $startedAt);
                }
            }

            // 3. CRM-first: route everything through the query pipeline.
            //    The planner will return intent="unknown"/mode="unsupported" for
            //    non-CRM questions and we gracefully fall through to general chat.
            return $this->handleQueryPlan($chat, $user, $message, $log, $startedAt);

        } catch (Throwable $exception) {
            $log->update([
                'status'       => 'failed',
                'duration_ms'  => (int) ((microtime(true) - $startedAt) * 1000),
                'error_message' => $exception->getMessage(),
            ]);

            return $this->storeFailureMessage($chat, $exception);
        }
    }

    /**
     * Pure general-chat path — used for greetings and as a fallback from the
     * CRM pipeline when the planner returns "unsupported".
     */
    private function handleGeneralChat(
        AiChat      $chat,
        User        $user,
        string      $message,
        AiQueryLog  $log,
        float       $startedAt
    ): AiChat {
        $enrichedMessage = $this->enrichWithPreviousCrmContext($message, $chat);

        $response = $this->openAiService->createResponse(
            $enrichedMessage,
            $chat->openai_response_id,
            $this->userContext($user)
        );

        DB::transaction(function () use ($chat, $response, $log, $startedAt) {
            $chat->messages()->create([
                'role'     => 'assistant',
                'content'  => $response['text'],
                'metadata' => [
                    'openai_response_id' => $response['id'],
                    'model'              => $response['model'],
                ],
            ]);

            $usage = $response['usage'];
            $log->update([
                'status'            => 'success',
                'response_id'       => $response['id'],
                'model'             => $response['model'],
                'prompt_tokens'     => $usage['input_tokens'] ?? null,
                'completion_tokens' => $usage['output_tokens'] ?? null,
                'total_tokens'      => $usage['total_tokens'] ?? null,
                'duration_ms'       => (int) ((microtime(true) - $startedAt) * 1000),
                'request_payload'   => $response['payload'],
                'response_payload'  => $response['raw'],
            ]);

            $chat->update([
                'openai_response_id' => $response['id'],
                'last_message_at'    => now(),
            ]);
        });

        return $chat->fresh('messages');
    }

    private function createQueryLog(AiChat $chat, User $user, string $message): AiQueryLog
    {
        return AiQueryLog::create([
            'ai_chat_id' => $chat->id,
            'user_id' => $user->id,
            'provider' => 'openai',
            'status' => 'pending',
            'model' => config('services.openai.model', 'gpt-4.1-mini'),
            'request_payload' => [
                'message' => $message,
                'previous_response_id' => $chat->openai_response_id,
            ],
        ]);
    }

    private function userContext(User $user): array
    {
        return [
            'app_name' => config('app.name', 'CRM'),
            'user_name' => $user->name,
            'username' => $user->username,
            'roles' => $user->roles->pluck('name')->values()->all(),
        ];
    }

    private function storeFailureMessage(AiChat $chat, Throwable $exception): AiChat
    {
        $message = str_contains(strtolower($exception->getMessage()), 'openai')
            ? 'OpenAI is unavailable right now. Please retry in a moment.'
            : 'I could not complete that request safely. Please try again.';

        $chat->messages()->create([
            'role' => 'assistant',
            'content' => $message,
            'metadata' => [
                'status' => 'failed',
                'error_type' => str_contains(strtolower($exception->getMessage()), 'permission') ? 'permission_denied' : 'openai_failure',
                'retryable' => true,
            ],
        ]);

        $chat->update(['last_message_at' => now()]);

        return $chat->fresh('messages');
    }

    private function handleQueryPlan(AiChat $chat, User $user, string $message, AiQueryLog $log, float $startedAt): AiChat
    {
        $planned = $this->aiQueryPlannerService->plan($message, $user, $this->previousCrmContext($chat));
        $plan = $planned['plan'];
        $response = $planned['openai'];
        $usage = $response['usage'];
        $sqlPreview = null;
        $planValidation = null;
        $entityResolution = null;
        $validation = null;
        $execution = null;
        $answer = null;

        // Lane movement is a named query — bypass the generic SQL pipeline
        if (in_array($plan['intent'], ['project_lane_movement', 'project_lane_summary'], true)) {
            return $this->handleLaneMovementQuery($chat, $user, $message, $plan, $log, $startedAt, $planned['openai']);
        }

        // Planner says this is not a CRM question at all → fall back to general chat
        if ($plan['intent'] === 'unknown' && in_array($plan['mode'] ?? '', ['unsupported', 'unknown'], true)) {
            return $this->handleGeneralChat($chat, $user, $message, $log, $startedAt);
        }

        $textToSqlUsed = false;

        // "fixed_action" plans come from the curated keyword router (named reports
        // with bespoke formatting). Everything else is an open-ended data question
        // the planner mapped dynamically — those go through AI Text-to-SQL first.
        $isDataExplorer = ($plan['mode'] ?? '') !== 'fixed_action';

        if ($plan['intent'] !== 'unknown') {
            // 1. Open-ended questions → let the GPT-4-class model write SQL directly.
            //    It is far more flexible than the structured builder and honours every
            //    filter in the question (location, date range, status, person, …),
            //    which is what makes different prompts return different answers.
            if ($isDataExplorer) {
                $tts = $this->attemptTextToSql($message, $user);

                if ($tts['ok']) {
                    $textToSqlUsed  = true;
                    $sqlPreview     = $tts['sql'];
                    $execution      = $tts['execution'];
                    $answer         = $this->aiAnswerFormatterService->format($message, $plan, $execution);
                    // Text-to-SQL enforces its own table/column/finance permission
                    // checks and SELECT-only validation, so the structured-plan
                    // validators are not re-run here.
                    $planValidation = ['approved' => true, 'reason' => null, 'status' => 'text_to_sql'];
                    $validation     = ['approved' => true, 'reason' => null, 'status' => 'text_to_sql'];
                }
            }

            // 2. Structured pipeline: curated reports, or a fallback when Text-to-SQL
            //    did not run or could not produce a working query.
            if (! $textToSqlUsed) {
                $planValidation = $this->aiPlanValidatorService->validate($plan, $user);

                if ($planValidation['approved'] ?? false) {
                    $entityResolution = $this->aiEntityResolverService->resolve($plan);

                    if (in_array($entityResolution['status'] ?? null, ['clarification_required', 'not_found'], true)) {
                        // Before asking for clarification, give Text-to-SQL a chance on
                        // open-ended questions — it can frequently answer them directly.
                        $tts = $isDataExplorer ? $this->attemptTextToSql($message, $user) : ['ok' => false];

                        if ($tts['ok']) {
                            $textToSqlUsed = true;
                            $sqlPreview    = $tts['sql'];
                            $execution     = $tts['execution'];
                            $answer        = $this->aiAnswerFormatterService->format($message, $plan, $execution);
                            $validation    = ['approved' => true, 'reason' => null, 'status' => 'text_to_sql'];
                        } else {
                            $plan['intent']           = 'unknown';
                            $plan['mode']             = $entityResolution['status'];
                            $plan['fallback_message'] = $entityResolution['message'] ?? 'I need one more detail before I can answer safely.';
                        }
                    } else {
                        $plan       = $entityResolution['plan'] ?? $plan;
                        $sqlPreview = $this->aiSafeQueryBuilderService->build($plan, $user);
                        $validation = $this->aiSqlValidatorService->validate($sqlPreview, $plan, $user);

                        if ($validation['approved'] ?? false) {
                            $execution = $this->aiQueryExecutorService->execute($sqlPreview, $user->id);
                            $answer    = $this->aiAnswerFormatterService->format($message, $plan, $execution);

                            $executionFailed = ! ($execution['success'] ?? false);

                            // A curated query that returns NOTHING for a full-access
                            // (admin/finance) user is almost always a builder/routing
                            // limitation rather than genuinely-empty data — the same
                            // question via Text-to-SQL returns rows. Give the reliable
                            // engine a second attempt. Gated to unscoped users so scoped
                            // roles (Manager/Employee/…) never gain wider row visibility.
                            // "Empty" includes a count query whose only row is 0 — a
                            // common symptom of a mis-routed keyword plan.
                            $curatedEmptyForFullAccess = ! $isDataExplorer
                                && ($execution['success'] ?? false)
                                && $this->resultIsEmpty($plan, $execution)
                                && $this->userHasUnscopedAccess($user);

                            if ($executionFailed || $curatedEmptyForFullAccess) {
                                $tts = $this->attemptTextToSql($message, $user);

                                // On a hard failure accept any working result; on the
                                // empty-but-should-have-data case only swap when
                                // Text-to-SQL actually found data (otherwise keep the
                                // legitimate "no records" answer).
                                if (($tts['ok'] ?? false)
                                    && ($executionFailed || ! $this->resultIsEmpty($plan, $tts['execution'] ?? []))) {
                                    $textToSqlUsed = true;
                                    $sqlPreview    = $tts['sql'];
                                    $execution     = $tts['execution'];
                                    $answer        = $this->aiAnswerFormatterService->format($message, $plan, $execution);
                                }
                            }
                        } else {
                            // --- TEXT-TO-SQL FALLBACK ---
                            // Structured plan couldn't be validated (complex query, unsupported pattern).
                            // Ask AI to write safe SQL directly and execute it.
                            $tts = $this->attemptTextToSql($message, $user);

                            if ($tts['ok']) {
                                $textToSqlUsed = true;
                                $sqlPreview    = $tts['sql'];
                                $execution     = $tts['execution'];
                                $answer        = $this->aiAnswerFormatterService->format($message, $plan, $execution);
                                // Mark validation as approved so the response path proceeds normally
                                $validation    = ['approved' => true, 'reason' => null, 'status' => 'text_to_sql'];
                            }
                        }
                    }
                }
            }
        }

        // If intent is still unknown after all attempts → graceful general-chat fallback
        if ($plan['intent'] === 'unknown') {
            $fallback = $plan['fallback_message'] ?? null;
            if (! $fallback || $fallback === '') {
                return $this->handleGeneralChat($chat, $user, $message, $log, $startedAt);
            }
            $assistantMessage = $fallback;
        } elseif (! ($planValidation['approved'] ?? false)) {
            $assistantMessage = $planValidation['reason']
                ?? "You don't have permission to access that data. Contact your administrator if you think this is a mistake.";
        } elseif (! ($validation['approved'] ?? false)) {
            // Both structured + Text-to-SQL failed — try general chat as last resort
            return $this->handleGeneralChat($chat, $user, $message, $log, $startedAt);
        } elseif (! ($execution['success'] ?? false)) {
            $assistantMessage = $execution['error_message'] ?? 'I ran into an issue executing that query. Please try again or rephrase your question.';
        } elseif (($execution['row_count'] ?? 0) === 0) {
            $assistantMessage = 'No records found for that request. The data may not exist yet, or try adjusting your filters.';
            $answer = ['type' => 'text', 'message' => $assistantMessage, 'columns' => [], 'rows' => [], 'cards' => []];
        } else {
            $assistantMessage = $answer['message'] ?? 'Here are the CRM results.';
        }

        DB::transaction(function () use ($chat, $plan, $response, $usage, $log, $startedAt, $assistantMessage, $sqlPreview, $planValidation, $entityResolution, $validation, $message, $execution, $answer, $textToSqlUsed) {
            $chat->messages()->create([
                'role'     => 'assistant',
                'content'  => $assistantMessage,
                'metadata' => [
                    'type'              => 'query_plan',
                    'query_plan'        => $plan,
                    'plan_validation'   => $planValidation,
                    'entity_resolution' => $entityResolution,
                    'sql_preview'       => $sqlPreview,
                    'sql_validation'    => $validation,
                    'query_execution'   => $execution,
                    'answer'            => $answer,
                    'text_to_sql_used'  => $textToSqlUsed,
                    'status'            => $plan['intent'] === 'unknown'
                        ? 'invalid_question'
                        : ((! ($planValidation['approved'] ?? true) || ! ($validation['approved'] ?? true)) ? 'unsafe_query_rejected' : ((! ($execution['success'] ?? true)) ? 'failed' : 'success')),
                    'retryable'         => $plan['intent'] !== 'unknown' && (! ($planValidation['approved'] ?? true) || ! ($validation['approved'] ?? true) || ! ($execution['success'] ?? true)),
                    'openai_response_id' => $response['id'],
                    'model'             => $response['model'],
                ],
            ]);

            $log->update([
                'status' => $plan['intent'] === 'unknown'
                    ? 'planned_unknown'
                    : (! ($planValidation['approved'] ?? false)
                        ? 'rejected'
                        : (! ($validation['approved'] ?? false)
                        ? 'rejected'
                        : (($execution['success'] ?? false) ? 'executed' : 'execution_failed'))),
                'response_id' => $response['id'],
                'model' => $response['model'],
                'prompt_tokens' => $usage['input_tokens'] ?? null,
                'completion_tokens' => $usage['output_tokens'] ?? null,
                'total_tokens' => $usage['total_tokens'] ?? null,
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                'request_payload' => $response['payload'],
                'response_payload' => [
                    'question' => $message,
                    'query_plan' => $plan,
                    'plan_validation' => $planValidation,
                    'entity_resolution' => $entityResolution,
                    'sql_preview' => $sqlPreview,
                    'sql_validation' => $validation,
                    'query_execution' => $execution,
                    'answer' => $answer,
                    'openai_raw' => $response['raw'],
                ],
                'error_message' => ! ($planValidation['approved'] ?? true)
                    ? ($planValidation['reason'] ?? 'Plan rejected by validator.')
                    : (! ($validation['approved'] ?? true)
                    ? ($validation['reason'] ?? 'Query rejected by validator.')
                    : ((! ($execution['success'] ?? true)) ? ($execution['error_message'] ?? 'Query execution failed.') : null)),
            ]);

            $chat->update([
                'openai_response_id' => $response['id'],
                'last_message_at' => now(),
            ]);
        });

        $this->storeQueryExample($message, $plan, $sqlPreview, $execution);

        return $chat->fresh('messages');
    }

    /**
     * Whether a successful query result carries no usable data. Covers both an
     * empty row set and a count query whose only value is 0 — the latter being a
     * common symptom of a mis-routed keyword plan (a single count of 0 still has
     * row_count = 1, so it would otherwise look "non-empty").
     */
    private function resultIsEmpty(array $plan, array $execution): bool
    {
        $rows = $execution['rows'] ?? [];

        if ((int) ($execution['row_count'] ?? count($rows)) === 0) {
            return true;
        }

        if (($plan['answer_type'] ?? null) === 'count') {
            $first = is_array($rows[0] ?? null) ? $rows[0] : [];
            $value = $first['aggregate'] ?? $first['value'] ?? $first['count'] ?? null;

            if ($value !== null && is_numeric($value) && (int) $value === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * True when the user sees CRM data without row-level scoping (Super Admin,
     * Admin, or any finance-capable role). Mirrors the early-return condition in
     * AiSqlBuilderService::applyRoleScope so the Text-to-SQL safety net never
     * widens visibility for scoped roles.
     */
    private function userHasUnscopedAccess(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Admin'])
            || $this->aiPermissionService->canAccessFinance($user);
    }

    /**
     * The previous successful CRM query plan in this chat, used to resolve
     * conversational follow-ups ("details of those projects"). Returns the prior
     * turn's intent/tables/filters, or null when there is no usable prior query.
     */
    private function previousCrmContext(AiChat $chat): ?array
    {
        $lastAssistant = $chat->messages()
            ->where('role', 'assistant')
            ->latest('id')
            ->first();

        if (! $lastAssistant) {
            return null;
        }

        $meta = is_array($lastAssistant->metadata) ? $lastAssistant->metadata : [];
        $plan = $meta['query_plan'] ?? null;

        if (! is_array($plan) || ($plan['intent'] ?? 'unknown') === 'unknown') {
            return null;
        }

        return [
            'intent'                  => $plan['intent'] ?? null,
            'tables'                  => $plan['tables'] ?? [],
            'columns'                 => $plan['columns'] ?? [],
            'filters'                 => $plan['filters'] ?? [],
            'requires_finance_access' => $plan['requires_finance_access'] ?? false,
        ];
    }

    /**
     * Generate SQL from natural language, run it on the read-only connection, and
     * self-correct once if the database rejects the query. The retry feeds the real
     * DB error back to the model, which recovers most "query failed" cases (wrong
     * column/table name, ambiguous column, bad join, type mismatch).
     *
     * @return array{ok:bool,sql:?array,execution:?array}
     */
    private function attemptTextToSql(string $message, User $user): array
    {
        // SECURITY: row-level scoping is enforced inside AiTextToSqlService —
        // scoped (non-admin/non-finance) users get a mandatory projects.id IN (...)
        // predicate mirroring ProjectService::projectQuery, and any query that
        // can't carry that scope is refused (we then fall back to the structured
        // pipeline). Full-access users (admin/finance) are unscoped by design.
        $textSql = $this->aiTextToSqlService->generate($message, $user);

        if (! ($textSql['success'] ?? false)) {
            return ['ok' => false, 'sql' => $textSql, 'execution' => null];
        }

        $execution = $this->aiQueryExecutorService->execute($textSql, $user->id);

        if ($execution['success'] ?? false) {
            return ['ok' => true, 'sql' => $textSql, 'execution' => $execution];
        }

        // Self-correction: one retry with the real database error as feedback.
        $retry = $this->aiTextToSqlService->regenerate(
            $message,
            $user,
            (string) ($textSql['sql'] ?? ''),
            (string) ($execution['raw_error'] ?? $execution['error_message'] ?? 'Query execution failed.')
        );

        if ($retry['success'] ?? false) {
            $retryExecution = $this->aiQueryExecutorService->execute($retry, $user->id);

            if ($retryExecution['success'] ?? false) {
                return ['ok' => true, 'sql' => $retry, 'execution' => $retryExecution];
            }

            return ['ok' => false, 'sql' => $retry, 'execution' => $retryExecution];
        }

        return ['ok' => false, 'sql' => $textSql, 'execution' => $execution];
    }

    private function handleLaneMovementQuery(AiChat $chat, User $user, string $message, array $plan, AiQueryLog $log, float $startedAt, array $openAiResponse): AiChat
    {
        $searchTerm  = $this->extractProjectSearchTerm($message);
        $wantsDetail = $this->wantsDetailedLaneRows($message);

        // If no project name in this message, check if it's a follow-up to a previous lane query
        if ($searchTerm === '') {
            $searchTerm = $this->getPreviousLaneSearchTerm($chat);
        }

        // "summary" word in prompt means project_lane_summary intent,
        // but if a specific project is mentioned, "summary" means totals FOR that project.
        $isGlobalSummary = $plan['intent'] === 'project_lane_summary' && $searchTerm === '';

        if ($isGlobalSummary) {
            // No project specified → all-projects aggregate
            $result      = $this->aiProjectLaneService->getSummaryReport($user);
            $resultLabel = 'all-projects lane summary';
        } elseif ($wantsDetail && $searchTerm !== '') {
            // Full row-by-row history (user explicitly asked for details)
            $result      = $this->aiProjectLaneService->getMovementReport($user, ['search' => $searchTerm, 'limit' => 10]);
            $resultLabel = 'detailed lane history';
        } else {
            // Specific project (or "summary of project X") → per-department totals
            $result      = $this->aiProjectLaneService->getProjectTotals($user, $searchTerm);
            $resultLabel = 'lane totals per department';
        }

        $rowCount = $result['row_count'];

        if ($rowCount === 0) {
            $notFound         = $searchTerm ? "No department log found for **\"{$searchTerm}\"**. Please check the project name and try again." : 'No department lane data found.';
            $answer           = ['type' => 'text', 'message' => $notFound, 'columns' => [], 'rows' => [], 'cards' => []];
            $assistantMessage = $notFound;
        } elseif ($isGlobalSummary) {
            $assistantMessage = "Here is the department lane summary — how long projects typically stay in each lane ({$rowCount} lanes):";
            $answer           = ['type' => 'table', 'message' => $assistantMessage, 'columns' => $result['columns'], 'rows' => $result['rows'], 'cards' => []];
        } elseif ($wantsDetail) {
            $projectNames     = array_unique(array_column($result['rows'], 'Project'));
            $projectLabel     = count($projectNames) === 1 ? "**{$projectNames[0]}**" : count($projectNames) . ' projects';
            $assistantMessage = "Here is the detailed lane history for {$projectLabel} — {$rowCount} entries:";
            $answer           = ['type' => 'table', 'message' => $assistantMessage, 'columns' => $result['columns'], 'rows' => $result['rows'], 'cards' => []];
        } else {
            $projectNames     = array_unique(array_column($result['rows'], 'Project'));
            $projectLabel     = count($projectNames) === 1 ? "**{$projectNames[0]}**" : count($projectNames) . ' projects';
            $assistantMessage = "Here is how long **{$projectLabel}** spent in each department lane:";
            $answer           = ['type' => 'table', 'message' => $assistantMessage, 'columns' => $result['columns'], 'rows' => $result['rows'], 'cards' => []];
        }

        $usage = $openAiResponse['usage'] ?? [];

        DB::transaction(function () use ($chat, $plan, $openAiResponse, $usage, $log, $startedAt, $assistantMessage, $answer, $message, $rowCount, $searchTerm) {
            $chat->messages()->create([
                'role'     => 'assistant',
                'content'  => $assistantMessage,
                'metadata' => [
                    'type'              => 'query_plan',
                    'query_plan'        => $plan,
                    'answer'            => $answer,
                    'search_term'       => $searchTerm,  // persist for follow-up context
                    'status'            => $rowCount > 0 ? 'success' : 'no_data',
                    'retryable'         => false,
                    'openai_response_id' => $openAiResponse['id'],
                    'model'             => $openAiResponse['model'],
                ],
            ]);

            $log->update([
                'status'            => $rowCount > 0 ? 'executed' : 'no_data',
                'response_id'       => $openAiResponse['id'],
                'model'             => $openAiResponse['model'],
                'prompt_tokens'     => $usage['input_tokens'] ?? null,
                'completion_tokens' => $usage['output_tokens'] ?? null,
                'total_tokens'      => $usage['total_tokens'] ?? null,
                'duration_ms'       => (int) ((microtime(true) - $startedAt) * 1000),
                'request_payload'   => ['message' => $message],
                'response_payload'  => ['answer' => $answer, 'openai_raw' => $openAiResponse['raw']],
            ]);

            $chat->update([
                'openai_response_id' => $openAiResponse['id'],
                'last_message_at'    => now(),
            ]);
        });

        return $chat->fresh('messages');
    }

    /**
     * True for "details/summary of <project>" style requests. Triggers when the
     * message asks for detail/summary AND has a project signal — either the word
     * "project" or an explicit project reference (hyphenated name / quoted / code).
     * This avoids hijacking non-project summaries (e.g. "summary of tickets").
     */
    private function isProjectDetailRequest(string $message): bool
    {
        $lower = mb_strtolower($message);

        $hasDetailWord = false;
        foreach (['detail', 'summary', 'summarize', 'summarise', 'overview', 'full info', 'complete info', 'mukammal', 'tafseel'] as $word) {
            if (str_contains($lower, $word)) {
                $hasDetailWord = true;
                break;
            }
        }

        if (! $hasDetailWord) {
            return false;
        }

        // Exclude generic project reports/summaries (finance, status, department,
        // group-bys, …) — those are handled by the report/AI pipeline, not the
        // single-named-project detail handler.
        foreach (['financ', 'status', 'department', 'acceptance', 'forecast', 'override',
                  'transaction', 'revenue', 'profit', 'commission', 'contract', 'holdback',
                  'loan', 'payment', 'wise', ' by '] as $aspect) {
            if (str_contains($lower, $aspect)) {
                return false;
            }
        }

        // Require a concrete project reference (a name/code), not just a keyword.
        return $this->stripToProjectName($message) !== '' || $this->extractProjectSearchTerm($message) !== '';
    }

    /**
     * Resolve which project a detail request is about. Rather than guess by word
     * position (brittle), strip the command/intent words and use whatever remains
     * as the project name/code; then fall back to an explicit reference, then to
     * the project carried from the previous turn. Empty string when unresolved.
     */
    private function resolveProjectSearch(string $message, AiChat $chat): string
    {
        $stripped = $this->stripToProjectName($message);

        if ($stripped !== '') {
            return $stripped;
        }

        // Hyphenated proper name / quoted name / code.
        $term = $this->extractProjectSearchTerm($message);

        if ($term !== '') {
            return $term;
        }

        // Follow-up: a project carried from a previous lane/detail turn.
        return $this->getPreviousLaneSearchTerm($chat);
    }

    /**
     * Remove command/intent words from a detail request, leaving the project
     * name/code. Word-order independent, so it handles "details of X project",
     * "project detail of X", and "X project details" alike.
     */
    private function stripToProjectName(string $message): string
    {
        $text = ' ' . trim($message) . ' ';

        // Multi-word phrases first (longest match wins).
        $phrases = [
            'can you share', 'could you share', 'can you show', 'please show me', 'show me',
            'give me', 'tell me', 'i want to see', 'i would like', 'i want', 'i need',
            'the project details of', 'the project detail of', 'project details of', 'project detail of',
            'the project summary of', 'project summary of', 'project overview of',
            'the details of', 'the detail of', 'details of', 'detail of',
            'the summary of', 'summary of', 'overview of', 'full info of',
            'complete details of', 'complete detail of', 'info of',
        ];
        foreach ($phrases as $p) {
            $text = preg_replace('/\b' . preg_quote($p, '/') . '\b/iu', ' ', $text);
        }

        // Remaining standalone keywords (English + Roman Urdu).
        $words = [
            'project', 'projects', 'details', 'detail', 'summary', 'summarize', 'summarise',
            'overview', 'info', 'information', 'show', 'me', 'the', 'of', 'for', 'about',
            'please', 'give', 'tell', 'can', 'you', 'share', 'want', 'kindly', 'complete', 'full',
            'dikhao', 'dikha', 'batao', 'bata', 'mujhe', 'ki', 'ka', 'ke', 'nikalo',
        ];
        $text = preg_replace('/\b(' . implode('|', array_map(fn ($w) => preg_quote($w, '/'), $words)) . ')\b/iu', ' ', $text);

        $text = trim(preg_replace('/\s+/u', ' ', (string) $text));
        $text = trim($text, " -:,.\t");

        return (mb_strlen($text) >= 2 && mb_strlen($text) <= 60) ? $text : '';
    }

    private function handleProjectDetailSummary(AiChat $chat, User $user, string $message, string $search, AiQueryLog $log, float $startedAt): AiChat
    {
        $detail     = $this->aiProjectLaneService->getProjectDetail($user, $search);
        $projects   = $detail['projects'] ?? [];
        $matchCount = (int) ($detail['project_count'] ?? count($projects));

        if ($projects === []) {
            $assistantMessage = "I couldn't find a project matching \"{$search}\". Please check the name or code and try again.";
            $answer = ['type' => 'text', 'message' => $assistantMessage, 'columns' => [], 'rows' => [], 'cards' => []];

            return $this->storeProjectDetailMessage($chat, $message, $assistantMessage, $answer, $log, $startedAt, 'no_data', $search);
        }

        // Multiple matches → ask which one (by code), with a compact table.
        if ($matchCount > 1) {
            $rows = array_map(fn (array $p) => [
                'Project'            => $p['project_name'],
                'Code'               => $p['code'],
                'Customer'           => $p['customer_name'],
                'Current Department' => $p['current_department'],
                'Status'             => $p['current_status'],
                'Age (days)'         => $p['age_days'],
            ], $projects);

            $assistantMessage = "I found {$matchCount} projects matching \"{$search}\". Tell me which one (by code) and I'll show the full details:";
            $answer = [
                'type'    => 'table',
                'message' => $assistantMessage,
                'columns' => ['Project', 'Code', 'Customer', 'Current Department', 'Status', 'Age (days)'],
                'rows'    => $rows,
                'cards'   => [],
            ];

            return $this->storeProjectDetailMessage($chat, $message, $assistantMessage, $answer, $log, $startedAt, 'success', $search);
        }

        // Single project → formatted text summary + per-department table.
        $project          = $projects[0];
        $assistantMessage = $this->formatProjectDetailText($project);
        $answer = [
            'type'    => 'table',
            'message' => $assistantMessage,
            'columns' => ['Department', 'Days', 'Status', 'Entry', 'Exit', 'Notes', 'Action By'],
            'rows'    => $project['departments'],
            'cards'   => [],
        ];

        return $this->storeProjectDetailMessage($chat, $message, $assistantMessage, $answer, $log, $startedAt, 'success', $search);
    }

    private function formatProjectDetailText(array $p): string
    {
        $delayThreshold = (int) config('ai.project_detail.lane_delay_days', 30);

        $lines = [
            "{$p['project_name']} ({$p['code']})",
            '',
            'Customer:        ' . $p['customer_name'],
            'Assigned to:     ' . $p['assigned_employee'],
            'Current status:  ' . $p['current_status'] . ' — currently in ' . $p['current_department'],
            'Project age:     ' . $p['age_days'] . ' days (created ' . $p['created_at'] . ')',
            'Total time across departments: ' . $p['total_days'] . ' days',
            '',
        ];

        if ($p['bottleneck_days'] > $delayThreshold) {
            $issue = "Heads up: this project spent {$p['bottleneck_days']} days in {$p['bottleneck_dept']} — its longest stage.";
            $issue .= ($p['bottleneck_notes'] ?? '') !== ''
                ? ' Notes there: ' . $p['bottleneck_notes']
                : ' No notes were recorded for that stage.';
            $lines[] = $issue;
        } elseif (in_array($p['current_status'], ['Hold', 'Cancelled'], true)) {
            $lines[] = "This project is currently {$p['current_status']} in {$p['current_department']}.";
        } else {
            $lines[] = 'Progress looks normal — no single department is taking unusually long.';
        }

        $lines[] = '';
        $lines[] = 'Time spent in each department:';

        return implode("\n", $lines);
    }

    private function storeProjectDetailMessage(AiChat $chat, string $message, string $assistantMessage, array $answer, AiQueryLog $log, float $startedAt, string $status, string $search): AiChat
    {
        DB::transaction(function () use ($chat, $message, $assistantMessage, $answer, $log, $startedAt, $status, $search) {
            $chat->messages()->create([
                'role'     => 'assistant',
                'content'  => $assistantMessage,
                'metadata' => [
                    'type'        => 'query_plan',
                    'query_plan'  => [
                        'intent'  => 'project_detail_summary',
                        'tables'  => ['projects', 'tasks', 'departments'],
                        'filters' => [['column' => 'project_name', 'operator' => 'like', 'value' => $search]],
                    ],
                    'answer'      => $answer,
                    'search_term' => $search,
                    'status'      => $status,
                    'retryable'   => false,
                ],
            ]);

            $log->update([
                'status'           => $status === 'no_data' ? 'no_data' : 'executed',
                'duration_ms'      => (int) ((microtime(true) - $startedAt) * 1000),
                'request_payload'  => ['message' => $message],
                'response_payload' => ['answer' => $answer],
            ]);

            $chat->update(['last_message_at' => now()]);
        });

        return $chat->fresh('messages');
    }

    private function extractProjectSearchTerm(string $message): string
    {
        // Match hyphenated proper names: Annie-Ewing, John-Doe
        if (preg_match('/\b([A-Z][a-zA-Z]+-[A-Z][a-zA-Z]+)\b/', $message, $m)) {
            return $m[1];
        }

        // Match quoted name: "Annie Ewing" or 'Annie Ewing'
        if (preg_match('/["\']([A-Za-z][A-Za-z\s\-]+)["\']/', $message, $m)) {
            return trim($m[1]);
        }

        // Match alphanumeric project code: 1001, SS-001 etc.
        if (preg_match('/\b([A-Z]{1,4}-\d{3,6}|\b\d{4,6})\b/', $message, $m)) {
            return $m[1];
        }

        return '';
    }

    /**
     * Retrieve the search_term from the most recent lane movement assistant message.
     * Used to carry context into follow-up questions.
     */
    private function getPreviousLaneSearchTerm(AiChat $chat): string
    {
        $prevMessage = $chat->messages()
            ->where('role', 'assistant')
            ->latest()
            ->first();

        if (! $prevMessage) {
            return '';
        }

        $meta = is_array($prevMessage->metadata) ? $prevMessage->metadata : [];
        $intent = $meta['query_plan']['intent'] ?? '';

        // Only carry context from lane-type queries
        if (! in_array($intent, ['project_lane_movement', 'project_lane_summary'], true)) {
            return '';
        }

        return (string) ($meta['search_term'] ?? '');
    }

    /**
     * True when the user explicitly wants individual log rows, not aggregated totals.
     */
    private function wantsDetailedLaneRows(string $message): bool
    {
        $lower = strtolower($message);

        return str_contains($lower, 'detail')
            || str_contains($lower, 'full')
            || str_contains($lower, 'all entries')
            || str_contains($lower, 'har entry')
            || str_contains($lower, 'complete log')
            || str_contains($lower, 'individual')
            || str_contains($lower, 'ek ek')
            || str_contains($lower, 'poora log')
            || str_contains($lower, 'complete history');
    }

    /**
     * When a general-chat follow-up seems to reference the previous CRM result,
     * prepend a compact text snapshot so OpenAI can answer questions about it.
     */
    private function enrichWithPreviousCrmContext(string $message, AiChat $chat): string
    {
        $looksLikeFollowUp = preg_match(
            '/\b(is result|is data|ye data|pichla|previous result|above|uper wala|is table|yeh table|summarize this|sort this|filter this|is mein|isko)\b/i',
            $message
        );

        if (! $looksLikeFollowUp) {
            return $message;
        }

        $prevMessage = $chat->messages()
            ->where('role', 'assistant')
            ->latest()
            ->first();

        if (! $prevMessage) {
            return $message;
        }

        $meta   = is_array($prevMessage->metadata) ? $prevMessage->metadata : [];
        $answer = $meta['answer'] ?? null;

        if (! is_array($answer) || empty($answer['rows'])) {
            return $message;
        }

        $rows    = $answer['rows'];
        $columns = $answer['columns'] ?? (empty($rows) ? [] : array_keys($rows[0]));
        $total   = count($rows);
        $sample  = array_slice($rows, 0, 20);

        $contextLines = ["[Previous CRM result — {$total} rows, columns: " . implode(', ', $columns) . "]"];
        foreach ($sample as $row) {
            $contextLines[] = json_encode($row, JSON_UNESCAPED_UNICODE);
        }
        if ($total > 20) {
            $contextLines[] = '... (' . ($total - 20) . ' more rows not shown)';
        }

        return implode("\n", $contextLines) . "\n\nUser question: " . $message;
    }

    /**
     * True for messages that are clearly non-CRM conversational messages
     * (pure greetings, thanks, one-word social phrases). These bypass the
     * CRM planner entirely to save an API call.
     */
    private function isObviouslyGeneralChat(string $message): bool
    {
        $trimmed = mb_strtolower(trim($message, " \t\n\r.,!?"));

        $obvious = [
            'hi', 'hello', 'hey', 'helo', 'hii',
            'thanks', 'thank you', 'shukriya', 'shukria',
            'ok', 'okay', 'k', 'ок',
            'bye', 'goodbye', 'alvida',
            'good morning', 'good evening', 'good afternoon', 'good night',
            'how are you', 'how r u', 'aap kaisa hain', 'tum kaisa ho',
            'who are you', 'what are you', 'aap kaun hain',
        ];

        if (in_array($trimmed, $obvious, true)) {
            return true;
        }

        // Single word with no CRM connotation
        if (! str_contains($trimmed, ' ') && mb_strlen($trimmed) < 6) {
            return true;
        }

        return false;
    }

    private function isHelpRequest(string $message): bool
    {
        $lower = strtolower(trim($message));
        $keywords = ['help', 'kya kar sakte', 'what can you do', 'capabilities', 'kya poochu', 'kya puchna', 'guide me', 'mujhe guide', 'commands', 'features'];

        foreach ($keywords as $keyword) {
            if (str_contains($lower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function handleHelpRequest(AiChat $chat, User $user, AiQueryLog $log, float $startedAt, string $message): AiChat
    {
        $suggested = $this->suggestedQuestionsFor($user);
        $roleNames = $user->roles->pluck('name')->join(', ');
        $suggestedList = implode("\n", array_map(fn ($q) => "- {$q}", $suggested));

        $helpText = implode("\n", [
            "Hi {$user->name}! Here's what I can help you with as **{$roleNames}**:",
            '',
            '**Live CRM Data**',
            'Ask me about projects, tasks, tickets, customers, or teams — I\'ll fetch the latest data securely.',
            '',
            '**Suggested questions for your role:**',
            $suggestedList,
            '',
            '**General Questions**',
            'Ask me how the CRM works, what a status means, or how a workflow operates.',
            '',
            '**What I cannot do:**',
            '- Create, update, or delete any records',
            '- Access data outside your role\'s permissions',
            '',
            'Just type your question naturally and I\'ll do my best to help!',
        ]);

        $chat->messages()->create([
            'role' => 'assistant',
            'content' => $helpText,
            'metadata' => ['type' => 'help_response', 'status' => 'success'],
        ]);

        $log->update([
            'status' => 'success',
            'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            'request_payload' => ['message' => $message],
        ]);

        $chat->update(['last_message_at' => now()]);

        return $chat->fresh('messages');
    }

    private function storeQueryExample(string $message, array $plan, ?array $sqlPreview, ?array $execution): void
    {
        if (! $sqlPreview || ($plan['intent'] ?? 'unknown') === 'unknown' || ! Schema::hasTable('ai_query_examples')) {
            return;
        }

        $example = AiQueryExample::firstOrCreate(
            ['question' => Str::limit($message, 500, '')],
            [
                'plan' => $plan,
                'sql' => $sqlPreview['sql'] ?? null,
            ]
        );

        $example->increment(($execution['success'] ?? false) ? 'success_count' : 'fail_count');
    }
}
