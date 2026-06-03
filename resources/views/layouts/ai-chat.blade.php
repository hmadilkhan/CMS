<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf_token" content="{{ csrf_token() }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SolenAssist') - {{ config('app.name', 'CRM') }}</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('website/images/favicon_big.png') }}">
    <link rel="shortcut icon" href="{{ asset('website/images/favicon_big.png') }}">

    {{-- Brand palette (Solen Energy Construction) --}}
    @include('layouts.partials.solen-palette')

    {{-- Figtree (matches the CRM's tailwind.config sans family) --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Figtree', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        solen: {
                            DEFAULT: '#ee8f45',
                            deep: '#c8642d',
                            dark: '#a94f1f',
                            gold: '#ffc18f',
                            ink: '#342416',
                            muted: '#7c6f60',
                            border: '#eadfce',
                            cream: '#fbf7f1',
                            night: '#17120d',
                            night2: '#241a12',
                        },
                    },
                    boxShadow: {
                        solen: '0 24px 60px -28px rgba(151, 76, 18, 0.45)',
                        'solen-sm': '0 6px 20px -10px rgba(151, 76, 18, 0.35)',
                    },
                },
            },
        };
    </script>

    <style>
        html,
        body {
            height: 100%;
        }

        body {
            margin: 0;
            font-family: 'Figtree', ui-sans-serif, system-ui, sans-serif;
            background: var(--solen-background);
        }

        /* Reusable brand gradient helpers */
        .solen-gradient {
            background-image: var(--solen-gradient);
        }

        .solen-gradient-text {
            background-image: var(--solen-gradient-horizontal);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        /* Refined warm scrollbar */
        .solen-scroll {
            scrollbar-width: thin;
            scrollbar-color: #e4d4bf transparent;
        }
        .solen-scroll::-webkit-scrollbar { width: 8px; height: 8px; }
        .solen-scroll::-webkit-scrollbar-track { background: transparent; }
        .solen-scroll::-webkit-scrollbar-thumb {
            background: #e4d4bf;
            border-radius: 9999px;
        }
        .solen-scroll::-webkit-scrollbar-thumb:hover { background: #d6c0a3; }

        /* ChatGPT/Claude-style shimmer "thinking" text */
        @keyframes solenShimmer {
            0% { background-position: 200% center; }
            100% { background-position: -200% center; }
        }
        .solen-shimmer {
            background: linear-gradient(
                90deg,
                #b8a690 0%,
                #8c7b66 25%,
                #ee8f45 50%,
                #8c7b66 75%,
                #b8a690 100%
            );
            background-size: 200% auto;
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            color: transparent;
            animation: solenShimmer 2.4s linear infinite;
        }

        /* Soft pulsing glow ring on the assistant avatar while thinking */
        @keyframes solenPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(238, 143, 69, 0.45); }
            50% { box-shadow: 0 0 0 8px rgba(238, 143, 69, 0); }
        }
        .solen-pulse { animation: solenPulse 1.8s ease-out infinite; }

        /* Three-dot wave (kept subtle, brand-coloured) */
        @keyframes solenDot {
            0%, 80%, 100% { transform: translateY(0); opacity: 0.35; }
            40% { transform: translateY(-3px); opacity: 1; }
        }
        .solen-dot { animation: solenDot 1.3s ease-in-out infinite; }

        /* Gentle entrance for newly added messages */
        @keyframes solenRise {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .solen-rise { animation: solenRise 0.32s ease-out both; }
    </style>
</head>

<body class="min-h-screen overflow-hidden text-solen-ink antialiased">
    @yield('content')

    @yield('scripts')
</body>

</html>
