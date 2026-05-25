@extends('frontEnd.layouts.master')
@section('title', $details->name) 
@push('seo')
@php
    $seoImage = $details->display_image;
@endphp
<meta name="app-url" content="{{ route('product', $details->slug) }}" />
<meta name="robots" content="index, follow" />
<meta name="description" content="{{ $details->meta_description }}" />
<meta name="keywords" content="{{ $details->meta_keyword }}" />

<!-- Twitter Card data -->
<meta name="twitter:card" content="product" />
<meta name="twitter:site" content="{{ $details->name }}" />
<meta name="twitter:title" content="{{ $details->name }}" />

<meta name="twitter:description" content="{{ $details->meta_description }}" />
<meta name="twitter:creator" content="" />
<meta property="og:url" content="{{ route('product', $details->slug) }}" />
<meta name="twitter:image" content="{{ asset($seoImage) }}" />

<!-- Open Graph data -->
<meta property="og:title" content="{{ $details->name }}" />
<meta property="og:type" content="product" />
<meta property="og:url" content="{{ route('product', $details->slug) }}" />
<meta property="og:image" content="{{ asset($seoImage) }}" />
<meta property="og:description" content="{{ $details->meta_description }}" />
<meta property="og:site_name" content="{{ $details->name }}" />
@endpush

