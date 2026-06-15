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
        private readonly AiFieldDictionaryService $aiFieldDictionaryService,
        private readonly AiProfiler $profiler,
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
        if ($storeUserMessage) {
            $chat->messages()->create(['role' => 'user', 'content' => $message]);
        }

        $chat->update(['last_message_at' => now()]);

        // Multi-intent: let the LLM split a message that asks for several things
        // ("financing details of this project AND also the logs") into separate,
        // self-contained questions, then answer each one in its own reply.
        if ($this->looksCompound($message)) {
            $parts = $this->decompose($message, $this->buildConversationMemory($chat, $message));

            if (count($parts) > 1) {
                foreach ($parts as $part) {
                    $this->routeSingle($chat, $user, $part);
                }

                return $chat->fresh('messages');
            }
        }

        return $this->routeSingle($chat, $user, $message);
    }

    /**
     * Route ONE self-contained question through the gate chain and answer it.
     * Cheap, unambiguous fast-paths (help, greetings, bare codes, field
     * explanations, named project detail) are tried first; anything else — and
     * anything a gate cannot confidently resolve — falls through to the LLM
     * query pipeline.
     */
    private function routeSingle(AiChat $chat, User $user, string $message): AiChat
    {
        $this->profiler->reset();

        $startedAt = microtime(true);
        $log       = $this->createQueryLog($chat, $user, $message);

        try {
            // 1. Help request — always fast-path
            if ($this->isHelpRequest($message)) {
                return $this->handleHelpRequest($chat, $user, $log, $startedAt, $message);
            }

            // 1.5 Bare project code / number ("1048", "SS-001") — almost always a
            //     lookup (often the reply to a "which one? give the code" prompt).
            //     Route to the clean project-detail summary; fall through if it
            //     matches nothing, so a stray number never dead-ends.
            if ($this->looksLikeBareProjectReference($message)) {
                $detail = $this->handleProjectDetailSummary($chat, $user, $message, trim($message), $log, $startedAt);

                if ($detail !== null) {
                    return $detail;
                }
            }

            // 2. Obvious greetings / one-word non-CRM — skip planner to save cost
            if ($this->isObviouslyGeneralChat($message)) {
                return $this->handleGeneralChat($chat, $user, $message, $log, $startedAt);
            }

            // 2.2 Field / term explanation — "meter_spot_result kya hai?", "what
            //     values can ticket status have?", "what is PTO?". Answered from
            //     the data dictionary (no OpenAI cost, always accurate, and
            //     permission-aware). Only fires for genuine explanation requests
            //     that resolve to a known field/term; otherwise falls through.
            if ($this->aiFieldDictionaryService->isExplanationRequest($message)) {
                $explained = $this->aiFieldDictionaryService->explain($message, $user);

                if ($explained['handled']) {
                    return $this->storeDictionaryMessage($chat, $message, $explained['message'], $log, $startedAt);
                }
            }

            // 2.4 Estimated & Actual Project Costs — the CRM "Financial Ledger" panel.
            //      Its estimated material/labor figures are COMPUTED from the catalogue
            //      + customer specs (not stored columns), so Text-to-SQL cannot reproduce
            //      them. Handle deterministically, mirroring the ProjectCost component,
            //      gated on the same permission the CRM uses. Falls through when no
            //      project resolves, so a non-cost question never dead-ends here.
            if ($this->isProjectCostLedgerRequest($message)) {
                $ledger = $this->handleProjectCostLedger($chat, $user, $message, $log, $startedAt);

                if ($ledger !== null) {
                    return $ledger;
                }
            }

            // 2.5 Named project detail — "details/summary of <project>" → a formatted
            //      text summary + per-department table (assignment, age, days in each
            //      lane, status, delay reason, notes). Only when a project is named or
            //      carried from the previous turn; otherwise fall through.
            if ($this->isProjectDetailRequest($message)) {
                $search = $this->resolveProjectSearch($message, $chat);

                if ($search !== '') {
                    $detail = $this->handleProjectDetailSummary($chat, $user, $message, $search, $log, $startedAt);

                    // No project matched the term — it was probably a non-project
                    // request ("high priority ticket details"). Fall through to the
                    // LLM pipeline instead of dead-ending with "couldn't find...".
                    if ($detail !== null) {
                        return $detail;
                    }
                }
            }

            // 2.7 "Who moved project X" / project move history — answered directly
            //      from the activity log (with correct subject_type + code→id
            //      resolution that AI-written SQL routinely gets wrong). Falls
            //      through when no project is named or nothing matched.
            if ($this->isProjectMoveActivityRequest($message)) {
                $moved = $this->handleProjectMoveActivity($chat, $user, $message, $log, $startedAt);

                if ($moved !== null) {
                    return $moved;
                }
            }

            // 3. CRM-first: route everything through the query pipeline.
            //    The planner will return intent="unknown"/mode="unsupported" for
            //    non-CRM questions and we gracefully fall through to general chat.
            return $this->handleQueryPlan($chat, $user, $message, $log, $startedAt);

        } catch (Throwable $exception) {
            $log->update(array_merge([
                'status'       => 'failed',
                'duration_ms'  => (int) ((microtime(true) - $startedAt) * 1000),
                'error_message' => $exception->getMessage(),
            ], $this->profileColumns($startedAt, 'error')));

            return $this->storeFailureMessage($chat, $exception);
        }
    }

    /**
     * Cheap pre-check: does the message look like it bundles more than one
     * request? Only when this is true do we spend an LLM call to actually split
     * it — so simple messages never pay for decomposition.
     */
    private function looksCompound(string $message): bool
    {
        $lower = ' ' . mb_strtolower($message) . ' ';

        foreach ([' also ', ' as well', '; ', ' plus ', ' aur ', ' bhi ', ' saath ', ' along with '] as $cue) {
            if (str_contains($lower, $cue)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ask the LLM to split a multi-intent message into independent, SELF-CONTAINED
     * questions (shared context like "this project" is carried into each). Returns
     * one element (the original message) when it is a single request or on any
     * failure, so the caller can always proceed safely.
     *
     * @return array<int,string>
     */
    private function decompose(string $message, string $conversationMemory = ''): array
    {
        try {
            $this->profiler->stage('decompose');

            // Give the splitter the prior turns so it can resolve back-references
            // ("this project", "those", "isko") to the CONCRETE subject and bake the
            // real name into each split question. Without this the split parts kept
            // an unresolved "this project", leaving resolution to the downstream
            // planner — which was non-deterministic (the follow-up sometimes came
            // back with 0 rows). Substituting the name up front makes each
            // sub-question self-contained and the result deterministic.
            $input = $conversationMemory !== ''
                ? "Conversation so far (use it ONLY to resolve references — replace \"this project\", \"these\", \"those\", \"isko\", \"inki\" with the concrete subject, e.g. the actual project name):\n{$conversationMemory}\n\nMessage to split: {$message}"
                : $message;

            $response = $this->openAiService->createJsonResponse(
                'You split a user\'s CRM chat message into the distinct, independent questions it contains. '
                . 'Return JSON {"questions": [...]}. Each question MUST be self-contained: when a "Conversation so far" '
                . 'block is provided, RESOLVE references like "this project", "these", "those", "isko", "inki" by '
                . 'REPLACING them with the concrete subject so every question stands alone WITHOUT needing prior '
                . 'context. Use the EXACT, COMPLETE subject as it appeared earlier — include the full project name '
                . 'with any address/unit suffix (e.g. "Yunjiao-Guan - 61st Ave", NOT just "Yunjiao-Guan") and never '
                . 'shorten or paraphrase it. Carry the shared subject across all split parts '
                . '(e.g. "financing of this project AND its logs" → "financing details of <Project Name>" + '
                . '"logs of <Project Name>"). If the message is really a single request, return exactly one question. '
                . 'Keep the user\'s language (English/Roman-Urdu). Maximum 4 questions.',
                $input,
                400,
                [
                    'type'   => 'json_schema',
                    'name'   => 'message_decomposition',
                    'strict' => true,
                    'schema' => [
                        'type'                 => 'object',
                        'additionalProperties' => false,
                        'required'             => ['questions'],
                        'properties'           => [
                            'questions' => ['type' => 'array', 'items' => ['type' => 'string']],
                        ],
                    ],
                ]
            );

            $questions = array_values(array_filter(
                array_map('trim', (array) ($response['json']['questions'] ?? [])),
                fn ($q) => $q !== ''
            ));

            return $questions !== [] ? array_slice($questions, 0, 4) : [$message];
        } catch (Throwable) {
            // Decomposition is best-effort — never let it break a normal answer.
            return [$message];
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

        // Ground the model with relevant data-dictionary snippets so it explains
        // CRM fields/terms accurately instead of guessing, and can guide users
        // who ask about the structure or mention a field by name.
        $fieldContext = $this->aiFieldDictionaryService->contextFor($message, $user);
        if ($fieldContext !== '') {
            $enrichedMessage = $fieldContext . "\n\nUser question: " . $enrichedMessage;
        }

        $this->profiler->stage('general_chat');
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
            $log->update(array_merge([
                'status'            => 'success',
                'response_id'       => $response['id'],
                'model'             => $response['model'],
                'prompt_tokens'     => $usage['input_tokens'] ?? null,
                'completion_tokens' => $usage['output_tokens'] ?? null,
                'total_tokens'      => $usage['total_tokens'] ?? null,
                'duration_ms'       => (int) ((microtime(true) - $startedAt) * 1000),
                'request_payload'   => $response['payload'],
                'response_payload'  => $response['raw'],
            ], $this->profileColumns($startedAt, 'general_chat')));

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
            'question_hash' => $this->questionHash($message),
            'request_payload' => [
                'message' => $message,
                'previous_response_id' => $chat->openai_response_id,
            ],
        ]);
    }

    /**
     * Stable hash of a normalized question (lowercased, whitespace-collapsed),
     * used by ai:profile-report to estimate the repeat-question rate (caching
     * potential). Always set — it is cheap and does not depend on profiling.
     */
    private function questionHash(string $message): string
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', mb_strtolower($message)));

        return md5($normalized);
    }

    /**
     * Profiling column values for an ai_query_logs row, or [] when profiling is
     * disabled. Merged into the existing $log->update() payloads so persistence
     * stays in one transaction and adds nothing when the flag is off.
     *
     * @return array<string,mixed>
     */
    private function profileColumns(float $startedAt, string $engine): array
    {
        return $this->profiler->toColumns(
            (int) ((microtime(true) - $startedAt) * 1000),
            $engine
        );
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
        $memory  = $this->buildConversationMemory($chat, $message);
        $planned = $this->aiQueryPlannerService->plan($message, $user, $this->previousCrmContext($chat), $memory);
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
                $tts = $this->attemptTextToSql($message, $user, $memory);

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
                } else {
                    // Open-ended Text-to-SQL did not produce a working query →
                    // we drop back to the structured builder below.
                    $this->profiler->incrementFallback('text_to_sql_to_structured');
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
                        if ($isDataExplorer) {
                            $this->profiler->incrementFallback('entity_resolution_to_text_to_sql');
                        }
                        $tts = $isDataExplorer ? $this->attemptTextToSql($message, $user, $memory) : ['ok' => false];

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
                                $this->profiler->incrementFallback($executionFailed ? 'execution_failed_to_text_to_sql' : 'curated_empty_to_text_to_sql');
                                $tts = $this->attemptTextToSql($message, $user, $memory);

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
                            $this->profiler->incrementFallback('sql_validation_to_text_to_sql');
                            $tts = $this->attemptTextToSql($message, $user, $memory);

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
            // Append field-dictionary guidance ("Did you mean these fields?") only
            // for genuinely unrecognised questions — NOT when we are asking the user
            // to disambiguate a matched entity (clarification_required / not_found),
            // where that guidance is irrelevant noise that clutters the prompt.
            $isEntityClarification = in_array($plan['mode'] ?? '', ['clarification_required', 'not_found'], true);
            $assistantMessage = $isEntityClarification
                ? $fallback
                : $fallback . "\n\n" . $this->aiFieldDictionaryService->guidanceFor($message, $user);
        } elseif (! ($planValidation['approved'] ?? false)) {
            $assistantMessage = $planValidation['reason']
                ?? "You don't have permission to access that data. Contact your administrator if you think this is a mistake.";
        } elseif (! ($validation['approved'] ?? false)) {
            // Both structured + Text-to-SQL failed — try general chat as last resort
            return $this->handleGeneralChat($chat, $user, $message, $log, $startedAt);
        } elseif (! ($execution['success'] ?? false)) {
            $assistantMessage = $execution['error_message'] ?? 'I ran into an issue executing that query. Please try again or rephrase your question.';
        } elseif (($execution['row_count'] ?? 0) === 0) {
            // Query ran successfully but matched 0 rows — this is a valid result
            // (e.g. "customers with missing phone" when all customers have phones).
            // Do NOT append guidanceFor() here: that helper is for unknown-intent
            // situations; appending it to a clean 0-row result misleads the user
            // into thinking the system misunderstood them.
            $assistantMessage = 'No records found matching your request. The data may not exist yet, or try adjusting your filters.';
            $answer = ['type' => 'text', 'message' => $assistantMessage, 'columns' => [], 'rows' => [], 'cards' => []];
        } else {
            $assistantMessage = $answer['message'] ?? 'Here are the CRM results.';
        }

        $engine = $textToSqlUsed
            ? 'text_to_sql'
            : ($plan['intent'] === 'unknown' ? 'clarification' : 'structured');
        $profileColumns = $this->profileColumns($startedAt, $engine);

        DB::transaction(function () use ($chat, $plan, $response, $usage, $log, $startedAt, $assistantMessage, $sqlPreview, $planValidation, $entityResolution, $validation, $message, $execution, $answer, $textToSqlUsed, $profileColumns) {
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

            $log->update(array_merge([
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
            ], $profileColumns));

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
    /**
     * Build a compact transcript of the recent conversation so the planner and
     * Text-to-SQL can resolve references ("those", "them", "inko", "wahi wale")
     * and compound a previous query with new constraints — instead of relying on
     * brittle regex. Includes, per assistant turn, the intent/filters/SQL it used
     * and the result size, which is exactly what the model needs to build on the
     * last answer. Returns '' when there is no usable prior context.
     */
    private function buildConversationMemory(AiChat $chat, string $currentMessage, int $maxMessages = 6): string
    {
        $messages = $chat->messages()->orderBy('id')->get();

        if ($messages->isEmpty()) {
            return '';
        }

        // The current question is passed to the model separately — drop it here.
        $last = $messages->last();
        if ($last && $last->role === 'user' && trim((string) $last->content) === trim($currentMessage)) {
            $messages = $messages->slice(0, -1);
        }

        $window = $messages->slice(-$maxMessages);

        if ($window->isEmpty()) {
            return '';
        }

        $lines = [];

        foreach ($window as $m) {
            if ($m->role === 'user') {
                $lines[] = 'User: ' . Str::limit(trim((string) $m->content), 200);

                continue;
            }

            $meta   = is_array($m->metadata) ? $m->metadata : [];
            $plan   = is_array($meta['query_plan'] ?? null) ? $meta['query_plan'] : [];
            $intent = $plan['intent'] ?? null;

            if ($intent && $intent !== 'unknown') {
                $parts = ['intent=' . $intent];

                $filters = $plan['filters'] ?? [];
                if (is_array($filters) && $filters !== []) {
                    $parts[] = 'filters=' . Str::limit((string) json_encode(array_values($filters), JSON_UNESCAPED_UNICODE), 240);
                }

                $sql = $meta['sql_preview']['sql'] ?? null;
                if (is_string($sql) && $sql !== '') {
                    $parts[] = 'sql=' . Str::limit($sql, 300);
                }

                $answer = is_array($meta['answer'] ?? null) ? $meta['answer'] : [];
                if (is_array($answer['rows'] ?? null)) {
                    $cols = is_array($answer['columns'] ?? null) ? $answer['columns'] : [];
                    $parts[] = 'result=' . count($answer['rows']) . ' rows'
                        . ($cols !== [] ? ' [' . implode(', ', array_slice($cols, 0, 8)) . ']' : '');
                }

                $lines[] = 'Assistant answered (' . implode('; ', $parts) . ')';

                continue;
            }

            $lines[] = 'Assistant: ' . Str::limit(trim((string) $m->content), 160);
        }

        return implode("\n", $lines);
    }

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
    private function attemptTextToSql(string $message, User $user, string $conversationMemory = ''): array
    {
        // SECURITY: row-level scoping is enforced inside AiTextToSqlService —
        // scoped (non-admin/non-finance) users get a mandatory projects.id IN (...)
        // predicate mirroring ProjectService::projectQuery, and any query that
        // can't carry that scope is refused (we then fall back to the structured
        // pipeline). Full-access users (admin/finance) are unscoped by design.
        $textSql = $this->aiTextToSqlService->generate($message, $user, $conversationMemory);

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
            (string) ($execution['raw_error'] ?? $execution['error_message'] ?? 'Query execution failed.'),
            $conversationMemory
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
        $profileColumns = $this->profileColumns($startedAt, 'lane');

        DB::transaction(function () use ($chat, $plan, $openAiResponse, $usage, $log, $startedAt, $assistantMessage, $answer, $message, $rowCount, $searchTerm, $profileColumns) {
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

            $log->update(array_merge([
                'status'            => $rowCount > 0 ? 'executed' : 'no_data',
                'response_id'       => $openAiResponse['id'],
                'model'             => $openAiResponse['model'],
                'prompt_tokens'     => $usage['input_tokens'] ?? null,
                'completion_tokens' => $usage['output_tokens'] ?? null,
                'total_tokens'      => $usage['total_tokens'] ?? null,
                'duration_ms'       => (int) ((microtime(true) - $startedAt) * 1000),
                'request_payload'   => ['message' => $message],
                'response_payload'  => ['answer' => $answer, 'openai_raw' => $openAiResponse['raw']],
            ], $profileColumns));

            $chat->update([
                'openai_response_id' => $openAiResponse['id'],
                'last_message_at'    => now(),
            ]);
        });

        return $chat->fresh('messages');
    }

    /**
     * True for "Estimated & Actual Project Costs" / "project cost" / "Financial
     * Ledger" style questions. The handler still requires a resolvable project, so
     * a broad aggregate cost question (no project named) falls through harmlessly.
     */
    private function isProjectCostLedgerRequest(string $message): bool
    {
        $lower = mb_strtolower($message);

        foreach ([
            'estimated & actual', 'estimated and actual',
            'estimated project cost', 'actual project cost',
            'project cost', 'project costs',
            'financial ledger', 'pre and post project cost', 'pre and post cost',
        ] as $cue) {
            if (str_contains($lower, $cue)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Answer an "Estimated & Actual Project Costs" question deterministically,
     * mirroring the CRM's Financial Ledger panel. Returns null (fall through) when
     * no project resolves; a permission-denied message when the user lacks the
     * 'Pre and Post Project Cost' permission the CRM gates that panel with.
     */
    private function handleProjectCostLedger(AiChat $chat, User $user, string $message, AiQueryLog $log, float $startedAt): ?AiChat
    {
        // Strip the cost/ledger vocabulary first so it never pollutes the project
        // name (otherwise "Estimated & Actual Project Costs of <name>" leaves
        // tokens like "estimated"/"actual"/"cost" that match no project).
        $cleaned = preg_replace(
            '/\b(estimated|actual|financial|ledger|pre|post|profit|profitability|material|labou?r|permit|internal|contract|realized|cost|costs)\b/iu',
            ' ',
            $message
        ) ?? $message;

        $search = $this->extractProjectSearchTerm($message);

        if ($search === '') {
            $search = $this->stripToProjectName($cleaned);
        }

        if ($search === '') {
            $search = $this->getRecentProjectSearch($chat);
        }

        if ($search === '') {
            return null;
        }

        $result = $this->aiProjectLaneService->getProjectCostLedger($user, $search);

        if (! ($result['authorized'] ?? false)) {
            $denied = "You don't have permission to view a project's Estimated & Actual Project Costs. Please contact your administrator if you think this is a mistake.";
            $answer = ['type' => 'text', 'message' => $denied, 'columns' => [], 'rows' => [], 'cards' => []];

            return $this->storeProjectDetailMessage($chat, $message, $denied, $answer, $log, $startedAt, 'permission_denied', $search);
        }

        $projects = $result['projects'] ?? [];
        $count    = (int) ($result['project_count'] ?? count($projects));

        if ($projects === []) {
            return null;
        }

        // Multiple matches → ask which one (by code), like the project-detail handler.
        if ($count > 1) {
            $rows = array_map(fn (array $p) => [
                'Project'  => $p['project_name'],
                'Code'     => $p['code'],
                'Customer' => $p['customer_name'],
            ], $projects);

            $msg    = "I found {$count} projects matching \"{$search}\". Tell me which one (by code) and I'll show its Estimated & Actual Project Costs:";
            $answer = ['type' => 'table', 'message' => $msg, 'columns' => ['Project', 'Code', 'Customer'], 'rows' => $rows, 'cards' => []];

            return $this->storeProjectDetailMessage($chat, $message, $msg, $answer, $log, $startedAt, 'success', $search);
        }

        $p = $projects[0];
        $l = $result['ledger'];

        $money = static fn ($v) => '$' . number_format((float) $v, 2);
        $pct   = static fn ($v) => number_format((float) $v, 2) . '%';

        $rows = [
            ['Item' => 'Internal Contract Amount', 'Estimated' => $money($l['internal_contract']),    'Actual' => $money($l['internal_contract'])],
            ['Item' => 'Material Cost',            'Estimated' => $money($l['estimated']['material']), 'Actual' => $money($l['actual']['material'])],
            ['Item' => 'Labor Cost',               'Estimated' => $money($l['estimated']['labor']),    'Actual' => $money($l['actual']['labor'])],
            ['Item' => 'Permit Cost',              'Estimated' => $money($l['estimated']['permit']),   'Actual' => $money($l['actual']['permit'])],
            ['Item' => 'Profit',                   'Estimated' => $money($l['estimated']['profit']),   'Actual' => $money($l['actual']['profit'])],
            ['Item' => 'Profit %',                 'Estimated' => $pct($l['estimated']['profit_pct']), 'Actual' => $pct($l['actual']['profit_pct'])],
        ];

        $label = $p['project_name'] . (($p['code'] ?? '-') !== '-' ? ' (' . $p['code'] . ')' : '');
        $msg   = 'Estimated & Actual Project Costs for **' . $label . '** — Estimated profit '
            . $money($l['estimated']['profit']) . ' (' . $pct($l['estimated']['profit_pct']) . '), Realized profit '
            . $money($l['actual']['profit']) . ' (' . $pct($l['actual']['profit_pct']) . ').';

        $answer = ['type' => 'table', 'message' => $msg, 'columns' => ['Item', 'Estimated', 'Actual'], 'rows' => $rows, 'cards' => []];

        return $this->storeProjectDetailMessage($chat, $message, $msg, $answer, $log, $startedAt, 'success', $search);
    }

    /**
     * True for "who moved project X" / move-history / project-activity questions.
     * These need the activity log (causer = who), which the generic pipeline tends
     * to get wrong (subject_type FQCN + code-vs-id), so we handle them directly.
     */
    private function isProjectMoveActivityRequest(string $message): bool
    {
        $lower = mb_strtolower($message);

        foreach ([
            'who moved', 'who changed', 'who shifted', 'who pushed', 'who transferred',
            'move history', 'movement history', 'moved history', 'move log',
            'department change history', 'project activity', 'activity log', 'last moved',
            'kisne move', 'kisne shift', 'kisne badla', 'kisne change',
        ] as $cue) {
            if (str_contains($lower, $cue)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Answer a "who moved / move history" question from the activity log. Returns
     * null (so the caller falls through to the normal pipeline) when no project is
     * named or nothing matched — never dead-ends on a stray phrase.
     */
    private function handleProjectMoveActivity(AiChat $chat, User $user, string $message, AiQueryLog $log, float $startedAt): ?AiChat
    {
        // Resolve the project: explicit code / hyphenated or quoted name, else the
        // project carried from the previous turn ("this project").
        $search = $this->extractProjectSearchTerm($message);

        if ($search === '') {
            $search = $this->getRecentProjectSearch($chat);
        }

        if ($search === '') {
            return null;
        }

        $lower    = mb_strtolower($message);
        $lastOnly = str_contains($lower, 'last') || str_contains($lower, 'latest')
            || str_contains($lower, 'recent') || str_contains($lower, 'akhri') || str_contains($lower, 'aakhri');

        $result   = $this->aiProjectLaneService->getProjectMoveActivity($user, $search, $lastOnly ? 1 : 20);
        $rowCount = $result['row_count'];

        if ($rowCount === 0) {
            return null;
        }

        $label = $result['project_label'] ?: $search;

        if ($lastOnly) {
            $row = $result['rows'][0];
            $assistantMessage = "**{$row['Moved By']}** made the last recorded move on **{$label}** — {$row['Movement']} ({$row['When']}).";
        } else {
            $assistantMessage = "Here is the recent move history for **{$label}** — {$rowCount} " . ($rowCount === 1 ? 'entry' : 'entries') . ':';
        }

        $answer = [
            'type'    => 'table',
            'message' => $assistantMessage,
            'columns' => $result['columns'],
            'rows'    => $result['rows'],
            'cards'   => [],
        ];

        return $this->storeProjectDetailMessage($chat, $message, $assistantMessage, $answer, $log, $startedAt, 'success', $search);
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

        // Exclude requests that are clearly about another entity or a generic
        // report (finance, tickets, logs, tasks, group-bys, …) — those belong to
        // the LLM pipeline, not the single-named-project detail handler. This is
        // what stops "high priority ticket details" being treated as a project.
        foreach (['financ', 'status', 'department', 'acceptance', 'forecast', 'override',
                  'transaction', 'revenue', 'profit', 'commission', 'contract', 'holdback',
                  'loan', 'payment', 'wise', ' by ',
                  'ticket', 'log', 'task', 'customer', 'employee', 'survey', 'note',
                  'call', 'email', 'invoice', 'report'] as $aspect) {
            if (str_contains($lower, $aspect)) {
                return false;
            }
        }

        // Whether a concrete project (name/code/"this project") can be resolved is
        // decided by resolveProjectSearch at the call site, which also handles the
        // "no match → fall through" case — so we don't hard-require it here.
        return true;
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

        // Follow-up ("this project", "is project ki summary") → the most recent
        // project discussed anywhere in the conversation.
        return $this->getRecentProjectSearch($chat);
    }

    /**
     * True when the whole message is just a project code/number (e.g. "1048",
     * "SS-001") — typically the reply to a "which one? give me the code" prompt.
     */
    private function looksLikeBareProjectReference(string $message): bool
    {
        return (bool) preg_match('/^\s*([A-Za-z]{1,4}-)?\d{3,6}\s*$/', $message);
    }

    /**
     * The most recent project referenced in the conversation, so "this project"
     * style follow-ups resolve to it. Looks back through recent assistant turns
     * for: a stored search term, a project_name/code filter, or a single-project
     * result row carrying a code. Returns '' when none is found.
     */
    private function getRecentProjectSearch(AiChat $chat): string
    {
        $messages = $chat->messages()
            ->where('role', 'assistant')
            ->latest('id')
            ->limit(8)
            ->get();

        foreach ($messages as $msg) {
            $meta = is_array($msg->metadata) ? $msg->metadata : [];

            if (! empty($meta['search_term'])) {
                return (string) $meta['search_term'];
            }

            foreach (($meta['query_plan']['filters'] ?? []) as $filter) {
                if (is_array($filter)
                    && in_array($filter['column'] ?? '', ['project_name', 'code'], true)
                    && ! blank($filter['value'] ?? null)
                    && ! is_array($filter['value'])) {
                    return (string) $filter['value'];
                }
            }

            $rows = $meta['answer']['rows'] ?? [];
            if (is_array($rows) && count($rows) === 1 && is_array($rows[0])) {
                foreach (['code', 'Code'] as $key) {
                    if (! empty($rows[0][$key])) {
                        return (string) $rows[0][$key];
                    }
                }
            }
        }

        return '';
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
            // Back-reference words — these point at the previous result, not a name,
            // so strip them out (a leftover "this"/"these" must not become a search).
            'this', 'these', 'those', 'them', 'they', 'it', 'its', 'their',
            'ye', 'yeh', 'in', 'inn', 'un', 'unn', 'wo', 'woh', 'sab', 'sare', 'saare', 'all',
        ];
        $text = preg_replace('/\b(' . implode('|', array_map(fn ($w) => preg_quote($w, '/'), $words)) . ')\b/iu', ' ', $text);

        $text = trim(preg_replace('/\s+/u', ' ', (string) $text));
        $text = trim($text, " -:,.\t");

        return (mb_strlen($text) >= 2 && mb_strlen($text) <= 60) ? $text : '';
    }

    private function handleProjectDetailSummary(AiChat $chat, User $user, string $message, string $search, AiQueryLog $log, float $startedAt): ?AiChat
    {
        $detail     = $this->aiProjectLaneService->getProjectDetail($user, $search);
        $projects   = $detail['projects'] ?? [];
        $matchCount = (int) ($detail['project_count'] ?? count($projects));

        // No project matched — return null so the caller can fall through to the
        // LLM pipeline (the request may not have been about a project at all).
        if ($projects === []) {
            return null;
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

    /**
     * Persist a deterministic data-dictionary answer (field/term explanation).
     * No OpenAI call is made, so no token usage is recorded.
     */
    private function storeDictionaryMessage(AiChat $chat, string $message, string $assistantMessage, AiQueryLog $log, float $startedAt): AiChat
    {
        DB::transaction(function () use ($chat, $message, $assistantMessage, $log, $startedAt) {
            $chat->messages()->create([
                'role'     => 'assistant',
                'content'  => $assistantMessage,
                'metadata' => [
                    'type'      => 'field_explanation',
                    'status'    => 'success',
                    'retryable' => false,
                ],
            ]);

            $log->update(array_merge([
                'status'           => 'success',
                'duration_ms'      => (int) ((microtime(true) - $startedAt) * 1000),
                'request_payload'  => ['message' => $message],
                'response_payload' => ['answer' => ['type' => 'text', 'message' => $assistantMessage]],
            ], $this->profileColumns($startedAt, 'dictionary')));

            $chat->update(['last_message_at' => now()]);
        });

        return $chat->fresh('messages');
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

            $log->update(array_merge([
                'status'           => $status === 'no_data' ? 'no_data' : 'executed',
                'duration_ms'      => (int) ((microtime(true) - $startedAt) * 1000),
                'request_payload'  => ['message' => $message],
                'response_payload' => ['answer' => $answer],
            ], $this->profileColumns($startedAt, 'project_detail')));

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

        // Persist all three writes atomically, mirroring the other handlers, so a
        // failure can't leave the message stored while the query log stays 'pending'.
        DB::transaction(function () use ($chat, $helpText, $log, $startedAt, $message) {
            $chat->messages()->create([
                'role' => 'assistant',
                'content' => $helpText,
                'metadata' => ['type' => 'help_response', 'status' => 'success'],
            ]);

            $log->update(array_merge([
                'status' => 'success',
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                'request_payload' => ['message' => $message],
            ], $this->profileColumns($startedAt, 'help')));

            $chat->update(['last_message_at' => now()]);
        });

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
