<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>{{ $generalsetting->name }}</title>
        <link rel="shortcut icon" href="{{asset($generalsetting->favicon)}}" type="image/x-icon" />
        <!-- fot awesome -->
        <link rel="stylesheet" href="{{ asset('public/frontEnd/campaign/css') }}/all.css" />
        <!-- core css -->
        <link rel="stylesheet" href="{{ asset('public/frontEnd/campaign/css') }}/bootstrap.min.css" />
        <link rel="stylesheet" href="{{ asset('public/frontEnd/campaign/css') }}/animate.css" />
        <!-- owl carousel -->
        <link rel="stylesheet" href="{{ asset('public/frontEnd/campaign/css') }}/owl.theme.default.css" />
        <link rel="stylesheet" href="{{ asset('public/frontEnd/campaign/css') }}/owl.carousel.min.css" />
        <!-- owl carousel -->
        <link rel="stylesheet" href="{{ asset('public/frontEnd/campaign/css') }}/select2.min.css" />
        <!-- common css -->
        <link rel="stylesheet" href="{{ asset('public/frontEnd/campaign/css') }}/style.css" />
        <link rel="stylesheet" href="{{ asset('public/frontEnd/campaign/css') }}/responsive.css" />
        <link rel="stylesheet" href="{{ asset('public/frontEnd/css/aminsuk-brand.css') }}" />
        
        <meta name="app-url" content="{{route('campaign',$campaign_data->slug)}}" />
        <meta name="robots" content="index, follow" />
        <meta name="description" content="{{$campaign_data->description}}" />
        <meta name="keywords" content="{{ $campaign_data->slug }}" />
        
        <!-- Twitter Card data -->
        <meta name="twitter:card" content="product" />
        <meta name="twitter:site" content="{{$campaign_data->name}}" />
        <meta name="twitter:title" content="{{$campaign_data->name}}" />
        <meta name="twitter:description" content="{{ $campaign_data->description}}" />
        <meta name="twitter:creator" content="hellodinajpur.com" />
        <meta property="og:url" content="{{route('campaign',$campaign_data->slug)}}" />
        <meta name="twitter:image" content="{{asset($campaign_data->image_one)}}" />
        
        <!-- Open Graph data -->
        <meta property="og:title" content="{{$campaign_data->name}}" />
        <meta property="og:type" content="product" />
        <meta property="og:url" content="{{route('campaign',$campaign_data->slug)}}" />
        <meta property="og:image" content="{{asset($campaign_data->image_one)}}" />
        <meta property="og:description" content="{{ $campaign_data->description}}" />
        <meta property="og:site_name" content="{{$campaign_data->name}}" />
        @include('frontEnd.layouts.partials.tracking-head')
        <style>
            :root {
                --aminsuk-navy: #1b2c40;
                --aminsuk-navy-strong: #111f31;
                --aminsuk-teal: #008f88;
                --aminsuk-teal-dark: #006f6a;
                --aminsuk-aqua: #34b9dd;
                --aminsuk-blue: #2367ad;
                --aminsuk-surface: #ffffff;
                --aminsuk-surface-soft: #f3fbfd;
                --aminsuk-border: #cce8ee;
                --aminsuk-muted: #587084;
                --aminsuk-shadow: rgba(27, 44, 64, 0.16);
            }

            body {
                background: #f8fdfe !important;
                color: var(--aminsuk-navy) !important;
                font-family: 'Tiro Bangla', 'Hind Siliguri', sans-serif !important;
            }

            /* Custom Premium Fonts integration */
            @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Hind+Siliguri:wght@300;400;500;600;700&family=Tiro+Bangla&display=swap');

            /* Core Premium Overrides */
            .btn, button, input, select, textarea {
                font-family: 'Hind Siliguri', 'Outfit', sans-serif !important;
            }

            /* Floating/Sticky Mobile Checkout Bar */
            .mobile-floating-checkout {
                position: fixed;
                bottom: 16px;
                left: 16px;
                right: 16px;
                background: rgba(27, 44, 64, 0.95);
                backdrop-filter: blur(12px);
                border: 1px solid rgba(255, 255, 255, 0.15);
                border-radius: 16px;
                padding: 12px 20px;
                box-shadow: 0 10px 30px rgba(27, 44, 64, 0.35);
                z-index: 9999;
                display: flex;
                justify-content: space-between;
                align-items: center;
                transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
                transform: translateY(120px);
                opacity: 0;
                pointer-events: none;
            }

            .mobile-floating-checkout.active {
                transform: translateY(0);
                opacity: 1;
                pointer-events: auto;
            }

            .mobile-floating-checkout .price-info {
                color: #ffffff;
                text-align: left;
            }

            .mobile-floating-checkout .price-info span {
                font-size: 11px;
                text-transform: uppercase;
                letter-spacing: 1px;
                color: var(--aminsuk-aqua);
                display: block;
                font-weight: 600;
            }

            .mobile-floating-checkout .price-info strong {
                font-size: 20px;
                font-weight: 700;
            }

            .mobile-floating-checkout .checkout-btn {
                background: var(--aminsuk-teal);
                color: #ffffff !important;
                border: none;
                border-radius: 999px;
                padding: 8px 20px;
                font-size: 15px;
                font-weight: 700;
                display: flex;
                align-items: center;
                gap: 8px;
                box-shadow: 0 4px 12px rgba(0, 143, 136, 0.3);
                transition: all 0.3s ease;
            }

            .wc-front-shell .mobile-floating-checkout a.checkout-btn:hover,
            .wc-front-shell .mobile-floating-checkout .checkout-btn:hover,
            .mobile-floating-checkout .checkout-btn:hover {
                background: var(--aminsuk-aqua) !important;
                color: #ffffff !important;
                box-shadow: 0 4px 16px rgba(52, 185, 221, 0.4) !important;
                transform: translateY(-2px) !important;
            }

            /* Product checkbox cards custom checkmark */
            .product-card {
                position: relative;
                border: 2px solid var(--aminsuk-border) !important;
                border-radius: 12px !important;
                overflow: hidden;
                background: #ffffff !important;
                transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            }

            .product-card:hover {
                transform: translateY(-4px);
                border-color: var(--aminsuk-teal) !important;
                box-shadow: 0 10px 25px rgba(27, 44, 64, 0.1) !important;
            }

            .product-card.selected {
                border-color: var(--aminsuk-teal) !important;
                box-shadow: 0 12px 30px rgba(0, 143, 136, 0.15) !important;
                background: var(--aminsuk-surface-soft) !important;
            }

            .product-card.selected::after {
                content: "\f00c";
                font-family: "Font Awesome 6 Free";
                font-weight: 900;
                position: absolute;
                top: 8px;
                right: 8px;
                background: var(--aminsuk-teal);
                color: #ffffff;
                width: 22px;
                height: 22px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 11px;
                box-shadow: 0 2px 8px rgba(0, 143, 136, 0.3);
                z-index: 2;
                animation: scaleIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            }

            @keyframes scaleIn {
                from { transform: scale(0); }
                to { transform: scale(1); }
            }

            /* Premium Hero Gradient */
            .premium-hero-gradient {
                background: linear-gradient(135deg, var(--aminsuk-navy) 0%, var(--aminsuk-navy-strong) 100%) !important;
                position: relative;
                overflow: hidden;
                padding: 20px 0 !important;
            }

            .premium-hero-gradient,
            .premium-hero-gradient h4,
            .premium-hero-gradient h4 span,
            .premium-hero-gradient span {
                color: #ffffff !important;
            }

            .premium-hero-gradient::before {
                content: "";
                position: absolute;
                top: 0; left: 0; right: 0; bottom: 0;
                background: radial-gradient(circle at 80% 20%, rgba(0, 143, 136, 0.15) 0%, transparent 50%),
                            radial-gradient(circle at 20% 80%, rgba(52, 185, 221, 0.1) 0%, transparent 50%);
                pointer-events: none;
            }

            /* Glassmorphic Countdown Counters */
            .glass-counter-card {
                background: rgba(255, 255, 255, 0.03) !important;
                border: 1px solid rgba(255, 255, 255, 0.08) !important;
                backdrop-filter: blur(10px);
                border-radius: 16px !important;
                padding: 12px 8px !important;
                box-shadow: 0 8px 32px 0 rgba(17, 31, 49, 0.3) !important;
                transition: all 0.3s ease;
                text-align: center;
            }

            .glass-counter-card:hover {
                background: rgba(255, 255, 255, 0.06) !important;
                border-color: rgba(255, 255, 255, 0.15) !important;
                transform: translateY(-2px);
            }

            .glass-counter-card div {
                font-size: 26px !important;
                font-weight: 800 !important;
                color: #ffffff !important;
                font-family: 'Outfit', sans-serif !important;
                line-height: 1 !important;
                margin-bottom: 4px !important;
                text-shadow: 0 0 12px rgba(52, 185, 221, 0.5);
            }

            .glass-counter-card span {
                font-size: 11px !important;
                color: var(--aminsuk-aqua) !important;
                font-weight: 600 !important;
                text-transform: uppercase;
                letter-spacing: 1px;
                display: block;
            }

            /* Section Header Polish */
            .premium-section-title {
                text-align: center;
                font-size: 26px !important;
                font-weight: 800 !important;
                color: var(--aminsuk-navy) !important;
                padding: 18px 24px !important;
                background: #ffffff !important;
                border: 1px solid var(--aminsuk-border) !important;
                border-radius: 16px !important;
                box-shadow: 0 10px 30px var(--aminsuk-shadow) !important;
                position: relative;
                overflow: hidden;
            }

            /* SPECIFICITY RESOLUTION: If the header has inline background styles from summernote, force text to white */
            .wc-front-shell h2.premium-section-title[style*="background"],
            .wc-front-shell h2.premium-section-title[style*="background-color"],
            h2.premium-section-title[style*="background"] *,
            h2.premium-section-title[style*="background-color"] * {
                color: #ffffff !important;
            }

            .premium-section-title::after {
                content: "";
                position: absolute;
                bottom: 0;
                left: 50%;
                transform: translateX(-50%);
                width: 60px;
                height: 4px;
                background: var(--aminsuk-teal);
                border-radius: 999px;
            }

            /* Premium Accent Boxes for Features */
            .premium-feature-card {
                background: #ffffff !important;
                border: 1px solid var(--aminsuk-border) !important;
                border-left: 4px solid var(--aminsuk-teal) !important;
                border-radius: 12px !important;
                padding: 20px !important;
                box-shadow: 0 8px 24px rgba(27, 44, 64, 0.04) !important;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                justify-content: center;
                height: 100%;
            }

            .premium-feature-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 12px 30px rgba(27, 44, 64, 0.08) !important;
                border-left-color: var(--aminsuk-aqua) !important;
            }

            .premium-feature-card h3 {
                font-size: 20px !important;
                font-weight: 700 !important;
                color: var(--aminsuk-navy) !important;
                margin: 0 !important;
                text-align: center;
            }

            /* Description Section Overlap Fix Styles */
            .rules_sec {
                padding: 40px 0 !important;
                background: var(--wc-bg) !important;
            }

            .rules_sec .card {
                border: 1px solid var(--aminsuk-border) !important;
                border-radius: 20px !important;
                box-shadow: 0 15px 45px var(--aminsuk-shadow) !important;
                overflow: hidden;
            }

            .rules_sec .card-body {
                padding: 40px !important;
            }

            .rules_sec .card-body h2 {
                font-size: 28px !important;
                font-weight: 800 !important;
                color: var(--aminsuk-navy) !important;
                margin-bottom: 24px !important;
                position: relative;
                padding-bottom: 12px !important;
                border-bottom: 2px solid var(--aminsuk-border) !important;
            }

            .rules_sec .card-body h2::after {
                content: "";
                position: absolute;
                bottom: -2px;
                left: 0;
                width: 80px;
                height: 3px;
                background: var(--aminsuk-teal);
                border-radius: 999px;
            }

            .campaign-description-content {
                color: var(--aminsuk-navy) !important;
                font-size: 17px !important;
                line-height: 1.85 !important;
                overflow-wrap: anywhere !important;
                word-break: break-word !important;
                display: flow-root !important;
                clear: both !important;
            }

            .campaign-description-content p {
                margin-bottom: 18px !important;
                color: var(--aminsuk-navy) !important;
            }

            .campaign-description-content img {
                max-width: 100% !important;
                height: auto !important;
                border-radius: 12px !important;
                margin: 16px 0 !important;
                box-shadow: 0 8px 24px rgba(27, 44, 64, 0.08) !important;
                display: block;
            }

            .campaign-description-content iframe,
            .campaign-description-content video {
                max-width: 100% !important;
                border-radius: 12px !important;
                margin: 20px 0 !important;
                box-shadow: 0 12px 36px rgba(27, 44, 64, 0.15) !important;
            }

            /* Responsive Tables in Description */
            .campaign-description-content table {
                width: 100% !important;
                margin: 20px 0 !important;
                border-collapse: collapse !important;
                font-size: 15px !important;
            }

            .campaign-description-content th,
            .campaign-description-content td {
                padding: 12px 16px !important;
                border: 1px solid var(--aminsuk-border) !important;
                text-align: left !important;
            }

            .campaign-description-content th {
                background: var(--aminsuk-surface-soft) !important;
                font-weight: 700 !important;
                color: var(--aminsuk-navy) !important;
            }

            /* Call Section redesign */
            .premium-call-section {
                background: linear-gradient(135deg, var(--aminsuk-surface-soft) 0%, #ffffff 100%) !important;
                border-top: 1px solid var(--aminsuk-border) !important;
                border-bottom: 1px solid var(--aminsuk-border) !important;
                padding: 50px 0 !important;
            }

            .premium-call-title {
                font-size: 24px !important;
                font-weight: 800 !important;
                color: var(--aminsuk-navy) !important;
                text-align: center !important;
                background: #ffffff !important;
                border: 1px dashed var(--aminsuk-teal) !important;
                border-radius: 16px !important;
                padding: 20px 30px !important;
                box-shadow: 0 8px 24px rgba(0, 143, 136, 0.05) !important;
            }

            /* Call & WhatsApp Buttons */
            .action-btn-3d {
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 12px;
                padding: 16px 28px !important;
                font-size: 20px !important;
                font-weight: 700 !important;
                border-radius: 14px !important;
                transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
                box-shadow: 0 8px 24px rgba(27, 44, 64, 0.12) !important;
                color: #ffffff !important;
                overflow: hidden;
            }

            .wc-front-shell a.action-btn-3d:hover,
            .wc-front-shell .action-btn-3d:hover,
            .action-btn-3d:hover {
                transform: translateY(-4px) scale(1.02);
                box-shadow: 0 14px 32px rgba(27, 44, 64, 0.2) !important;
                color: #ffffff !important;
            }

            .action-btn-3d.btn-phone {
                background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
                border: 2px solid #fecaca !important;
            }

            .action-btn-3d.btn-whatsapp {
                background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
                border: 2px solid #a7f3d0 !important;
            }

            /* Order Now Call-To-Action Button */
            .cam_order_now {
                display: inline-flex !important;
                align-items: center;
                justify-content: center;
                gap: 12px;
                background: var(--aminsuk-teal) !important;
                color: #ffffff !important;
                font-size: 22px !important;
                font-weight: 800 !important;
                padding: 16px 40px !important;
                border-radius: 50px !important;
                box-shadow: 0 10px 25px rgba(0, 143, 136, 0.3) !important;
                transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) !important;
                border: 2px solid rgba(255, 255, 255, 0.2) !important;
                animation: heartbeat 2s ease-in-out infinite !important;
            }

            .wc-front-shell a.cam_order_now:hover,
            .wc-front-shell .cam_order_now:hover,
            .cam_order_now:hover {
                background: var(--aminsuk-navy) !important;
                box-shadow: 0 12px 30px rgba(27, 44, 64, 0.4) !important;
                transform: scale(1.05) !important;
                color: #ffffff !important;
            }

            @keyframes heartbeat {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.04); }
            }

            /* Video Section enhancements */
            .premium-video-wrapper {
                border: 6px solid var(--aminsuk-teal) !important;
                border-radius: 20px !important;
                overflow: hidden;
                box-shadow: 0 20px 50px rgba(0, 143, 136, 0.2) !important;
                background: #000;
            }

            h2.premium-video-header,
            .wc-front-shell h2.premium-video-header {
                background: var(--aminsuk-navy) !important;
                border: 1px solid var(--aminsuk-teal) !important;
                color: #ffffff !important;
                font-size: 24px !important;
                font-weight: 800 !important;
                padding: 14px 20px !important;
                border-radius: 16px !important;
                box-shadow: 0 8px 24px rgba(27, 44, 64, 0.1) !important;
            }

            /* Form checkout container */
            .form_inn {
                background: #ffffff !important;
                border: 1px solid var(--aminsuk-border) !important;
                border-top: 8px solid var(--aminsuk-teal) !important;
                border-radius: 20px !important;
                padding: 40px !important;
                box-shadow: 0 20px 50px var(--aminsuk-shadow) !important;
            }

            .campaign_offer {
                background: var(--aminsuk-teal) !important;
                border-radius: 12px !important;
                padding: 16px 20px !important;
                font-size: 24px !important;
                font-weight: 800 !important;
                color: #ffffff !important;
                box-shadow: 0 8px 20px rgba(0, 143, 136, 0.25) !important;
            }

            /* Form elements style */
            .form-control, select {
                background: var(--aminsuk-surface-soft) !important;
                border: 2px solid var(--aminsuk-border) !important;
                border-radius: 10px !important;
                padding: 12px 16px !important;
                color: var(--aminsuk-navy) !important;
                font-weight: 500 !important;
                transition: all 0.3s ease !important;
            }

            .form-control:focus, select:focus {
                border-color: var(--aminsuk-teal) !important;
                box-shadow: 0 0 0 4px rgba(0, 143, 136, 0.15) !important;
                background: #ffffff !important;
            }

            button.order_place {
                background: var(--aminsuk-teal) !important;
                border: none !important;
                border-radius: 10px !important;
                padding: 14px !important;
                font-size: 22px !important;
                font-weight: 800 !important;
                box-shadow: 0 8px 24px rgba(0, 143, 136, 0.25) !important;
                transition: all 0.3s ease !important;
            }

            button.order_place:hover {
                background: var(--aminsuk-navy) !important;
                box-shadow: 0 12px 30px rgba(27, 44, 64, 0.35) !important;
                transform: translateY(-2px);
            }

            .checkout-shipping label {
                font-weight: 700 !important;
                color: var(--aminsuk-navy) !important;
                margin-bottom: 8px !important;
                font-size: 15px !important;
            }

            /* Cart Table Premium styling */
            .cart_table {
                border-radius: 12px !important;
                overflow: hidden !important;
                border: 1px solid var(--aminsuk-border) !important;
            }

            .cart_table th {
                background: var(--aminsuk-navy) !important;
                color: #ffffff !important;
                font-weight: 700 !important;
                padding: 14px !important;
                border: none !important;
            }

            .cart_table td {
                padding: 14px !important;
                vertical-align: middle !important;
                border-color: var(--aminsuk-border) !important;
                font-weight: 600 !important;
            }

            .cart_qty .quantity {
                border: 2px solid var(--aminsuk-border) !important;
                border-radius: 8px !important;
                overflow: hidden !important;
                height: 38px !important;
                background: #ffffff !important;
            }

            .cart_qty .quantity button {
                width: 32px !important;
                height: 100% !important;
                background: var(--aminsuk-surface-soft) !important;
                color: var(--aminsuk-navy) !important;
                font-size: 16px !important;
                font-weight: 700 !important;
                transition: all 0.2s ease !important;
            }

            .cart_qty .quantity button:hover {
                background: var(--aminsuk-teal) !important;
                color: #ffffff !important;
            }

            .cart_qty .quantity input {
                font-weight: 700 !important;
                color: var(--aminsuk-navy) !important;
                border: none !important;
            }

            /* Review and slider container */
            .campro_inn, .rev_inn {
                background: #ffffff !important;
                border: 1px solid var(--aminsuk-border) !important;
                border-top: 6px solid var(--aminsuk-teal) !important;
                border-radius: 20px !important;
                box-shadow: 0 15px 40px var(--aminsuk-shadow) !important;
                padding: 30px !important;
            }

            .campro_head {
                background: var(--aminsuk-navy) !important;
                border-radius: 12px !important;
                box-shadow: 0 6px 20px rgba(27, 44, 64, 0.15) !important;
            }

            .wc-front-shell .campro_head h2,
            .campro_head h2 {
                color: #ffffff !important;
            }

            /* Fixes for Owl Carousel items height */
            .campro_img_item, .review_item {
                height: auto !important;
                border-radius: 12px !important;
                overflow: hidden !important;
                border: 1px solid var(--aminsuk-border) !important;
            }

            .campro_img_item img, .review_item img {
                width: 100% !important;
                height: auto !important;
                object-fit: cover !important;
            }
        </style>

