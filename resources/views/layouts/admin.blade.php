<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $adminSiteName = data_get($generalsetting ?? null, 'name') ?: config('app.name', 'Laravel');
        $adminBrandLogo = data_get($generalsetting ?? null, 'dark_logo') ?: data_get($generalsetting ?? null, 'white_logo');
        $adminFavicon = data_get($generalsetting ?? null, 'favicon');
        $adminDescription = data_get($generalsetting ?? null, 'description')
            ?: data_get($generalsetting ?? null, 'meta_description')
            ?: 'Secure management dashboard';
        $adminHeaderSubtitle = Str::limit(strip_tags($adminDescription), 72);
        $adminSupportPhone = data_get($contact ?? null, 'hotline') ?: data_get($contact ?? null, 'phone');
        $adminSupportEmail = data_get($contact ?? null, 'email');
        $adminSupportAddress = data_get($contact ?? null, 'address');
        $adminSupportText = $adminSupportPhone
            ?: $adminSupportEmail
            ?: 'Authorized access only';
        $adminSupportHref = $adminSupportPhone
            ? 'tel:' . preg_replace('/[^0-9+]/', '', $adminSupportPhone)
            : ($adminSupportEmail ? 'mailto:' . $adminSupportEmail : null);

        $adminPages = collect($pages ?? []);
        $adminPageLinks = $adminPages
            ->filter(function ($page) {
                return !empty($page->slug) && !empty($page->name);
            })
            ->map(function ($page) {
                return [
                    'url' => route('page', ['slug' => $page->slug]),
                    'label' => $page->name,
                ];
            })
            ->values();

        $adminHeaderLinks = collect([
            ['url' => route('home'), 'label' => 'Home'],
        ])
            ->merge($adminPageLinks->take(3))
            ->push(['url' => route('contact'), 'label' => 'Contact'])
            ->unique('url')
            ->values()
            ->all();

        $adminFooterLinks = collect([
            ['url' => route('home'), 'label' => 'Home'],
        ])
            ->merge($adminPageLinks)
            ->push(['url' => route('contact'), 'label' => 'Contact'])
            ->unique('url')
            ->values()
            ->all();

        $adminAssetUrl = function ($path) {
            if (empty($path)) {
                return null;
            }

            return preg_match('~^(https?:)?//~', $path) ? $path : asset($path);
        };
    @endphp
    <title>{{ $adminSiteName }} - Admin Login</title>
    <link rel="shortcut icon" href="{{ $adminFavicon ? $adminAssetUrl($adminFavicon) : asset('public/backEnd/assets/images/favicon.ico') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    {{-- Essential Frontend CSS for Header --}}
    <link rel="stylesheet" href="{{asset('public/frontEnd/css/bootstrap.min.css')}}" />
    <link rel="stylesheet" href="{{asset('public/frontEnd/css/all.min.css')}}" />
    <link rel="stylesheet" href="{{asset('public/frontEnd/css/select2.min.css')}}" />
    <link rel="stylesheet" href="{{asset('public/frontEnd/css/mobile-menu.css')}}" />
    <link rel="stylesheet" href="{{asset('public/frontEnd/css/sellzy-theme.css')}}" />
    <link rel="stylesheet" href="{{asset('public/frontEnd/css/aminsuk-brand.css')}}" />
    
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            background:
                radial-gradient(circle at 12% 0, #d9e8ff 0, transparent 38%),
                radial-gradient(circle at 100% 15%, #d7f3ef 0, transparent 34%),
                linear-gradient(180deg, #f6f9ff 0, var(--app-bg) 100%);
            background-attachment: fixed;
        }

        #app {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            width: 100%;
        }

        .auth-shell {
            padding: 32px 0;
            flex: 1;
        }

        .app-footer {
            background: rgba(255, 255, 255, 0.92);
            border-top: 1px solid var(--app-border);
            padding: 2rem 0 1rem;
            width: 100%;
            margin-top: 0;
        }

        .app-login-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(360px, 460px);
            align-items: center;
            gap: 28px;
            min-height: auto;
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
            overflow: hidden;
        }

        .app-login-icon img {
            display: block;
            width: 70%;
            height: 70%;
            object-fit: contain;
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
            cursor: pointer;
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
            border: 0;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--app-primary) 0, #2f6cd7 100%);
            box-shadow: 0 10px 18px rgba(15, 76, 168, 0.22);
            font-weight: 700;
            cursor: pointer;
        }

        .app-login-submit:hover {
            background: linear-gradient(135deg, #0c3f8f 0, #235dbf 100%);
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
            font-family: "Outfit", "Segoe UI", sans-serif;
        }

        .form-control:focus {
            border-color: var(--app-primary);
            box-shadow: 0 0 0 .22rem rgba(15, 76, 168, 0.16);
        }

        .invalid-feedback {
            font-size: .84rem;
            font-weight: 600;
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

        /* Footer Styles */
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 1.5rem;
        }

        .footer-section h5 {
            color: #0d2f6f;
            font-weight: 700;
            margin-bottom: 1rem;
            font-size: 1rem;
        }

        .footer-section p {
            color: #64748b;
            font-size: .9rem;
            line-height: 1.6;
            margin-bottom: 0.5rem;
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-section ul li {
            margin-bottom: 0.5rem;
        }

        .footer-section ul li a {
            color: #1f4b9a;
            text-decoration: none;
            font-size: .9rem;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .footer-section ul li a:hover {
            color: var(--app-primary);
            text-decoration: underline;
        }

        .footer-bottom {
            border-top: 1px solid var(--app-border);
            padding-top: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .footer-bottom p {
            color: #64748b;
            font-size: .85rem;
            margin: 0;
        }

        .footer-bottom .version {
            color: #cbd5e1;
            font-size: .8rem;
        }

        @media (max-width: 1024px) {
            .app-login-grid {
                grid-template-columns: 1fr;
            }

            .app-login-panel {
                min-height: 300px;
                padding: 32px;
            }
        }

        @media (max-width: 767px) {
            .auth-shell {
                padding: 16px 0 24px;
            }

            .app-login-grid {
                grid-template-columns: 1fr;
                gap: 20px;
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

            .footer-content {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .footer-bottom {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body class="wc-front-shell">
    <div id="app">
        @if(request()->routeIs('login'))
            @include('components.admin-login-header', [
                'siteName' => $adminSiteName,
                'headerLogo' => $adminBrandLogo,
                'headerTitle' => $adminSiteName . ' Admin Portal',
                'headerSubtitle' => $adminHeaderSubtitle,
                'supportText' => $adminSupportText,
                'supportHref' => $adminSupportHref,
                'headerLinks' => $adminHeaderLinks
            ])
        @else
            @include('components.admin-header', [
                'siteName' => $adminSiteName,
                'headerLogo' => $adminBrandLogo,
                'supportText' => $adminSupportText,
                'supportHref' => $adminSupportHref,
                'supportLabel' => 'Need Support?',
                'showSupport' => true,
                'navLinks' => $adminHeaderLinks
            ])
        @endif

        <main class="auth-shell">
            @yield('content')
        </main>

        @include('components.admin-footer', [
            'siteName' => $adminSiteName,
            'footerLogo' => $adminBrandLogo,
            'showCompanyInfo' => true,
            'companyName' => $adminSiteName,
            'companyDescription' => $adminDescription,
            'showLinks' => true,
            'footerLinks' => $adminFooterLinks,
            'showContact' => true,
            'contactEmail' => $adminSupportEmail,
            'contactPhone' => $adminSupportPhone,
            'contactAddress' => $adminSupportAddress,
            'copyrightText' => $adminSiteName
        ])
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

        // Frontend menu toggle handlers
        document.addEventListener('DOMContentLoaded', function() {
            const mobileOpenButtons = document.querySelectorAll('[data-sellzy-mobile-open]');
            const mobileCloseButtons = document.querySelectorAll('[data-sellzy-mobile-close]');
            const drawer = document.getElementById('sellzyMobileDrawer');

            mobileOpenButtons.forEach(button => {
                button.addEventListener('click', () => {
                    if (drawer) {
                        drawer.setAttribute('aria-hidden', 'false');
                        drawer.style.display = 'block';
                    }
                });
            });

            mobileCloseButtons.forEach(button => {
                button.addEventListener('click', () => {
                    if (drawer) {
                        drawer.setAttribute('aria-hidden', 'true');
                        drawer.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>
