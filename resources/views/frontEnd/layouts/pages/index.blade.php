@extends('frontEnd.layouts.master') @section('title', 'Home') @push('seo')
<meta name="app-url" content="" />
<meta name="robots" content="index, follow" />
<meta name="description" content="" />
<meta name="keywords" content="" />

<!-- Open Graph data -->
<meta property="og:title" content="" />
<meta property="og:type" content="website" />
<meta property="og:url" content="" />
<meta property="og:image" content="{{ !empty($generalsetting?->white_logo) ? asset($generalsetting->white_logo) : asset('public/uploads/default/no-image.png') }}" />
<meta property="og:description" content="" />
@endpush @push('css')
<link rel="stylesheet" href="{{ asset('public/frontEnd/css/owl.carousel.min.css') }}" />
<link rel="stylesheet" href="{{ asset('public/frontEnd/css/owl.theme.default.min.css') }}" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.5.2/animate.css" rel="stylesheet" />
<style>
    body.wc-front-shell,
    body.wc-front-shell #content,
    body.wc-front-shell section,
    body.wc-front-shell .homeproduct,
    body.wc-front-shell .sellzy-home-shell {
        background: #ffffff !important;
        background-image: none !important;
    }
</style>
@endpush @section('content')
@php $menucategories = $menucategories ?? collect(); @endphp

@include('frontEnd.layouts.components.sellzy-home-hero', ['sliders' => $sliders, 'menucategories' => $menucategories])

