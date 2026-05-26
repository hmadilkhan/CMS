@extends('layouts.master')

@section('title', 'AI Chat')

@section('content')
    <script src="https://cdn.tailwindcss.com"></script>
    <div class="w-full" id="ai-chat-page">
        <div class="mx-auto flex h-[calc(100vh-126px)] min-h-[640px] w-full max-w-[1500px] overflow-hidden rounded-lg border border-slate-200 bg-white shadow-xl">
            <aside class="hidden w-80 shrink-0 border-r border-slate-200 bg-slate-950 text-white md:flex md:flex-col">
                <div class="border-b border-white/10 p-4">
                    <a href="{{ route('ai-chat.index') }}" class="flex w-full items-center justify-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-amber-100">
                        <span class="text-lg leading-none">+</span>
                        New chat
                    </a>
                </div>

                <div class="flex-1 overflow-y-auto p-3">
                    <p class="px-2 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Chat history</p>
                    <div class="space-y-1">
                        @forelse ($chats as $chat)
                            <a href="{{ route('ai-chat.show', $chat) }}" class="block rounded-lg px-3 py-3 transition {{ optional($activeChat)->id === $chat->id ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                                <div class="truncate text-sm font-semibold">{{ $chat->title }}</div>
                                <div class="mt-1 flex items-center justify-between text-xs {{ optional($activeChat)->id === $chat->id ? 'text-slate-500' : 'text-slate-400' }}">
                                    <span>{{ $chat->messages_count }} messages</span>
                                    <span>{{ optional($chat->last_message_at ?? $chat->created_at)->diffForHumans() }}</span>
                                </div>
                            </a>
                        @empty
                            <div class="rounded-lg border border-dashed border-white/15 p-4 text-sm text-slate-400">
                                Your AI conversations will appear here.
                            </div>
                        @endforelse
                    </div>
                </div>
            </aside>

            <main class="flex min-w-0 flex-1 flex-col bg-slate-50">
                <header class="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-4 sm:px-6">
                    <div class="min-w-0">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-950 text-sm font-bold text-white">AI</div>
                            <div class="min-w-0">
                                <h1 class="truncate text-lg font-bold text-slate-950">{{ $activeChat?->title ?? 'CRM AI Chat' }}</h1>
                                <p class="text-sm text-slate-500">OpenAI assistant for normal chat replies.</p>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('ai-chat.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-100 md:hidden">
                        New
                    </a>
                </header>

                <div class="border-b border-slate-200 bg-white px-4 py-3 md:hidden">
                    <select class="w-full rounded-lg border-slate-200 text-sm" onchange="if (this.value) window.location.href = this.value">
                        <option value="{{ route('ai-chat.index') }}">New chat</option>
                        @foreach ($chats as $chat)
                            <option value="{{ route('ai-chat.show', $chat) }}" @selected(optional($activeChat)->id === $chat->id)>{{ $chat->title }}</option>
                        @endforeach
                    </select>
                </div>

                <section id="messages" class="flex-1 space-y-5 overflow-y-auto px-4 py-6 sm:px-6">
                    @if ($activeChat && $activeChat->messages->isNotEmpty())
                        @foreach ($activeChat->messages as $message)
                            <div class="flex {{ $message->role === 'user' ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[86%] rounded-2xl px-4 py-3 text-sm leading-6 shadow-sm sm:max-w-[72%] {{ $message->role === 'user' ? 'rounded-br-md bg-slate-950 text-white' : 'rounded-bl-md border border-slate-200 bg-white text-slate-800' }}">
                                    <div class="whitespace-pre-wrap">{{ $message->content }}</div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div id="empty-state" class="flex h-full items-center justify-center">
                            <div class="max-w-xl text-center">
                                <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-950 text-xl font-bold text-white shadow-lg">AI</div>
                                <h2 class="text-2xl font-bold text-slate-950">Start a conversation</h2>
                                <p class="mt-2 text-sm leading-6 text-slate-500">Ask for drafting help, summaries, planning, or general CRM assistance. Database query logic is intentionally not connected yet.</p>
                            </div>
                        </div>
                    @endif
                </section>

                <div id="typing" class="hidden px-4 pb-2 sm:px-6">
                    <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm text-slate-500 shadow-sm">
                        <span class="h-2 w-2 animate-pulse rounded-full bg-amber-500"></span>
                        Assistant is thinking
                    </div>
                </div>

                <form id="chat-form" class="border-t border-slate-200 bg-white p-4 sm:p-5">
                    @csrf
                    <input type="hidden" id="chat-id" value="{{ $activeChat?->id }}">
                    <div class="flex items-end gap-3 rounded-lg border border-slate-200 bg-slate-50 p-2 shadow-inner">
                        <textarea id="message-input" rows="1" class="max-h-40 min-h-[46px] flex-1 resize-none border-0 bg-transparent px-3 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:ring-0" placeholder="Message CRM AI..."></textarea>
                        <button id="send-button" type="submit" class="inline-flex h-11 shrink-0 items-center justify-center rounded-lg bg-amber-500 px-5 text-sm font-bold text-white shadow-sm transition hover:bg-amber-600 disabled:cursor-not-allowed disabled:bg-slate-300">
                            Send
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-slate-400">AI can make mistakes. Review important details before using them.</p>
                </form>
            </main>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const form = document.getElementById('chat-form');
        const input = document.getElementById('message-input');
        const messages = document.getElementById('messages');
        const typing = document.getElementById('typing');
        const sendButton = document.getElementById('send-button');
        const chatId = document.getElementById('chat-id');

        function scrollMessages() {
            messages.scrollTop = messages.scrollHeight;
        }

        function appendMessage(role, content) {
            const wrapper = document.createElement('div');
            wrapper.className = `flex ${role === 'user' ? 'justify-end' : 'justify-start'}`;

            const bubble = document.createElement('div');
            bubble.className = role === 'user'
                ? 'max-w-[86%] sm:max-w-[72%] rounded-2xl rounded-br-md bg-slate-950 px-4 py-3 text-sm leading-6 text-white shadow-sm'
                : 'max-w-[86%] sm:max-w-[72%] rounded-2xl rounded-bl-md border border-slate-200 bg-white px-4 py-3 text-sm leading-6 text-slate-800 shadow-sm';

            const text = document.createElement('div');
            text.className = 'whitespace-pre-wrap';
            text.textContent = content;

            bubble.appendChild(text);
            wrapper.appendChild(bubble);
            messages.appendChild(wrapper);
            scrollMessages();
        }

        input.addEventListener('input', () => {
            input.style.height = 'auto';
            input.style.height = `${input.scrollHeight}px`;
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                form.requestSubmit();
            }
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const content = input.value.trim();
            if (!content) return;

            document.getElementById('empty-state')?.remove();
            appendMessage('user', content);
            input.value = '';
            input.style.height = 'auto';
            sendButton.disabled = true;
            typing.classList.remove('hidden');

            try {
                const response = await fetch('{{ route('ai-chat.send') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf_token"]').content,
                    },
                    body: JSON.stringify({
                        message: content,
                        chat_id: chatId.value || null,
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Unable to send message.');
                }

                chatId.value = data.chat.id;
                const assistant = data.messages[data.messages.length - 1];
                appendMessage('assistant', assistant.content);

                if (window.location.pathname === '{{ route('ai-chat.index', [], false) }}') {
                    window.history.replaceState({}, '', data.chat.url);
                }
            } catch (error) {
                appendMessage('assistant', error.message || 'Something went wrong. Please try again.');
            } finally {
                typing.classList.add('hidden');
                sendButton.disabled = false;
                input.focus();
            }
        });

        scrollMessages();
    </script>
@endsection
