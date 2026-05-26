<?php

namespace App\Services;

use App\Models\AiChat;
use App\Models\AiQueryLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class AiChatService
{
    public function __construct(
        private readonly OpenAiService $openAiService,
        private readonly AiQueryPlannerService $aiQueryPlannerService
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

        $startedAt = microtime(true);
        $log = AiQueryLog::create([
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

        $chat->messages()->create([
            'role' => 'user',
            'content' => $message,
        ]);

        $chat->update(['last_message_at' => now()]);

        try {
            if ($this->aiQueryPlannerService->looksLikeCrmDataQuestion($message)) {
                return $this->handleQueryPlan($chat, $user, $message, $log, $startedAt);
            }

            $response = $this->openAiService->createResponse($message, $chat->openai_response_id);

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

            throw $exception;
        }

        return $chat->fresh('messages');
    }

    private function handleQueryPlan(AiChat $chat, User $user, string $message, AiQueryLog $log, float $startedAt): AiChat
    {
        $planned = $this->aiQueryPlannerService->plan($message, $user);
        $plan = $planned['plan'];
        $response = $planned['openai'];
        $usage = $response['usage'];
        $assistantMessage = $plan['intent'] === 'unknown'
            ? $plan['fallback_message']
            : "Safe CRM query plan generated. SQL execution is disabled for now.\n\n" . json_encode($plan, JSON_PRETTY_PRINT);

        DB::transaction(function () use ($chat, $plan, $response, $usage, $log, $startedAt, $assistantMessage) {
            $chat->messages()->create([
                'role' => 'assistant',
                'content' => $assistantMessage,
                'metadata' => [
                    'type' => 'query_plan',
                    'query_plan' => $plan,
                    'openai_response_id' => $response['id'],
                    'model' => $response['model'],
                ],
            ]);

            $log->update([
                'status' => $plan['intent'] === 'unknown' ? 'planned_unknown' : 'planned',
                'response_id' => $response['id'],
                'model' => $response['model'],
                'prompt_tokens' => $usage['input_tokens'] ?? null,
                'completion_tokens' => $usage['output_tokens'] ?? null,
                'total_tokens' => $usage['total_tokens'] ?? null,
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                'request_payload' => $response['payload'],
                'response_payload' => [
                    'query_plan' => $plan,
                    'openai_raw' => $response['raw'],
                ],
            ]);

            $chat->update([
                'last_message_at' => now(),
            ]);
        });

        return $chat->fresh('messages');
    }
}
