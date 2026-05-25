@extends('backEnd.layouts.master')
@section('title','Campaign Landing Page')
@section('content')

<!--========== CSS Styles ===========-->
<style>
    .button-3d {
        position: relative;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .button-3d:hover {
        transform: scale(1.05);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }

    .button-animated-border {
        position: relative;
        overflow: hidden;
        border: 3px solid white;
        border-radius: 10px;
        transition: color 0.3s ease;
        animation: border-animation 3s linear infinite;
    }

    @keyframes border-animation {
        0% { border-color: white; }
        25% { border-color: yellow; }
        50% { border-color: white; }
        75% { border-color: yellow; }
        100% { border-color: white; }
    }

    .button-animated-border {
        will-change: border-color;
    }

    .animated-heading {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        will-change: opacity;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: .8; }
    }

    .product-card {
        transition: all 0.3s ease;
    }

    .product-card.selected {
        border-color: #28a745 !important;
        background-color: #f0f8f5;
    }

    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .rules_sec .card-body h2 {
        margin-bottom: 16px;
        line-height: 1.35;
        word-break: break-word;
    }

    .campaign-description-content {
        color: #222;
        line-height: 1.8;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .campaign-description-content p,
    .campaign-description-content li,
    .campaign-description-content div,
    .campaign-description-content blockquote {
        margin: 0 0 14px 0 !important;
        line-height: 1.8 !important;
    }

    .campaign-description-content span,
    .campaign-description-content strong,
    .campaign-description-content b,
    .campaign-description-content em,
    .campaign-description-content a {
        line-height: inherit !important;
    }

    .campaign-description-content ul,
    .campaign-description-content ol {
        margin: 0 0 14px 0 !important;
        padding-left: 22px;
    }

    .campaign-description-content img,
    .campaign-description-content iframe,
    .campaign-description-content video,
    .campaign-description-content table {
        max-width: 100%;
    }
</style>

{!! $generalsetting->header_code ?? '' !!}

<body>
    @include('frontEnd.layouts.partials.tracking-noscript')

    <!-- Campaign Header with Countdown -->
    @include('frontEnd.components.campaign.header', ['campaign' => $campaign_data])

    <!-- Campaign Headings -->
    @include('frontEnd.components.campaign.headings', ['campaign' => $campaign_data])

    <!-- Campaign Image Gallery -->
    @include('frontEnd.components.campaign.image-gallery', ['campaign' => $campaign_data])

    <!-- Campaign Video Section -->
    @include('frontEnd.components.campaign.video', ['campaign' => $campaign_data])

    <!-- Featured Section with Contact Info -->
    @include('frontEnd.components.campaign.featured-section', ['campaign' => $campaign_data, 'contact' => $contact])

    <!-- Detailed Description -->
    @include('frontEnd.components.campaign.description', ['campaign' => $campaign_data])

    <!-- Product & Reviews Section -->
    @include('frontEnd.components.campaign.product-reviews', ['campaign' => $campaign_data])

    <!-- Order Form Section -->
    @include('frontEnd.components.campaign.order-form', [
        'campaign' => $campaign_data,
        'products' => $products,
        'shippingcharge' => $shippingcharge
    ])

    <!--========== JavaScript Dependencies ===========-->
    <script src="{{ asset('public/frontEnd/campaign/js/jquery-2.1.4.min.js') }}"></script>
    <script src="{{ asset('public/frontEnd/campaign/js/all.js') }}"></script>
    <script src="{{ asset('public/frontEnd/campaign/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('public/frontEnd/campaign/js/owl.carousel.min.js') }}"></script>
    <script src="{{ asset('public/frontEnd/campaign/js/select2.min.js') }}"></script>
    <script src="{{ asset('public/frontEnd/campaign/js/script.js') }}"></script>

    <!--========== Carousel Initialization ===========-->
    <script>
        $(document).ready(function () {
            // Initialize carousels
            $(".owl-carousel").owlCarousel({
                margin: 15,
                loop: true,
                dots: false,
                autoplay: true,
                autoplayTimeout: 6000,
                autoplayHoverPause: true,
                items: 1,
                responsive: {
                    300: { items: 1 },
                    480: { items: 2 },
                    768: { items: 3 },
                    1170: { items: 3 }
                }
            });
            $('.owl-nav').remove();

            // Initialize select2
            $('.select2').select2();
        });
    </script>

    <!--========== Countdown Timer ===========-->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deadline = new Date("{{ $campaign_data->deadline }}").getTime();
            const daysEl = document.getElementById("days");
            const hoursEl = document.getElementById("hours");
            const minutesEl = document.getElementById("minutes");
            const secondsEl = document.getElementById("seconds");
            const countdownEl = document.getElementById("countdown");
            
            // Reduce update frequency to every 100ms instead of 1000ms to avoid jank
            setInterval(function() {
                const distance = deadline - new Date().getTime();

                if (distance < 0) {
                    if (countdownEl) countdownEl.innerHTML = "EXPIRED";
                } else {
                    // Batch DOM updates to reduce reflow/repaint
                    if (daysEl) daysEl.textContent = Math.floor(distance / (1000 * 60 * 60 * 24));
                    if (hoursEl) hoursEl.textContent = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    if (minutesEl) minutesEl.textContent = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    if (secondsEl) secondsEl.textContent = Math.floor((distance % (1000 * 60)) / 1000);
                }
            }, 100);
        });
    </script>

    <!--========== Cart Management ===========-->
    <script>
        function syncCampaignSelectionFromCart() {
            let selectedProductIds = {};

            $('.cartlist tr[data-product-id]').each(function() {
                selectedProductIds[String($(this).data('product-id'))] = true;
            });

            $('.campaign-product-input').each(function() {
                let productId = String($(this).val());
                let isSelected = !!selectedProductIds[productId];
                this.checked = isSelected;
                $('label[for="' + this.id + '"]').toggleClass('selected', isSelected);
            });
        }

        function updateCart(productId) {
            let $selectedInput = $('#product_' + productId);
            if (!$selectedInput.length) {
                return;
            }

            let isSelected = $selectedInput.is(':checked');
            $('label[for="product_' + productId + '"]').toggleClass('selected', isSelected);
            
            $("#loading").show();
            $.ajax({
                type: "GET",
                data: { id: productId, selected: isSelected ? 1 : 0, context: 'campaign' },
                url: "{{ route('cart.changeProduct') }}",
                success: function (data) {
                    $(".cartlist").html(data);
                    syncCampaignSelectionFromCart();
                    $("#loading").hide();
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            syncCampaignSelectionFromCart();
        });

        // Cart increment/decrement
        $(document).on('click', '.cart_increment, .cart_decrement, .cart_remove', function() {
            let url = '', data = {};
            
            if ($(this).hasClass('cart_increment')) {
                url = "{{ route('cart.increment') }}";
            } else if ($(this).hasClass('cart_decrement')) {
                url = "{{ route('cart.decrement') }}";
            } else {
                url = "{{ route('cart.remove') }}";
            }
            
            data.id = $(this).data("id");
            data.context = 'campaign';
            
            $("#loading").show();
            $.ajax({
                type: "GET",
                data: data,
                url: url,
                success: function (response) {
                    $(".cartlist").html(response);
                    syncCampaignSelectionFromCart();
                    $("#loading").hide();
                }
            });
        });

        // Size and color selection
        $(document).on('change', '.cart-size-selector, .cart-color-selector', function() {
            let rowId = $(this).data('id');
            let product_size = $('.cart-size-selector[data-id="' + rowId + '"]').val();
            let product_color = $('.cart-color-selector[data-id="' + rowId + '"]').val();
            
            $("#loading").show();
            $.ajax({
                type: "GET",
                data: { id: rowId, product_size: product_size, product_color: product_color, context: 'campaign' },
                url: "{{ route('cart.update') }}",
                success: function (response) {
                    $(".cartlist").html(response);
                    syncCampaignSelectionFromCart();
                    $("#loading").hide();
                }
            });
        });

        // Shipping area change
        $("#area").on("change", function () {
            let id = $(this).val();
            $.ajax({
                type: "GET",
                data: { id: id, context: 'campaign' },
                url: "{{ route('shipping.charge') }}",
                dataType: "html",
                success: function(response) {
                    $('.cartlist').html(response);
                    syncCampaignSelectionFromCart();
                }
            });
        });
    </script>

    {!! $generalsetting->footer_code ?? '' !!}
</body>
</html>
