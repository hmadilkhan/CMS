<?php

namespace App\Http\Controllers;

use App\Models\AiChat;
use App\Services\AiChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        ]);
    }

    public function show(Request $request, AiChat $chat): View
    {
        abort_unless($chat->user_id === $request->user()->id, 403);

        return view('ai-chat.index', [
            'chats' => $this->aiChatService->chatsFor($request->user()),
            'activeChat' => $chat->load('messages'),
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
            'messages' => $chat->messages->map(fn ($message) => [
                'id' => $message->id,
                'role' => $message->role,
                'content' => $message->content,
                'created_at' => optional($message->created_at)->diffForHumans(),
            ]),
        ]);
    }
}
