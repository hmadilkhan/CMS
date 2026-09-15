@extends('layouts.master')
@section('title', 'Operations')

@section('content')
    {{--
        The Operations console: one window for the twenty-odd Operations
        screens, laid out like the report builder - what there is on the left,
        what you are working on in the middle.

        Phase 1 opens each section's existing page embedded (the same URL, the
        same permissions, minus the chrome), so every screen is in the console
        from day one and nothing working had to be rewritten to get there. A
        section can be replaced with a native panel later without touching this
        page - see config/operations_console.php.
    --}}
    <div class="ops-console container-fluid px-0" data-ops-console>
        <style>
            .ops-console {
                color: #342416;
                width: 100%;
            }

            .ops-console .ops-bar {
                display: flex;
                align-items: center;
                gap: 16px;
                padding: 0 4px 14px;
                border-bottom: 1px solid #eadfce;
            }

            .ops-console .ops-shell {
                display: flex;
                align-items: stretch;
                min-height: 620px;
                height: calc(100vh - 200px);
            }

            .ops-console .ops-nav {
                width: 268px;
                flex-shrink: 0;
                border-right: 1px solid #eadfce;
                display: flex;
                flex-direction: column;
                min-height: 0;
            }

            .ops-console .ops-nav-scroll {
                flex: 1;
                overflow-y: auto;
                padding: 12px 12px 20px;
            }

            .ops-console .ops-group {
                font-size: 11px;
                font-weight: 700;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                color: #7c6f60;
                margin: 14px 8px 6px;
            }

            .ops-console .ops-link {
                display: flex;
                align-items: center;
                gap: 9px;
                width: 100%;
                text-align: left;
                border: 0;
                background: transparent;
                border-radius: 6px;
                padding: 7px 9px;
                font-size: 13px;
                color: #342416;
                text-decoration: none;
            }

            .ops-console .ops-link:hover {
                background: rgba(240, 122, 36, 0.08);
                color: #a94f1f;
            }

            .ops-console .ops-link.is-active {
                background: rgba(240, 122, 36, 0.12);
                color: #a94f1f;
                font-weight: 600;
            }

            .ops-console .ops-link i {
                width: 16px;
                text-align: center;
                opacity: 0.75;
            }

            .ops-console .ops-main {
                flex: 1;
                min-width: 0;
                display: flex;
                flex-direction: column;
            }

            .ops-console .ops-main-head {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 11px 16px;
                border-bottom: 1px solid #eadfce;
            }

            .ops-console .ops-frame-wrap {
                flex: 1;
                min-height: 0;
                position: relative;
            }

            .ops-console iframe {
                width: 100%;
                height: 100%;
                border: 0;
                display: block;
                background: #ffffff;
            }

            .ops-console .ops-loading {
                position: absolute;
                inset: 0;
                display: none;
                align-items: center;
                justify-content: center;
                background: rgba(255, 255, 255, 0.72);
                color: #7c6f60;
                font-size: 13px;
            }

            .ops-console.is-loading .ops-loading {
                display: flex;
            }

            .ops-console .form-control {
                border: 1px solid #eadfce;
                border-radius: 6px;
                font-size: 12.5px;
                min-height: 34px;
            }

            .ops-console .form-control:focus {
                border-color: #ee8f45;
                box-shadow: 0 0 0 0.2rem rgba(240, 122, 36, 0.18);
            }

            .ops-console .btn-solen {
                background: linear-gradient(135deg, #ffc18f 0%, #ee8f45 56%, #c8642d 100%);
                border: 1px solid transparent;
                color: #ffffff;
                font-weight: 600;
            }

            .ops-console .btn-solen:hover {
                color: #ffffff;
                filter: brightness(1.03);
            }

            @media (max-width: 991px) {
                .ops-console .ops-shell {
                    flex-direction: column;
                    height: auto;
                }

                .ops-console .ops-nav {
                    width: 100%;
                    border-right: 0;
                    border-bottom: 1px solid #eadfce;
                }

                .ops-console .ops-nav-scroll {
                    max-height: 260px;
                }

                .ops-console .ops-frame-wrap {
                    height: 70vh;
                }
            }
        </style>

        <div class="ops-bar">
            <div class="flex-grow-1">
                <div class="text-muted" style="font-size: 11px;">Operations</div>
                <h3 class="fw-bold mb-0" style="font-size: 18px;" data-ops-title>
                    {{ $section['label'] ?? 'Operations' }}
                </h3>
            </div>

            <a href="{{ $section['url'] ?? '#' }}" class="btn btn-sm btn-outline-secondary" data-ops-open-full
                target="_blank" rel="noopener">
                <i class="icofont-external-link me-1"></i>Open full page
            </a>
        </div>

        @if (empty($groups))
            <div class="text-center py-5 text-muted">
                <i class="icofont-briefcase" style="font-size: 2.4rem;"></i>
                <div class="fw-bold mt-2">Nothing to show</div>
                <div style="font-size: 12.5px;">No Operations screen is available to your account.</div>
            </div>
        @else
            <div class="ops-shell">
                <nav class="ops-nav">
                    <div class="p-3 pb-0">
                        <input type="search" class="form-control" placeholder="Search Operations"
                            data-ops-search aria-label="Search Operations screens">
                    </div>

                    <div class="ops-nav-scroll">
                        @foreach ($groups as $group)
                            <div data-ops-group>
                                <div class="ops-group">{{ $group['name'] }}</div>

                                @foreach ($group['sections'] as $item)
                                    {{-- A real link: it works with the script, and it still
                                         opens the page on its own without it. --}}
                                    <a class="ops-link {{ ($section['key'] ?? null) === $item['key'] ? 'is-active' : '' }}"
                                        href="{{ route('operations.console', ['section' => $item['key']]) }}"
                                        data-ops-link data-key="{{ $item['key'] }}"
                                        data-label="{{ $item['label'] }}"
                                        data-url="{{ $item['url'] }}">
                                        <i class="{{ $item['icon'] ?? 'icofont-circle' }}"></i>
                                        <span>{{ $item['label'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endforeach

                        <p class="text-muted px-2 mt-3 mb-0" style="font-size: 11.5px;" data-ops-empty hidden>
                            No screen matches that search.
                        </p>
                    </div>
                </nav>

                <section class="ops-main">
                    <div class="ops-main-head">
                        <i class="icofont-window"></i>
                        <span class="fw-bold" style="font-size: 13px;" data-ops-section-name>
                            {{ $section['label'] ?? '' }}
                        </span>
                        <div class="flex-grow-1"></div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-ops-reload>
                            <i class="icofont-refresh me-1"></i>Reload
                        </button>
                    </div>

                    <div class="ops-frame-wrap">
                        <div class="ops-loading">Loading…</div>
                        <iframe title="Operations screen" data-ops-frame
                            src="{{ isset($section['url']) ? $section['url'] . (str_contains($section['url'], '?') ? '&' : '?') . 'embedded=1' : '' }}"></iframe>
                    </div>
                </section>
            </div>
        @endif
    </div>

    <script>
        (function () {
            const console_ = document.querySelector('[data-ops-console]');

            if (!console_) {
                return;
            }

            const frame = console_.querySelector('[data-ops-frame]');
            const title = console_.querySelector('[data-ops-title]');
            const sectionName = console_.querySelector('[data-ops-section-name]');
            const openFull = console_.querySelector('[data-ops-open-full]');
            const search = console_.querySelector('[data-ops-search]');
            const links = Array.from(console_.querySelectorAll('[data-ops-link]'));

            function embeddedUrl(url) {
                return url + (url.includes('?') ? '&' : '?') + 'embedded=1';
            }

            function open(link, pushState) {
                links.forEach(function (other) {
                    other.classList.toggle('is-active', other === link);
                });

                const label = link.dataset.label;
                const url = link.dataset.url;

                title.textContent = label;
                sectionName.textContent = label;
                openFull.href = url;
                console_.classList.add('is-loading');
                frame.src = embeddedUrl(url);

                if (pushState) {
                    // A section is a place: it can be linked to, and Back works.
                    window.history.pushState({ key: link.dataset.key }, '', link.href);
                }
            }

            links.forEach(function (link) {
                link.addEventListener('click', function (event) {
                    if (event.metaKey || event.ctrlKey || event.shiftKey) {
                        return;     // let the browser open it in a tab
                    }

                    event.preventDefault();
                    open(link, true);
                });
            });

            window.addEventListener('popstate', function () {
                const key = new URLSearchParams(window.location.search).get('section');
                const link = links.find(function (candidate) {
                    return candidate.dataset.key === key;
                }) || links[0];

                if (link) {
                    open(link, false);
                }
            });

            frame.addEventListener('load', function () {
                console_.classList.remove('is-loading');
            });

            console_.querySelector('[data-ops-reload]').addEventListener('click', function () {
                console_.classList.add('is-loading');
                frame.contentWindow.location.reload();
            });

            if (search) {
                const empty = console_.querySelector('[data-ops-empty]');

                search.addEventListener('input', function () {
                    const needle = search.value.trim().toLowerCase();
                    let shown = 0;

                    links.forEach(function (link) {
                        const hit = !needle || link.dataset.label.toLowerCase().includes(needle);
                        link.hidden = !hit;
                        shown += hit ? 1 : 0;
                    });

                    console_.querySelectorAll('[data-ops-group]').forEach(function (group) {
                        const any = Array.from(group.querySelectorAll('[data-ops-link]'))
                            .some(function (link) { return !link.hidden; });
                        group.hidden = !any;
                    });

                    empty.hidden = shown !== 0;
                });
            }
        })();
    </script>
@endsection