@push('css')
<link rel="stylesheet" href="{{ asset('public/frontEnd/css/zoomsl.css') }}">
<style>
    .main-details-page .details_slider,
    .main-details-page .details_slider .owl-stage-outer,
    .main-details-page .details_slider .owl-stage,
    .main-details-page .details_slider .owl-item,
    .main-details-page .dimage_item {
        height: clamp(360px, 52vw, 560px) !important;
    }

    .main-details-page .dimage_item {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        background: #fff !important;
    }

    .main-details-page .dimage_item img,
    .main-details-page .dimage_item img.block__pic {
        width: 100% !important;
        height: 100% !important;
        max-height: none !important;
        object-fit: contain !important;
        object-position: center center !important;
        background: #fff !important;
    }

    .pro_details_area .description-content,
    .pro_details_area #description .description-body {
        clear: both;
        display: flow-root;
        overflow: hidden;
    }

    .pro_details_area #writeReview {
        clear: both;
        display: flow-root;
        position: relative;
        z-index: 1;
    }

    .pro_details_area #description .description-body img,
    .pro_details_area #description .description-body iframe,
    .pro_details_area #description .description-body video,
    .pro_details_area #description .description-body table {
        max-width: 100% !important;
    }

    .inline-review-form {
        display: none;
        margin: 16px 0 22px;
        padding: 16px;
        border: 1px solid var(--aminsuk-border, #cce8ee);
        border-radius: 8px;
        background: var(--aminsuk-surface-soft, #f3fbfd);
        box-shadow: 0 10px 24px var(--aminsuk-shadow, rgba(27, 44, 64, 0.16));
    }

    .inline-review-form.is-visible {
        display: block;
    }

    .inline-review-form textarea#message-text {
        border-color: var(--aminsuk-teal, #008f88);
        max-width: 100%;
    }

    .inline-review-form textarea#message-text:focus {
        border-color: var(--aminsuk-teal-dark, #006f6a);
        box-shadow: var(--wc-ring, 0 0 0 0.22rem rgba(0, 143, 136, 0.18));
    }

    .inline-review-form .rating > label,
    .inline-review-form .rating > label.active:before,
    .inline-review-form .rating > label.active ~ label:before,
    .inline-review-form .rating > label:hover:before,
    .inline-review-form .rating > label:hover ~ label:before {
        color: var(--aminsuk-teal, #008f88);
    }

    .inline-review-form .details-review-button,
    .inline-review-form .customer-login-redirect {
        background: var(--aminsuk-teal, #008f88) !important;
        border: 1px solid var(--aminsuk-teal, #008f88) !important;
        color: #fff !important;
    }

    .inline-review-form .details-review-button:hover,
    .inline-review-form .customer-login-redirect:hover {
        background: var(--aminsuk-navy, #1b2c40) !important;
        border-color: var(--aminsuk-navy, #1b2c40) !important;
    }

    @media (max-width: 767px) {
        .main-details-page .details_slider,
        .main-details-page .details_slider .owl-stage-outer,
        .main-details-page .details_slider .owl-stage,
        .main-details-page .details_slider .owl-item,
        .main-details-page .dimage_item {
            height: clamp(300px, 88vw, 430px) !important;
        }
    }
</style>
@endpush

@section('content')
<div class="homeproduct main-details-page">
    <div class="container">
        <section class="product-section">
            <div class="row">
                <div class="col-sm-6 position-relative">
                                @if($details->old_price)
                                <div class="product-details-discount-badge">
                                    <div class="sale-badge">
                                        <div class="sale-badge-inner">
                                            <div class="sale-badge-box">
                                                <span class="sale-badge-text">
                                                    <p> @php $discount=(((($details->old_price)-($details->new_price))*100) / ($details->old_price)) @endphp {{ number_format($discount, 0) }}%</p>
                                                    ছাড়
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                <div class="details-gallery">
                                    <div class="indicator_thumb">
                                        @forelse (($displayGallery ?? collect()) as $key => $galleryImage)
                                            <button type="button" class="indicator-item @if ($key === 0) active @endif" data-id="{{ $key }}">
                                                <img
                                                    src="{{ asset($galleryImage['src']) }}"
                                                    alt="{{ $details->name }} thumbnail {{ $key + 1 }}"
                                                    data-variant-id="{{ $galleryImage['variant_id'] ?? '' }}"
                                                />
                                            </button>
                                        @empty
                                            <button type="button" class="indicator-item active" data-id="0">
                                                <img src="{{ asset($details->display_image) }}" alt="{{ $details->name }} thumbnail" />
                                            </button>
                                        @endforelse
                                    </div>

                                    <div class="details_slider owl-carousel">
                                        @forelse (($displayGallery ?? collect()) as $galleryImage)
                                            <div class="dimage_item">
                                                <img
                                                    src="{{ asset($galleryImage['src']) }}"
                                                    alt="{{ $details->name }}"
                                                    class="block__pic"
                                                    data-variant-id="{{ $galleryImage['variant_id'] ?? '' }}"
                                                />
                                            </div>
                                        @empty
                                            <div class="dimage_item">
                                                <img src="{{ asset($details->display_image) }}" alt="{{ $details->name }}" class="block__pic" />
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="details_right">
                                    <div class="breadcrumb">
                                        <ul>
                                            <li><a href="{{ url('/') }}">Home</a></li>
                                            <li><span>/</span></li>
                                            @if ($details->category)
                                                <li><a
                                                        href="{{ url('/category/' . $details->category->slug) }}">{{ $details->category->name }}</a>
                                                </li>
                                            @endif
                                            @if ($details->subcategory)
                                                <li><span>/</span></li>
                                                <li><a
                                                        href="{{ route('subcategory', $details->subcategory->slug) }}">{{ $details->subcategory->subcategoryName }}</a>
                                                </li>
                                            @endif
                                            @if ($details->childcategory)
                                                    <li><span>/</span></li>
                                                    <li><a
                                                            href="{{ route('products', $details->childcategory->slug) }}">{{ $details->childcategory->childcategoryName }}</a>
                                                    </li>
                                                @endif
                                        </ul>
                                    </div>

                                    <div class="product">
                                        <div class="product-cart">
                                            <p class="name">{{ $details->name }}</p>
                                            @if(!empty($details->short_description))
                                            <p class="text-muted mb-2">{!! nl2br(e($details->short_description)) !!}</p>
                                            @endif
                                            @php
                                                $variantRows = collect($variantPayload['variants'] ?? [])->values();
                                                $hasVariantRows = $variantRows->isNotEmpty();
                                                $defaultVariant = $variantRows->first();
                                                $baseCurrentPrice = (float) ($details->new_price ?? 0);
                                                $initialCurrentPrice = $hasVariantRows
                                                    ? (float) ($defaultVariant['price'] ?? $baseCurrentPrice)
                                                    : $baseCurrentPrice;
                                                $initialSellableStock = $hasVariantRows
                                                    ? (float) ($defaultVariant['sellable_stock'] ?? 0)
                                                    : (float) ($details->available_stock ?? 0);
                                                $isOutOfStock = $initialSellableStock <= 0;
                                            @endphp
                                            <p class="details-price" id="details-price-wrapper">
                                                @if ($details->old_price)
                                                    <del id="details-old-price">&#2547;{{ number_format((float) $details->old_price, 2) }}</del>
                                                @endif
                                                <span id="details-current-price">&#2547;{{ number_format($initialCurrentPrice, 2) }}</span>
                                            </p>
                                            <div class="details-ratting-wrapper">
                                            @php
                                                $averageRating = $reviews->avg('ratting');
                                                $filledStars = floor($averageRating);
                                                $emptyStars = 5 - $filledStars;
                                            @endphp
                                            
                                            @if ($averageRating >= 0 && $averageRating <= 5)
                                                @for ($i = 1; $i <= $filledStars; $i++)
                                                    <i class="fas fa-star"></i>
                                                @endfor
                                            
                                                @if ($averageRating == $filledStars)
                                                    {{-- If averageRating is an integer, don't display half star --}}
                                                @else
                                                    <i class="far fa-star-half-alt"></i>
                                                @endif
                                            
                                                @for ($i = 1; $i <= $emptyStars; $i++)
                                                    <i class="far fa-star"></i>
                                                @endfor
                                            
                                                <span>{{ number_format($averageRating, 2) }}/5</span>
                                            @else
                                                <span>Invalid rating range</span>
                                            @endif
                                            <a class="all-reviews-button" href="#writeReview">See Reviews</a>
                                            </div>
                                            <div class="product-code">
                                                <p><span>প্রোডাক্ট কোড : </span>{{ $details->product_code }}</p>
                                            </div>
                                            <form
                                                action="{{ route('cart.store') }}"
                                                method="POST"
                                                name="formName"
                                                id="product-detail-form"
                                                data-has-variants="{{ $hasVariantRows ? '1' : '0' }}"
                                            >
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $details->id }}" />
                                                <input type="hidden" name="product_variant_id" id="product-variant-id" value="{{ $defaultVariant['id'] ?? '' }}" />
                                                <input type="hidden" name="variant_id" id="variant-id" value="{{ $defaultVariant['id'] ?? '' }}" />
                                                <input type="hidden" name="product_color" id="product-color-input" value="{{ $defaultVariant['color'] ?? '' }}" />
                                                <input type="hidden" name="product_size" id="product-size-input" value="{{ $defaultVariant['size'] ?? '' }}" />
                                                <input type="hidden" name="product_age" id="product-age-input" value="{{ $defaultVariant['age'] ?? '' }}" />
                                                @if ($hasVariantRows)
                                                    <div id="variant-attributes-container" class="mb-2"></div>
                                                @endif
                                                        @if ($details->pro_unit)
                                                            <div class="pro_unig">
                                                                <label>Unit: {{ $details->pro_unit }}</label>
                                                                <input type="hidden" name="pro_unit"
                                                                    value="{{ $details->pro_unit }}" />
                                                            </div>
                                                        @endif
                                                        <div class="pro_brand">
                                                            <p>Brand :
                                                                {{ $details->brand ? $details->brand->name : 'N/A' }}
                                                            </p>
                                                        </div>
                                                        <p
                                                            id="variant-stock-indicator"
                                                            class="fw-bold mb-2 {{ $isOutOfStock ? 'text-danger' : 'text-success' }}"
                                                        >
                                                            {{ $isOutOfStock ? 'Stock Out' : 'In Stock' }}
                                                            <span id="variant-stock-count">({{ rtrim(rtrim(number_format($initialSellableStock, 2, '.', ''), '0'), '.') }})</span>
                                                        </p>

                                                        <div class="row">
                                                            <div class="qty-cart col-sm-12">
                                                                <div class="quantity">
                                                                    <span class="minus">-</span>
                                                                    <input type="text" name="qty"
                                                                        value="1" @if ($isOutOfStock) disabled @endif />
                                                                    <span class="plus">+</span>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex single_product col-sm-12">
                                                                <input type="submit" id="add_to_cart" class="btn px-4 add_cart_btn"
                                                                    onclick="return sendSuccess();" name="add_cart"
                                                                    @if ($isOutOfStock) disabled @endif
                                                                    value="কার্টে যোগ করুন" />

                                                                <input type="submit"
                                                                    id="order_now" class="btn px-4 order_now_btn order_now_btn_m"
                                                                    onclick="return sendSuccess();" name="order_now"
                                                                    @if ($isOutOfStock) disabled @endif
                                                                    value="অর্ডার করুন" />
                                                            </div>

                                                        </div>
                                                        <div class="row gx-2 mt-2">
                                                            <div class="col-6">
                                                                <h4 class="font-weight-bold mb-0">
                                                                    <a class="btn btn-success w-100 call_now_btn"
                                                                        href="tel: {{ $contact->hotline }}">
                                                                        <i class="fa fa-phone-square"></i>
                                                                        {{ $contact->hotline }}
                                                                    </a>
                                                                </h4>
                                                            </div>
                                                            <div class="col-6">
                                                                <h4 class="font-weight-bold mb-0">
                                                                    <a class="btn btn-success w-100 call_now_btn"
                                                                        href="https://api.whatsapp.com/send?phone={{ $contact->whatsapp }}&text=হ্যালো, আমি এই পণ্যটির ব্যাপারে জানতে চাই: {{ urlencode(Request::url()) }}"
                                                                        target="_blank">
                                                                        <i class="fa-brands fa-whatsapp"></i>
                                                                        WhatsApp
                                                                    </a>
                                                                </h4>
                                                            </div>
                                                        </div>
                                                        <div class="row mt-2">
                                                            @php
                                                                $shareUrl = urlencode(Request::url());
                                                                $shareTitle = urlencode($details->name);
                                                            @endphp
                                                            <div class="product-share-actions col-sm-12" aria-label="Share this product">
                                                                <span>Share</span>
                                                                <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener" aria-label="Share on Facebook">
                                                                    <i class="fab fa-facebook-f"></i>
                                                                </a>
                                                                <a href="https://api.whatsapp.com/send?text={{ $shareTitle }}%20{{ $shareUrl }}" target="_blank" rel="noopener" aria-label="Share on WhatsApp">
                                                                    <i class="fab fa-whatsapp"></i>
                                                                </a>
                                                                <a href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareTitle }}" target="_blank" rel="noopener" aria-label="Share on Twitter">
                                                                    <i class="fab fa-twitter"></i>
                                                                </a>
                                                                <button type="button" class="product-share-copy" data-share-url="{{ Request::url() }}" aria-label="Copy product link">
                                                                    <i class="fas fa-link"></i>
                                                                </button>
                                                            </div>
                                                        </div>

                                                        <div class="mt-md-2 mt-2">
                                                            <div class="del_charge_area">
                                                        <div class="alert alert-info text-xs">
                                                                    <strong class="me-2">Shipping:</strong>
                                                                    <div class="d-flex flex-wrap gap-2">
                                                                        @foreach ($shippingcharge as $key => $value)
                                                                            <span class="me-3"><i class="fa-solid fa-truck-fast me-1"></i> {{ $value->name }}</span>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                     
                                            </form>


                                        </div>
                                    </div>
                                </div>
                            </div>
            </div>
        </section>
    </div>
</div>

<div class="description-nav-wrapper">
    <div class="container">
        <div class="row">

            <div class="col-sm-12">
                <div class="description-nav">
                    <ul class="desc-nav-ul">
                        {{-- <li class="active">
                            <a href="#specification" target="_self">Specification</a>
                        </li> --}}
                        <li>
                            <a href="#description" target="_self">Description</a>
                        </li>
                        {{-- <li>
                            <a href="#question" target="_self">Questions (0)</a>
                        </li> --}}
                        <li>
                            <a href="#writeReview" target="_self">Reviews ({{ $reviews->count() }}) </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="pro_details_area">
    <div class="container">
        <div class="row details-content-row">
            <div class="col-sm-12 details-main-column">
                <div class="description tab-content details-action-box description-content" id="description">
                    <h2>বিস্তারিত</h2>
                    @php
                        $rawDescription = (string) ($details->description ?? '');
                        $sanitizedDescription = '';

                        if ($rawDescription !== '' && class_exists(\DOMDocument::class)) {
                            libxml_use_internal_errors(true);

                            $dom = new \DOMDocument('1.0', 'UTF-8');
                            $wrappedHtml = '<div id="description-root">' . $rawDescription . '</div>';

                            $dom->loadHTML(
                                '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>' . $wrappedHtml . '</body></html>',
                                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
                            );

                            $descriptionRoot = $dom->getElementById('description-root');
                            if ($descriptionRoot) {
                                foreach ($descriptionRoot->childNodes as $childNode) {
                                    $sanitizedDescription .= $dom->saveHTML($childNode);
                                }
                            }

                            libxml_clear_errors();
                        } else {
                            $sanitizedDescription = $rawDescription;
                        }
                    @endphp
                    <div class="description-body">{!! $sanitizedDescription !!}</div>
                </div>
                <div class="tab-content details-action-box" id="writeReview">
                    <div class="row details-review-row">
                        <div class="col-sm-12 px-0">
                                <div class="section-head">
                                    <div class="title">
                                        <h2>Reviews ({{ $reviews->count() }})</h2>
                                        <p>Get specific details about this product from customers who own it.</p>
                                    </div>
                                    <div class="action">
                                        <div>
                                            <button type="button" class="details-action-btn question-btn js-write-review-btn">
                                                Write a review
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="insert-review inline-review-form" id="inlineReviewForm">
                                    @if (Auth::guard('customer')->user())
                                        <form action="{{ route('customer.review') }}" id="review-form" method="POST">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $details->id }}">
                                            <div class="fz-12 mb-2">
                                                <div class="rating">
                                                    <label title="Excelent">&#9734;<input required type="radio" name="ratting" value="5" /></label>
                                                    <label title="Best">&#9734;<input required type="radio" name="ratting" value="4" /></label>
                                                    <label title="Better">&#9734;<input required type="radio" name="ratting" value="3" /></label>
                                                    <label title="Very Good">&#9734;<input required type="radio" name="ratting" value="2" /></label>
                                                    <label title="Good">&#9734;<input required type="radio" name="ratting" value="1" /></label>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="message-text" class="col-form-label">Message:</label>
                                                <textarea required class="form-control" name="review" id="message-text"></textarea>
                                                <span id="validation-message" style="color: red;"></span>
                                            </div>
                                            <div class="form-group mt-2">
                                                <button class="details-review-button" type="submit">Submit Review</button>
                                            </div>
                                        </form>
                                    @else
                                        <a class="customer-login-redirect" href="{{ route('customer.login') }}">Login to Post Your Review</a>
                                    @endif
                                </div>
                                @if ($reviews->count() > 0)
                                    <div class="customer-review">
                                        <div class="row">
                                            @foreach ($reviews as $key => $review)
                                                <div class="col-sm-12 col-12">
                                                    <div class="review-card">
                                                        <p class="reviewer_name"><i data-feather="message-square"></i>
                                                            {{ $review->name }}</p>
                                                        <p class="review_data">{{ $review->created_at->format('d-m-Y') }}</p>
                                                        <p class="review_star">{!! str_repeat('<i class="fa-solid fa-star"></i>', $review->ratting) !!}</p>
                                                        <p class="review_content">{{ $review->review }}</p>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <div class="empty-content">
                                        <i class="fa fa-clipboard-list"></i>
                                        <p class="empty-text">This product has no reviews yet. Be the first one to write a review.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                </div>
            </div>
            @if($details->pro_video)
            <div class="col-sm-12">
                <div class="pro_vide details-video-block">
                    <h2>ভিডিও</h2>
                    <iframe width="100%" height="315"
                        src="https://www.youtube.com/embed/{{ $details->pro_video }}" title="YouTube video player"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen></iframe>
                </div>
            </div>
            @endif
        </div>
    </div>
</section>

<section class="related-product-section">
    <div class="container">
        <div class="row">
            <div class="related-title">
                <h5>Related Product</h5>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <div class="product-inner owl-carousel related_slider">
                    @foreach ($products as $key => $value)
                        @include('frontEnd.layouts.partials._product_card', ['value' => $value, 'key' => $key])
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

@if(false)
<div class="modal fade" id="productReviewModal" tabindex="-1" aria-labelledby="productReviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="productReviewModalLabel">Your review</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="insert-review">
                    @if (Auth::guard('customer')->user())
                        <form action="{{ route('customer.review') }}" id="review-form" method="POST">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $details->id }}">
                            <div class="fz-12 mb-2">
                                <div class="rating">
                                    <label title="Excelent">☆<input required type="radio" name="ratting" value="5" /></label>
                                    <label title="Best">☆<input required type="radio" name="ratting" value="4" /></label>
                                    <label title="Better">☆<input required type="radio" name="ratting" value="3" /></label>
                                    <label title="Very Good">☆<input required type="radio" name="ratting" value="2" /></label>
                                    <label title="Good">☆<input required type="radio" name="ratting" value="1" /></label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="message-text" class="col-form-label">Message:</label>
                                <textarea required class="form-control" name="review" id="message-text"></textarea>
                                <span id="validation-message" style="color: red;"></span>
                            </div>
                            <div class="form-group mt-2">
                                <button class="details-review-button" type="submit">Submit Review</button>
                            </div>
                        </form>
                    @else
                        <a class="customer-login-redirect" href="{{ route('customer.login') }}">Login to Post Your Review</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@endif

@endsection
@push('seo_content')
@if(!empty($details->meta_description))
<section class="homeproduct">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="meta_des">
                    {!! $details->meta_description !!}
                </div>
            </div>
        </div>
    </div>
</section>
@endif
@endpush
@push('script')
<script src="{{ asset('public/frontEnd/js/owl.carousel.min.js') }}"></script>

<script src="{{ asset('public/frontEnd/js/zoomsl.min.js') }}"></script>

<script>
    $(document).ready(function() {
        $(".details_slider").owlCarousel({
            margin: 0,
            items: 1,
            loop: false,
            dots: false,
            autoplay: false,
            autoplayTimeout: 6000,
            autoplayHoverPause: true,
        });

        $(".indicator-item").on("mouseenter focus click", function() {
            var $thumb = $(this);
            var slideIndex = $thumb.data("id");

            $(".indicator-item").removeClass("active");
            $thumb.addClass("active");
            $(".details_slider").trigger("to.owl.carousel", [slideIndex, 180]);
        });

        $(".details_slider").on("changed.owl.carousel", function(event) {
            var index = event.item.index || 0;
            $(".indicator-item").removeClass("active");
            $('.indicator-item[data-id="' + index + '"]').addClass("active");
        });
    });
</script>
<!--Data Layer Start-->
<script type="text/javascript">
    window.dataLayer = window.dataLayer || [];
    dataLayer.push({ ecommerce: null });
    dataLayer.push({
        event: "view_item",
        ecommerce: {
            currency: "BDT",
            value: {{ $details->new_price ?? 0 }},
            items: [{
                item_name: "{{ $details->name }}",
                item_id: "{{ $details->id }}",
                price: {{ $details->new_price ?? 0 }},
                item_brand: "{{ $details->brand ? $details->brand->name : '' }}",
                item_category: "{{ $details->category ? $details->category->name : '' }}",
                item_variant: "{{ $details->pro_unit }}",
                quantity: 1
            }]
        }
    });
</script>
<script type="text/javascript">
    $(document).ready(function() {
        $('#add_to_cart, #order_now').click(function() {
            var qty = parseInt($('input[name="qty"]').val()) || 1;
            window.dataLayer = window.dataLayer || [];
            dataLayer.push({ ecommerce: null });
            dataLayer.push({
                event: "add_to_cart",
                ecommerce: {
                    currency: "BDT",
                    value: ({{ $details->new_price ?? 0 }}) * qty,
                    items: [{
                        item_id: "{{ $details->id }}",
                        item_name: "{{ $details->name }}",
                        price: {{ $details->new_price ?? 0 }},
                        item_brand: "{{ $details->brand ? $details->brand->name : '' }}",
                        item_category: "{{ $details->category ? $details->category->name : '' }}",
                        item_variant: "{{ $details->pro_unit }}",
                        quantity: qty
                    }]
                }
            });
        });
    });
</script>
<!-- Data Layer End-->
<script>
    $(document).ready(function() {
        $(".related_slider").owlCarousel({
            margin: 10,
            items: 6,
            loop: true,
            dots: true,
            nav: true,
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
                    loop: true,
                },
            },
        });
        // $('.owl-nav').remove();
    });
