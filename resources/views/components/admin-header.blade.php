@php
    $siteName = $siteName ?? $appName ?? data_get($generalsetting ?? null, 'name') ?? config('app.name', 'Laravel');
    $siteName = $siteName ?: config('app.name', 'Laravel');
    $headerLogo = $headerLogo ?? null;
    $headerLogo = $headerLogo ?: (data_get($generalsetting ?? null, 'dark_logo') ?: data_get($generalsetting ?? null, 'white_logo'));
    $supportLabel = $supportLabel ?? 'Need Support?';
    $supportText = ($supportText ?? null)
        ?: data_get($contact ?? null, 'hotline')
        ?: data_get($contact ?? null, 'phone')
        ?: data_get($contact ?? null, 'email')
        ?: 'Authorized Access Only';
    $supportHref = $supportHref ?? (data_get($contact ?? null, 'hotline')
        ? 'tel:' . preg_replace('/[^0-9+]/', '', data_get($contact ?? null, 'hotline'))
        : (data_get($contact ?? null, 'email') ? 'mailto:' . data_get($contact ?? null, 'email') : null));
    $navLinks = collect($navLinks ?? []);

    if ($navLinks->isEmpty()) {
        $navLinks = collect($pages ?? [])
            ->take(5)
            ->filter(function ($page) {
                return !empty($page->slug) && !empty($page->name);
            })
            ->map(function ($page) {
                return [
                    'url' => route('page', ['slug' => $page->slug]),
                    'label' => $page->name,
                ];
            });
    }

    if ($navLinks->isEmpty()) {
        $navLinks = collect([
            ['url' => route('home'), 'label' => 'Home'],
            ['url' => route('page', 'about-us'), 'label' => 'About Us'],
            ['url' => route('shop'), 'label' => 'Shop'],
            ['url' => route('blog'), 'label' => 'Blog'],
            ['url' => route('contact'), 'label' => 'Contact'],
        ]);
    }

    $topbarLinks = collect($topbarLinks ?? $navLinks->take(4)->values());

    $logoUrl = function ($path) {
        if (empty($path)) {
            return null;
        }

        return preg_match('~^(https?:)?//~', $path) ? $path : asset($path);
    };
@endphp

{{-- Admin Header - Same as Frontend Header --}}
<header class="sellzy-site-header">
    <div class="sellzy-mobile-promo">
        <span><i class="fa-solid fa-lock"></i> {{ $siteName }} Admin</span>
        <mark>Secure</mark>
        <span>Login</span>
    </div>

    <div class="sellzy-topbar">
        <div class="container sellzy-topbar-inner">
            <div class="sellzy-topbar-left">
                <span><i class="fa-solid fa-headset"></i> {{ $supportLabel }}</span>
                @if(!empty($supportHref))
                    <a href="{{ $supportHref }}">{{ $supportText }}</a>
                @else
                    <span>{{ $supportText }}</span>
                @endif
            </div>
            <nav class="sellzy-topbar-links">
                @foreach($topbarLinks as $link)
                    <a href="{{ $link['url'] }}">{{ $link['label'] }}</a>
                @endforeach
            </nav>
        </div>
    </div>

    <div class="sellzy-brand-row">
        <div class="container sellzy-brand-inner">
            <button class="sellzy-mobile-menu-button" type="button" aria-label="Open menu" aria-controls="sellzyMobileDrawer" aria-expanded="false" data-sellzy-mobile-open>
                <i class="fa-solid fa-bars"></i>
            </button>

            <a href="{{ route('home') }}" class="sellzy-logo" aria-label="{{ $siteName }}">
                @if(!empty($headerLogo))
                    <img src="{{ $logoUrl($headerLogo) }}" alt="{{ $siteName }}" />
                @else
                    <span class="sellzy-logo-mark">{{ Str::upper(Str::substr($siteName, 0, 1)) }}</span>
                    <span>{{ $siteName }}</span>
                @endif
            </a>

            <div class="sellzy-actions">
                @guest
                    <a href="{{ route('login') }}" class="sellzy-action">
                        <span class="sellzy-action-icon"><i class="fa-regular fa-circle-user"></i></span>
                        <span><small>Admin</small>login</span>
                        <i class="fa-solid fa-chevron-down"></i>
                    </a>
                @else
                    <a href="{{ route('admin.users.index') }}" class="sellzy-action">
                        <span class="sellzy-action-icon"><i class="fa-solid fa-sliders"></i></span>
                        <span><small>Admin</small>Dashboard</span>
                        <i class="fa-solid fa-chevron-down"></i>
                    </a>
                @endguest
            </div>
        </div>
    </div>

    <div class="sellzy-nav-row">
        <div class="container sellzy-nav-inner">
            <nav class="sellzy-main-nav">
                @foreach($navLinks as $link)
                    <a href="{{ $link['url'] }}" @class(['active' => request()->url() === $link['url']])>{{ $link['label'] }}</a>
                @endforeach
            </nav>
            @if($showSupport ?? true)
                <a href="{{ $supportHref ?? route('contact') }}" class="sellzy-support">
                    <span><i class="fa-solid fa-headset"></i></span>
                    <small>24/7 Support</small>
                    {{ $supportText }}
                </a>
            @endif
        </div>
    </div>

    <div class="sellzy-mobile-backdrop" data-sellzy-mobile-close></div>
    <aside class="sellzy-mobile-drawer" id="sellzyMobileDrawer" aria-hidden="true">
        <div class="sellzy-mobile-drawer-head">
            <a href="{{ route('home') }}" class="sellzy-mobile-drawer-logo" aria-label="{{ $siteName }}">
                @if(!empty($headerLogo))
                    <img src="{{ $logoUrl($headerLogo) }}" alt="{{ $siteName }}" />
                @else
                    <span class="sellzy-logo-mark">{{ Str::upper(Str::substr($siteName, 0, 1)) }}</span>
                    <strong>{{ $siteName }}</strong>
                @endif
            </a>
            <button class="sellzy-mobile-close" type="button" aria-label="Close menu" data-sellzy-mobile-close>
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <nav class="sellzy-mobile-drawer-nav">
            @foreach($navLinks as $link)
                <a href="{{ $link['url'] }}" @class(['active' => request()->url() === $link['url']])>{{ $link['label'] }}</a>
            @endforeach
        </nav>

        <div class="sellzy-mobile-contact-card">
            @guest
                <a href="{{ route('login') }}">
                    <span><i class="fa-regular fa-circle-user"></i></span>
                    Admin Login
                </a>
            @else
                <a href="{{ route('admin.users.index') }}">
                    <span><i class="fa-solid fa-sliders"></i></span>
                    Dashboard
                </a>
                <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <span><i class="fa-solid fa-sign-out-alt"></i></span>
                    Logout
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            @endguest
            <a href="{{ $supportHref ?? route('contact') }}">
                <span><i class="fa-solid fa-phone"></i></span>
                {{ $supportText }}
            </a>
        </div>
    </aside>
</header>
