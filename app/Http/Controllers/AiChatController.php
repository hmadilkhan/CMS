<?php

namespace App\Http\Controllers;

use App\Models\AiChat;
use App\Models\AiChatMessage;
use App\Models\AiQueryExample;
use App\Models\AiQueryFeedback;
use App\Services\AiChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AiChatController extends Controller
{
    public function __construct(private readonly AiChatService $aiChatService)
    {
    }

    public function index(Request $request): View
    {
        return view('ai-chat.index', [
            'chats' => $this->aiChatService->chatsFor($request->user()),
            'activeChat' => null,
            'suggestedQuestions' => $this->aiChatService->suggestedQuestionsFor($request->user()),
            'userName' => $request->user()->name,
            'appName' => config('app.name', 'CRM'),
            'backUrl' => $this->backUrl($request),
        ]);
    }

    public function show(Request $request, AiChat $chat): View
    {
        abort_unless($chat->user_id === $request->user()->id, 403);

        return view('ai-chat.index', [
            'chats' => $this->aiChatService->chatsFor($request->user()),
            'activeChat' => $chat->load('messages'),
            'suggestedQuestions' => $this->aiChatService->suggestedQuestionsFor($request->user()),
            'userName' => $request->user()->name,
            'appName' => config('app.name', 'CRM'),
            'backUrl' => $this->backUrl($request),
        ]);
    }

    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:8000'],
            'chat_id' => ['nullable', 'integer', 'exists:ai_chats,id'],
        ]);

        $chat = $this->aiChatService->send(
            $request->user(),
            $validated['message'],
            $validated['chat_id'] ?? null
        );

        return response()->json([
            'chat' => [
                'id' => $chat->id,
                'title' => $chat->title,
                'url' => route('ai-chat.show', $chat),
            ],
            'messages' => $this->aiChatService->messagePayload($chat),
        ]);
    }

    public function rename(Request $request, AiChat $chat): JsonResponse
    {
        abort_unless($chat->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100'],
        ]);

        $chat = $this->aiChatService->rename($chat, $validated['title']);

        return response()->json([
            'id' => $chat->id,
            'title' => $chat->title,
        ]);
    }

    public function destroy(Request $request, AiChat $chat): JsonResponse
    {
        abort_unless($chat->user_id === $request->user()->id, 403);

        $this->aiChatService->delete($chat);

        return response()->json([
            'redirect' => route('ai-chat.index'),
        ]);
    }

    public function retry(Request $request, AiChat $chat): JsonResponse
    {
        abort_unless($chat->user_id === $request->user()->id, 403);

        $chat = $this->aiChatService->retryLastUserMessage($request->user(), $chat);

        return response()->json([
            'chat' => [
                'id' => $chat->id,
                'title' => $chat->title,
                'url' => route('ai-chat.show', $chat),
            ],
            'messages' => $this->aiChatService->messagePayload($chat),
        ]);
    }

    public function feedback(Request $request, AiChatMessage $message): JsonResponse
    {
        $message->load('chat');

        abort_unless(Schema::hasTable('ai_query_feedback'), 404);
        abort_unless($message->role === 'assistant', 404);
        abort_unless($message->chat?->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'rating' => ['required', 'in:up,down'],
            'comment' => ['nullable', 'string', 'max:500'],
            'expected_result' => ['nullable', 'string', 'max:1000'],
        ]);

        AiQueryFeedback::updateOrCreate(
            [
                'ai_chat_message_id' => $message->id,
                'user_id' => $request->user()->id,
            ],
            [
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
                'expected_result' => $validated['expected_result'] ?? null,
            ]
        );

        $question = $message->chat->messages()
            ->where('role', 'user')
            ->where('created_at', '<=', $message->created_at)
            ->latest('id')
            ->value('content');

        if ($question && Schema::hasTable('ai_query_examples')) {
            $example = AiQueryExample::firstOrCreate(
                ['question' => Str::limit($question, 500, '')],
                [
                    'plan' => $message->metadata['query_plan'] ?? null,
                    'sql' => $message->metadata['sql_preview']['sql'] ?? null,
                ]
            );

            $example->increment('feedback_score', $validated['rating'] === 'up' ? 1 : -1);
        }

        return response()->json(['success' => true]);
    }

    private function backUrl(Request $request): string
    {
        $fallback = route('dashboard');
        $previous = url()->previous();

        if ($this->isValidCrmBackUrl($previous)) {
            $request->session()->put('ai_chat_back_url', $previous);

            return $previous;
        }

        return $request->session()->get('ai_chat_back_url', $fallback);
    }

    private function isValidCrmBackUrl(?string $url): bool
    {
        if (blank($url)) {
            return false;
        }

        $urlHost = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH) ?: '/';

        return $urlHost === request()->getHost() && ! Str::startsWith($path, '/ai-chat');
    }
}
