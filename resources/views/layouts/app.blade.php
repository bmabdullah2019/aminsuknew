<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --app-primary: #0f4ca8;
            --app-secondary: #0d9488;
            --app-accent: #f59e0b;
            --app-bg: #eff5ff;
            --app-text: #0f172a;
            --app-border: #dbe6f7;
            --app-shadow: 0 20px 44px rgba(15, 23, 42, 0.12);
            --app-radius: 14px;
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            font-family: "Outfit", "Segoe UI", sans-serif;
            color: var(--app-text);
            background:
                radial-gradient(circle at 12% 0, #d9e8ff 0, transparent 38%),
                radial-gradient(circle at 100% 15%, #d7f3ef 0, transparent 34%),
                linear-gradient(180deg, #f6f9ff 0, var(--app-bg) 100%);
            background-attachment: fixed;
        }

        .app-navbar {
            background: rgba(255, 255, 255, 0.92);
            border-bottom: 1px solid var(--app-border);
            will-change: auto;
        }

        .app-navbar .navbar-brand {
            color: #0b2f72;
            font-weight: 700;
            letter-spacing: .01em;
        }

        .app-navbar .nav-link {
            border-radius: 10px;
            color: #1e335b;
            font-weight: 600;
            padding: .45rem .85rem;
        }

        .app-navbar .nav-link:hover {
            background: #edf3ff;
            color: var(--app-primary);
        }

        .auth-shell {
            padding: 32px 0;
        }

        .auth-card {
            border: 1px solid var(--app-border);
            border-radius: var(--app-radius);
            background: linear-gradient(180deg, #ffffff 0, #f9fbff 100%);
            box-shadow: var(--app-shadow);
        }

        .auth-card .card-header {
            background: transparent;
            border-bottom: 1px solid var(--app-border);
            color: #0d2f6f;
            font-size: 1.1rem;
            font-weight: 700;
            padding: 1rem 1.25rem;
        }

        .auth-card .card-body {
            padding: 1.25rem;
        }

        .form-label {
            color: #1e335b;
            font-weight: 600;
            margin-bottom: .45rem;
        }

        .form-control {
            border: 1px solid #cddcf3;
            border-radius: 10px;
            min-height: 44px;
            background: #fcfdff;
        }

        .form-control:focus {
            border-color: var(--app-primary);
            box-shadow: 0 0 0 .22rem rgba(15, 76, 168, 0.16);
        }

        .btn-primary {
            border: 0;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--app-primary) 0, #2f6cd7 100%);
            box-shadow: 0 10px 18px rgba(15, 76, 168, 0.22);
            font-weight: 700;
            min-height: 42px;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #0c3f8f 0, #235dbf 100%);
        }

        .btn-link {
            color: #1f4b9a;
            font-weight: 600;
            text-decoration: none;
        }

        .btn-link:hover {
            color: #153975;
            text-decoration: underline;
        }

        .alert {
            border-radius: 12px;
            border: 1px solid transparent;
        }

        .invalid-feedback {
            font-size: .84rem;
            font-weight: 600;
        }

        .app-login-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(360px, 460px);
            align-items: center;
            gap: 28px;
            min-height: calc(100vh - 132px);
        }

        .app-login-panel {
            min-height: 520px;
            padding: 44px;
            border-radius: 16px;
            background: linear-gradient(135deg, #0b2f72 0%, #0f4ca8 56%, #2f6cd7 100%);
            color: #fff;
            box-shadow: 0 26px 70px rgba(15, 76, 168, .22);
            overflow: hidden;
            position: relative;
        }

        .app-login-panel::after {
            content: "";
            position: absolute;
            right: -120px;
            bottom: -150px;
            width: 330px;
            height: 330px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .1);
        }

        .app-login-badge {
            display: inline-flex;
            align-items: center;
            min-height: 30px;
            padding: 0 13px;
            margin-bottom: 18px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .16);
            color: #fff;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .app-login-panel h1 {
            max-width: 620px;
            margin: 0 0 16px;
            color: #fff;
            font-size: 2.55rem;
            font-weight: 800;
            line-height: 1.12;
        }

        .app-login-panel p {
            max-width: 560px;
            margin: 0 0 24px;
            color: rgba(255, 255, 255, .82);
            font-size: 1rem;
            line-height: 1.75;
        }

        .app-login-modules {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            position: relative;
            z-index: 1;
        }

        .app-login-modules span {
            min-height: 64px;
            display: flex;
            align-items: center;
            padding: 14px;
            border: 1px solid rgba(255, 255, 255, .16);
            border-radius: 14px;
            background: rgba(255, 255, 255, .1);
            color: #fff;
            font-weight: 800;
        }

        .app-login-card {
            padding: 30px;
            border: 1px solid var(--app-border);
            border-radius: 16px;
            background: #fff;
            box-shadow: var(--app-shadow);
        }

        .app-login-head {
            display: grid;
            grid-template-columns: 58px minmax(0, 1fr);
            align-items: center;
            gap: 14px;
            margin-bottom: 24px;
        }

        .app-login-icon {
            display: grid;
            place-items: center;
            width: 58px;
            height: 58px;
            border-radius: 50%;
            color: #fff;
            background: linear-gradient(135deg, var(--app-primary) 0, #2f6cd7 100%);
            font-size: 1.25rem;
            font-weight: 900;
        }

        .app-login-head h2 {
            margin: 0 0 4px;
            color: #0d2f6f;
            font-size: 1.35rem;
            font-weight: 800;
        }

        .app-login-head p {
            margin: 0;
            color: #64748b;
            font-size: .92rem;
        }

        .app-login-input {
            position: relative;
        }

        .app-login-input > span {
            position: absolute;
            top: 50%;
            left: 15px;
            z-index: 2;
            color: #64748b;
            font-weight: 900;
            transform: translateY(-50%);
        }

        .app-login-input .form-control {
            min-height: 48px;
            padding-left: 42px;
        }

        .app-login-input #password {
            padding-right: 68px;
        }

        .app-password-toggle {
            position: absolute;
            top: 50%;
            right: 8px;
            z-index: 3;
            min-width: 52px;
            height: 34px;
            border: 0;
            border-radius: 9px;
            background: #eef4fc;
            color: #1f4b9a;
            font-size: .78rem;
            font-weight: 800;
            transform: translateY(-50%);
        }

        .app-login-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 1rem;
        }

        .app-login-options span {
            color: #64748b;
            font-size: .82rem;
            font-weight: 700;
        }

        .app-login-submit {
            min-height: 48px;
        }

        .app-login-note {
            margin-top: 16px;
            padding: 12px;
            border-radius: 12px;
            background: #f5f8fd;
            color: #64748b;
            font-size: .84rem;
            line-height: 1.45;
        }

        .wc-btn-loading {
            position: relative;
            color: transparent !important;
            pointer-events: none;
            will-change: transform;
        }

        .wc-btn-loading::after {
            content: "";
            width: 1rem;
            height: 1rem;
            position: absolute;
            top: 50%;
            left: 50%;
            margin: -.5rem 0 0 -.5rem;
            border: 2px solid rgba(255, 255, 255, .45);
            border-top-color: #fff;
            border-radius: 50%;
            animation: app-spin 1s linear infinite;
            backface-visibility: hidden;
            transform: translateZ(0);
        }

        @keyframes app-spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 767px) {
            .auth-shell {
                padding: 16px 0 24px;
            }

            .auth-card .card-body {
                padding: 1rem;
            }

            .app-login-grid {
                grid-template-columns: 1fr;
                min-height: auto;
            }

            .app-login-panel {
                min-height: auto;
                padding: 24px;
            }

            .app-login-panel h1 {
                font-size: 1.75rem;
            }

            .app-login-card {
                padding: 20px;
            }

            .app-login-options {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-lg app-navbar">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    {{ config('app.name', 'Laravel') }}
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#app-navbar-content" aria-controls="app-navbar-content" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="app-navbar-content">
                    <ul class="navbar-nav me-auto"></ul>
                    <ul class="navbar-nav ms-auto">
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                                </li>
                            @endif
                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                </li>
                            @endif
                        @else
                            @if (Route::has('admin.users.index'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('admin.users.index') }}">Manage Users</a>
                                </li>
                            @endif

                            @if (Route::has('admin.roles.index'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('admin.roles.index') }}">Manage Roles</a>
                                </li>
                            @endif

                            @if (Route::has('admin.products.index'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('admin.products.index') }}">Manage Products</a>
                                </li>
                            @endif

                            @if (Route::has('logout'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        {{ __('Logout') }}
                                    </a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </li>
                            @endif

                            <li class="nav-item">
                                <span class="nav-link">{{ Auth::user()->name }}</span>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="auth-shell">
            @yield('content')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll("form").forEach(function (form) {
            form.addEventListener("submit", function () {
                if (form.dataset.wcSubmitting === "true") {
                    return;
                }
                form.dataset.wcSubmitting = "true";
                var submit = form.querySelector("button[type='submit'], input[type='submit']");
                if (submit) {
                    submit.classList.add("wc-btn-loading");
                    submit.setAttribute("disabled", "disabled");
                    submit.setAttribute("aria-disabled", "true");
                }
            });
        });

        document.querySelectorAll("[data-password-toggle]").forEach(function (button) {
            button.addEventListener("click", function () {
                var input = document.getElementById("password");
                if (!input) {
                    return;
                }

                var isHidden = input.type === "password";
                input.type = isHidden ? "text" : "password";
                button.textContent = isHidden ? "Hide" : "Show";
                button.setAttribute("aria-label", isHidden ? "Hide password" : "Show password");
            });
        });
    </script>
</body>
</html>