</script>
<script>
    $(document).ready(function() {
        $(".minus").click(function() {
            var $input = $(this).parent().find("input");
            var count = parseInt($input.val()) - 1;
            count = count < 1 ? 1 : count;
            $input.val(count);
            $input.change();
            return false;
        });
        $(".plus").click(function() {
            var $input = $(this).parent().find("input");
            $input.val(parseInt($input.val()) + 1);
            $input.change();
            return false;
        });
    });
</script>

<script>
    (function () {
        const form = document.getElementById('product-detail-form');
        if (!form) {
            return;
        }

        const payload = @json($variantPayload ?? ['variants' => [], 'attribute_groups' => []]);
        const hasVariantRows = String(form.dataset.hasVariants || '0') === '1';
        const assetBaseUrl = @json(asset(''));
        const baseCurrentPrice = Number(@json((float) ($details->new_price ?? 0)));
        const baseStock = Number(@json((float) $initialSellableStock));

        const priceEl = document.getElementById('details-current-price');
        const stockIndicatorEl = document.getElementById('variant-stock-indicator');
        const variantIdInputEl = document.getElementById('product-variant-id');
        const variantMirrorInputEl = document.getElementById('variant-id');
        const colorInputEl = document.getElementById('product-color-input');
        const sizeInputEl = document.getElementById('product-size-input');
        const ageInputEl = document.getElementById('product-age-input');
        const qtyInputEl = form.querySelector('input[name="qty"]');
        const submitButtons = Array.from(form.querySelectorAll('input[type="submit"]'));
        const attributeContainerEl = document.getElementById('variant-attributes-container');

        let sliderImageEls = Array.from(document.querySelectorAll('.details_slider .dimage_item img'));
        let thumbImageEls = Array.from(document.querySelectorAll('.indicator_thumb .indicator-item img'));
        const defaultPrimaryImage = sliderImageEls[0] ? sliderImageEls[0].getAttribute('src') : '';
        const defaultThumbImage = thumbImageEls[0] ? thumbImageEls[0].getAttribute('src') : defaultPrimaryImage;
        const galleryByVariantId = {};
        const defaultGalleryImages = [];

        sliderImageEls.forEach((imageEl, index) => {
            const src = String(imageEl.getAttribute('src') || '').trim();
            const variantId = String(imageEl.getAttribute('data-variant-id') || '').trim();
            const thumbSrc = thumbImageEls[index] ? String(thumbImageEls[index].getAttribute('src') || '').trim() : src;
            const entry = { src, thumbSrc };

            defaultGalleryImages.push(entry);
            if (variantId !== '') {
                if (!galleryByVariantId[variantId]) {
                    galleryByVariantId[variantId] = [];
                }
                galleryByVariantId[variantId].push(entry);
            }
        });

        if (defaultGalleryImages.length === 0 && defaultPrimaryImage !== '') {
            defaultGalleryImages.push({
                src: defaultPrimaryImage,
                thumbSrc: defaultThumbImage || defaultPrimaryImage,
            });
        }

        function normalize(value) {
            return String(value ?? '').trim();
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function formatPrice(value) {
            const number = Number(value || 0);
            return '&#2547;' + number.toFixed(2);
        }

        function formatStock(value) {
            const number = Number(value || 0);
            return Number.isInteger(number) ? String(number) : number.toFixed(2).replace(/\.00$/, '');
        }

        function shouldSplitVariantValues(attributeSlug, attributeKey = '') {
            const normalizedKey = normalize(attributeKey).toLowerCase();

            return normalizedKey.endsWith('_legacy');
        }

        function splitVariantValues(rawValue, attributeSlug, attributeKey = '') {
            const cleaned = normalize(rawValue);
            if (cleaned === '') {
                return [];
            }

            if (!shouldSplitVariantValues(attributeSlug, attributeKey)) {
                return [cleaned];
            }

            return cleaned
                .split(',')
                .map((item) => normalize(item))
                .filter((item) => item !== '');
        }

        function mergeVariantValueMap(targetMap, targetLabelMap, targetListMap, key, values) {
            const normalizedKey = normalize(key);
            if (normalizedKey === '') {
                return;
            }

            const mergedValues = Array.from(new Set(
                (Array.isArray(targetListMap[normalizedKey]) ? targetListMap[normalizedKey] : [])
                    .concat(Array.isArray(values) ? values : [])
                    .map((item) => normalize(item))
                    .filter((item) => item !== '')
            ));

            if (mergedValues.length === 0) {
                return;
            }

            targetListMap[normalizedKey] = mergedValues;
            targetMap[normalizedKey] = mergedValues[0];
            targetLabelMap[normalizedKey] = mergedValues[0];
        }

        function resolveImageUrl(path) {
            const cleaned = normalize(path);
            if (cleaned === '') {
                return '';
            }

            if (/^(https?:)?\/\//i.test(cleaned) || cleaned.startsWith('data:')) {
                return cleaned;
            }

            let resolvedPath = cleaned.replace(/^\/+/, '');
            if (!/^public\//i.test(resolvedPath) && /^(storage|uploads)\//i.test(resolvedPath)) {
                resolvedPath = 'public/' + resolvedPath;
            }

            return assetBaseUrl + resolvedPath;
        }

        function rebindGalleryNodes() {
            sliderImageEls = Array.from(document.querySelectorAll('.details_slider .dimage_item img'));
            thumbImageEls = Array.from(document.querySelectorAll('.indicator_thumb .indicator-item img'));
        }

        function initDetailsSlider() {
            if (!window.jQuery || typeof window.jQuery.fn.owlCarousel !== 'function') {
                return;
            }

            const $slider = window.jQuery('.details_slider');
            $slider.owlCarousel({
                margin: 0,
                items: 1,
                loop: false,
                dots: false,
                nav: false,
                autoplay: false,
                autoplayTimeout: 6000,
                autoplayHoverPause: true,
            });
        }

        function renderGalleryImages(images) {
            const targetGallery = Array.isArray(images) && images.length > 0
                ? images
                : defaultGalleryImages;

            if (!Array.isArray(targetGallery) || targetGallery.length === 0) {
                return;
            }

            const indicatorEl = document.querySelector('.indicator_thumb');
            const sliderEl = document.querySelector('.details_slider');
            if (!indicatorEl || !sliderEl) {
                return;
            }

            if (window.jQuery && typeof window.jQuery.fn.owlCarousel === 'function') {
                const $slider = window.jQuery(sliderEl);
                if ($slider.hasClass('owl-loaded')) {
                    $slider.trigger('destroy.owl.carousel');
                    $slider.removeClass('owl-loaded owl-hidden');
                    $slider.find('.owl-stage-outer').children().unwrap();
                }
            }

            indicatorEl.innerHTML = targetGallery.map((item, index) => {
                const thumbSrc = item.thumbSrc || item.src;
                return '<button type="button" class="indicator-item ' + (index === 0 ? 'active' : '') + '" data-id="' + index + '">' +
                    '<img src="' + escapeHtml(thumbSrc) + '" alt="Variant thumbnail ' + (index + 1) + '">' +
                    '</button>';
            }).join('');

            sliderEl.innerHTML = targetGallery.map((item) => {
                return '<div class="dimage_item">' +
                    '<img src="' + escapeHtml(item.src) + '" alt="Product image" class="block__pic">' +
                    '</div>';
            }).join('');

            rebindGalleryNodes();
            initDetailsSlider();

            if (window.jQuery && typeof window.jQuery.fn.imagezoomsl === 'function') {
                window.jQuery('.details_slider .block__pic').imagezoomsl({
                    zoomrange: [2.5, 2.5],
                    innerzoom: true,
                    magnifierborder: "none"
                });
            }
        }

        document.addEventListener('click', function(event) {
            const thumb = event.target.closest('.indicator_thumb .indicator-item');
            if (!thumb) {
                return;
            }

            const slideIndex = Number(thumb.getAttribute('data-id') || 0);
            document.querySelectorAll('.indicator_thumb .indicator-item').forEach((item) => item.classList.remove('active'));
            thumb.classList.add('active');

            if (window.jQuery && typeof window.jQuery.fn.owlCarousel === 'function') {
                window.jQuery('.details_slider').trigger('to.owl.carousel', [slideIndex, 180]);
            }
        });

        if (window.jQuery && typeof window.jQuery.fn.owlCarousel === 'function') {
            window.jQuery('.details_slider').on('changed.owl.carousel', function(event) {
                const index = event.item.index || 0;
                document.querySelectorAll('.indicator_thumb .indicator-item').forEach((item) => item.classList.remove('active'));
                const activeThumb = document.querySelector('.indicator_thumb .indicator-item[data-id="' + index + '"]');
                if (activeThumb) {
                    activeThumb.classList.add('active');
                }
            });
        }

        function updatePrimaryImage(path) {
            const resolved = resolveImageUrl(path) || defaultPrimaryImage;
            if (!resolved) {
                return;
            }

            // If the image is the same as current, skip update to prevent flicker
            if (sliderImageEls[0] && sliderImageEls[0].getAttribute('src') === resolved) {
                return;
            }

            if (sliderImageEls[0]) {
                sliderImageEls[0].setAttribute('src', resolved);
                // Re-init zoom for the new image if needed
                if (window.jQuery && typeof window.jQuery.fn.imagezoomsl === 'function') {
                    window.jQuery(sliderImageEls[0]).imagezoomsl({ 
                        zoomrange: [2.5, 2.5],
                        innerzoom: true,
                        magnifierborder: "none"
                    });
                }
            }
            if (window.jQuery && typeof window.jQuery.fn.owlCarousel === 'function') {
                window.jQuery('.details_slider').trigger('to.owl.carousel', [0, 200]);
            }
        }

        function updateGalleryForVariant(variantId) {
            const variantKey = normalize(variantId);
            const targetGallery = (variantKey !== '' && Array.isArray(galleryByVariantId[variantKey]) && galleryByVariantId[variantKey].length > 0)
                ? galleryByVariantId[variantKey]
                : defaultGalleryImages;

            if (!Array.isArray(targetGallery) || targetGallery.length === 0) {
                return;
            }

            renderGalleryImages(targetGallery);
        }

        function hasVariantSpecificGallery(variantId) {
            const variantKey = normalize(variantId);

            return variantKey !== ''
                && Array.isArray(galleryByVariantId[variantKey])
                && galleryByVariantId[variantKey].length > 0;
        }

        function resolveGalleryLeadImage(variantId) {
            const variantKey = normalize(variantId);
            const targetGallery = (variantKey !== '' && Array.isArray(galleryByVariantId[variantKey]) && galleryByVariantId[variantKey].length > 0)
                ? galleryByVariantId[variantKey]
                : defaultGalleryImages;

            return Array.isArray(targetGallery) && targetGallery[0]
                ? normalize(targetGallery[0].src)
                : '';
        }

        function resolvePreviewImagePath(variant) {
            if (!variant) {
                return '';
            }

            if (hasVariantSpecificGallery(variant.id)) {
                return resolveGalleryLeadImage(variant.id);
            }

            const directImage = normalize(variant.image);
            if (directImage !== '') {
                return directImage;
            }

            return resolveGalleryLeadImage(variant.id);
        }

        function setAvailability(stock, label) {
            const inStock = Number(stock || 0) > 0;

            if (qtyInputEl) {
                qtyInputEl.disabled = !inStock;
            }
            submitButtons.forEach((button) => {
                button.disabled = !inStock;
            });

            if (!stockIndicatorEl) {
                return;
            }

            stockIndicatorEl.classList.remove('text-success', 'text-danger', 'text-warning');
            if (label === 'In Stock') {
                stockIndicatorEl.classList.add('text-success');
            } else if (label === 'Select attributes') {
                stockIndicatorEl.classList.add('text-warning');
            } else {
                stockIndicatorEl.classList.add('text-danger');
            }

            stockIndicatorEl.innerHTML = `${escapeHtml(label)} <span id="variant-stock-count">(${escapeHtml(formatStock(stock))})</span>`;
        }

        function setPrice(value) {
            if (!priceEl) {
                return;
            }
            priceEl.innerHTML = formatPrice(value);
        }

        const normalizedVariants = (Array.isArray(payload.variants) ? payload.variants : []).map((variant) => {
            const id = normalize(variant.id);
            const attributes = (variant.attributes && typeof variant.attributes === 'object') ? variant.attributes : {};
            const attributeRows = Array.isArray(variant.attribute_values) ? variant.attribute_values : [];

            const valueMap = {};
            const valueLabelMap = {};
            const valueListMap = {};

            attributeRows.forEach((row) => {
                const attributeIdKey = normalize(row.attribute_id);
                const attributeSlug = normalize(row.attribute_slug).toLowerCase();
                const valueText = normalize(row.value);
                const splitValues = splitVariantValues(valueText, attributeSlug, attributeIdKey);

                if (attributeIdKey !== '' && splitValues.length > 0) {
                    mergeVariantValueMap(valueMap, valueLabelMap, valueListMap, attributeIdKey, splitValues);
                }

                if (attributeSlug !== '' && splitValues.length > 0) {
                    mergeVariantValueMap(valueMap, valueLabelMap, valueListMap, 'slug:' + attributeSlug, splitValues);
                }
            });

            Object.keys(attributes).forEach((slug) => {
                const slugKey = 'slug:' + normalize(slug).toLowerCase();
                const splitValues = splitVariantValues(attributes[slug], slug, slugKey);
                if (splitValues.length === 0) {
                    return;
                }
                mergeVariantValueMap(valueMap, valueLabelMap, valueListMap, slugKey, splitValues);
            });

            const colorValue = normalize(attributes.color ?? variant.color);
            const sizeValue = normalize(attributes.size ?? variant.size);
            const ageValue = normalize(attributes.age ?? variant.age);

            return {
                id,
                price: Number(variant.price || baseCurrentPrice),
                stock: Number(variant.sellable_stock || 0),
                image: normalize(variant.image),
                label: normalize(variant.label || ''),
                sku: normalize(variant.sku_code || ''),
                color: colorValue,
                size: sizeValue,
                age: ageValue,
                valueMap,
                valueLabelMap,
                valueListMap,
            };
        });

        let groups = (Array.isArray(payload.attribute_groups) ? payload.attribute_groups : [])
            .map((group) => {
                const attributeId = normalize(group.attribute_id);
                const attributeSlug = normalize(group.attribute_slug).toLowerCase();
                const key = attributeId !== '' ? attributeId : 'slug:' + attributeSlug;
                const valuesMap = {};

                (Array.isArray(group.values) ? group.values : []).forEach((value) => {
                    splitVariantValues(value.value, attributeSlug, key).forEach((optionValue) => {
                        const normalizedOption = normalize(optionValue);
                        if (normalizedOption === '') {
                            return;
                        }

                        valuesMap[normalizedOption] = {
                            value_id: normalizedOption,
                            value: normalizedOption,
                        };
                    });
                });

                return {
                    key,
                    name: normalize(group.attribute_name) || 'Attribute',
                    slug: attributeSlug,
                    values: Object.values(valuesMap),
                };
            })
            .filter((group) => group.key !== '' && group.values.length > 0);

        if (groups.length === 0 && normalizedVariants.length > 0) {
            const derived = {};
            normalizedVariants.forEach((variant) => {
                Object.keys(variant.valueListMap).forEach((key) => {
                    if (!key.startsWith('slug:')) {
                        return;
                    }

                    if (!derived[key]) {
                        const slug = key.replace(/^slug:/, '');
                        derived[key] = {
                            key,
                            slug,
                            name: slug
                                .split('-')
                                .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
                                .join(' '),
                            values: {},
                        };
                    }

                    (Array.isArray(variant.valueListMap[key]) ? variant.valueListMap[key] : []).forEach((valueText) => {
                        const normalizedValue = normalize(valueText);
                        if (normalizedValue === '') {
                            return;
                        }

                        derived[key].values[normalizedValue] = {
                            value_id: normalizedValue,
                            value: normalizedValue,
                        };
                    });
                });
            });

            groups = Object.values(derived).map((group) => ({
                key: group.key,
                slug: group.slug,
                name: group.name,
                values: Object.values(group.values),
            }));
        }

        const selectedByGroupKey = {};
        groups.forEach((group) => {
            selectedByGroupKey[group.key] = '';
        });

        function variantMatchesSelection(variant, ignoredGroupKey = '') {
            return groups.every((group) => {
                if (group.key === ignoredGroupKey) {
                    return true;
                }

                const selectedValue = normalize(selectedByGroupKey[group.key]);
                if (selectedValue === '') {
                    return true;
                }

                const variantValues = Array.isArray(variant.valueListMap[group.key])
                    ? variant.valueListMap[group.key]
                    : [normalize(variant.valueMap[group.key])].filter((value) => value !== '');

                return variantValues.some((value) => normalize(value) === selectedValue);
            });
        }

        function resolveGroupValues(groupKey) {
            const allowed = {};

            normalizedVariants.forEach((variant) => {
                if (!variantMatchesSelection(variant, groupKey)) {
                    return;
                }

                const variantValues = Array.isArray(variant.valueListMap[groupKey])
                    ? variant.valueListMap[groupKey]
                    : [normalize(variant.valueMap[groupKey])].filter((value) => value !== '');

                variantValues.forEach((value) => {
                    const normalizedValue = normalize(value);
                    if (normalizedValue !== '') {
                        allowed[normalizedValue] = normalizedValue;
                    }
                });
            });

            return Object.keys(allowed).map((valueId) => ({
                value_id: valueId,
                value: allowed[valueId],
            }));
        }

        function resolveSelectedValueForSlug(slug, fallbackValue = '') {
            const normalizedSlug = normalize(slug).toLowerCase();
            if (normalizedSlug === '') {
                return normalize(fallbackValue);
            }

            const matchingGroup = groups.find((group) => group.slug === normalizedSlug);
            const selectedValue = matchingGroup ? normalize(selectedByGroupKey[matchingGroup.key]) : '';

            return selectedValue !== '' ? selectedValue : normalize(fallbackValue);
        }

        function updateSelectedVariantState(selectedVariant, previewVariant = null) {
            if (!hasVariantRows) {
                setPrice(baseCurrentPrice);
                setAvailability(baseStock, baseStock > 0 ? 'In Stock' : 'Stock Out');
                return;
            }

            if (!selectedVariant) {
                if (variantIdInputEl) {
                    variantIdInputEl.value = '';
                }
                if (variantMirrorInputEl) {
                    variantMirrorInputEl.value = '';
                }
                if (colorInputEl) {
                    colorInputEl.value = resolveSelectedValueForSlug('color', '');
                }
                if (sizeInputEl) {
                    sizeInputEl.value = resolveSelectedValueForSlug('size', '');
                }
                if (ageInputEl) {
                    ageInputEl.value = resolveSelectedValueForSlug('age', '');
                }

                setPrice(baseCurrentPrice);
                setAvailability(0, 'Select attributes');

                if (previewVariant) {
                    updateGalleryForVariant(previewVariant.id);
                    updatePrimaryImage(resolvePreviewImagePath(previewVariant));
                } else {
                    updateGalleryForVariant('');
                    updatePrimaryImage('');
                }
                return;
            }

            if (variantIdInputEl) {
                variantIdInputEl.value = selectedVariant.id;
            }
            if (variantMirrorInputEl) {
                variantMirrorInputEl.value = selectedVariant.id;
            }
            if (colorInputEl) {
                colorInputEl.value = resolveSelectedValueForSlug('color', selectedVariant.color);
            }
            if (sizeInputEl) {
                sizeInputEl.value = resolveSelectedValueForSlug('size', selectedVariant.size);
            }
            if (ageInputEl) {
                ageInputEl.value = resolveSelectedValueForSlug('age', selectedVariant.age);
            }

            setPrice(selectedVariant.price);
            setAvailability(
                selectedVariant.stock,
                selectedVariant.stock > 0 ? 'In Stock' : 'Stock Out'
            );
            updateGalleryForVariant(selectedVariant.id);
            updatePrimaryImage(resolvePreviewImagePath(selectedVariant));
        }

        function resolvePreviewVariant() {
            const selectedGroups = groups.filter((group) => normalize(selectedByGroupKey[group.key]) !== '');
            if (selectedGroups.length === 0) {
                return null;
            }

            return normalizedVariants.find((variant) => {
                return selectedGroups.every((group) => {
                    const selectedValue = normalize(selectedByGroupKey[group.key]);
                    const variantValues = Array.isArray(variant.valueListMap[group.key])
                        ? variant.valueListMap[group.key]
                        : [normalize(variant.valueMap[group.key])].filter((value) => value !== '');

                    return variantValues.some((value) => normalize(value) === selectedValue);
                });
            }) || null;
        }

        function resolveSelectedVariant() {
            const matches = normalizedVariants.filter((variant) => variantMatchesSelection(variant));
            const allSelected = groups.every((group) => normalize(selectedByGroupKey[group.key]) !== '');

            if (groups.length > 0 && allSelected && matches.length === 1) {
                return matches[0];
            }

            if (groups.length === 0 && normalizedVariants.length === 1) {
                return normalizedVariants[0];
            }

            return null;
        }

        function refreshSelectors(changedGroupKey = '') {
            if (!attributeContainerEl || groups.length === 0) {
                updateSelectedVariantState(resolveSelectedVariant());
                return;
            }

            groups.forEach((group) => {
                const selectEl = attributeContainerEl.querySelector(`select[data-group-key="${group.key}"]`);
                if (!selectEl) {
                    return;
                }

                const options = resolveGroupValues(group.key);
                const selectedValue = normalize(selectedByGroupKey[group.key]);

                const optionHtml = ['<option value="">Select ' + escapeHtml(group.name) + '</option>']
                    .concat(options.map((option) => {
                        const selectedAttr = selectedValue === option.value_id ? ' selected' : '';
                        return '<option value="' + escapeHtml(option.value_id) + '"' + selectedAttr + '>' + escapeHtml(option.value) + '</option>';
                    }));

                selectEl.innerHTML = optionHtml.join('');

                if (
                    selectedValue !== '' &&
                    !options.some((option) => option.value_id === selectedValue)
                ) {
                    selectedByGroupKey[group.key] = '';
                    selectEl.value = '';
                } else if (
                    selectedByGroupKey[group.key] === '' &&
                    options.length === 1 &&
                    changedGroupKey !== group.key
                ) {
                    selectedByGroupKey[group.key] = options[0].value_id;
                    selectEl.value = options[0].value_id;
                } else {
                    selectEl.value = selectedByGroupKey[group.key] || '';
                }
            });

            const selectedVariant = resolveSelectedVariant();
            updateSelectedVariantState(selectedVariant, selectedVariant || resolvePreviewVariant());
        }

        function renderSelectors() {
            if (!attributeContainerEl || groups.length === 0) {
                return;
            }

            attributeContainerEl.innerHTML = groups.map((group) => {
                return `
                    <div class="mb-2">
                        <label class="form-label mb-1">${escapeHtml(group.name)}:</label>
                        <select class="form-control variant-attribute-select" data-group-key="${escapeHtml(group.key)}">
                            <option value="">Select ${escapeHtml(group.name)}</option>
                        </select>
                    </div>
                `;
            }).join('');

            attributeContainerEl.querySelectorAll('.variant-attribute-select').forEach((selectEl) => {
                selectEl.addEventListener('change', function () {
                    const groupKey = String(this.getAttribute('data-group-key') || '');
                    selectedByGroupKey[groupKey] = normalize(this.value);
                    refreshSelectors(groupKey);
                });
            });
        }

        function bootstrapSelectedValues() {
            if (!hasVariantRows || normalizedVariants.length === 0) {
                return;
            }

            const existingVariantId = normalize(variantIdInputEl ? variantIdInputEl.value : '');
            const selectedVariant = normalizedVariants.find((variant) => variant.id === existingVariantId) || normalizedVariants[0];
            if (!selectedVariant) {
                return;
            }

            groups.forEach((group) => {
                const variantValues = Array.isArray(selectedVariant.valueListMap[group.key])
                    ? selectedVariant.valueListMap[group.key]
                    : [normalize(selectedVariant.valueMap[group.key])].filter((value) => value !== '');

                selectedByGroupKey[group.key] = variantValues[0] || '';
            });
        }

        bootstrapSelectedValues();
        renderSelectors();
        refreshSelectors();

        window.__productVariantSelection = {
            hasVariantRows: hasVariantRows && normalizedVariants.length > 0,
            variantIdInputEl,
            isInStock: function () {
                return submitButtons.every((button) => !button.disabled);
            },
        };
    })();

    function sendSuccess() {
        const state = window.__productVariantSelection || null;
        if (!state || !state.hasVariantRows) {
            return true;
        }

        const selectedVariantId = state.variantIdInputEl
            ? String(state.variantIdInputEl.value || '').trim()
            : '';

        if (selectedVariantId === '') {
            toastr.warning('Please select all required attributes.');
            return false;
        }

        if (!state.isInStock()) {
            toastr.error('Selected variant is out of stock.');
            return false;
        }

        return true;
    }
</script>
<script>
    $(document).ready(function() {
        $(".js-write-review-btn").on("click", function(event) {
            event.preventDefault();

            const reviewForm = $("#inlineReviewForm");
            reviewForm.addClass("is-visible");

            $("html, body").animate({
                scrollTop: Math.max(reviewForm.offset().top - 120, 0)
            }, 250);

            $("#message-text").trigger("focus");
        });

        $(".rating label").click(function() {
            $(".rating label").removeClass("active");
            $(this).addClass("active");
        });

        $(".product-share-copy").on("click", function() {
            var shareUrl = $(this).data("share-url");

            if (navigator.clipboard && shareUrl) {
                navigator.clipboard.writeText(shareUrl).then(function() {
                    toastr.success("Product link copied.");
                });
            }
        });
    });
</script>
<script>
    $(document).ready(function() {
        $(".thumb_slider").owlCarousel({
            margin: 15,
            items: 4,
            loop: true,
            dots: false,
            nav: true,
            autoplayTimeout: 6000,
            autoplayHoverPause: true,
        });
    });
</script>

<script type="text/javascript">
    $(".block__pic").imagezoomsl({
        zoomrange: [2.5, 2.5],
        innerzoom: true,
        magnifierborder: "none"
    });

</script>
@endpush
