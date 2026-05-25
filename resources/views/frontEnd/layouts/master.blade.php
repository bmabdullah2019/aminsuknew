<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <title>@yield('title') - {{ $generalsetting->name ?? 'Kenakatar' }}</title>
        <!-- App favicon -->
        <link rel="shortcut icon" href="{{ asset($generalsetting->favicon ?? '') }}" alt="Super Ecommerce Favicon" />
        <meta name="author" content="Super Ecommerce" />
        <link rel="canonical" href="" />
        @stack('seo')
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/bootstrap.min.css')}}" />
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/animate.css')}}" />
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/all.min.css')}}" />
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/owl.carousel.min.css')}}" />
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/owl.theme.default.min.css')}}" />
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/mobile-menu.css')}}" />
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/select2.min.css')}}" />
        <!-- toastr css -->
        <link rel="stylesheet" href="{{asset('public/backEnd/')}}/assets/css/toastr.min.css" />

        <link rel="stylesheet" href="{{asset('public/frontEnd/css/wsit-menu.css')}}" />
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/style.css')}}?v=1.1" />
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/responsive.css')}}" />
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/main.css')}}" />
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/worldclass-ui.css')}}?v=1.1" />
        <!-- Modern Design System -->
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/modern.css')}}" />
        <!-- Modern Homepage Styles -->
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/modern-homepage.css')}}" />
        <!-- Modern Product Listing Styles -->
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/modern-products.css')}}" />
        <!-- Modern Mobile & Navigation -->
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/modern-mobile.css')}}" />
        <!-- Modern Forms & Components -->
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/modern-components.css')}}" />
        <!-- Hero Section Styles -->
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/hero-section.css')}}" />
        <!-- Dynamic Hero Slider Styles -->
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/dynamic-hero-slider.css')}}" />
        <!-- Sellzy Theme Styles -->
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/sellzy-theme.css')}}" />
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/sellzy-home.css')}}" />
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/aminsuk-brand.css')}}" />

        <style>
            /* Force absolute minimal gap for filter items */
            .subcategory-filter-list {
                margin: 0 !important;
                padding: 0 !important;
                line-height: 1 !important;
                display: block !important;
            }
            .subcategory-filter-label {
                margin: 0 !important;
                padding: 0px 16px !important;
                min-height: 18px !important;
                display: flex !important;
                align-items: center !important;
                line-height: 1 !important;
            }
            .subcategory-filter-list p {
                margin: 0 !important;
                padding: 0 !important;
                line-height: 1 !important;
            }
            .attribute-filter-group {
                margin-top: 5px !important;
            }
            .attribute-filter-title {
                padding: 4px 14px !important;
                margin-bottom: 4px !important;
            }
        </style>
        @stack('css')

        <!-- GSAP Animation Library -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
        @if($generalsetting && !empty($generalsetting->facebook_verification))
            <meta name="facebook-domain-verification" content="{{ $generalsetting->facebook_verification }}" />
        @endif
        @include('frontEnd.layouts.partials.tracking-head')
    </head>
    <body class="gotop wc-front-shell">
        @php $subtotal = Cart::instance('shopping')->subtotal(); @endphp
        @php $menucategories = $menucategories ?? collect(); @endphp
        @include('frontEnd.layouts.components.sellzy-header')
        @if(false)
        <header>
        <div class="mobile-menu">
                <div class="mobile-menu-logo">
                    <div class="logo-image">
                        <a href="{{route('home')}}" class="wc-brand-lockup wc-brand-lockup-mobile">
                            <img src="{{asset($generalsetting->dark_logo ?? '')}}" alt="{{$generalsetting->name ?? ''}}" class="wc-brand-image" onerror="this.style.display='none'" />
                        </a>
                    </div>
                    <div class="mobile-menu-close">
                        <i class="fa fa-times"></i>
                    </div>
                </div>
                <ul class="first-nav">
                    @foreach($menucategories as $scategory)
                    <li class="parent-category">
                        <a href="{{url('category/'.$scategory->slug)}}" class="menu-category-name">
                            <img src="{{asset($scategory->image ?? '')}}" alt="" class="side_cat_img" />
                            {{$scategory->name}}
                        </a>
                        @if($scategory->subcategories->count() > 0)
                        <span class="menu-category-toggle">
                            <i class="fa fa-chevron-down"></i>
                        </span>
                        @endif
                        <ul class="second-nav" style="display: none;">
                            @foreach($scategory->subcategories as $subcategory)
                            <li class="parent-subcategory">
                                <a href="{{url('subcategory/'.$subcategory->slug)}}" class="menu-subcategory-name">{{$subcategory->subcategoryName}}</a>
                                @if($subcategory->childcategories->count() > 0)
                                <span class="menu-subcategory-toggle"><i class="fa fa-chevron-down"></i></span>
                                @endif
                                <ul class="third-nav" style="display: none;">
                                    @foreach($subcategory->childcategories as $childcat)
                                    <li class="childcategory"><a href="{{url('products/'.$childcat->slug)}}" class="menu-childcategory-name">{{$childcat->childcategoryName}}</a></li>
                                    @endforeach
                                </ul>
                            </li>
                            @endforeach
                        </ul>
                    </li>
                    @endforeach
                </ul>
            </div>

            <div class="mobile-search">
                <form action="{{route('search')}}">
                    <input type="text" placeholder="Search products..." value="" class="msearch_keyword msearch_click" name="keyword" />
                    <button><i data-feather="search"></i></button>
                </form>
                <div class="search_result"></div>
            </div>

            <div class="main-header">
                <!-- header to end -->
                <div class="logo-area">
                    <div class="container">
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="logo-header">
                                    <div class="main-logo">
                                        <a href="{{route('home')}}" class="wc-brand-lockup">
                                            <img src="{{asset($generalsetting->dark_logo ?? '')}}" alt="{{$generalsetting->name ?? ''}}" class="wc-brand-image" onerror="this.style.display='none'" />
                                        </a>
                                    </div>
                                    <div class="main-search">
                                        <form action="{{route('search')}}">
                                            <input type="text" placeholder="Search products..." class="search_keyword search_click" name="keyword" />
                                            <button>
                                                <i data-feather="search"></i>
                                            </button>
                                        </form>
                                        <div class="search_result"></div>
                                    </div>
                                    <div class="header-list-items">
                                        <ul>
                                            <li class="track_btn">
                                                <a href="{{route('customer.order_track')}}"> <i class="fa fa-truck"></i>Track Order</a>
                                            </li>

                                            <li class="compare-dialog">
                                                <a href="{{ route('compare.show') }}">
                                                    <p class="margin-shopping">
                                                        <i class="fa-solid fa-code-compare"></i>
                                                        <span class="compare-qty">{{ Cart::instance('compare')->count() }}</span>
                                                    </p>
                                                </a>
                                            </li>
                                           

                                            <li class="cart-dialog" id="cart-qty">
                                                <a href="{{route('cart.show')}}">
                                                    <p class="margin-shopping">
                                                        <i class="fa-solid fa-cart-shopping"></i>
                                                        <span class="cart-qty-count">{{Cart::instance('shopping')->count()}}</span>
                                                    </p>
                                                </a>
                                                <div class="cshort-summary">
                                                    <ul>
                                                        @foreach(Cart::instance('shopping')->content() as $key=>$value)
                                                        @php
                                                            $cartImage = (string) ($value->options->image ?? 'public/frontEnd/images/no-image.jpg');
                                                            if (\Illuminate\Support\Str::startsWith($cartImage, 'storage/')) {
                                                                $cartImage = 'public/' . $cartImage;
                                                            } elseif (\Illuminate\Support\Str::startsWith($cartImage, 'uploads/')) {
                                                                $cartImage = 'public/' . $cartImage;
                                                            }
                                                        @endphp
                                                        <li>
                                                            <a href=""><img src="{{asset($cartImage)}}" alt="" /></a>
                                                        </li>
                                                        <li><a href="">{{Str::limit($value->name, 30)}}</a></li>
                                                        <li>Qty: {{$value->qty}}</li>
                                                        <li>
                                                            <p>৳{{$value->price}}</p>
                                                            <button class="remove-cart cart_remove" data-id="{{$value->rowId}}"><i data-feather="x"></i></button>
                                                        </li>
                                                        @endforeach
                                                    </ul>
                                                    <p><strong>সর্বমোট : ৳{{$subtotal}}</strong></p>
                                                    <a href="{{route('customer.checkout')}}" class="go_cart"> অর্ডার করুন </a>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="menu-area">
                    <div class="container">
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="catagory_menu">
                                    <ul class="heder__category">
                                        <li class="all__category__list">
                                            <a href="#">ALL CATEGORIES <i class="fa-solid fa-list"></i></a>
                                            @if(!request()->routeIs('home'))
                                            <div class="sidebar-menu side__bar">
                                                <ul class="hideshow">
                                                    @foreach ($menucategories as $key => $category)
                                                        @php 
                                                            $hasSubcategories = $category->subcategories->count() > 0;
                                                            $isHidden = $key >= 10;
                                                        @endphp
                                                        <li class="{{ $hasSubcategories ? 'has-children' : '' }} {{ $isHidden ? 'sidebar-extra-item' : '' }}" style="{{ $isHidden ? 'display:none;' : '' }}">
                                                            <a href="{{ route('category', $category->slug) }}">
                                                                <img src="{{ asset($category->image) }}" alt="" />
                                                                {{ $category->name }}
                                                                @if($hasSubcategories)
                                                                <i class="fa-solid fa-chevron-right"></i>
                                                                @endif
                                                            </a>
                                                            @if($hasSubcategories)
                                                            <ul class="sidebar-submenu side__barsub">
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
                                                                        <ul class="sidebar-childmenu side__barchild">
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
                                                    @if($menucategories->count() > 10)

                                                        <li class="sidebar-toggle-li">
                                                            <a href="javascript:void(0)" id="sidebarMoreToggle">
                                                                <i class="fa-solid fa-plus-circle"></i>
                                                                <span id="sidebarToggleText">More Categories</span>
                                                                <i class="fa-solid fa-chevron-down" style="float: right; margin-top: 5px;"></i>
                                                            </a>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>

                                            @endif
                                        </li>

                                        <li><a href="{{route('home')}}">Home</a></li>
                                        <li class="wc-hot-deal-nav">
                                            <a href="{{route('hotdeals')}}">Hot Deals <i class="fa-solid fa-angle-down"></i></a>
                                            <div class="wc-hot-deal-dropdown" aria-label="Hot deal products">
                                                <div class="wc-hot-deal-dropdown-header">
                                                    <strong>Hot Deal Products</strong>
                                                    <a href="{{ route('hotdeals') }}">View All</a>
                                                </div>
                                                <ul class="wc-hot-deal-products">
                                                    @forelse(($hotDealMenuProducts ?? collect()) as $hotDealMenuProduct)
                                                        <li>
                                                            <a href="{{ route('product', $hotDealMenuProduct->slug) }}">
                                                                <span class="wc-hot-deal-thumb">
                                                                    <img src="{{ asset($hotDealMenuProduct->display_image) }}" alt="{{ $hotDealMenuProduct->name }}" />
                                                                </span>
                                                                <span class="wc-hot-deal-meta">
                                                                    <span class="wc-hot-deal-name">{{ Str::limit($hotDealMenuProduct->name, 55) }}</span>
                                                                    <span class="wc-hot-deal-price">
                                                                        @if((float) $hotDealMenuProduct->old_price > (float) $hotDealMenuProduct->new_price)
                                                                            <del>&#2547;{{ number_format((float) $hotDealMenuProduct->old_price, 2) }}</del>
                                                                        @endif
                                                                        <strong>&#2547;{{ number_format((float) $hotDealMenuProduct->new_price, 2) }}</strong>
                                                                    </span>
                                                                </span>
                                                            </a>
                                                        </li>
                                                    @empty
                                                        <li class="wc-hot-deal-empty">No hot deal products are available right now.</li>
                                                    @endforelse
                                                </ul>
                                            </div>
                                        </li>
                                        <li><a href="{{ route('blog') }}">Blog</a></li>
                                        <li class="contact__menu"><a href="{{route('contact')}}">Contact</a></li>
                                        <li class="right__menu__top for_order">
                                            @if(Auth::guard('customer')->user())
                                            <a href="{{route('customer.account')}}">
                                                <i class="fa-regular fa-user"></i>
                                                {{Str::limit(Auth::guard('customer')->user()->name,14)}}
                                            </a>
                                            @else
                                            <a href="{{route('customer.login')}}">
                                                <i class="fa-regular fa-user"></i>
                                                Login / Sign Up
                                            </a>
                                            @endif
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- main-header end -->
        </header>
        @endif
        <div id="content">
            @yield('content')
        </div>
            <!-- content end -->
        @stack('seo_content')
        @if(false)
        <footer>
            <div class="footer-top">
                <div class="container">
                    <div class="row">
                        <div class="col-sm-4 mb-3 mb-sm-0">
                            <div class="footer-about">
                                <a href="{{route('home')}}" class="footer-logo-link">
                                    <img src="{{asset($generalsetting->white_logo ?? '')}}" alt="" />
                                </a>
                                <p>{{$contact->address ?? ''}}</p>
                                <a href="tel:{{$contact->hotline ?? ''}}" class="footer-hotlint">{{$contact->hotline ?? ''}}</a>
                            </div>
                        </div>
                        <!-- col end -->
                        <div class="col-sm-3 mb-3 mb-sm-0 col-6">
                            <div class="footer-menu">
                                <ul>
                                    <li class="title"><a>Useful Link</a></li>
                                    <li>
                                        <a href="{{route('contact')}}">Contact Us</a>
                                    </li>
                                    @foreach($pages as $page)
                                    <li><a href="{{route('page',['slug'=>$page->slug])}}">{{$page->name}}</a></li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <!-- col end -->
                        <div class="col-sm-2 mb-3 mb-sm-0 col-6">
                            <div class="footer-menu">
                                <ul>
                                    <li class="title"><a>Link</a></li>
                                    @foreach($pagesright as $key=>$value)
                                    <li>
                                        <a href="{{route('page',['slug'=>$value->slug])}}">{{$value->name}}</a>
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>

                        <!-- col end -->
                        <div class="col-sm-3 mb-3 mb-sm-0">
                            <div class="footer-menu">
                                <ul>
                                    <li class="title stay_conn"><a>Stay Connected</a></li>
                                </ul>
                                <ul class="social_link">
                                    @foreach($socialicons as $value)
                                    <li class="social_list">
                                        <a class="mobile-social-link" href="{{$value->link}}"><i class="{{$value->icon}}"></i></a>
                                    </li>
                                    @endforeach
                                </ul>
                                <div class="d_app">
                                    <h2>Download App</h2>
                                    <a href="">
                                        <img src="{{asset('public/frontEnd/images/app-download.png')}}" alt="" />
                                    </a>
                                </div>
                            </div>
                        </div>
                        <!-- col end -->
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <div class="container">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="copyright">
                                <p>
                                    <span>Copyright &copy; {{ date('Y') }} {{$generalsetting->name ?? ''}}. All rights reserved</span>
                                    |
                                    <span>Website Designed by: <a href="https://www.source-bd.com">Source-Tech</a></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
        @endif
        @include('frontEnd.layouts.components.seo-footer-content')
        @include('frontEnd.layouts.components.sellzy-footer')


        <div class="footer_nav">
            <ul>
                <li>
                    <a class="toggle">
                        <span>
                            <i class="fa-solid fa-bars"></i>
                        </span>
                        <span>Category</span>
                    </a>
                </li>

                <li>
                    <a href="https://api.whatsapp.com/send?phone={{ $contact->whatsapp ?? '' }}&text=Hello">
                        <span>
                            <i class="fa-brands fa-whatsapp"></i>
                        </span>
                        <span>Message</span>
                    </a>
                </li>

                <li class="mobile_home">
                    <a href="{{route('home')}}" class="home-nav-btn">
                        <span><i class="fa-solid fa-home"></i></span> <span>Home</span>
                    </a>
                </li>

                <li>
                    <a href="{{route('cart.show')}}">
                        <span>
                            <i class="fa-solid fa-cart-shopping"></i>
                        </span>
                        <span>Cart (<b class="mobilecart-qty cart-qty-count">{{Cart::instance('shopping')->count()}}</b>)</span>
                    </a>
                </li>
                @if(Auth::guard('customer')->user())
                <li>
                    <a href="{{route('customer.account')}}">
                        <span>
                            <i class="fa-solid fa-user"></i>
                        </span>
                        <span>Account</span>
                    </a>
                </li>
                @else
                <li>
                    <a href="{{route('customer.login')}}">
                        <span>
                            <i class="fa-solid fa-user"></i>
                        </span>
                        <span>Login</span>
                    </a>
                </li>
                @endif
            </ul>
        </div>
        

        <div class="scrolltop">
            <div class="scroll">
                <i class="fa fa-angle-up"></i>
            </div>
        </div>

        <a href="https://api.whatsapp.com/send?phone={{ $contact->whatsapp ?? '' }}&text=Hello" class="float" target="_blank">
        <i class="fa-brands fa-whatsapp my-float"></i>
        </a>
        <!-- /. fixed sidebar -->

        <div id="custom-modal"></div>
        <div id="page-overlay"></div>
        <div id="loading"><div class="custom-loader"></div></div>

        <script src="{{asset('public/frontEnd/js/jquery-3.6.3.min.js')}}"></script>
        <script src="{{asset('public/frontEnd/js/bootstrap.min.js')}}"></script>
        <script src="{{asset('public/frontEnd/js/owl.carousel.min.js')}}"></script>
        <script src="{{asset('public/frontEnd/js/mobile-menu.js')}}"></script>
        <script src="{{asset('public/frontEnd/js/wsit-menu.js')}}"></script>
        <script src="{{asset('public/frontEnd/js/mobile-menu-init.js')}}"></script>
        <script src="{{asset('public/frontEnd/js/wow.min.js')}}"></script>
        <script>
            new WOW().init();
        </script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

        <!-- feather icon -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.0/feather.min.js"></script>
        <script>
            feather.replace();
        </script>
        <script src="{{asset('public/backEnd/')}}/assets/js/toastr.min.js"></script>
        {!! Toastr::message() !!} @stack('script')
        <script>
            $(document).on("click", ".quick_view", function () {
                var id = $(this).data("id");
                $("#loading").show();
                if (id) {
                    $.ajax({
                        type: "GET",
                        data: { id: id },
                        url: "{{route('quickview')}}",
                        success: function (data) {
                            if (data) {
                                $("#custom-modal").html(data);
                                $("#custom-modal").show();
                                $("#loading").hide();
                                $("#page-overlay").show();
                            }
                        },
                    });
                }
            });
        </script>
        <!-- quick view end -->
        <!-- cart js start -->
        <script>
            var csrfToken = $('meta[name="csrf-token"]').attr('content');

            $(document).on("click", ".addcartbutton", function () {
                if ($(this).hasClass("out-of-stock") || Number($(this).data("stock") || 1) <= 0) {
                    toastr.error('Stock Out', 'This product is currently out of stock');
                    return false;
                }

                var id = $(this).data("id");
                var checkout = $(this).data("checkout");
                var name = $(this).data("name") || "Product " + id;
                var price = parseFloat($(this).data("price")) || 0;
                var qty = 1;

                if (id) {
                    window.dataLayer = window.dataLayer || [];
                    dataLayer.push({ ecommerce: null });
                    dataLayer.push({
                        event: "add_to_cart",
                        ecommerce: {
                            currency: "BDT",
                            value: price * qty,
                            items: [{
                                item_id: id.toString(),
                                item_name: name,
                                price: price,
                                quantity: qty
                            }]
                        }
                    });
                    $.ajax({
                        cache: false,
                        type: "GET",
                        url: "{{url('add-to-cart')}}/" + id + "/" + qty,
                        success: function (data) {
                            toastr.success('Success', 'Product add to cart successfully');
                            refreshCartUiAfterMutation(data);
                        },
                        error: function (xhr) {
                            var message = xhr?.responseJSON?.message || 'Unable to add product to cart';
                            toastr.error('Stock Out', message);
                        }
                    });
                }
                if(checkout){
                    window.location.href = '{{route('customer.checkout')}}'; 
                }
            });
            $(document).on("submit", "#product-detail-form", function (event) {
                event.preventDefault();
                var form = $(this);
                var url = form.attr('action');
                var formData = form.serialize();
                var isOrderNow = $(document.activeElement).attr('name') === 'order_now';

                $.ajax({
                    type: "POST",
                    url: url,
                    data: formData,
                    success: function (response) {
                        if (isOrderNow) {
                            window.location.href = "{{ route('customer.checkout') }}";
                        } else {
                            toastr.success('Success', 'Product added to cart successfully');
                            refreshCartUiAfterMutation(response);
                        }
                    },
                    error: function (xhr) {
                        var message = xhr?.responseJSON?.message || 'Unable to add product to cart';
                        toastr.error('Error', message);
                    }
                });
            });

            $(document).on("click", ".cart_store", function (event) {
                event.preventDefault();
                if ($(this).hasClass("out-of-stock") || Number($(this).data("stock") || 1) <= 0) {
                    toastr.error('Stock Out', 'This product is currently out of stock');
                    return false;
                }

                var id = $(this).data("id");
                var qty = $(this).parent().find("input").val();
                if (id) {
                    $.ajax({
                        type: "GET",
                        data: { id: id, qty: qty ? qty : 1 },
                        url: "{{route('cart.store')}}",
                        success: function (data) {
                            toastr.success('Success', 'Product add to cart successfully');
                            refreshCartUiAfterMutation(data);
                        },
                        error: function (xhr) {
                            var message = xhr?.responseJSON?.message || 'Unable to add product to cart';
                            toastr.error('Stock Out', message);
                        }
                    });
                }
            });

            $(document).on("click", ".compare_store", function (event) {
                event.preventDefault();
                var id = parseInt($(this).data("id"), 10);
                if (!id) {
                    return;
                }

                $.ajax({
                    type: "POST",
                    url: "{{ route('compare.store') }}",
                    data: {
                        _token: csrfToken,
                        id: id
                    },
                    success: function (response) {
                        compare_count();
                        if (response && response.message) {
                            toastr.success('Success', response.message);
                        }
                    },
                    error: function (xhr) {
                        var message = xhr?.responseJSON?.message || 'Unable to add product to comparison list';
                        toastr.error('Compare', message);
                    }
                });
            });

            $(document).on("click", ".compare_remove", function (event) {
                event.preventDefault();
                var rowId = $(this).data("rowid");
                if (!rowId) {
                    return;
                }

                $.ajax({
                    type: "POST",
                    url: "{{ route('compare.remove') }}",
                    data: {
                        _token: csrfToken,
                        row_id: rowId
                    },
                    success: function (response) {
                        compare_count();
                        toastr.success('Success', response?.message || 'Compared product removed');
                        if ($(".compare-section").length) {
                            window.location.reload();
                        }
                    },
                    error: function (xhr) {
                        var message = xhr?.responseJSON?.message || 'Unable to remove compared product';
                        toastr.error('Compare', message);
                    }
                });
            });

            $(document).on("click", ".compare_clear", function (event) {
                event.preventDefault();

                $.ajax({
                    type: "POST",
                    url: "{{ route('compare.clear') }}",
                    data: {
                        _token: csrfToken
                    },
                    success: function (response) {
                        compare_count();
                        toastr.success('Success', response?.message || 'Comparison list cleared');
                        if ($(".compare-section").length) {
                            window.location.reload();
                        }
                    },
                    error: function (xhr) {
                        var message = xhr?.responseJSON?.message || 'Unable to clear comparison list';
                        toastr.error('Compare', message);
                    }
                });
            });
        </script>
        <script>
            // Use event delegation for cart remove button
            function refreshCartUiAfterMutation(data) {
                var isCartPage = $("#cartlist").length > 0 && $(".vcart-section").length > 0;

                // Cart page expects full layout; avoid injecting mini-cart partial into it.
                if (isCartPage) {
                    window.location.reload();
                    return;
                }

                if ($(".cartlist").length && data) {
                    $(".cartlist").html(data);
                }

                cart_count();
                mobile_cart();
                cart_summary();
            }
        </script>
        <script>
            $(document).on("click", ".cart_remove", function (event) {
                console.log("Cart remove clicked");
                event.preventDefault();
                var id = $(this).data("id");
                console.log("Item ID:", id);
                var $btn = $(this);
                if (id) {
                    console.log("Sending AJAX to remove item...");
                    $btn.css('opacity', '0.5').css('pointer-events', 'none');
                    $.ajax({
                        type: "GET",
                        data: { id: id },
                        url: "{{route('cart.remove')}}",
                        success: function (data) {
                            console.log("Cart removal success", data ? "Data received" : "No data");
                            toastr.success('Success', 'Item removed from cart');
                            refreshCartUiAfterMutation(data);
                        },
                        error: function(xhr) {
                            console.error("Cart removal error", xhr.status, xhr.responseText);
                            toastr.error('Error', 'Unable to remove item');
                            $btn.css('opacity', '1').css('pointer-events', 'auto');
                        }
                    });
                }
            });

            // Use event delegation for cart increment/decrement buttons
            $(document).on("click", ".cart_increment", function (event) {
                event.preventDefault();
                var id = $(this).data("id");
                if (id) {
                    $.ajax({
                        type: "GET",
                        data: { id: id },
                        url: "{{route('cart.increment')}}",
                        success: function (data) {
                            if (data) {
                                refreshCartUiAfterMutation(data);
                            }
                        },
                    });
                }
            });

            $(document).on("click", ".cart_decrement", function (event) {
                event.preventDefault();
                var id = $(this).data("id");
                if (id) {
                    $.ajax({
                        type: "GET",
                        data: { id: id },
                        url: "{{route('cart.decrement')}}",
                        success: function (data) {
                            if (data) {
                                refreshCartUiAfterMutation(data);
                            }
                        },
                    });
                }
            });
        </script>
        <script>
            function cart_count() {
                $.ajax({
                    type: "GET",
                    url: "{{route('cart.count')}}",
                    success: function (data) {
                        if (!data) return;

                        // Replace mini-cart block (if present)
                        if ($("#cart-qty").length) {
                            $("#cart-qty").html(data);
                        }

                        // Extract count from returned HTML robustly
                        var count = $(data).find("span.cart-qty-count").first().text();
                        if (count === "") {
                            count = $(data).find("span").first().text();
                        }

                        // Update both desktop + any other badge occurrences
                        if (count !== "") {
                            $(".cart-qty-count").text(count);
                        }
                    },
                });
            }
            function mobile_cart() {
                $.ajax({
                    type: "GET",
                    url: "{{route('mobile.cart.count')}}",
                    success: function (data) {
                        if (data) {
                            $(".mobilecart-qty").html(data);
                        }
                    },
                });
            }
            function cart_summary() {
                // Placeholder for cart summary update if needed
                if ($("#cart_summary").length > 0) {
                     // Potential AJAX for summary
                }
            }
        </script>
        <script>
            if (window.MmenuLight) {
                var menuRoot = document.querySelector("#menu");
                var menuTrigger = document.querySelector('a[href="#menu"]');

                if (menuRoot && menuTrigger) {
                    var menu = new MmenuLight(menuRoot, "all");
                    var navigator = menu.navigation({
                        selectedClass: "Selected",
                        slidingSubmenus: true,
                        title: "Category",
                    });

                    var drawer = menu.offcanvas({});

                    menuTrigger.addEventListener("click", function (evnt) {
                        evnt.preventDefault();
                        drawer.open();
                    });
                }
            }
        </script>

        <script>
            // document.addEventListener("DOMContentLoaded", function () {
            //     window.addEventListener("scroll", function () {
            //         if (window.scrollY > 200) {
            //             document.getElementById("navbar_top").classList.add("fixed-top");
            //         } else {
            //             document.getElementById("navbar_top").classList.remove("fixed-top");
            //             document.body.style.paddingTop = "0";
            //         }
            //     });
            // });
            /*=== Main Menu Fixed === */
            // document.addEventListener("DOMContentLoaded", function () {
            //     window.addEventListener("scroll", function () {
            //         if (window.scrollY > 0) {
            //             document.getElementById("m_navbar_top").classList.add("fixed-top");
            //             // add padding top to show content behind navbar
            //             navbar_height = document.querySelector(".navbar").offsetHeight;
            //             document.body.style.paddingTop = navbar_height + "px";
            //         } else {
            //             document.getElementById("m_navbar_top").classList.remove("fixed-top");
            //             // remove padding top from body
            //             document.body.style.paddingTop = "0";
            //         }
            //     });
            // });
            /*=== Main Menu Fixed === */

            document.addEventListener("DOMContentLoaded", function () {
                var navbarTop = document.getElementById("navbar_top");
                if (!navbarTop) {
                    return;
                }

                var threshold = 80;
                var toggleTopHeader = function () {
                    if (window.scrollY > threshold) {
                        navbarTop.classList.add("wc-top-header-hidden");
                    } else {
                        navbarTop.classList.remove("wc-top-header-hidden");
                    }
                };

                window.addEventListener("scroll", toggleTopHeader, { passive: true });
                toggleTopHeader();
            });

            $(window).scroll(function () {
                if ($(this).scrollTop() > 50) {
                    $(".scrolltop:hidden").stop(true, true).fadeIn();
                } else {
                    $(".scrolltop").stop(true, true).fadeOut();
                }
            });
            $(function () {
                $(".scroll").click(function () {
                    $("html,body").animate({ scrollTop: $(".gotop").offset().top }, "1000");
                    return false;
                });
            });
        </script>
        <script>
            $(".filter_btn").click(function(){
               $(".filter_sidebar").addClass('active');
               $("body").css("overflow-y", "hidden");
            })
            $(".filter_close").click(function(){
               $(".filter_sidebar").removeClass('active');
               $("body").css("overflow-y", "auto");
            })
        </script>
        <script>
            $(function () {
                $(document).on("click", "a[href='#']", function (event) {
                    event.preventDefault();
                });

                var currentPath = window.location.pathname.replace(/\/+$/, "") || "/";

                $(".heder__category > li > a[href], .footer_nav a[href]").each(function () {
                    var path = this.pathname ? this.pathname.replace(/\/+$/, "") || "/" : "";
                    if (path && path !== "#" && path === currentPath) {
                        $(this).addClass("wc-active-link");
                    }
                });

                $("form").on("submit", function () {
                    var $form = $(this);
                    if ($form.data("wc-submitting")) {
                        return;
                    }
                    $form.data("wc-submitting", true);

                    var $submit = $form.find("button[type='submit'], input[type='submit']").first();
                    if ($submit.length) {
                        $submit.addClass("wc-btn-loading").prop("disabled", true).attr("aria-disabled", "true");
                    }
                });
            });
        </script>
        <!-- Modern Animations with GSAP -->
        <script src="{{asset('public/frontEnd/js/modern-animations.js')}}"></script>
        <script>
            $(document).on("click", "#sidebarMoreToggle", function() {
                var isHidden = $(".sidebar-extra-item").first().is(":hidden");
                if (isHidden) {
                    $(".sidebar-extra-item").slideDown();
                    $("#sidebarToggleText").text("Show Less");
                    $(this).find(".fa-plus-circle").removeClass("fa-plus-circle").addClass("fa-minus-circle");
                    $(this).find(".fa-chevron-down").removeClass("fa-chevron-down").addClass("fa-chevron-up");
                } else {
                    $(".sidebar-extra-item").slideUp();
                    $("#sidebarToggleText").text("More Categories");
                    $(this).find(".fa-minus-circle").removeClass("fa-minus-circle").addClass("fa-plus-circle");
                    $(this).find(".fa-chevron-up").removeClass("fa-chevron-up").addClass("fa-chevron-down");
                }
            });
        </script>
        @include('frontEnd.layouts.partials.tracking-noscript')
    </body>
</html>


