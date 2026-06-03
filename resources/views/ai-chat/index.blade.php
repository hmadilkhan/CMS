@extends('layouts.ai-chat')

@section('title', 'SolenAssist')

@php
    $spark = '<svg viewBox="0 0 24 24" fill="none" class="h-5 w-5"><path d="M12 3v2.4M12 18.6V21M5.05 5.05l1.7 1.7M17.25 17.25l1.7 1.7M3 12h2.4M18.6 12H21M5.05 18.95l1.7-1.7M17.25 6.75l1.7-1.7" stroke="white" stroke-width="1.7" stroke-linecap="round"/><circle cx="12" cy="12" r="3.3" fill="white"/></svg>';
@endphp

@section('content')
    <div class="flex h-screen w-screen overflow-hidden bg-solen-cream" id="ai-chat-page">
        {{-- ===================== SIDEBAR ===================== --}}
        <aside class="hidden w-[312px] shrink-0 flex-col bg-solen-night md:flex">
            <div class="px-4 pb-5 pt-5">
                <div class="flex items-center justify-between">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="solen-gradient flex h-11 w-11 items-center justify-center rounded-2xl shadow-solen-sm">
                            {!! $spark !!}
                        </div>
                        <div class="min-w-0 leading-tight">
                            <div class="truncate text-base font-extrabold text-white">SolenAssist</div>
                            <div class="truncate text-[11px] font-semibold uppercase tracking-wider text-solen-gold">Solen Energy CRM</div>
                        </div>
                    </div>
                    <a href="{{ $backUrl ?? route('dashboard') }}" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-white/60 transition hover:bg-white/10 hover:text-white" title="Back to CRM">
                        <svg viewBox="0 0 24 24" class="h-4 w-4"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>
                    </a>
                </div>

                <a href="{{ route('ai-chat.index') }}" class="solen-gradient mt-6 flex h-12 w-full items-center justify-center gap-2 rounded-2xl px-4 text-sm font-bold text-[#3a2310] shadow-solen-sm transition hover:brightness-105">
                    <svg viewBox="0 0 24 24" class="h-4 w-4"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
                    New chat
                </a>

                <div class="mt-4 flex h-11 items-center gap-3 rounded-xl border border-white/10 bg-white/5 px-3 text-white/50 transition focus-within:border-solen/50">
                    <svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8" fill="none"/><path d="m20 20-3-3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    <input id="chat-search" type="search" class="h-full min-w-0 flex-1 border-0 bg-transparent text-sm text-white outline-none placeholder:text-white/40 focus:ring-0" placeholder="Search chats">
                </div>
            </div>

            <div class="solen-scroll min-h-0 flex-1 overflow-y-auto px-3">
                <p class="px-2 pb-2 text-[11px] font-bold uppercase tracking-wider text-white/40">Recent</p>
                <div class="space-y-1">
                    @forelse ($chats as $chat)
                        @php($isActive = optional($activeChat)->id === $chat->id)
                        <div class="chat-history-item relative rounded-xl transition {{ $isActive ? 'bg-white/10' : 'hover:bg-white/5' }}" data-title="{{ strtolower($chat->title) }}" data-chat-id="{{ $chat->id }}" data-rename-url="{{ route('ai-chat.rename', $chat) }}" data-delete-url="{{ route('ai-chat.destroy', $chat) }}">
                            @if ($isActive)
                                <span class="solen-gradient absolute inset-y-2 left-0 w-1 rounded-full"></span>
                            @endif
                            <div class="flex items-center gap-2 px-3 py-2.5">
                                <a href="{{ route('ai-chat.show', $chat) }}" class="flex min-w-0 flex-1 items-center gap-3">
                                    <span class="text-white/40">
                                        <svg viewBox="0 0 24 24" class="h-4 w-4"><path d="M4 5h16v11H8l-4 3V5z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" fill="none"/></svg>
                                    </span>
                                    <div class="min-w-0">
                                        <div class="chat-title truncate text-sm font-semibold {{ $isActive ? 'text-white' : 'text-white/75' }}">{{ $chat->title }}</div>
                                        <div class="mt-0.5 text-[11px] text-white/40">{{ $chat->messages_count }} messages</div>
                                    </div>
                                </a>
                                <div class="flex shrink-0 gap-1">
                                    <button type="button" class="rename-chat rounded-md px-2 py-1 text-[11px] font-semibold text-white/40 transition hover:bg-white/10 hover:text-white" title="Rename">Edit</button>
                                    <button type="button" class="delete-chat rounded-md px-2 py-1 text-[11px] font-semibold text-white/40 transition hover:bg-white/10 hover:text-rose-300" title="Delete">Del</button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-white/15 bg-white/5 p-4 text-sm text-white/50">
                            Your SolenAssist conversations will appear here.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="border-t border-white/10 p-4">
                <div class="flex items-center justify-between">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="solen-gradient flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold text-white shadow-solen-sm">
                            {{ strtoupper(mb_substr($userName ?? auth()->user()->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <div class="truncate text-sm font-bold text-white">{{ $userName ?? auth()->user()->name }}</div>
                            <div class="text-[11px] text-white/40">CRM user</div>
                        </div>
                    </div>
                    <a href="{{ $backUrl ?? route('dashboard') }}" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-white/50 transition hover:bg-white/10 hover:text-white" title="Back to CRM">
                        <svg viewBox="0 0 24 24" class="h-4 w-4"><path d="M12 8.5A3.5 3.5 0 1 0 12 15.5 3.5 3.5 0 0 0 12 8.5z" stroke="currentColor" stroke-width="1.6" fill="none"/><path d="M19 12a7 7 0 0 0-.13-1.3l1.7-1.32-1.7-2.94-2 .8a7 7 0 0 0-2.26-1.3L14 3h-4l-.31 2.64a7 7 0 0 0-2.26 1.3l-2-.8-1.7 2.94 1.7 1.32A7 7 0 0 0 5 12c0 .44.05.87.13 1.3l-1.7 1.32 1.7 2.94 2-.8a7 7 0 0 0 2.26 1.3L10 21h4l.31-2.64a7 7 0 0 0 2.26-1.3l2 .8 1.7-2.94-1.7-1.32c.08-.43.13-.86.13-1.3z" stroke="currentColor" stroke-width="1.3" fill="none"/></svg>
                    </a>
                </div>
            </div>
        </aside>

        {{-- ===================== MAIN ===================== --}}
        <main class="flex min-w-0 flex-1 flex-col bg-white">
            <header class="flex h-16 shrink-0 items-center justify-between border-b border-solen-border bg-white/80 px-5 backdrop-blur sm:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="solen-gradient flex h-9 w-9 items-center justify-center rounded-xl shadow-solen-sm md:hidden">
                        {!! $spark !!}
                    </div>
                    <div class="min-w-0">
                        <h1 class="truncate text-base font-extrabold text-solen-ink">{{ $activeChat?->title ?? 'Welcome to SolenAssist' }}</h1>
                        <p class="hidden text-[11px] font-medium text-solen-muted sm:block">Secure, read-only CRM assistant</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ $backUrl ?? route('dashboard') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-solen-border bg-white px-3 py-2 text-sm font-semibold text-solen-ink transition hover:border-solen hover:text-solen">
                        <svg viewBox="0 0 24 24" class="h-4 w-4"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>
                        <span class="hidden sm:inline">Back to CRM</span>
                    </a>
                    <a href="{{ route('ai-chat.index') }}" class="solen-gradient inline-flex items-center justify-center rounded-xl px-3 py-2 text-sm font-bold text-[#3a2310] transition hover:brightness-105 md:hidden">
                        New
                    </a>
                </div>
            </header>

            <div class="border-b border-solen-border bg-white px-4 py-3 md:hidden">
                <select class="w-full rounded-xl border-solen-border text-sm text-solen-ink focus:border-solen focus:ring-solen" onchange="if (this.value) window.location.href = this.value">
                    <option value="{{ route('ai-chat.index') }}">New chat</option>
                    @foreach ($chats as $chat)
                        <option value="{{ route('ai-chat.show', $chat) }}" @selected(optional($activeChat)->id === $chat->id)>{{ $chat->title }}</option>
                    @endforeach
                </select>
            </div>

            <section id="messages" class="solen-scroll min-h-0 flex-1 space-y-5 overflow-y-auto scroll-smooth bg-solen-cream px-4 py-7 pb-10 sm:px-6">
                @if ($activeChat && $activeChat->messages->isNotEmpty())
                    @foreach ($activeChat->messages as $message)
                        @php($answer = $message->metadata['answer'] ?? null)
                        @php($failed = ($message->metadata['status'] ?? null) === 'failed')
                        <div class="mx-auto flex max-w-4xl {{ $message->role === 'user' ? 'justify-end' : 'justify-start' }}">
                            @if ($message->role === 'assistant')
                                <div class="solen-gradient mr-3 hidden h-9 w-9 shrink-0 items-center justify-center rounded-full text-white shadow-solen-sm sm:flex">{!! $spark !!}</div>
                            @endif
                            <div class="{{ $message->role === 'user'
                                ? 'solen-gradient max-w-[88%] rounded-2xl rounded-br-md px-5 py-3 text-[15px] leading-7 text-white shadow-solen-sm sm:max-w-[74%]'
                                : 'max-w-[92%] rounded-2xl rounded-bl-md border border-solen-border bg-white px-5 py-4 text-[15px] leading-7 text-solen-ink shadow-solen-sm sm:max-w-[90%]' }}">
                                @if ($message->role === 'assistant')
                                    <div class="mb-2 flex items-center justify-between gap-3 border-b border-solen-border pb-2">
                                        @if ($failed)
                                            <span class="text-xs font-bold uppercase tracking-wider text-rose-600">Needs attention</span>
                                        @else
                                            <span class="solen-gradient-text text-xs font-bold uppercase tracking-wider">SolenAssist</span>
                                        @endif
                                        <div class="flex gap-2">
                                            <button type="button" class="copy-answer rounded-lg border border-solen-border px-2.5 py-1 text-xs font-semibold text-solen-muted transition hover:border-solen hover:text-solen" data-copy="{{ e($message->content) }}">Copy</button>
                                            @if (($message->metadata['retryable'] ?? false) && $activeChat)
                                                <button type="button" class="retry-response rounded-lg border border-solen/40 bg-solen/5 px-2.5 py-1 text-xs font-semibold text-solen-deep transition hover:bg-solen/10" data-retry-url="{{ route('ai-chat.retry', $activeChat) }}">Retry</button>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                                <div class="whitespace-pre-wrap">{{ $message->content }}</div>
                                @if ($answer && in_array($answer['type'] ?? '', ['count', 'card']))
                                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                        @foreach (($answer['cards'] ?? []) as $card)
                                            <div class="rounded-xl border border-solen-border bg-gradient-to-br from-white to-[#fff4e8] p-4 shadow-solen-sm">
                                                @if (array_key_exists('label', $card) || array_key_exists('value', $card))
                                                    <div class="text-xs font-bold uppercase tracking-wider text-solen-deep">{{ $card['label'] ?? 'Result' }}</div>
                                                    <div class="mt-1.5 text-2xl font-extrabold text-solen-ink">{{ $card['value'] ?? '' }}</div>
                                                @else
                                                    @foreach ($card as $label => $value)
                                                        <div class="{{ $loop->first ? 'text-xs font-bold uppercase tracking-wider text-solen-deep' : 'mt-1 text-sm font-semibold text-solen-ink' }}">
                                                            {{ ucwords(str_replace('_', ' ', $label)) }}: {{ $value }}
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                @if ($answer && ($answer['type'] ?? '') === 'table')
                                    <div class="mt-3 max-w-full overflow-x-auto rounded-xl border border-solen-border">
                                        <table class="min-w-full divide-y divide-solen-border text-left text-xs">
                                            <thead class="bg-solen-cream text-solen-muted">
                                                <tr>
                                                    @foreach (($answer['columns'] ?? []) as $column)
                                                        <th class="px-3 py-2.5 font-bold uppercase tracking-wide">{{ ucwords(str_replace('_', ' ', $column)) }}</th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-[#f1e7d8] bg-white text-solen-ink">
                                                @foreach (($answer['rows'] ?? []) as $row)
                                                    <tr class="transition hover:bg-solen-cream/60">
                                                        @foreach (($answer['columns'] ?? []) as $column)
                                                            <td class="whitespace-nowrap px-3 py-2.5">{{ $row[$column] ?? '' }}</td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                                @if ($message->role === 'assistant')
                                    <div class="feedback-panel mt-3 flex flex-wrap items-center gap-2 border-t border-solen-border pt-3 text-xs text-solen-muted" data-feedback-url="{{ route('ai-chat.feedback', $message) }}">
                                        <span>Was this helpful?</span>
                                        <button type="button" class="feedback-button rounded-lg border border-solen-border px-2.5 py-1 font-semibold transition hover:border-emerald-300 hover:text-emerald-600" data-rating="up">Yes</button>
                                        <button type="button" class="feedback-button rounded-lg border border-solen-border px-2.5 py-1 font-semibold transition hover:border-rose-300 hover:text-rose-600" data-rating="down">No</button>
                                        <textarea class="feedback-comment hidden min-h-[44px] w-full rounded-lg border border-solen-border px-3 py-2 text-xs text-solen-ink outline-none focus:border-solen" placeholder="What were you expecting?"></textarea>
                                        <button type="button" class="feedback-submit hidden rounded-lg solen-gradient px-3 py-1 font-semibold text-white">Submit feedback</button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @else
                    <div id="empty-state" class="mx-auto flex min-h-full max-w-2xl flex-col items-center justify-center text-center">
                        <div class="solen-gradient flex h-16 w-16 items-center justify-center rounded-3xl shadow-solen">
                            <span class="scale-150">{!! $spark !!}</span>
                        </div>
                        <h2 class="mt-5 text-2xl font-extrabold text-solen-ink">Hi {{ $userName ?? auth()->user()->name }}, I'm SolenAssist</h2>
                        <p class="mt-2 max-w-md text-sm leading-6 text-solen-muted">
                            Your AI assistant for {{ $appName ?? config('app.name', 'CRM') }}. Ask about projects, tickets, customers, finance reports — or how the CRM works. Everything is secure and read-only.
                        </p>
                        <div class="mt-7 grid w-full gap-2.5 sm:grid-cols-2">
                            @foreach ($suggestedQuestions as $question)
                                <button type="button" class="suggestion-chip group flex items-center justify-between gap-3 rounded-2xl border border-solen-border bg-white px-4 py-3 text-left text-sm font-semibold text-solen-ink shadow-solen-sm transition hover:border-solen hover:bg-[#fff7ef]" data-question="{{ $question }}">
                                    <span class="min-w-0 truncate">{{ $question }}</span>
                                    <svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-solen-muted transition group-hover:translate-x-0.5 group-hover:text-solen"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>

            {{-- ===================== TYPING / THINKING INDICATOR ===================== --}}
            <div id="typing" class="hidden bg-solen-cream px-4 pb-3 sm:px-6">
                <div class="mx-auto flex max-w-4xl items-center">
                    <div class="solen-gradient solen-pulse mr-3 hidden h-9 w-9 shrink-0 items-center justify-center rounded-full text-white sm:flex">{!! $spark !!}</div>
                    <div class="inline-flex items-center gap-3 rounded-2xl rounded-bl-md border border-solen-border bg-white px-4 py-3 shadow-solen-sm">
                        <span class="flex items-end gap-1">
                            <span class="solen-dot h-1.5 w-1.5 rounded-full bg-solen"></span>
                            <span class="solen-dot h-1.5 w-1.5 rounded-full bg-solen" style="animation-delay:.16s"></span>
                            <span class="solen-dot h-1.5 w-1.5 rounded-full bg-solen" style="animation-delay:.32s"></span>
                        </span>
                        <span id="typing-label" class="solen-shimmer text-sm font-semibold">SolenAssist is thinking</span>
                    </div>
                </div>
            </div>

            {{-- ===================== COMPOSER ===================== --}}
            <form id="chat-form" class="shrink-0 border-t border-solen-border bg-white px-4 py-3 sm:px-6">
                @csrf
                <input type="hidden" id="chat-id" value="{{ $activeChat?->id }}">
                <div class="solen-scroll mx-auto mb-3 flex max-w-4xl gap-2 overflow-x-auto pb-1">
                    @foreach ($suggestedQuestions as $question)
                        <button type="button" class="suggestion-chip shrink-0 rounded-full border border-solen-border bg-white px-3.5 py-1.5 text-xs font-semibold text-solen-muted transition hover:border-solen hover:text-solen" data-question="{{ $question }}">{{ $question }}</button>
                    @endforeach
                </div>
                <div class="mx-auto flex max-w-4xl items-end gap-2 rounded-3xl border border-solen-border bg-white px-4 py-2 shadow-solen-sm transition focus-within:border-solen focus-within:shadow-solen">
                    <textarea id="message-input" rows="1" class="max-h-40 min-h-[40px] flex-1 resize-none border-0 bg-transparent px-2 py-2 text-[15px] text-solen-ink outline-none placeholder:text-solen-muted/70 focus:ring-0" placeholder="Message SolenAssist…"></textarea>
                    <button id="send-button" type="submit" class="solen-gradient inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl text-white shadow-solen-sm transition hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-40" title="Send">
                        <svg viewBox="0 0 24 24" class="h-5 w-5"><path d="M3.4 20.4 21 12 3.4 3.6 3 10l12 2-12 2z" fill="white"/></svg>
                    </button>
                </div>
                <p class="mx-auto mt-2 max-w-4xl text-center text-[11px] text-solen-muted">SolenAssist can make mistakes. Check important info.</p>
            </form>
        </main>
    </div>
@endsection

@section('scripts')
    <script>
        const SPARK_SVG = `{!! $spark !!}`;
        const form = document.getElementById('chat-form');
        const input = document.getElementById('message-input');
        const messages = document.getElementById('messages');
        const typing = document.getElementById('typing');
        const typingLabel = document.getElementById('typing-label');
        const sendButton = document.getElementById('send-button');
        const chatId = document.getElementById('chat-id');
        const csrfToken = document.querySelector('meta[name="csrf_token"]').content;
        let typingTimers = [];

        function scrollMessages(extra = 0) {
            requestAnimationFrame(() => {
                messages.scrollTo({
                    top: messages.scrollHeight + extra,
                    behavior: 'smooth',
                });
            });
        }

        function clearTypingTimers() {
            typingTimers.forEach((t) => clearTimeout(t));
            typingTimers = [];
        }

        function setTypingLabel(text) {
            if (typingLabel) typingLabel.textContent = text;
            scrollMessages(240);
        }

        // Professional, ChatGPT/Claude-style indicator: one shimmering line that
        // gently escalates on longer waits — without exposing internal pipeline steps.
        function showTyping(mode = 'chat') {
            clearTypingTimers();
            typing.classList.remove('hidden');
            setTypingLabel(mode === 'retry' ? 'Regenerating response' : 'SolenAssist is thinking');
            scrollMessages(240);
            typingTimers.push(setTimeout(() => setTypingLabel('Working through your CRM data'), 4200));
            typingTimers.push(setTimeout(() => setTypingLabel('Almost there'), 9000));
        }

        function hideTyping() {
            clearTypingTimers();
            typing.classList.add('hidden');
        }

        function actionBar(content, metadata = {}) {
            const bar = document.createElement('div');
            bar.className = 'mb-2 flex items-center justify-between gap-3 border-b border-solen-border pb-2';

            const label = document.createElement('span');
            if (metadata.status === 'failed') {
                label.className = 'text-xs font-bold uppercase tracking-wider text-rose-600';
                label.textContent = 'Needs attention';
            } else {
                label.className = 'solen-gradient-text text-xs font-bold uppercase tracking-wider';
                label.textContent = 'SolenAssist';
            }
            bar.appendChild(label);

            const actions = document.createElement('div');
            actions.className = 'flex gap-2';

            const copy = document.createElement('button');
            copy.type = 'button';
            copy.className = 'copy-answer rounded-lg border border-solen-border px-2.5 py-1 text-xs font-semibold text-solen-muted transition hover:border-solen hover:text-solen';
            copy.dataset.copy = content;
            copy.textContent = 'Copy';
            actions.appendChild(copy);

            if (metadata.retryable && chatId.value) {
                const retry = document.createElement('button');
                retry.type = 'button';
                retry.className = 'retry-response rounded-lg border border-solen/40 bg-solen/5 px-2.5 py-1 text-xs font-semibold text-solen-deep transition hover:bg-solen/10';
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
                    item.className = 'rounded-xl border border-solen-border bg-gradient-to-br from-white to-[#fff4e8] p-4 shadow-solen-sm';
                    if (Object.prototype.hasOwnProperty.call(card, 'label') || Object.prototype.hasOwnProperty.call(card, 'value')) {
                        const label = document.createElement('div');
                        label.className = 'text-xs font-bold uppercase tracking-wider text-solen-deep';
                        label.textContent = card.label || 'Result';
                        const value = document.createElement('div');
                        value.className = 'mt-1.5 text-2xl font-extrabold text-solen-ink';
                        value.textContent = card.value ?? '';
                        item.appendChild(label);
                        item.appendChild(value);
                    } else {
                        Object.entries(card).forEach(([key, value], index) => {
                            const line = document.createElement('div');
                            line.className = index === 0 ? 'text-xs font-bold uppercase tracking-wider text-solen-deep' : 'mt-1 text-sm font-semibold text-solen-ink';
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
                wrap.className = 'mt-3 max-w-full overflow-x-auto rounded-xl border border-solen-border';
                const table = document.createElement('table');
                table.className = 'min-w-full divide-y divide-solen-border text-left text-xs';
                const thead = document.createElement('thead');
                thead.className = 'bg-solen-cream text-solen-muted';
                const headRow = document.createElement('tr');
                (answer.columns || []).forEach((column) => {
                    const th = document.createElement('th');
                    th.className = 'px-3 py-2.5 font-bold uppercase tracking-wide';
                    th.textContent = column.replaceAll('_', ' ');
                    headRow.appendChild(th);
                });
                thead.appendChild(headRow);
                const tbody = document.createElement('tbody');
                tbody.className = 'divide-y divide-[#f1e7d8] bg-white text-solen-ink';
                (answer.rows || []).forEach((row) => {
                    const tr = document.createElement('tr');
                    tr.className = 'transition hover:bg-solen-cream/60';
                    (answer.columns || []).forEach((column) => {
                        const td = document.createElement('td');
                        td.className = 'whitespace-nowrap px-3 py-2.5';
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

        function feedbackPanel(messageId) {
            if (!messageId) return null;

            const panel = document.createElement('div');
            panel.className = 'feedback-panel mt-3 flex flex-wrap items-center gap-2 border-t border-solen-border pt-3 text-xs text-solen-muted';
            panel.dataset.feedbackUrl = `{{ url('/ai-chat/messages') }}/${messageId}/feedback`;
            panel.innerHTML = `
                <span>Was this helpful?</span>
                <button type="button" class="feedback-button rounded-lg border border-solen-border px-2.5 py-1 font-semibold transition hover:border-emerald-300 hover:text-emerald-600" data-rating="up">Yes</button>
                <button type="button" class="feedback-button rounded-lg border border-solen-border px-2.5 py-1 font-semibold transition hover:border-rose-300 hover:text-rose-600" data-rating="down">No</button>
                <textarea class="feedback-comment hidden min-h-[44px] w-full rounded-lg border border-solen-border px-3 py-2 text-xs text-solen-ink outline-none focus:border-solen" placeholder="What were you expecting?"></textarea>
                <button type="button" class="feedback-submit hidden rounded-lg solen-gradient px-3 py-1 font-semibold text-white">Submit feedback</button>
            `;

            return panel;
        }

        function appendMessage(role, content, answer = null, metadata = {}, messageId = null) {
            const wrapper = document.createElement('div');
            wrapper.className = `solen-rise mx-auto flex max-w-4xl ${role === 'user' ? 'justify-end' : 'justify-start'}`;

            if (role === 'assistant') {
                const avatar = document.createElement('div');
                avatar.className = 'solen-gradient mr-3 hidden h-9 w-9 shrink-0 items-center justify-center rounded-full text-white shadow-solen-sm sm:flex';
                avatar.innerHTML = SPARK_SVG;
                wrapper.appendChild(avatar);
            }

            const bubble = document.createElement('div');
            bubble.className = role === 'user'
                ? 'solen-gradient max-w-[88%] rounded-2xl rounded-br-md px-5 py-3 text-[15px] leading-7 text-white shadow-solen-sm sm:max-w-[74%]'
                : 'max-w-[92%] rounded-2xl rounded-bl-md border border-solen-border bg-white px-5 py-4 text-[15px] leading-7 text-solen-ink shadow-solen-sm sm:max-w-[90%]';

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
            if (role === 'assistant') {
                const panel = feedbackPanel(messageId);
                if (panel) bubble.appendChild(panel);
            }
            wrapper.appendChild(bubble);
            messages.appendChild(wrapper);
            scrollMessages(role === 'user' ? 260 : 120);
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
                showTyping('retry');
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
                    setTypingLabel('Rendering answer');
                    appendMessage('assistant', assistant.content, assistant.metadata?.answer || null, assistant.metadata || {}, assistant.id);
                } catch (error) {
                    appendMessage('assistant', error.message || 'Retry failed. Please try again.', null, {status: 'failed', retryable: false});
                } finally {
                    hideTyping();
                    retryButton.disabled = false;
                }
                return;
            }

            const feedbackButton = event.target.closest('.feedback-button');
            if (feedbackButton) {
                const panel = feedbackButton.closest('.feedback-panel');
                if (feedbackButton.dataset.rating === 'down') {
                    panel.querySelector('.feedback-comment')?.classList.remove('hidden');
                    panel.querySelector('.feedback-submit')?.classList.remove('hidden');
                    panel.dataset.rating = 'down';
                    return;
                }

                await submitFeedback(panel, 'up');
                return;
            }

            const feedbackSubmit = event.target.closest('.feedback-submit');
            if (feedbackSubmit) {
                const panel = feedbackSubmit.closest('.feedback-panel');
                await submitFeedback(panel, panel.dataset.rating || 'down');
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
            showTyping('chat');

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
                setTypingLabel('Rendering answer');
                appendMessage('assistant', assistant.content, assistant.metadata?.answer || null, assistant.metadata || {}, assistant.id);

                if (window.location.pathname === '{{ route('ai-chat.index', [], false) }}') {
                    window.history.replaceState({}, '', data.chat.url);
                }
            } catch (error) {
                appendMessage('assistant', error.message || 'Something went wrong. Please try again.', null, {status: 'failed', retryable: false});
            } finally {
                hideTyping();
                sendButton.disabled = false;
                input.focus();
                scrollMessages(160);
            }
        });

        async function submitFeedback(panel, rating) {
            if (!panel?.dataset.feedbackUrl) return;

            const comment = panel.querySelector('.feedback-comment')?.value || '';
            const response = await fetch(panel.dataset.feedbackUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({rating, comment}),
            });

            if (response.ok) {
                panel.innerHTML = '<span class="font-semibold text-emerald-600">Thanks for the feedback.</span>';
            } else {
                panel.innerHTML = '<span class="font-semibold text-rose-600">Feedback could not be saved.</span>';
            }
        }

        scrollMessages();
    </script>
@endsection
