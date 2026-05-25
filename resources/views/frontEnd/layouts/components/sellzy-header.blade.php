@php
    $siteName = $generalsetting->name ?? config('app.name');
    $headerLogo = $generalsetting->dark_logo
        ?? $generalsetting->white_logo
        ?? null;
@endphp

<header class="sellzy-site-header">
    <div class="sellzy-mobile-promo">
        <span><i class="fa-solid fa-link"></i> Fashion Category</span>
        <mark>25% OFF</mark>
        <span>Today</span>
    </div>

    <div class="sellzy-topbar">
        <div class="container sellzy-topbar-inner">
            <div class="sellzy-topbar-left">
                <span><i class="fa-solid fa-headset"></i> Need Support ?</span>
                <span>Call Us <mark>{{ $contact->hotline ?? '(480) 555-0103' }}</mark></span>
            </div>
            <nav class="sellzy-topbar-links">
                <a href="{{ route('page', 'about-us') }}">About us</a>
                <a href="{{ Auth::guard('customer')->check() ? route('customer.account') : route('customer.login') }}">My Account</a>
                <a href="{{ route('compare.show') }}">My Wishlist</a>
                <a href="{{ route('customer.order_track') }}">Order Tracking</a>
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
                    <img src="{{ asset($headerLogo) }}" alt="{{ $siteName }}" />
                @else
                    <span class="sellzy-logo-mark">{{ Str::upper(Str::substr($siteName, 0, 1)) }}</span>
                    <span>{{ $siteName }}</span>
                @endif
            </a>

            <form class="sellzy-search" action="{{ route('search') }}">
                <input type="text" name="keyword" class="search_keyword search_click" placeholder="Search for the Items" autocomplete="off" />
                <button type="submit" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
                <div class="search_result"></div>
            </form>

            <div class="sellzy-actions">
                <a href="{{ Auth::guard('customer')->check() ? route('customer.account') : route('customer.login') }}" class="sellzy-action">
                    <span class="sellzy-action-icon"><i class="fa-regular fa-circle-user"></i></span>
                    <span><small>Account</small>log in</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </a>
                <a href="{{ route('compare.show') }}" class="sellzy-action sellzy-action-wishlist">
                    <span class="sellzy-action-icon"><i class="fa-regular fa-heart"></i></span>
                    <span><small>Wishlist</small></span>
                </a>
                @include('frontEnd.layouts.partials._sellzy_cart_action')
            </div>
        </div>
    </div>

    <div class="sellzy-nav-row">
        <div class="container sellzy-nav-inner">
            <div class="sellzy-category-menu">
                <button class="sellzy-category-button" type="button" aria-haspopup="true" aria-expanded="false">
                    <i class="fa-solid fa-table-cells-large"></i>
                    Explore All Categories
                    <i class="fa-solid fa-chevron-down"></i>
                </button>

                @unless(request()->routeIs('home'))
                    <div class="sellzy-category-dropdown">
                        @include('frontEnd.layouts.partials._sellzy_category_sidebar', ['categories' => $menucategories, 'limit' => 11])
                    </div>
                @endunless
            </div>
            <nav class="sellzy-main-nav">
                <a href="{{ route('home') }}" @class(['active' => request()->routeIs('home')])>Home <i class="fa-solid fa-chevron-down"></i></a>
                <a href="{{ route('page', 'about-us') }}">About Us</a>
                <a href="{{ route('shop') }}" @class(['active' => request()->routeIs('shop')])>Shop <i class="fa-solid fa-chevron-down"></i></a>
                <a href="{{ route('blog') }}" @class(['active' => request()->routeIs('blog')])>Blog <i class="fa-solid fa-chevron-down"></i></a>
                <a href="{{ route('contact') }}" @class(['active' => request()->routeIs('contact')])>Contact</a>
            </nav>
            <a href="tel:{{ $contact->hotline ?? '888-777-999' }}" class="sellzy-support">
                <span><i class="fa-solid fa-headset"></i></span>
                <small>24/7 Support</small>
                {{ $contact->hotline ?? '888-777-999' }}
            </a>
        </div>
    </div>

    <div class="sellzy-mobile-backdrop" data-sellzy-mobile-close></div>
    <aside class="sellzy-mobile-drawer" id="sellzyMobileDrawer" aria-hidden="true">
        <div class="sellzy-mobile-drawer-head">
            <a href="{{ route('home') }}" class="sellzy-mobile-drawer-logo" aria-label="{{ $siteName }}">
                @if(!empty($headerLogo))
                    <img src="{{ asset($headerLogo) }}" alt="{{ $siteName }}" />
                @else
                    <span class="sellzy-logo-mark">{{ Str::upper(Str::substr($siteName, 0, 1)) }}</span>
                    <strong>{{ $siteName }}</strong>
                @endif
            </a>
            <button class="sellzy-mobile-close" type="button" aria-label="Close menu" data-sellzy-mobile-close>
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form class="sellzy-mobile-drawer-search" action="{{ route('search') }}">
            <input type="text" name="keyword" class="search_keyword search_click" placeholder="Search for the Items" autocomplete="off" />
            <button type="submit" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
            <div class="search_result"></div>
        </form>

        <nav class="sellzy-mobile-drawer-nav">
            <a href="{{ route('home') }}" @class(['active' => request()->routeIs('home')])>Home</a>
            <a href="{{ route('page', 'about-us') }}">About Us</a>

            <div class="sellzy-mobile-accordion">
                <button type="button" data-sellzy-accordion>
                    Shop
                    <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="sellzy-mobile-panel">
                    <a href="{{ route('shop') }}">All Products</a>
                    <a href="{{ route('hotdeals') }}">Hot Deals</a>
                    <a href="{{ route('flashsales') }}">Flash Sales</a>
                </div>
            </div>

            <div class="sellzy-mobile-accordion">
                <button type="button" data-sellzy-accordion>
                    Categories
                    <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="sellzy-mobile-panel">
                    @forelse(($menucategories ?? collect()) as $category)
                        <a href="{{ route('category', $category->slug) }}">{{ $category->name }}</a>
                    @empty
                        <a href="{{ route('shop') }}">Shop All</a>
                    @endforelse
                </div>
            </div>

            <div class="sellzy-mobile-accordion">
                <button type="button" data-sellzy-accordion>
                    Pages
                    <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="sellzy-mobile-panel">
                    @foreach(($pages ?? collect())->take(6) as $page)
                        <a href="{{ route('page', ['slug' => $page->slug]) }}">{{ $page->name }}</a>
                    @endforeach
                </div>
            </div>

            <a href="{{ route('blog') }}">Blog</a>
            <a href="{{ route('contact') }}">Contact</a>
        </nav>

        <div class="sellzy-mobile-contact-card">
            <a href="{{ Auth::guard('customer')->check() ? route('customer.account') : route('customer.login') }}">
                <span><i class="fa-regular fa-circle-user"></i></span>
                log in / Sign Up
            </a>
            <a href="tel:{{ $contact->hotline ?? '888-777-999' }}">
                <span><i class="fa-solid fa-phone"></i></span>
                {{ $contact->hotline ?? '888-777-999' }}
            </a>
        </div>

        @if(($socialicons ?? collect())->isNotEmpty())
            <div class="sellzy-mobile-social">
                <h3>Follow us</h3>
                <ul>
                    @foreach($socialicons as $social)
                        <li>
                            <a href="{{ $social->link }}" aria-label="Social link" target="_blank" rel="noopener">
                                <i class="{{ $social->icon }}"></i>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </aside>
