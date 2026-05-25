@php
    $heroSliders = ($sliders ?? collect())->filter(fn ($slider) => !empty($slider->image))->values();
    $heroLink = $heroSliders->first()?->link ?: route('shop');
    $heroSlides = $heroSliders->isNotEmpty()
        ? $heroSliders
        : collect([(object) ['image' => 'public/frontEnd/images/sellzy-hero-woman.png', 'link' => route('shop')]]);
@endphp

<section class="sellzy-home-shell">
    <div class="container sellzy-hero-grid">
        @include('frontEnd.layouts.partials._sellzy_category_sidebar', ['categories' => $menucategories, 'limit' => 11])

        <div class="sellzy-hero-panel" data-sellzy-hero-slider>
            <button type="button" class="sellzy-slider-arrow sellzy-prev" data-sellzy-hero-prev aria-label="Previous"><i class="fa-solid fa-chevron-left"></i></button>
            <div class="sellzy-hero-track">
                @foreach($heroSlides as $slide)
                    @php
                        $slideLink = $slide->link ?: route('shop');
                    @endphp
                    <div class="sellzy-hero-slide @if($loop->first) active @endif" data-sellzy-hero-slide>
                        <a href="{{ $slideLink }}" class="sellzy-hero-image-link">
                            <img src="{{ asset($slide->image) }}" alt="Hero banner" class="sellzy-hero-banner-image" />
                        </a>
                    </div>
                @endforeach
            </div>
            <button type="button" class="sellzy-slider-arrow sellzy-next" data-sellzy-hero-next aria-label="Next"><i class="fa-solid fa-chevron-right"></i></button>
            <div class="sellzy-slider-dots" aria-label="Hero slider navigation">
                @foreach($heroSlides as $slide)
                    <button type="button" class="@if($loop->first) active @endif" data-sellzy-hero-dot aria-label="Go to slide {{ $loop->iteration }}"></button>
                @endforeach
            </div>
        </div>
    </div>

    <div class="container">
        <div class="sellzy-trust-badges" aria-label="Store benefits">
            <div class="sellzy-trust-badge">
                <span><i class="fa-solid fa-award"></i></span>
                <strong>Best Quality</strong>
            </div>
            <div class="sellzy-trust-badge">
                <span><i class="fa-solid fa-map-location-dot"></i></span>
                <strong>All Bangladesh Delivery</strong>
            </div>
            <div class="sellzy-trust-badge">
                <span><i class="fa-solid fa-truck-fast"></i></span>
                <strong>Free Delivery</strong>
            </div>
            <div class="sellzy-trust-badge">
                <span><i class="fa-solid fa-shield-halved"></i></span>
                <strong>Trusted Online Shop</strong>
            </div>
            <div class="sellzy-trust-badge">
                <span><i class="fa-solid fa-headset"></i></span>
                <strong>Customer Support</strong>
            </div>
        </div>
    </div>
</section>
