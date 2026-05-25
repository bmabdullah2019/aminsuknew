@php
    $siteName = $siteName ?? $appName ?? data_get($generalsetting ?? null, 'name') ?? config('app.name', 'Laravel');
    $siteName = $siteName ?: config('app.name', 'Laravel');
    $headerLogo = $headerLogo ?? null;
    $headerLogo = $headerLogo ?: (data_get($generalsetting ?? null, 'dark_logo') ?: data_get($generalsetting ?? null, 'white_logo'));
    $cleanDisplayText = function ($value) {
        $text = trim(strip_tags((string) $value));

        return in_array(strtolower($text), ['sadf', 'asdf'], true) ? null : $text;
    };
    $headerTitle = $headerTitle ?? ($siteName . ' Admin Portal');
    $headerSubtitle = $cleanDisplayText($headerSubtitle ?? null)
        ?: $cleanDisplayText(data_get($generalsetting ?? null, 'description'))
        ?: $cleanDisplayText(data_get($generalsetting ?? null, 'meta_description'))
        ?: 'Secure management dashboard';
    $headerSubtitle = Str::limit(strip_tags($headerSubtitle), 72);
    $supportText = ($supportText ?? null)
        ?: data_get($contact ?? null, 'hotline')
        ?: data_get($contact ?? null, 'phone')
        ?: data_get($contact ?? null, 'email')
        ?: 'Authorized access only';
    $supportHref = $supportHref ?? (data_get($contact ?? null, 'hotline')
        ? 'tel:' . preg_replace('/[^0-9+]/', '', data_get($contact ?? null, 'hotline'))
        : (data_get($contact ?? null, 'email') ? 'mailto:' . data_get($contact ?? null, 'email') : null));
    $headerLinks = collect($headerLinks ?? $navLinks ?? []);

    if ($headerLinks->isEmpty()) {
        $headerLinks = collect($pages ?? [])
            ->take(3)
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

    if ($headerLinks->isEmpty()) {
        $headerLinks = collect([
            ['url' => route('home'), 'label' => 'Home'],
            ['url' => route('contact'), 'label' => 'Contact'],
        ]);
    }

    $logoUrl = function ($path) {
        if (empty($path)) {
            return null;
        }

        return preg_match('~^(https?:)?//~', $path) ? $path : asset($path);
    };
@endphp

<header class="app-login-header">
    <div class="container">
        <div class="app-login-header-inner">
            <a href="{{ route('home') }}" class="app-login-logo" aria-label="{{ $siteName }} Admin">
                @if(!empty($headerLogo))
                    <img src="{{ $logoUrl($headerLogo) }}" alt="{{ $siteName }} Logo" class="app-login-logo-img" />
                @else
                    <span class="app-login-logo-mark">{{ Str::upper(Str::substr($siteName, 0, 1)) }}</span>
                    <span class="app-login-logo-text">{{ $siteName }}</span>
                @endif
            </a>
            <div class="app-login-title">
                <h1>{{ $headerTitle }}</h1>
                <span>{{ $headerSubtitle }}</span>
            </div>
        </div>
    </div>
</header>

<style>
.app-login-header {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(15, 76, 168, 0.12);
    padding: 1rem 0;
    position: relative;
    z-index: 10;
}

.app-login-header-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

.app-login-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
}

.app-login-logo-img {
    height: 44px;
    width: auto;
    max-width: 140px;
    border-radius: 8px;
    flex-shrink: 0;
}

.app-login-logo-mark {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #0f4ca8 0%, #2f6cd7 100%);
    color: white;
    display: grid;
    place-items: center;
    font-size: 1.25rem;
    font-weight: 900;
    flex-shrink: 0;
}

.app-login-logo-text {
    color: #0d2f6f;
    font-size: 1.5rem;
    font-weight: 800;
}

.app-login-title h1 {
    margin: 0 0 2px;
    color: #0f172a;
    font-size: 1.4rem;
    font-weight: 800;
}

.app-login-title span {
    color: #64748b;
    font-size: 0.9rem;
    font-weight: 500;
}

@media (max-width: 768px) {
    .app-login-header-inner {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
        padding: 1rem;
    }
    
    .app-login-logo-text {
        font-size: 1.3rem;
    }
}
</style>