<!-- Original Slider Section (Hidden - Using new dynamic slider above) -->
<section class="slider-section" style="display: none;">
    <div class="container">
        <div class="row">
             
            <div class="col-md-3 d-none d-md-block mt-0">
                <div class="sidebar-menu">
                    <ul class="hideshow">
                        @foreach ($menucategories as $key => $category)
                            @php $hasSubcategories = $category->subcategories->count() > 0; @endphp
                            <li class="{{ $hasSubcategories ? 'has-children' : '' }}">
                                <a href="{{ route('category', $category->slug) }}">
                                   
                                    {{ $category->name }}
                                    @if($hasSubcategories)
                                    <i class="fa-solid fa-chevron-right"></i>
                                    @endif
                                </a>
                                @if($hasSubcategories)
                                <ul class="sidebar-submenu">
                                    @foreach ($category->subcategories as $key => $subcategory)
                                        @php $hasChildcategories = $subcategory->childcategories->count() > 0; @endphp
                                        <li class="{{ $hasChildcategories ? 'has-children' : '' }}">
                                            <a href="{{ route('subcategory', $subcategory->slug) }}">
                                                {{ $subcategory->subcategoryName }}
                                                @if($hasChildcategories)
                                                <i class="fa-solid fa-chevron-right"></i>
                                                @endif
                                            </a>
                                            @if($hasChildcategories)
                                            <ul class="sidebar-childmenu">
                                                @foreach ($subcategory->childcategories as $key => $childcat)
                                                    <li>
                                                        <a href="{{ route('products', $childcat->slug) }}">
                                                            {{ $childcat->childcategoryName }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            
            <div class="col-sm-9">
                <div class="home-slider-container">
                    <div class="main_slider owl-carousel">
                        @foreach ($sliders as $key => $value)
                            <div class="slider-item">
                                <img src="{{ asset($value->image) }}" alt="" />
                            </div>
                            <!-- slider item -->
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- slider end -->
@if($sliderbottomads && $sliderbottomads->count() > 0)
<section class="bottoads_area">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="bottoads_inner">
                    @foreach ($sliderbottomads as $key => $value)
                        <div class="ads_item">
                            <a href="{{ $value->link }}">
                                <img src="{{ asset($value->image) }}" alt="" />
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
@endif
@if($hitdealsbaner && $hitdealsbaner->count() > 0)
<section class="mobile-hide-banner">
    <div class="container">
        <div class="row">
            @foreach($hitdealsbaner as $hotads)
            <div class="col-md-12">
                <a href="{{$hotads->link}}?sold=show">
                    <img class="img-fluid w-100" src="{{ asset($hotads->image) }}"/>
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif


<section class="homeproduct">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="sec_title">
                    <h3 class="section-title-header">
                        <span class="section-title-name"> Hot Deal </span>
                        <div class="hot-deal-right">
                            <div class="offer_timer" id="simple_timer"></div>
                            <a href="{{ route('hotdeals') }}" class="view_more_btn">View More</a>
                        </div>
                    </h3>
                </div>
            </div>
            <div class="col-sm-12">
                @php
                    $hotDealCarouselItems = collect($hotdeal_top);

                    if ($hotDealCarouselItems->isNotEmpty()) {
                        $originalHotDealItems = $hotDealCarouselItems->values()->all();
                        
                        // Repeat items until we have a substantial number (at least 40 products / 20 columns)
                        // to ensure Owl Carousel always has enough items to loop smoothly.
                        while ($hotDealCarouselItems->count() < 40) {
                            foreach ($originalHotDealItems as $item) {
                                $hotDealCarouselItems->push($item);
                            }
                        }

                        // Ensure even number so the last column is never half-empty
                        if ($hotDealCarouselItems->count() % 2 !== 0) {
                            $hotDealCarouselItems->push($originalHotDealItems[0]);
                        }
                    }
                @endphp
                <div class="product_slider hot-deal-product-slider owl-carousel">
                    @foreach ($hotDealCarouselItems->chunk(2) as $chunkKey => $hotDealChunk)
                        <div class="hot-deal-card-stack">
                            @foreach ($hotDealChunk as $key => $value)
                                @include('frontEnd.layouts.partials._product_card', ['value' => $value, 'key' => ($chunkKey * 2) + $key])
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>


@if($homepageads && $homepageads->count() > 0)
<section class="mobile-hide-banner">
    <div class="container">
        <div class="row">
            @foreach($homepageads as $homeads)
            <div class="col-md-12">
                <a href="{{$homeads->link}}?sold=show">
                    <img class="img-fluid w-100" src="{{ asset($homeads->image) }}"/>
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif



@foreach ($homeproducts as $homecat)
    <section class="homeproduct">
        <div class="container">
            <div class="sec_title">
                <h3 class="section-title-header">
                    <span class="section-title-name">{{ $homecat->name }}</span>
                    <a href="{{ route('category', $homecat->slug) }}" class="view_more_btn">View More</a>
                </h3>
            </div>

            <div class="home-category-feature">
                @php
                    $homeCategoryBanner = $homecat->home_banner ?: ($homecat->image ?: 'public/uploads/default/no-image.png');
                @endphp
                <a href="{{ route('category', $homecat->slug) }}" class="home-category-banner">
                    <img src="{{ asset($homeCategoryBanner) }}" alt="{{ $homecat->name }}" />
                </a>

                <div class="home-category-products">
                    @forelse ($homecat->products as $key => $value)
                        @include('frontEnd.layouts.partials._product_card', ['value' => $value, 'key' => $key])
                    @empty
                        <div class="home-category-empty">
                            No products found.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
@endforeach


@if($homepageads2 && $homepageads2->count() > 0)
<section class="mobile-hide-banner">
    <div class="container">
        <div class="row">
            @foreach($homepageads2 as $homeads2)
            <div class="col-md-12">
                <a href="{{$homeads2->link}}?sold=show">
                    <img class="img-fluid w-100" src="{{ asset($homeads2->image) }}"/>
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif


@if($reviews->count()>0)
<section class="homeproduct">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="sec_title">
                    <h5 class="text-center text-light py-2" style="background-color:#1d2224">
                        Positive reviews from valued customers
                    </h5>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="customer-review owl-carousel">
                    @foreach ($reviews as $review)
                    <div class="border rounded">
                        <img class="img-fluid w-100" src="{{ asset($review->image) }}" />
                    </div>
                    @endforeach
                </div>
            </div>
            
        </div>
    </div>
</section>
@endif

@if(!empty($footerBlogPage))
<section class="sellzy-home-blog-cta">
    <div class="container">
        <div class="sellzy-blog-cta-card">
            <div>
                <span>From Our Blog</span>
                <h2>{{ $footerBlogPage->name }}</h2>
                <p>{{ Str::limit(strip_tags($footerBlogPage->description), 170) }}</p>
            </div>
            <a href="{{ route('blog') }}">
                Read Blog
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>
@endif


@if($footertopads && $footertopads->count() > 0)
<section class="footer_top_ads_area">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="footertop_ads_inner">
                    @foreach ($footertopads as $key => $value)
                        <div class="footertop_ads_item">
                            <a href="{{ $value->link }}">
                                <img src="{{ asset($value->image) }}" alt="" />
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
@endif

@endsection
@push('seo_content')
@if(!empty($generalsetting->meta_description))
<section class="homeproduct">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="meta_des">
                    {!! $generalsetting->meta_description !!}
                </div>
            </div>
        </div>
    </div>
</section>
@endif
@endpush
@push('script')
<script src="{{ asset('public/frontEnd/js/owl.carousel.min.js') }}"></script>
<script src="{{ asset('public/frontEnd/js/jquery.syotimer.min.js') }}"></script>

<script>
    $(document).ready(function() {
        $(".main_slider").owlCarousel({
            items: 1,
            loop: true,
            dots: false,
            autoplay: true,
            nav: true,
            autoplayHoverPause: false,
            margin: 0,
            mouseDrag: true,
            smartSpeed: 8000,
            autoplayTimeout: 3000,
            animateOut: "fadeOutDown",
            animateIn: "slideInDown",

            navText: ["<i class='fa-solid fa-angle-left'></i>",
                "<i class='fa-solid fa-angle-right'></i>"
            ],
        });
    });
</script>
<script>
    $(document).ready(function() {
        $(".hotdeals-slider").owlCarousel({
            margin: 15,
            loop: true,
            dots: false,
            autoplay: true,
            autoplayTimeout: 6000,
            autoplayHoverPause: true,
            responsiveClass: true,
            responsive: {
                0: {
                    items: 2,
                    nav: true,
                },
                600: {
                    items: 3,
                    nav: false,
                },
                1000: {
                    items: 5,
                    nav: true,
                    loop: false,
                },
            },
        });
    });
</script>
<script>
    $(document).ready(function() {
        $(".hot-deal-product-slider").owlCarousel({
            margin: 0,
            items: 5,
            loop: true,
            rewind: false,
            slideBy: 1,
            dots: false,
            autoplay: true,
            autoplayTimeout: 4000,
            autoplayHoverPause: true,
            smartSpeed: 1000,
            responsiveClass: true,
            responsive: {
                0: {
                    items: 2,
                    nav: false,
                    loop: true,
                },
                600: {
                    items: 3,
                    nav: false,
                    loop: true,
                },
                1000: {
                    items: 5,
                    nav: false,
                    loop: true,
                },
            },
        });
		$(".customer-review").owlCarousel({
            margin: 8,
            items: 6,
            loop: true,
            dots: false,
            autoplay: true,
            autoplayTimeout: 6000,
            autoplayHoverPause: true,
            responsiveClass: true,
            responsive: {
                0: {
                    items: 2,
                    nav: false,
                },
                600: {
                    items: 3,
                    nav: false,
                },
                1000: {
                    items: 5,
                    nav: false,
                },
            },
        });
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll("[data-sellzy-hero-slider]").forEach(function (slider) {
            const slides = Array.from(slider.querySelectorAll("[data-sellzy-hero-slide]"));
            const dots = Array.from(slider.querySelectorAll("[data-sellzy-hero-dot]"));
            const prev = slider.querySelector("[data-sellzy-hero-prev]");
            const next = slider.querySelector("[data-sellzy-hero-next]");

            if (slides.length <= 1) {
                if (prev) prev.style.display = "none";
                if (next) next.style.display = "none";
                return;
            }

            let current = 0;
            let timer = null;

            const showSlide = function (index) {
                current = (index + slides.length) % slides.length;
                slides.forEach((slide, slideIndex) => slide.classList.toggle("active", slideIndex === current));
                dots.forEach((dot, dotIndex) => dot.classList.toggle("active", dotIndex === current));
            };

            const start = function () {
                timer = window.setInterval(function () {
                    showSlide(current + 1);
                }, 5000);
            };

            const restart = function () {
                window.clearInterval(timer);
                start();
            };

            prev?.addEventListener("click", function () {
                showSlide(current - 1);
                restart();
            });

            next?.addEventListener("click", function () {
                showSlide(current + 1);
                restart();
            });

            dots.forEach(function (dot, index) {
                dot.addEventListener("click", function () {
                    showSlide(index);
                    restart();
                });
            });

            slider.addEventListener("mouseenter", function () {
                window.clearInterval(timer);
            });

            slider.addEventListener("mouseleave", start);
            start();
        });
    });
</script>
<script>
    $("#simple_timer").syotimer({
        date: new Date(2015, 0, 1),
        layout: "hms",
        doubleNumbers: false,
        effectType: "opacity",

        periodUnit: "d",
        periodic: true,
        periodInterval: 1,
    });
</script>
@endpush