</header>

@push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const drawer = document.getElementById('sellzyMobileDrawer');
            const openButton = document.querySelector('[data-sellzy-mobile-open]');
            const closeButtons = document.querySelectorAll('[data-sellzy-mobile-close]');

            if (!drawer || !openButton) {
                return;
            }

            const openDrawer = function () {
                document.body.classList.add('sellzy-mobile-menu-open');
                drawer.setAttribute('aria-hidden', 'false');
                openButton.setAttribute('aria-expanded', 'true');
            };

            const closeDrawer = function () {
                document.body.classList.remove('sellzy-mobile-menu-open');
                drawer.setAttribute('aria-hidden', 'true');
                openButton.setAttribute('aria-expanded', 'false');
            };

            openButton.addEventListener('click', openDrawer);
            closeButtons.forEach(function (button) {
                button.addEventListener('click', closeDrawer);
            });

            drawer.querySelectorAll('[data-sellzy-accordion]').forEach(function (button) {
                button.addEventListener('click', function () {
                    button.closest('.sellzy-mobile-accordion')?.classList.toggle('is-open');
                });
            });

            // Header scroll effect
            const header = document.querySelector('.sellzy-site-header');
            if (header) {
                let isScrolled = false;
                let scrollTimeout;
                window.addEventListener('scroll', function () {
                    if (!scrollTimeout) {
                        scrollTimeout = setTimeout(() => {
                            if (window.scrollY > 200 && !isScrolled) {
                                header.classList.add('sellzy-scroll-menu-only');
                                isScrolled = true;
                            } else if (window.scrollY < 50 && isScrolled) {
                                header.classList.remove('sellzy-scroll-menu-only');
                                isScrolled = false;
                            }
                            scrollTimeout = null;
                        }, 100);
                    }
                }, { passive: true });
            }
        });
    </script>
@endpush
