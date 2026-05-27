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
        private readonly AiSqlBuilderService $aiSqlBuilderService,
        private readonly AiSqlValidatorService $aiSqlValidatorService,
        private readonly AiQueryExecutorService $aiQueryExecutorService,
        private readonly AiAnswerFormatterService $aiAnswerFormatterService
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
        $log = $this->createQueryLog($chat, $user, $message);

        if ($storeUserMessage) {
            $chat->messages()->create([
                'role' => 'user',
                'content' => $message,
            ]);
        }

        $chat->update(['last_message_at' => now()]);

        try {
            if ($this->aiQueryPlannerService->looksLikeCrmDataQuestion($message)) {
                return $this->handleQueryPlan($chat, $user, $message, $log, $startedAt);
            }

            $response = $this->openAiService->createResponse(
                $message,
                $chat->openai_response_id,
                $this->userContext($user)
            );

            DB::transaction(function () use ($chat, $response, $log, $startedAt) {
                $chat->messages()->create([
                    'role' => 'assistant',
                    'content' => $response['text'],
                    'metadata' => [
                        'openai_response_id' => $response['id'],
                        'model' => $response['model'],
                    ],
                ]);

                $usage = $response['usage'];

                $log->update([
                    'status' => 'success',
                    'response_id' => $response['id'],
                    'model' => $response['model'],
                    'prompt_tokens' => $usage['input_tokens'] ?? null,
                    'completion_tokens' => $usage['output_tokens'] ?? null,
                    'total_tokens' => $usage['total_tokens'] ?? null,
                    'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                    'request_payload' => $response['payload'],
                    'response_payload' => $response['raw'],
                ]);

                $chat->update([
                    'openai_response_id' => $response['id'],
                    'last_message_at' => now(),
                ]);
            });
        } catch (Throwable $exception) {
            $log->update([
                'status' => 'failed',
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                'error_message' => $exception->getMessage(),
            ]);

            return $this->storeFailureMessage($chat, $exception);
        }

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
        $planned = $this->aiQueryPlannerService->plan($message, $user);
        $plan = $planned['plan'];
        $response = $planned['openai'];
        $usage = $response['usage'];
        $sqlPreview = null;
        $validation = null;
        $execution = null;
        $answer = null;

        if ($plan['intent'] !== 'unknown') {
            $sqlPreview = $this->aiSqlBuilderService->build($plan, $user);
            $validation = $this->aiSqlValidatorService->validate($sqlPreview, $plan, $user);

            if ($validation['approved'] ?? false) {
                $execution = $this->aiQueryExecutorService->execute($sqlPreview, $user->id);
                $answer = $this->aiAnswerFormatterService->format($message, $plan, $execution);
            }
        }

        if ($plan['intent'] === 'unknown') {
            $assistantMessage = $plan['fallback_message'] ?: 'I could not understand that CRM question yet. Try one of the suggested questions.';
        } elseif (! ($validation['approved'] ?? false)) {
            $assistantMessage = 'I could not safely prepare this CRM query. ' . ($validation['reason'] ?? 'Please try a different question.');
        } elseif (! ($execution['success'] ?? false)) {
            $assistantMessage = $execution['error_message'] ?? 'I could not safely run this CRM query. Please try again.';
        } elseif (($execution['row_count'] ?? 0) === 0) {
            $assistantMessage = 'No data found for this request.';
            $answer = [
                'type' => 'text',
                'message' => $assistantMessage,
                'columns' => [],
                'rows' => [],
                'cards' => [],
            ];
        } else {
            $assistantMessage = $answer['message'] ?? 'Here are the CRM results.';
        }

        DB::transaction(function () use ($chat, $plan, $response, $usage, $log, $startedAt, $assistantMessage, $sqlPreview, $validation, $message, $execution, $answer) {
            $chat->messages()->create([
                'role' => 'assistant',
                'content' => $assistantMessage,
                'metadata' => [
                    'type' => 'query_plan',
                    'query_plan' => $plan,
                    'sql_preview' => $sqlPreview,
                    'sql_validation' => $validation,
                    'query_execution' => $execution,
                    'answer' => $answer,
                    'status' => $plan['intent'] === 'unknown'
                        ? 'invalid_question'
                        : ((! ($validation['approved'] ?? true)) ? 'unsafe_query_rejected' : ((! ($execution['success'] ?? true)) ? 'failed' : 'success')),
                    'retryable' => $plan['intent'] !== 'unknown' && (! ($validation['approved'] ?? true) || ! ($execution['success'] ?? true)),
                    'openai_response_id' => $response['id'],
                    'model' => $response['model'],
                ],
            ]);

            $log->update([
                'status' => $plan['intent'] === 'unknown'
                    ? 'planned_unknown'
                    : (! ($validation['approved'] ?? false)
                        ? 'rejected'
                        : (($execution['success'] ?? false) ? 'executed' : 'execution_failed')),
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
                    'sql_preview' => $sqlPreview,
                    'sql_validation' => $validation,
                    'query_execution' => $execution,
                    'answer' => $answer,
                    'openai_raw' => $response['raw'],
                ],
                'error_message' => ! ($validation['approved'] ?? true)
                    ? ($validation['reason'] ?? 'Query rejected by validator.')
                    : ((! ($execution['success'] ?? true)) ? ($execution['error_message'] ?? 'Query execution failed.') : null),
            ]);

            $chat->update([
                'last_message_at' => now(),
            ]);
        });

        $this->storeQueryExample($message, $plan, $sqlPreview, $execution);

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
