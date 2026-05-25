@php
    $footerCategories = ($menucategories ?? collect())->take(6);
    $leftPages = ($pages ?? collect())->take(5);
    $rightPages = ($pagesright ?? collect())->take(6);
    $cleanDisplayText = function ($value) {
        $text = trim(strip_tags((string) $value));

        return in_array(strtolower($text), ['sadf', 'asdf'], true) ? null : $text;
    };
    $footerDescription = $cleanDisplayText(data_get($generalsetting ?? null, 'description'))
        ?: $cleanDisplayText(data_get($generalsetting ?? null, 'meta_description'))
        ?: $cleanDisplayText(data_get($contact ?? null, 'address'))
        ?: 'Stay connected with us for quality products, updates, and reliable support.';
    $siteName = data_get($generalsetting ?? null, 'name') ?? config('app.name');
    $footerLogo = data_get($generalsetting ?? null, 'white_logo')
        ?? data_get($generalsetting ?? null, 'dark_logo')
        ?? null;
@endphp

<footer class="sellzy-footer">
    <div class="sellzy-footer-newsletter">
        <p>Stay updated! Subscribe to our mailing list for news, updates, and exclusive offers.</p>
        <form action="{{ route('home') }}" method="get" class="sellzy-footer-subscribe">
            <i class="fa-regular fa-envelope"></i>
            <input type="email" name="email" placeholder="Enter your email" aria-label="Email address" />
            <button type="submit">Subscribe</button>
        </form>
    </div>

    <div class="container sellzy-footer-inner">
        <div class="sellzy-footer-about">
            <a href="{{ route('home') }}" class="sellzy-footer-logo">
                @if(!empty($footerLogo))
                    <img src="{{ asset($footerLogo) }}" alt="{{ $siteName }}" />
                @else
                    <span class="sellzy-footer-logo-mark">{{ Str::upper(Str::substr($siteName, 0, 1)) }}</span>
                    <strong>{{ $siteName }}</strong>
                @endif
            </a>
            <p>{{ Str::limit(strip_tags($footerDescription), 150) }}</p>

            @if(($socialicons ?? collect())->isNotEmpty())
                <ul class="sellzy-footer-social">
                    @foreach($socialicons as $social)
                        <li>
                            <a href="{{ $social->link }}" aria-label="Social link" target="_blank" rel="noopener">
                                <i class="{{ $social->icon }}"></i>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="sellzy-footer-apps">
                <h3>Download Our App:</h3>
                <img src="{{ asset('public/frontEnd/images/app-download.png') }}" alt="Download app" />
            </div>
        </div>

        <div class="sellzy-footer-menu">
            <h3>About</h3>
            <ul>
                <li><a href="{{ route('page', 'about-us') }}">About Us</a></li>
                @foreach($leftPages as $page)
                    <li><a href="{{ route('page', ['slug' => $page->slug]) }}">{{ $page->name }}</a></li>
                @endforeach
                <li><a href="{{ route('contact') }}">Contact Us</a></li>
            </ul>
        </div>

        <div class="sellzy-footer-menu">
            <h3>My Account</h3>
            <ul>
                <li><a href="{{ Auth::guard('customer')->check() ? route('customer.account') : route('customer.login') }}">Your Account</a></li>
                <li><a href="{{ route('customer.order_track') }}">Order Tracking</a></li>
                <li><a href="{{ route('compare.show') }}">Wishlist</a></li>
                @foreach($rightPages as $page)
                    <li><a href="{{ route('page', ['slug' => $page->slug]) }}">{{ $page->name }}</a></li>
                @endforeach
            </ul>
        </div>

        <div class="sellzy-footer-menu">
            <h3>Categories</h3>
            <ul>
                @forelse($footerCategories as $category)
                    <li><a href="{{ route('category', $category->slug) }}">{{ $category->name }}</a></li>
                @empty
                    <li><a href="{{ route('shop') }}">Shop</a></li>
                @endforelse
            </ul>
        </div>

        <div class="sellzy-footer-contact">
            <h3>Contact Information's</h3>
            <ul>
                @if(!empty($contact?->address))
                    <li><span><i class="fa-solid fa-location-dot"></i></span>{{ $contact->address }}</li>
                @endif
                @if(!empty($contact?->hotline))
                    <li><span><i class="fa-solid fa-phone"></i></span><a href="tel:{{ $contact->hotline }}">Call Us: {{ $contact->hotline }}</a></li>
                @endif
                @if(!empty($contact?->email))
                    <li><span><i class="fa-regular fa-envelope"></i></span><a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a></li>
                @endif
                @if(!empty($contact?->whatsapp))
                    <li><span><i class="fa-brands fa-whatsapp"></i></span><a href="https://api.whatsapp.com/send?phone={{ $contact->whatsapp }}">WhatsApp: {{ $contact->whatsapp }}</a></li>
                @endif
            </ul>
            <img src="{{ asset('public/frontEnd/images/onlinepay.png') }}" alt="Payment methods" class="sellzy-footer-payment" />
        </div>
    </div>

    <div class="container sellzy-footer-bottom">
        <p>{{ date('Y') }} Copyright By {{ $generalsetting->name ?? config('app.name') }}. All rights reserved.</p>
    </div>
</footer>
