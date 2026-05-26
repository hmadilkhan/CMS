@extends('layouts.ai-chat')

@section('title', 'AI Chat')

@section('content')
    <div class="flex h-screen w-screen overflow-hidden bg-white" id="ai-chat-page">
        <aside class="hidden w-[320px] shrink-0 border-r border-slate-200 bg-[#f6f8fb] md:flex md:flex-col">
            <div class="px-4 pb-5 pt-5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-950 text-lg font-bold text-white">✧</div>
                        <div class="truncate text-lg font-bold text-slate-950">Solen Energy Co.</div>
                    </div>
                    <a href="{{ $backUrl ?? route('dashboard') }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-200 hover:text-slate-900" title="Back to CRM">
                        <span class="text-lg leading-none">&larr;</span>
                    </a>
                </div>

                <a href="{{ route('ai-chat.index') }}" class="mt-6 flex h-12 w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-base font-bold text-slate-950 shadow-sm transition hover:bg-slate-50">
                    <span class="text-2xl font-light leading-none">+</span>
                    New chat
                </a>

                <div class="mt-4 flex h-11 items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 text-slate-500">
                    <span class="text-lg leading-none">⌕</span>
                    <input id="chat-search" type="search" class="h-full min-w-0 flex-1 border-0 bg-transparent text-sm text-slate-900 outline-none placeholder:text-slate-500 focus:ring-0" placeholder="Search chats">
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-3">
                <p class="px-2 pb-2 text-xs font-bold uppercase tracking-wide text-slate-500">Recent</p>
                <div class="space-y-1">
                    @forelse ($chats as $chat)
                        <div class="chat-history-item rounded-xl transition {{ optional($activeChat)->id === $chat->id ? 'bg-slate-100 text-slate-950' : 'text-slate-800 hover:bg-slate-100' }}" data-title="{{ strtolower($chat->title) }}" data-chat-id="{{ $chat->id }}" data-rename-url="{{ route('ai-chat.rename', $chat) }}" data-delete-url="{{ route('ai-chat.destroy', $chat) }}">
                            <div class="flex items-center gap-2 px-3 py-3">
                                <a href="{{ route('ai-chat.show', $chat) }}" class="flex min-w-0 flex-1 items-center gap-3">
                                    <span class="text-lg text-slate-500">▱</span>
                                    <div class="min-w-0">
                                        <div class="chat-title truncate text-sm font-medium">{{ $chat->title }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $chat->messages_count }} messages</div>
                                    </div>
                                </a>
                                <div class="flex shrink-0 gap-1 opacity-80">
                                    <button type="button" class="rename-chat rounded-md px-2 py-1 text-xs font-semibold text-slate-500 hover:bg-white" title="Rename">Edit</button>
                                    <button type="button" class="delete-chat rounded-md px-2 py-1 text-xs font-semibold text-red-500 hover:bg-white" title="Delete">Del</button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 bg-white p-4 text-sm text-slate-500">
                            Your AI conversations will appear here.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="border-t border-slate-200 p-4">
                <div class="flex items-center justify-between">
                    <div class="flex min-w-0 items-center gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-950 text-base text-white">♙</div>
                        <div class="min-w-0">
                            <div class="truncate text-sm font-bold text-slate-950">{{ $userName ?? auth()->user()->name }}</div>
                            <div class="text-xs text-slate-500">CRM user</div>
                        </div>
                    </div>
                    <a href="{{ $backUrl ?? route('dashboard') }}" class="text-lg text-slate-500 transition hover:text-slate-950" title="Back to CRM">⚙</a>
                </div>
            </div>
        </aside>

        <main class="flex min-w-0 flex-1 flex-col bg-white">
                <header class="flex h-14 shrink-0 items-center justify-between border-b border-slate-200 bg-white px-6">
                    <div class="min-w-0">
                        <h1 class="truncate text-lg font-bold text-slate-950">{{ $activeChat?->title ?? 'Welcome to your assistant' }}</h1>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ $backUrl ?? route('dashboard') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Back to CRM</a>
                        <a href="{{ route('ai-chat.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 md:hidden">
                            New
                        </a>
                    </div>
                </header>

                <div class="border-b border-slate-200 bg-white px-4 py-3 md:hidden">
                    <select class="w-full rounded-lg border-slate-200 text-sm" onchange="if (this.value) window.location.href = this.value">
                        <option value="{{ route('ai-chat.index') }}">New chat</option>
                        @foreach ($chats as $chat)
                            <option value="{{ route('ai-chat.show', $chat) }}" @selected(optional($activeChat)->id === $chat->id)>{{ $chat->title }}</option>
                        @endforeach
                    </select>
                </div>

                <section id="messages" class="min-h-0 flex-1 space-y-5 overflow-y-auto px-4 py-7 sm:px-8">
                    @if ($activeChat && $activeChat->messages->isNotEmpty())
                        @foreach ($activeChat->messages as $message)
                            @php($answer = $message->metadata['answer'] ?? null)
                            <div class="mx-auto flex max-w-5xl {{ $message->role === 'user' ? 'justify-end' : 'justify-start' }}">
                                @if ($message->role === 'assistant')
                                    <div class="mr-4 hidden h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-950 text-base font-bold text-white sm:flex">✧</div>
                                @endif
                                <div class="max-w-[86%] rounded-2xl px-5 py-3 text-base leading-7 sm:max-w-[74%] {{ $message->role === 'user' ? 'rounded-br-lg bg-slate-950 text-white' : 'rounded-bl-lg bg-slate-100 text-slate-950' }}">
                                    @if ($message->role === 'assistant')
                                        <div class="mb-2 flex items-center justify-between gap-3 border-b border-slate-200 pb-2">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">Assistant</span>
                                            <div class="flex gap-2">
                                                <button type="button" class="copy-answer rounded-md border border-slate-200 px-2 py-1 text-xs font-semibold text-slate-500 hover:bg-slate-50" data-copy="{{ e($message->content) }}">Copy</button>
                                                @if (($message->metadata['retryable'] ?? false) && $activeChat)
                                                    <button type="button" class="retry-response rounded-md border border-amber-200 px-2 py-1 text-xs font-semibold text-amber-700 hover:bg-amber-50" data-retry-url="{{ route('ai-chat.retry', $activeChat) }}">Retry</button>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                    <div class="whitespace-pre-wrap">{{ $message->content }}</div>
                                    @if ($answer && in_array($answer['type'] ?? '', ['count', 'card']))
                                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                            @foreach (($answer['cards'] ?? []) as $card)
                                                <div class="rounded-lg border border-amber-200 bg-gradient-to-br from-white to-amber-50 p-4 shadow-sm">
                                                    @if (array_key_exists('label', $card) || array_key_exists('value', $card))
                                                        <div class="text-xs font-semibold uppercase tracking-wide text-amber-700">{{ $card['label'] ?? 'Result' }}</div>
                                                        <div class="mt-2 text-2xl font-bold text-slate-950">{{ $card['value'] ?? '' }}</div>
                                                    @else
                                                        @foreach ($card as $label => $value)
                                                            <div class="{{ $loop->first ? 'text-xs font-semibold uppercase tracking-wide text-amber-700' : 'mt-1 text-sm font-semibold text-slate-950' }}">
                                                                {{ ucwords(str_replace('_', ' ', $label)) }}: {{ $value }}
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if ($answer && ($answer['type'] ?? '') === 'table')
                                        <div class="mt-3 max-w-full overflow-x-auto rounded-lg border border-slate-200">
                                            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                                                <thead class="bg-slate-50 text-slate-500">
                                                    <tr>
                                                        @foreach (($answer['columns'] ?? []) as $column)
                                                            <th class="px-3 py-2 font-semibold">{{ ucwords(str_replace('_', ' ', $column)) }}</th>
                                                        @endforeach
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                                                    @foreach (($answer['rows'] ?? []) as $row)
                                                        <tr>
                                                            @foreach (($answer['columns'] ?? []) as $column)
                                                                <td class="px-3 py-2">{{ $row[$column] ?? '' }}</td>
                                                            @endforeach
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div id="empty-state" class="mx-auto max-w-5xl">
                            <div class="flex justify-start">
                                <div class="mr-4 hidden h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-950 text-base font-bold text-white sm:flex">✧</div>
                                <div class="max-w-3xl rounded-2xl rounded-bl-lg bg-slate-100 px-5 py-4 text-base leading-7 text-slate-950">
                                    Hi {{ $userName ?? auth()->user()->name }}! I'm your AI assistant for {{ $appName ?? config('app.name', 'CRM') }}. Ask me anything about projects, tickets, summaries, writing, or planning. How can I help today?
                                </div>
                            </div>
                            <div class="mt-5 flex flex-wrap gap-2 pl-0 sm:pl-[52px]">
                                    @foreach ($suggestedQuestions as $question)
                                        <button type="button" class="suggestion-chip rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50" data-question="{{ $question }}">{{ $question }}</button>
                                    @endforeach
                            </div>
                        </div>
                    @endif
                </section>

                <div id="typing" class="hidden px-4 pb-2 sm:px-6">
                    <div class="inline-flex items-center gap-3 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm text-slate-500 shadow-sm">
                        <span class="flex gap-1">
                            <span class="h-2 w-2 animate-bounce rounded-full bg-amber-500"></span>
                            <span class="h-2 w-2 animate-bounce rounded-full bg-amber-500 [animation-delay:120ms]"></span>
                            <span class="h-2 w-2 animate-bounce rounded-full bg-amber-500 [animation-delay:240ms]"></span>
                        </span>
                        Securely checking CRM access
                    </div>
                </div>

                <form id="chat-form" class="shrink-0 border-t border-slate-200 bg-white px-4 py-3 sm:px-8">
                    @csrf
                    <input type="hidden" id="chat-id" value="{{ $activeChat?->id }}">
                    <div class="mx-auto mb-3 flex max-w-5xl gap-2 overflow-x-auto pb-1">
                        @foreach ($suggestedQuestions as $question)
                            <button type="button" class="suggestion-chip shrink-0 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-500 hover:bg-slate-50" data-question="{{ $question }}">{{ $question }}</button>
                        @endforeach
                    </div>
                    <div class="mx-auto flex max-w-5xl items-center gap-3 rounded-[24px] border-2 border-slate-300 bg-white px-5 py-2 shadow-sm">
                        <textarea id="message-input" rows="1" class="max-h-36 min-h-[38px] flex-1 resize-none border-0 bg-transparent px-1 py-2 text-base text-slate-900 outline-none placeholder:text-slate-500 focus:ring-0" placeholder="Message Solen Energy Co..."></textarea>
                        <button id="send-button" type="submit" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-400 text-2xl text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:bg-slate-300" title="Send">
                            <span class="-mt-1">⌁</span>
                        </button>
                    </div>
                    <p class="mx-auto mt-2 max-w-5xl text-center text-xs text-slate-500">Solen Energy Co. AI can make mistakes. Check important info.</p>
                </form>
            </main>
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
        const csrfToken = document.querySelector('meta[name="csrf_token"]').content;

        function scrollMessages() {
            messages.scrollTop = messages.scrollHeight;
        }

        function actionBar(content, metadata = {}) {
            const bar = document.createElement('div');
            bar.className = 'mb-2 flex items-center justify-between gap-3 border-b border-slate-200 pb-2';

            const label = document.createElement('span');
            label.className = 'text-xs font-semibold uppercase tracking-wide text-slate-400';
            label.textContent = metadata.status === 'failed' ? 'Needs attention' : 'Assistant';
            bar.appendChild(label);

            const actions = document.createElement('div');
            actions.className = 'flex gap-2';

            const copy = document.createElement('button');
            copy.type = 'button';
            copy.className = 'copy-answer rounded-md border border-slate-200 px-2 py-1 text-xs font-semibold text-slate-500 hover:bg-slate-50';
            copy.dataset.copy = content;
            copy.textContent = 'Copy';
            actions.appendChild(copy);

            if (metadata.retryable && chatId.value) {
                const retry = document.createElement('button');
                retry.type = 'button';
                retry.className = 'retry-response rounded-md border border-amber-200 px-2 py-1 text-xs font-semibold text-amber-700 hover:bg-amber-50';
                retry.dataset.retryUrl = `{{ url('/ai-chat') }}/${chatId.value}/retry`;
                retry.textContent = 'Retry';
                actions.appendChild(retry);
            }

            bar.appendChild(actions);

            return bar;
        }

        function renderAnswer(answer) {
            if (!answer || !answer.type) return null;

            if (answer.type === 'count' || answer.type === 'card') {
                const grid = document.createElement('div');
                grid.className = 'mt-3 grid gap-3 sm:grid-cols-2';
                (answer.cards || []).forEach((card) => {
                    const item = document.createElement('div');
                    item.className = 'rounded-lg border border-amber-200 bg-gradient-to-br from-white to-amber-50 p-4 shadow-sm';
                    if (Object.prototype.hasOwnProperty.call(card, 'label') || Object.prototype.hasOwnProperty.call(card, 'value')) {
                        const label = document.createElement('div');
                        label.className = 'text-xs font-semibold uppercase tracking-wide text-amber-700';
                        label.textContent = card.label || 'Result';
                        const value = document.createElement('div');
                        value.className = 'mt-2 text-2xl font-bold text-slate-950';
                        value.textContent = card.value ?? '';
                        item.appendChild(label);
                        item.appendChild(value);
                    } else {
                        Object.entries(card).forEach(([key, value], index) => {
                            const line = document.createElement('div');
                            line.className = index === 0 ? 'text-xs font-semibold uppercase tracking-wide text-amber-700' : 'mt-1 text-sm font-semibold text-slate-950';
                            line.textContent = `${key.replaceAll('_', ' ')}: ${value ?? ''}`;
                            item.appendChild(line);
                        });
                    }
                    grid.appendChild(item);
                });
                return grid;
            }

            if (answer.type === 'table') {
                const wrap = document.createElement('div');
                wrap.className = 'mt-3 max-w-full overflow-x-auto rounded-lg border border-slate-200';
                const table = document.createElement('table');
                table.className = 'min-w-full divide-y divide-slate-200 text-left text-xs';
                const thead = document.createElement('thead');
                thead.className = 'bg-slate-50 text-slate-500';
                const headRow = document.createElement('tr');
                (answer.columns || []).forEach((column) => {
                    const th = document.createElement('th');
                    th.className = 'px-3 py-2 font-semibold';
                    th.textContent = column.replaceAll('_', ' ');
                    headRow.appendChild(th);
                });
                thead.appendChild(headRow);
                const tbody = document.createElement('tbody');
                tbody.className = 'divide-y divide-slate-100 bg-white text-slate-700';
                (answer.rows || []).forEach((row) => {
                    const tr = document.createElement('tr');
                    (answer.columns || []).forEach((column) => {
                        const td = document.createElement('td');
                        td.className = 'px-3 py-2';
                        td.textContent = row[column] ?? '';
                        tr.appendChild(td);
                    });
                    tbody.appendChild(tr);
                });
                table.appendChild(thead);
                table.appendChild(tbody);
                wrap.appendChild(table);
                return wrap;
            }

            return null;
        }

        function appendMessage(role, content, answer = null, metadata = {}) {
            const wrapper = document.createElement('div');
            wrapper.className = `mx-auto flex max-w-5xl ${role === 'user' ? 'justify-end' : 'justify-start'}`;

            if (role === 'assistant') {
                const avatar = document.createElement('div');
                avatar.className = 'mr-4 hidden h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-950 text-base font-bold text-white sm:flex';
                avatar.textContent = '✧';
                wrapper.appendChild(avatar);
            }

            const bubble = document.createElement('div');
            bubble.className = role === 'user'
                ? 'max-w-[86%] sm:max-w-[74%] rounded-2xl rounded-br-lg bg-slate-950 px-5 py-3 text-base leading-7 text-white'
                : 'max-w-[86%] sm:max-w-[74%] rounded-2xl rounded-bl-lg bg-slate-100 px-5 py-3 text-base leading-7 text-slate-950';

            const text = document.createElement('div');
            text.className = 'whitespace-pre-wrap';
            text.textContent = content;

            if (role === 'assistant') {
                bubble.appendChild(actionBar(content, metadata));
            }

            bubble.appendChild(text);
            const answerElement = renderAnswer(answer);
            if (answerElement) {
                bubble.appendChild(answerElement);
            }
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

        document.getElementById('chat-search')?.addEventListener('input', (event) => {
            const term = event.target.value.toLowerCase();
            document.querySelectorAll('.chat-history-item').forEach((item) => {
                item.classList.toggle('hidden', !item.dataset.title.includes(term));
            });
        });

        document.querySelectorAll('.suggestion-chip').forEach((button) => {
            button.addEventListener('click', () => {
                input.value = button.dataset.question;
                input.dispatchEvent(new Event('input'));
                form.requestSubmit();
            });
        });

        document.addEventListener('click', async (event) => {
            const copyButton = event.target.closest('.copy-answer');
            if (copyButton) {
                await navigator.clipboard.writeText(copyButton.dataset.copy || '');
                copyButton.textContent = 'Copied';
                setTimeout(() => copyButton.textContent = 'Copy', 1200);
                return;
            }

            const retryButton = event.target.closest('.retry-response');
            if (retryButton) {
                retryButton.disabled = true;
                typing.classList.remove('hidden');
                try {
                    const response = await fetch(retryButton.dataset.retryUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                    });
                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.message || 'Unable to retry response.');
                    }
                    const assistant = data.messages[data.messages.length - 1];
                    appendMessage('assistant', assistant.content, assistant.metadata?.answer || null, assistant.metadata || {});
                } catch (error) {
                    appendMessage('assistant', error.message || 'Retry failed. Please try again.', null, {status: 'failed', retryable: false});
                } finally {
                    typing.classList.add('hidden');
                    retryButton.disabled = false;
                }
                return;
            }

            const renameButton = event.target.closest('.rename-chat');
            if (renameButton) {
                const item = renameButton.closest('.chat-history-item');
                const title = prompt('Rename chat', item.querySelector('.chat-title')?.textContent?.trim() || '');
                if (!title) return;
                const response = await fetch(item.dataset.renameUrl, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({title}),
                });
                if (response.ok) {
                    const data = await response.json();
                    item.querySelector('.chat-title').textContent = data.title;
                    item.dataset.title = data.title.toLowerCase();
                }
                return;
            }

            const deleteButton = event.target.closest('.delete-chat');
            if (deleteButton) {
                const item = deleteButton.closest('.chat-history-item');
                if (!confirm('Delete this chat?')) return;
                const response = await fetch(item.dataset.deleteUrl, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });
                if (response.ok) {
                    const data = await response.json();
                    if (String(item.dataset.chatId) === String(chatId.value)) {
                        window.location.href = data.redirect;
                    } else {
                        item.remove();
                    }
                }
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
                        'X-CSRF-TOKEN': csrfToken,
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
                appendMessage('assistant', assistant.content, assistant.metadata?.answer || null, assistant.metadata || {});

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
