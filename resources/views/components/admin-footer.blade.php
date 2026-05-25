{{-- Dynamic Admin Footer Component --}}
@php
    $siteName = $siteName ?? $companyName ?? data_get($generalsetting ?? null, 'name') ?? config('app.name', 'Laravel');
    $siteName = $siteName ?: config('app.name', 'Laravel');
    $footerLogo = $footerLogo ?? null;
    $footerLogo = $footerLogo ?: (data_get($generalsetting ?? null, 'dark_logo') ?: data_get($generalsetting ?? null, 'white_logo'));
    $cleanDisplayText = function ($value) {
        $text = trim(strip_tags((string) $value));

        return in_array(strtolower($text), ['sadf', 'asdf'], true) ? null : $text;
    };
    $companyDescription = $cleanDisplayText($companyDescription ?? null)
        ?: $cleanDisplayText(data_get($generalsetting ?? null, 'description'))
        ?: $cleanDisplayText(data_get($generalsetting ?? null, 'meta_description'))
        ?: $cleanDisplayText(data_get($contact ?? null, 'address'))
        ?: 'Your trusted ecommerce ERP solution.';
    $footerLinks = collect($footerLinks ?? []);

    if ($footerLinks->isEmpty()) {
        $footerLinks = collect($pages ?? [])
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

    if ($footerLinks->isEmpty()) {
        $footerLinks = collect([
            ['url' => route('home'), 'label' => 'Home'],
            ['url' => route('contact'), 'label' => 'Contact'],
        ]);
    }

    $contactEmail = $contactEmail ?? data_get($contact ?? null, 'email');
    $contactPhone = ($contactPhone ?? null) ?: data_get($contact ?? null, 'hotline') ?: data_get($contact ?? null, 'phone');
    $contactAddress = $contactAddress ?? data_get($contact ?? null, 'address');

    $footerLogoUrl = function ($path) {
        if (empty($path)) {
            return null;
        }

        return preg_match('~^(https?:)?//~', $path) ? $path : asset($path);
    };
@endphp

<footer class="app-footer">
    <div class="container">
        {{-- Dynamic Logo --}}
        <div class="footer-logo-section">
            <a href="{{ route('home') }}" class="footer-logo" aria-label="{{ $siteName }}">
                @if(!empty($footerLogo))
                    <img src="{{ $footerLogoUrl($footerLogo) }}" alt="{{ $siteName }} Logo" class="footer-logo-img" />
                @else
                    <span class="footer-logo-mark">{{ Str::upper(Str::substr($siteName, 0, 1)) }}</span>
                    <span class="footer-logo-text">{{ $siteName }}</span>
                @endif
            </a>
            @if($showCompanyInfo ?? true)
                <p class="footer-description">{{ Str::limit(strip_tags($companyDescription), 180) }}</p>
            @endif
        </div>

        <div class="footer-content">

            @if($showLinks ?? true)
                <div class="footer-section">
                    <h5>Links</h5>
                    <ul>
                        @foreach($footerLinks as $link)
                            <li><a href="{{ $link['url'] }}">{{ $link['label'] }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(($showContact ?? false) && (!empty($contactAddress) || !empty($contactPhone) || !empty($contactEmail)))
                <div class="footer-section">
                    <h5>Contact</h5>
                    @if(!empty($contactAddress))
                        <p>{{ $contactAddress }}</p>
                    @endif
                    @if(!empty($contactPhone))
                        <p>{{ $contactPhone }}</p>
                    @endif
                    @if(!empty($contactEmail))
                        <p>{{ $contactEmail }}</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="footer-bottom">
            <p>&copy; {{ date('Y') }} {{ $copyrightText ?? $siteName }}. All rights reserved.</p>
            @if($showVersion ?? false)
                <span class="version">{{ $versionText ?? 'v1.0.0' }}</span>
            @endif
        </div>
    </div>
</footer>

<style>
.footer-logo-section {
    text-align: center;
    margin-bottom: 2rem;
    padding: 1.5rem 0;
    border-bottom: 1px solid var(--app-border, #e2e8f0);
}

.footer-logo {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    margin-bottom: 0.5rem;
}

.footer-logo-img {
    height: 36px;
    width: auto;
    max-width: 120px;
    border-radius: 6px;
}

.footer-logo-mark {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: linear-gradient(135deg, #0f4ca8 0%, #2f6cd7 100%);
    color: white;
    display: grid;
    place-items: center;
    font-size: 1.1rem;
    font-weight: 900;
    flex-shrink: 0;
}

.footer-logo-text {
    color: #0d2f6f;
    font-size: 1.3rem;
    font-weight: 800;
}

.footer-description {
    color: #64748b;
    font-size: 0.95rem;
    margin: 0;
    max-width: 400px;
    margin: 0 auto;
}

.footer-bottom .version {
    color: #cbd5e1;
    font-size: 0.8rem;
}

@media (max-width: 768px) {
    .footer-logo-section {
        padding: 1rem 0;
    }
    
    .footer-logo-text {
        font-size: 1.2rem;
    }
}
</style>
