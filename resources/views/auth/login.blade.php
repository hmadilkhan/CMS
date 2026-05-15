<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Solen Energy Construction - Sign In</title>
    <link rel="icon" href="{{ asset('storage/favicon.ico') }}" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@500;600;700&display=swap" rel="stylesheet">
    @include('layouts.partials.solen-palette')

    <style>
        :root {
            --background: var(--solen-background);
            --foreground: var(--solen-foreground);
            --card: var(--solen-card);
            --muted: var(--solen-muted);
            --border: var(--solen-border);
            --primary: var(--solen-primary);
            --primary-dark: var(--solen-primary-dark);
            --secondary: #fff6e5;
            --danger: #b42318;
            --danger-bg: #fff1f0;
            --shadow-elegant: 0 30px 80px -20px rgba(151, 76, 18, 0.35);
            --shadow-glow: 0 0 60px rgba(245, 164, 42, 0.42);
            --gradient-sun: var(--solen-gradient);
            --gradient-glow: radial-gradient(circle at 30% 20%, rgba(255, 201, 71, 0.44), transparent 58%);
            --font-body: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            --font-display: Manrope, Inter, ui-sans-serif, system-ui, sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            min-height: 100%;
            margin: 0;
        }

        body {
            background: var(--background);
            color: var(--foreground);
            font-family: var(--font-body);
            font-weight: 400;
            letter-spacing: 0;
            text-rendering: geometricPrecision;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        a {
            color: inherit;
        }

        .login-shell {
            display: grid;
            min-height: 100vh;
            width: 100%;
        }

        .login-form-panel {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
            justify-content: space-between;
            padding: 26px 24px 30px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .brand-icon {
            position: relative;
            display: grid;
            height: 68px;
            width: 68px;
            place-items: center;
            overflow: hidden;
            border: 1px solid var(--solen-primary-border);
            border-radius: 50%;
            background: #fff;
            box-shadow: var(--shadow-glow);
            color: #fff;
        }

        .brand-icon img {
            display: block;
            height: 92%;
            width: 92%;
            object-fit: contain;
        }

        .brand-copy {
            line-height: 1.08;
        }

        .brand-title {
            display: block;
            font-family: var(--font-display);
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0;
        }

        .login-card {
            width: 100%;
            max-width: 448px;
            margin: 22px auto 30px;
        }

        .login-title {
            margin: 0;
            font-family: var(--font-display);
            font-size: clamp(40px, 8vw, 56px);
            font-weight: 600;
            line-height: 1.04;
            letter-spacing: 0;
        }

        .gradient-text {
            background: var(--gradient-sun);
            background-clip: text;
            color: transparent;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .login-intro {
            margin: 14px 0 0;
            color: var(--muted);
            font-size: 15px;
            line-height: 1.6;
        }

        .session-status {
            margin-top: 22px;
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            background: #f0fdf4;
            padding: 12px 14px;
            color: #166534;
            font-size: 13px;
            font-weight: 500;
        }

        .error-box {
            margin-top: 22px;
            border: 1px solid #f9b4ab;
            border-radius: 12px;
            background: var(--danger-bg);
            padding: 12px 14px;
            color: var(--danger);
            font-size: 13px;
            line-height: 1.45;
        }

        .login-form {
            display: grid;
            gap: 16px;
            margin-top: 26px;
        }

        .field-group {
            display: grid;
            gap: 7px;
        }

        .field-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .field-label {
            color: rgba(52, 36, 22, 0.82);
            font-size: 12px;
            font-weight: 500;
            letter-spacing: 0.01em;
        }

        .forgot-link {
            color: var(--primary-dark);
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
        }

        .forgot-link:hover {
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .input-wrap {
            position: relative;
        }

        .input-icon {
            pointer-events: none;
            position: absolute;
            left: 14px;
            top: 50%;
            height: 17px;
            width: 17px;
            color: var(--muted);
            transform: translateY(-50%);
        }

        .form-input {
            width: 100%;
            height: 48px;
            border: 1px solid var(--border);
            border-radius: 14px;
            background: var(--card);
            color: var(--foreground);
            font: inherit;
            font-size: 14px;
            font-weight: 400;
            outline: none;
            padding: 0 14px 0 42px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .form-input.has-toggle {
            padding-right: 48px;
        }

        .form-input::placeholder {
            color: #a49686;
        }

        .form-input:focus {
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 4px var(--solen-primary-focus);
        }

        .form-input.is-invalid {
            border-color: var(--danger);
        }

        .password-toggle {
            position: absolute;
            right: 8px;
            top: 50%;
            display: grid;
            height: 34px;
            width: 34px;
            place-items: center;
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: var(--muted);
            cursor: pointer;
            transform: translateY(-50%);
        }

        .password-toggle:hover,
        .password-toggle:focus-visible {
            background: #f8efe4;
            outline: none;
        }

        .field-error {
            color: var(--danger);
            font-size: 12px;
            line-height: 1.4;
        }

        .remember-row {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            color: var(--muted);
            cursor: pointer;
            font-size: 12px;
            line-height: 1.4;
        }

        .remember-row input {
            height: 15px;
            width: 15px;
            margin: 0;
            accent-color: var(--primary);
        }

        .submit-button {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            width: 100%;
            height: 50px;
            margin-top: 6px;
            overflow: hidden;
            border: 0;
            border-radius: 14px;
            background: var(--gradient-sun);
            box-shadow: var(--shadow-elegant);
            color: #fff;
            cursor: pointer;
            font-family: var(--font-display);
            font-size: 14px;
            font-weight: 600;
            transition: box-shadow 0.2s ease, transform 0.2s ease;
        }

        .submit-button::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.32), transparent);
            transform: translateX(-100%);
            transition: transform 0.65s ease;
        }

        .submit-button:hover {
            box-shadow: var(--shadow-glow);
        }

        .submit-button:hover::before {
            transform: translateX(100%);
        }

        .submit-button:active {
            transform: scale(0.99);
        }

        .submit-button span,
        .submit-button svg {
            position: relative;
            z-index: 1;
        }

        .request-access {
            margin-top: 28px;
            color: var(--muted);
            font-size: 14px;
            text-align: center;
        }

        .request-access a {
            color: var(--foreground);
            font-weight: 600;
            text-decoration: none;
        }

        .request-access a:hover {
            text-decoration: underline;
            text-underline-offset: 4px;
        }

        .login-footer {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            color: var(--muted);
            font-size: 12px;
        }

        .footer-links {
            display: inline-flex;
            gap: 16px;
        }

        .footer-links a {
            text-decoration: none;
        }

        .footer-links a:hover {
            color: var(--foreground);
        }

        .visual-panel {
            position: relative;
            display: none;
            min-height: 100vh;
            overflow: hidden;
        }

        .visual-panel img {
            position: absolute;
            inset: 0;
            height: 100%;
            width: 100%;
            object-fit: cover;
        }

        .image-overlay,
        .glow-overlay {
            position: absolute;
            inset: 0;
        }

        .image-overlay {
            background: linear-gradient(180deg, rgba(49, 31, 15, 0.18) 0%, rgba(35, 21, 11, 0.78) 100%);
        }

        .glow-overlay {
            background: var(--gradient-glow);
        }

        .metric-pill {
            position: absolute;
            right: 32px;
            top: 32px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            padding: 8px 12px;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            backdrop-filter: blur(14px);
        }

        .visual-content {
            position: relative;
            z-index: 1;
            display: flex;
            height: 100%;
            min-height: 100vh;
            flex-direction: column;
            justify-content: flex-start;
            padding: 78px 48px 48px;
            color: #fff;
        }

        .visual-copy {
            max-width: 438px;
        }

        .visual-copy h2 {
            margin: 0;
            font-family: var(--font-display);
            font-size: 42px;
            font-weight: 600;
            line-height: 1.12;
            letter-spacing: 0;
        }

        .visual-copy>p {
            margin: 18px 0 0;
            color: rgba(255, 255, 255, 0.76);
            font-size: 16px;
            line-height: 1.55;
        }

        .feature-list {
            display: grid;
            gap: 12px;
            margin-top: 30px;
        }

        .feature-card {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.07);
            padding: 15px;
            backdrop-filter: blur(14px);
        }

        .feature-icon {
            display: grid;
            height: 34px;
            width: 34px;
            flex: 0 0 34px;
            place-items: center;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.16);
        }

        .feature-card strong {
            display: block;
            font-family: var(--font-display);
            font-size: 14px;
            font-weight: 600;
        }

        .feature-card span {
            display: block;
            margin-top: 4px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 12px;
            line-height: 1.45;
        }

        @media (min-width: 1024px) {
            .login-shell {
                grid-template-columns: 1.05fr 1fr;
            }

            .login-form-panel {
                padding: 28px 80px 34px;
            }

            .visual-panel {
                display: block;
            }
        }

        @media (max-width: 640px) {
            .login-form-panel {
                padding: 20px 20px 24px;
            }

            .login-card {
                margin: 22px auto 28px;
            }

            .login-title {
                font-size: 40px;
            }

            .login-footer {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <main class="login-shell">
        <section class="login-form-panel">
            <header>
                <a class="brand" href="{{ url('/') }}" aria-label="Solen Energy Construction">
                    <span class="brand-icon" aria-hidden="true">
                        <img src="{{ asset('assets/images/logo.png') }}" alt="">
                    </span>
                    <span class="brand-copy">
                        <span class="brand-title">Solen Energy Co.</span>
                    </span>
                </a>
            </header>

            <div class="login-card">
                <h1 class="login-title">welcome <span class="gradient-text">back</span></h1>
                <p class="login-intro">Sign in to manage customers, projects, department tasks, service tickets, reports, and team updates from your Solen CRM workspace.</p>

                @if (session('status'))
                    <div class="session-status">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="error-box" role="alert">
                        <strong>Unable to sign in.</strong> {{ $errors->first() }}
                    </div>
                @endif

                <form class="login-form" method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="field-group">
                        <label for="username" class="field-label">Username</label>
                        <div class="input-wrap">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M20 21a8 8 0 0 0-16 0"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <input id="username" name="username" type="text" value="{{ old('username') }}" autocomplete="username" placeholder="Enter your username" class="form-input @error('username') is-invalid @enderror" required autofocus>
                        </div>
                        @error('username')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="field-group">
                        <div class="field-top">
                            <label for="password" class="field-label">Password</label>
                            <a href="{{ route('password.request') }}" class="forgot-link">Forgot?</a>
                        </div>
                        <div class="input-wrap">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="11" width="18" height="11" rx="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                            <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Enter your password" class="form-input has-toggle @error('password') is-invalid @enderror" required>
                            <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false" data-password-toggle>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <label class="remember-row" for="remember">
                        <input id="remember" name="remember" type="checkbox" @checked(old('remember'))>
                        Keep me signed in
                    </label>

                    <button type="submit" class="submit-button">
                        <span>Sign in to dashboard</span>
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14"></path>
                            <path d="m13 5 7 7-7 7"></path>
                        </svg>
                    </button>
                </form>

                <p class="request-access">New to Solen CRM? <a href="mailto:engineering@solenenergyco.com">Request access</a></p>
            </div>

            <footer class="login-footer">
                <p>&copy; {{ date('Y') }} Solen Energy Construction</p>
                <div class="footer-links" aria-label="Footer links">
                    <a href="mailto:info@solenenergyco.com">Support</a>
                    <a href="https://solenenergyco.com">Website</a>
                </div>
            </footer>
        </section>

        <aside class="visual-panel" aria-label="Solar CRM overview">
            <img src="{{ asset('assets/images/solar-hero-login.jpg') }}" alt="Solar farm at twilight">
            <div class="image-overlay"></div>
            <div class="glow-overlay"></div>

            <div class="metric-pill">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M13 2 3 14h9l-1 8 10-12h-9z"></path>
                </svg>
                Solar CRM command center
            </div>

            <div class="visual-content">
                <div class="visual-copy">
                    <h2>Manage every solar project from lead to completion.</h2>
                    <p>Solen CRM keeps customer records, project stages, department work, service tickets, and reporting connected in one reliable workspace.</p>

                    <div class="feature-list">
                        <div class="feature-card">
                            <span class="feature-icon" aria-hidden="true">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 3v18h18"></path>
                                    <path d="m7 14 4-4 4 4 5-5"></path>
                                </svg>
                            </span>
                            <div>
                                <strong>Project pipeline control</strong>
                                <span>Track customer details, project status, finance options, and movement across every department.</span>
                            </div>
                        </div>

                        <div class="feature-card">
                            <span class="feature-icon" aria-hidden="true">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11 20A7 7 0 0 1 4 13c0-3 2-6 7-9 5 3 7 6 7 9a7 7 0 0 1-7 7z"></path>
                                </svg>
                            </span>
                            <div>
                                <strong>Team and ticket coordination</strong>
                                <span>Keep tasks, notes, service tickets, schedules, and follow-ups visible for the right teams.</span>
                            </div>
                        </div>

                        <div class="feature-card">
                            <span class="feature-icon" aria-hidden="true">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="4"></circle>
                                    <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"></path>
                                </svg>
                            </span>
                            <div>
                                <strong>Reports that guide decisions</strong>
                                <span>Review forecasts, profitability, transactions, and operational progress without switching tools.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </main>

    <script>
        document.querySelector('[data-password-toggle]')?.addEventListener('click', function () {
            const input = document.getElementById('password');
            const showing = input.type === 'text';

            input.type = showing ? 'password' : 'text';
            this.setAttribute('aria-pressed', String(!showing));
            this.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
        });
    </script>
</body>

</html>