{!! $generalsetting->header_code !!}
    </head>

    <body class="wc-front-shell">
        @include('frontEnd.layouts.partials.tracking-noscript')
         @php
            $subtotal = Cart::instance('shopping')->subtotal();
            $subtotal=str_replace(',','',$subtotal);
            $subtotal=str_replace('.00', '',$subtotal);
            $shipping = Session::get('shipping')?Session::get('shipping'):0;
        @endphp
        <section class="premium-hero-gradient shadow-lg">
            <div class="container py-4 py-md-5">
                <div class="row gy-4 align-items-center">
                    <div class="col-md-7">
                        <h4 class="text-light text-center text-md-start py-2 fw-bolder" style="font-size: 28px; line-height: 1.4; text-shadow: 0 4px 12px rgba(0,0,0,0.3); color: #ffffff !important;">
                            {!! $campaign_data->top_title_1 !!} 
                            <span style="color: #ffffff !important;"> {!! $campaign_data->top_title_2 !!}</span>
                        </h4>
                    </div>
                    <div class="col-md-5">
                        <div class="countdown-container">
                            <div class="countdown" id="countdown">
                                <div class="row g-2">
                                    <div class="col-3">
                                       <div class="glass-counter-card">
                                            <div id="days">0</div>
                                            <span>Days</span>
                                        </div> 
                                    </div>
                                    <div class="col-3">
                                        <div class="glass-counter-card">
                                            <div id="hours">0</div>
                                            <span>Hours</span>
                                        </div>                                        
                                    </div>
                                    <div class="col-3">
                                        <div class="glass-counter-card">
                                            <div id="minutes">0</div>
                                            <span>Mins</span>
                                        </div>                                    
                                    </div>
                                    <div class="col-3">
                                        <div class="glass-counter-card">
                                            <div id="seconds">0</div>
                                            <span>Secs</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @if($campaign_data->heading_1)
        <section class="py-4">
            <div class="container">
                <h2 class="premium-section-title">{!! $campaign_data->heading_1 !!}</h2>
            </div>
        </section>
        @endif

        <section class="py-3">
            <div class="container">
                <div class="row gy-4">
                    @if($campaign_data->image_one)
                    <div class="col-sm-6">
                        <img class="img-fluid rounded-4 shadow-sm" src="{{asset($campaign_data->image_one)}}" style="border: 1px solid var(--aminsuk-border);" >
                    </div>
                    @endif
                    @if($campaign_data->image_two)
                    <div class="col-sm-6">
                        <img class="img-fluid rounded-4 shadow-sm" src="{{asset($campaign_data->image_two)}}" style="border: 1px solid var(--aminsuk-border);" >
                    </div>
                    @endif
                </div>
            </div>
        </section>

        <section class="py-3">
            <div class="container">
                <div class="row g-3">
                    @if($campaign_data->feature_1)
                    <div class="col-sm-6">
                       <div class="premium-feature-card">
                            <h3>{!! $campaign_data->feature_1 !!}</h3>
                        </div>
                    </div>
                    @endif
                    @if($campaign_data->feature_2)
                    <div class="col-sm-6">
                       <div class="premium-feature-card">
                            <h3>{!! $campaign_data->feature_2 !!}</h3>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </section>

        @if($campaign_data->heading_2)
        <section class="py-3">
            <div class="container">
                <h2 class="premium-section-title">{!! $campaign_data->heading_2 !!}</h2>
            </div>
        </section>
        @endif

        @if($campaign_data->heading_3)
        <section class="py-3">
            <div class="container">
                <h2 class="premium-section-title">{!! $campaign_data->heading_3 !!}</h2>
            </div>
        </section>
        @endif

        @if($campaign_data->video != null)
        <section class="camp_video_sec py-4">
            <div class="container">
                <div class="row justify-content-center gy-4">
                    <div class="col-md-8">
                        <h2 class="premium-video-header text-center" style="color: #ffffff !important;">প্রডাক্টের "ভিডিও দেখুন"</h2>
                    </div>
                    <div class="col-md-8 col-sm-12">
                        <div class="premium-video-wrapper">
                            <iframe width="100%" height="450" 
                            src="https://www.youtube.com/embed/{{$campaign_data->video}}" 
                            title="{{$campaign_data->banner_title}}" frameborder="0" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen=""></iframe>
                        </div>
                    </div>
                    <div class="col-sm-12 text-center mt-4">
                        <div class="ord_btn pt-0">
                            <a href="#order_form" class="cam_order_now">
                                অর্ডার করতে ক্লিক করুন <i class="fa-solid fa-hand-point-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        @endif
        
        <section class="premium-call-section">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-10 col-lg-8 text-center">
                        <h2 class="premium-call-title">আমাদের থেকে বিস্তারিত জানতে কল করুন: <span style="color: var(--aminsuk-teal);">{{$contact->phone}}</span></h2>
                        <div class="row justify-content-center my-4 g-3">
                            <div class="col-sm-6">
                                <a href="tel:{{$contact->phone}}" class="action-btn-3d btn-phone">
                                    <i class="fa-solid fa-phone"></i> আমাদের কল করুন
                                </a>
                            </div>
                            <div class="col-sm-6">
                                <a href="https://wa.me/{{$contact->whatsapp}}" class="action-btn-3d btn-whatsapp">
                                    <i class="fa-brands fa-whatsapp"></i> হোয়াটসঅ্যাপ করুন
                                </a>
                            </div>
                        </div>
                        
                        @if($campaign_data->heading_4)
                        <h2 class="premium-call-title mt-4" style="border-style: solid;">{!! $campaign_data->heading_4 !!}</h2>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        @if(optional($campaign_data)->short_description && strlen($campaign_data->short_description) > 15 || 
    optional($campaign_data)->description && strlen($campaign_data->description) > 15)
        <section class="rules_sec">
            <div class="container">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="card">
                            <div class="card-body">
                                <h2>বিস্তারিত</h2>
                                <div class="campaign-description-content">
                                {!! $campaign_data->short_description !!}
                                <br>
                                <br>
                                {!!$campaign_data->description !!} 
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        @endif
        <section>
            <div class="container">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="campro_inn">
                            <div class="campro_head">
                                <h2 style="color: #ffffff !important;">{{$campaign_data->name}}</h2>
                            </div>

                            <div class="campro_img_slider owl-carousel">
                                @if($campaign_data->image_one)
                               <div class="campro_img_item">
                                   <img src="{{asset($campaign_data->image_one)}}" alt="">
                               </div> 
                               @endif
                                @if($campaign_data->image_two)
                               <div class="campro_img_item">
                                   <img src="{{asset($campaign_data->image_two)}}" alt="">
                               </div> 
                               @endif
                                @if($campaign_data->image_three)
                               <div class="campro_img_item">
                                   <img src="{{asset($campaign_data->image_three)}}" alt="">
                               </div>
                               @endif
                            </div>
                            <div class="col-sm-12">
                                <div class="ord_btn">
                                    <a href="#order_form" class="cam_order_now"> অর্ডার করতে ক্লিক করুন <i class="fa-solid fa-hand-point-right"></i> </a>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </section>


        <section>
            <div class="container">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="rev_inn">
                            
                            <h2 class="campaign_offer">{{$campaign_data->review}}</h2>
                            
                            <div class="review_slider owl-carousel">
                            @foreach($campaign_data->images as $key=>$value)
                            <div class="review_item">
                                <img src="{{asset($value->image)}}" alt="">
                            </div>
                            @endforeach
                           </div>
                            <div class="col-sm-12">
                                <div class="ord_btn">
                                    <a href="#order_form" class="cam_order_now"> অর্ডার করতে ক্লিক করুন <i class="fa-solid fa-hand-point-right"></i> </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    <section class="form_sec">
        <div class="container">
           <div class="row">
             <div class="col-sm-12">
                <div class="form_inn">
                    <div class="col-sm-12">
                        <div class="row">
                <div class="col-sm-12">
                    <h2 class="campaign_offer">অফারটি সীমিত সময়ের জন্য, তাই অফার শেষ হওয়ার আগেই অর্ডার করুন</h2>
                    @if($campaign_data->note)
                    <p class="my-1 text-center">
                        {!! $campaign_data->note !!}
                    </p>
                    @endif
                </div>
                
            </div>
            <div class="row order_by">
                <div class="col-lg-7 cust-order-1">
                    <div class="cart_details">
                        @php
                            $selectedProductIds = Cart::instance('shopping')->content()
                                ->pluck('id')
                                ->map(fn ($id) => (int) $id)
                                ->unique()
                                ->values()
                                ->all();
                        @endphp
                        @if($products->count()>1)
                        <div class="card mb-4 border-0">
                          <div class="card-header border-0" style="background: var(--aminsuk-navy); color: #ffffff;">
                                <h5 class="potro_font m-0 py-1" style="font-size: 18px; font-weight: 700;">একটি পণ্য সিলেক্ট করুন</h5>
                            </div>  
                             <div class="card-body p-3" style="background: var(--aminsuk-surface-soft); border: 1px solid var(--aminsuk-border); border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                                <div class="row g-3">
                                    @foreach($products as $product)
                                        @php
                                            $isSelected = in_array((int) $product->id, $selectedProductIds, true);
                                        @endphp
                                        <div class="col-md-4 col-6">
                                            <input type="checkbox" class="form-check-input campaign-product-input" name="products[]" id="product_{{ $product->id }}" value="{{ $product->id }}" {{ $isSelected ? 'checked' : '' }} style="display: none;" onchange="updateCart('{{ $product->id }}')">
                                            <label for="product_{{ $product->id }}" class="product-card d-block {{ $isSelected ? 'selected' : '' }}" style="cursor: pointer;">
                                                <img src="{{ asset($product->display_image) }}" class="card-img-top" alt="{{ $product->name }}" style="height: 120px; width: 100%; object-fit: cover;">
                                                <div class="p-2 text-center">
                                                    <div class="card-title fw-bold mb-1" style="font-size: 14px; color: var(--aminsuk-navy);">{{ Str::limit($product->name, 25) }}</div>
                                                    <div class="card-text text-primary fw-bold" style="font-size: 15px;">৳{{ $product->new_price }} <del class="text-muted fw-normal" style="font-size: 13px;">৳{{ $product->old_price }}</del></div>
                                                </div>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                             </div>
                        </div>
                        @endif
                        <div class="card">
                            <div class="card-header">
                                <h5 class="potro_font">পণ্যের বিবরণ </h5>
                            </div>
                            <div class="card-body cartlist  table-responsive">
                                <table class="cart_table table table-bordered table-striped text-center mb-0">
                                    <tbody>
                                        @foreach(Cart::instance('shopping')->content() as $value)
                                        <tr>
                                            <td class="text-left">
                                                @php
                                                    $cartImage = (string) ($value->options->image ?? 'public/frontEnd/images/no-image.jpg');
                                                    if (\Illuminate\Support\Str::startsWith($cartImage, 'storage/')) {
                                                        $cartImage = 'public/' . $cartImage;
                                                    } elseif (\Illuminate\Support\Str::startsWith($cartImage, 'uploads/')) {
                                                        $cartImage = 'public/' . $cartImage;
                                                    }
                                                @endphp
                                                 <a style="font-size: 14px;" href="{{route('product',$value->options->slug)}}"><img src="{{asset($cartImage)}}" height="30" width="30"> {{Str::limit($value->name,20)}}</a>
                                                @php
                                                    $product = App\Models\Product::find($value->id);
                                                @endphp
                                             
                                               @if($product && ($product->sizes->isNotEmpty() || $product->colors->isNotEmpty()))
                                                <div class="row g-1 mt-2">
                                                    <!-- Size Selector -->
                                                    @if($product->sizes->isNotEmpty())
                                                    <div class="col-6">
                                                        <select id="size-selector-{{ $value->rowId }}" class="form-select form-select-sm cart-size-selector" data-id="{{ $value->rowId }}">
                                                            <option>Select an option</option>
                                                            @foreach($product->sizes as $size)
                                                            <option value="{{ $size->sizeName }}" {{ $size->sizeName == $value->options->product_size ? 'selected' : '' }}>
                                                                {{ $size->sizeName }}
                                                            </option>
                                                            @endforeach
                                                        </select>
                                                        <label for="size-selector-{{ $value->rowId }}" class="form-label text-muted text-start" style="font-size: 0.875rem;">Size:
                                                        @if($value->options->product_size)
                                                          {{$value->options->product_size}}
                                                        @endif
                                                        </label>
                                                    </div>
                                                    @endif
                                                
                                                    <!-- Color Selector -->
                                                    @if($product->colors->isNotEmpty())
                                                    <div class="col-6">
                                                        <select id="color-selector-{{ $value->rowId }}" class="form-select form-select-sm cart-color-selector" data-id="{{ $value->rowId }}">
                                                            <option>Select an option</option>
                                                            @foreach($product->colors as $color)
                                                            <option value="{{ $color->colorName }}" {{ $color->colorName == $value->options->product_color ? 'selected' : '' }}>
                                                                {{ $color->colorName }}
                                                            </option>
                                                            @endforeach
                                                        </select>
                                                        <label for="color-selector-{{ $value->rowId }}" class="form-label text-muted text-start" style="font-size: 0.875rem;">Color:
                                                        @if($value->options->product_color)
                                                           {{ $value->options->product_color }}
                                                        @endif
                                                        </label>
                                                    </div>
                                                    @endif
                                                </div>
                                                @endif
                                            </td>
                                            <td width="15%" class="cart_qty">
                                                <div class="qty-cart vcart-qty">
                                                    <div class="quantity">
                                                        <button class="minus cart_decrement"  data-id="{{$value->rowId}}">-</button>
                                                        <input type="text" value="{{$value->qty}}" readonly />
                                                        <button class="plus  cart_increment" data-id="{{$value->rowId}}">+</button>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>৳{{$value->price*$value->qty}}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                         <tr>
                                          <th colspan="2" class="text-end px-4">মোট</th>
                                          <td>
                                           <span id="net_total"><span class="alinur">৳ </span><strong>{{$subtotal}}</strong></span>
                                          </td>
                                         </tr>
                                         <tr>
                                          <th colspan="2" class="text-end px-4">ডেলিভারি চার্জ</th>
                                          <td>
                                           <span id="cart_shipping_cost"><span class="alinur">৳ </span><strong>{{$shipping}}</strong></span>
                                          </td>
                                         </tr>
                                         <tr>
                                          <th colspan="2" class="text-end px-4">সর্বমোট</th>
                                          <td>
                                           <span id="grand_total"><span class="alinur">৳ </span><strong>{{$subtotal+$shipping}}</strong></span>
                                          </td>
                                         </tr>
                                        </tfoot>
                                </table>
     
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 cus-order-2">
                    <div class="checkout-shipping" id="order_form">
                        <form action="{{route('customer.ordersave')}}" method="POST" data-parsley-validate="">
                        @csrf
                        <input type="hidden" name="payment_method" value="cod">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="potro_font">আপনার ইনফরমেশন দিন  </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group mb-3">
                                            <label for="name">আপনার নাম লিখুন * </label>
                                            <input type="text" id="name" class="form-control @error('name') is-invalid @enderror" name="name" value="{{old('name')}}" placeholder="নাম" required>
                                            @error('name')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>
                                    <!-- col-end -->
                                    <div class="col-sm-12">
                                        <div class="form-group mb-3">
                                            <label for="phone">আপনার মোবাইল লিখুন *</label>
                                            <input type="text" minlength="11" maxlength="11" pattern="0[0-9]+" title="Please enter an 11-digit number starting with 0" id="phone" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{old('phone')}}" placeholder="+৮৮ বাদে ১১ সংখ্যা "  required>
                                            @error('phone')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>
                                    <!-- col-end -->
                                    <div class="col-sm-12">
                                        <div class="form-group mb-3">
                                            <label for="address">আপনার ঠিকানা লিখুন   *</label>
                                            <input type="text" id="address" class="form-control @error('address') is-invalid @enderror" placeholder="জেলা, থানা, গ্রাম " name="address" value="{{old('address')}}"  required>
                                            @error('address')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-sm-12">
                                        <div class="form-group mb-3">
                                            <label for="area">আপনার এরিয়া সিলেক্ট করুন  *</label>
                                            <select id="area" class="form-control @error('area') is-invalid @enderror" name="area"   required>
                                                @foreach($shippingcharge as $key=>$value)
                                                <option value="{{$value->id}}">{{$value->name}}</option>
                                                @endforeach
                                            </select>
                                            @error('area')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>
                                    <!-- col-end -->
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <button class="order_place" type="submit">অর্ডার কন্ফার্ম করুন </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </form>
                    </div>
                    @if($campaign_data->billing_details)
                    <p class="my-1 text-center">
                        {!! $campaign_data->billing_details !!}
                    </p>
                    @endif
                </div>
                <!-- col end -->
            </div>
                    </div>
                </div>
             </div>
            </div>
        </div>
    </section>

        <script src="{{ asset('public/frontEnd/campaign/js') }}/jquery-2.1.4.min.js"></script>
        <script src="{{ asset('public/frontEnd/campaign/js') }}/all.js"></script>
        <script src="{{ asset('public/frontEnd/campaign/js') }}/bootstrap.min.js"></script>
        <script src="{{ asset('public/frontEnd/campaign/js') }}/owl.carousel.min.js"></script>
        <script src="{{ asset('public/frontEnd/campaign/js') }}/select2.min.js"></script>
        <script src="{{ asset('public/frontEnd/campaign/js') }}/script.js"></script>
        <!-- bootstrap js -->
        <script>
            $(document).ready(function () {
                $(".owl-carousel").owlCarousel({
                    margin: 15,
                    loop: true,
                    dots: false,
                    autoplay: true,
                    autoplayTimeout: 6000,
                    autoplayHoverPause: true,
                    items: 1,
                    });
                $('.owl-nav').remove();
            });
        </script>
        <script>
            $(document).ready(function() {
                $('.select2').select2();

                function syncCampaignSelectionFromCart() {
                    var selectedProductIds = {};

                    $('.cartlist tr[data-product-id]').each(function() {
                        selectedProductIds[String($(this).data('product-id'))] = true;
                    });

                    $('.campaign-product-input').each(function() {
                        var productId = String($(this).val());
                        var isSelected = !!selectedProductIds[productId];
                        this.checked = isSelected;

                        $('label[for="' + this.id + '"]').toggleClass('selected', isSelected);
                    });
                }

                function updateFloatingTotal() {
                    var totalText = $('#grand_total strong').text() || $('#grand_total').text();
                    var totalVal = totalText.replace(/[^0-9]/g, '');
                    if (totalVal) {
                        $('#floatingGrandTotal').text('৳' + totalVal);
                    }
                }

                function updateShippingDropdown() {
                    var metadata = $('#cart-shipping-metadata');
                    if (!metadata.length) {
                        return;
                    }
                    
                    var isWeightBased = metadata.data('is-weight-based');
                    if (typeof isWeightBased === 'string') {
                        isWeightBased = (isWeightBased === 'true');
                    }
                    
                    var rates = metadata.data('rates');
                    if (!rates) {
                        return;
                    }
                    
                    var currentSelectVal = $('#area').val();
                    var areaSelect = $('#area');
                    
                    // Rebuild options
                    areaSelect.empty();
                    
                    var anySelected = false;
                    var firstAllowedId = null;
                    
                    $.each(rates, function(id, data) {
                        if (data.is_allowed) {
                            if (firstAllowedId === null) {
                                firstAllowedId = id;
                            }
                            
                            var label = data.name;
                            if (isWeightBased) {
                                var rateVal = parseFloat(data.rate);
                                var formattedRate = (rateVal % 1 === 0) ? parseInt(rateVal) : rateVal.toFixed(2);
                                label = data.name + " - ৳" + formattedRate;
                            }
                            
                            var option = $('<option></option>').val(id).text(label);
                            if (id == currentSelectVal) {
                                option.attr('selected', 'selected');
                                anySelected = true;
                            }
                            areaSelect.append(option);
                        }
                    });
                    
                    // Select default if nothing selected
                    if (!anySelected && firstAllowedId !== null) {
                        areaSelect.val(firstAllowedId);
                        // Trigger shipping charge update
                        refreshCampaignCart("{{route('shipping.charge')}}", { id: firstAllowedId });
                    }
                }

                function refreshCampaignCart(url, payload) {
                    $("#loading").show();
                    $.ajax({
                        type: "GET",
                        url: url,
                        data: $.extend({ context: 'campaign' }, payload || {}),
                        dataType: "html",
                        success: function(response) {
                            $('.cartlist').html(response);
                            syncCampaignSelectionFromCart();
                            updateFloatingTotal();
                            updateShippingDropdown();
                        },
                        complete: function() {
                            $("#loading").hide();
                        }
                    });
                }

                $("#area").on("change", function () {
                    var id = $(this).val();
                    if (!id) {
                        return;
                    }

                    refreshCampaignCart("{{route('shipping.charge')}}", { id: id });
                });

                $(document).on("click", ".cart_remove, .cart_increment, .cart_decrement", function () {
                    var id = $(this).data("id");
                    if (!id) {
                        return;
                    }

                    var url = "{{route('cart.remove')}}";
                    if ($(this).hasClass('cart_increment')) {
                        url = "{{route('cart.increment')}}";
                    } else if ($(this).hasClass('cart_decrement')) {
                        url = "{{route('cart.decrement')}}";
                    }

                    refreshCampaignCart(url, { id: id });
                });

                window.updateCart = function(productId) {
                    const selectedInput = document.getElementById('product_' + productId);
                    if (!selectedInput) {
                        return;
                    }

                    const isSelected = selectedInput.checked;
                    if (selectedInput.nextElementSibling) {
                        selectedInput.nextElementSibling.classList.toggle('selected', isSelected);
                    }

                    refreshCampaignCart("{{route('cart.changeProduct')}}", {
                        id: productId,
                        selected: isSelected ? 1 : 0
                    });
                };

                syncCampaignSelectionFromCart();
                updateShippingDropdown();
            });
        </script>
        <script>
            $('.review_slider').owlCarousel({   
                dots: false,
                arrow: false,
                autoplay: true,
                loop: true,
                margin: 10,
                smartSpeed: 1000,
                mouseDrag: true,
                touchDrag: true,
                items: 6,
                responsiveClass: true,
                responsive: {
                    300: {
                        items: 1,
                    },
                    480: {
                        items: 2,
                    },
                    768: {
                        items: 5,
                    },
                    1170: {
                        items: 5,
                    },
                }
            });
        </script>

        <script>
            $('.campro_img_slider').owlCarousel({   
                dots: false,
                arrow: false,
                autoplay: true,
                loop: true,
                margin: 10,
                smartSpeed: 1000,
                mouseDrag: true,
                touchDrag: true,
                items: 3,
                responsiveClass: true,
                responsive: {
                    300: {
                        items: 1,
                    },
                    480: {
                        items: 2,
                    },
                    768: {
                        items: 3,
                    },
                    1170: {
                        items: 3,
                    },
                }
            });
        </script>
        <script>
            // Set the deadline from the campaign data
            const deadline = new Date("{{ $campaign_data->deadline }}").getTime();
        
            // Update the countdown every 1 second
            const x = setInterval(function() {
                // Get current date and time
                const now = new Date().getTime();
        
                // Calculate the distance between now and the deadline
                const distance = deadline - now;
        
                // Time calculations for days, hours, minutes and seconds
                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
                // Display the result in the respective elements
                document.getElementById("days").innerHTML = days;
                document.getElementById("hours").innerHTML = hours;
                document.getElementById("minutes").innerHTML = minutes;
                document.getElementById("seconds").innerHTML = seconds;
        
                // If the countdown is over, write some text
                if (distance < 0) {
                    clearInterval(x);
                    document.getElementById("countdown").innerHTML = "EXPIRED";
                }
            }, 1000);

            $(document).on('change', '.cart-size-selector, .cart-color-selector', function() {
                var rowId = $(this).data('id');
                if (!rowId) {
                    return;
                }

                var selectedSize = $('.cart-size-selector[data-id="' + rowId + '"]').val() || '';
                var selectedColor = $('.cart-color-selector[data-id="' + rowId + '"]').val() || '';

                $("#loading").show();
                $.ajax({
                    type: "GET",
                    url: "{{ route('cart.update') }}",
                    data: {
                        id: rowId,
                        product_size: selectedSize,
                        product_color: selectedColor,
                        context: 'campaign'
                    },
                    dataType: "html",
                    success: function(response) {
                        $('.cartlist').html(response);
                    },
                    complete: function() {
                        $("#loading").hide();
                    }
                });
            });

            $(document).on('click', '.cam_order_now', function(event) {
                var target = document.getElementById('order_form');
                if (!target) {
                    return;
                }

                event.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });

            $(window).scroll(function() {
                var heroHeight = $('.premium-hero-gradient').outerHeight() || 400;
                if ($(this).scrollTop() > heroHeight) {
                    $('#mobileFloatingBar').addClass('active');
                } else {
                    $('#mobileFloatingBar').removeClass('active');
                }
            });
        </script>
        <script>
            window.dataLayer = window.dataLayer || [];
            dataLayer.push({
                'event': 'landingPageView',
                'url': '{{ request()->fullUrl() }}'  // Full product URL
            });
        </script>

        <!-- Mobile Floating Checkout Bar -->
        <div class="mobile-floating-checkout d-md-none" id="mobileFloatingBar">
            <div class="price-info">
                <span>সর্বমোট মূল্য</span>
                <strong id="floatingGrandTotal">৳{{ $subtotal + $shipping }}</strong>
            </div>
            <a href="#order_form" class="checkout-btn cam_order_now">
                <i class="fa-solid fa-cart-shopping"></i> অর্ডার করুন
            </a>
        </div>
    </body>
</html>
