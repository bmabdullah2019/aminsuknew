{{-- Global Product Card Partial --}}
{{-- Usage: @include('frontEnd.layouts.partials._product_card', ['value' => $value, 'key' => $key]) --}}
@php
    $oldPrice = (float) ($value->old_price ?? 0);
    $newPrice = (float) ($value->new_price ?? 0);
    $hasDiscount = $oldPrice > $newPrice && $newPrice >= 0;
    $discount = $hasDiscount ? (($oldPrice - $newPrice) * 100) / $oldPrice : 0;
    $cardImg = $value->display_image;

    $reviews = $value->relationLoaded('reviews') ? $value->reviews : collect();
    $averageRating = (float) $reviews->avg('ratting');
    $ratingCount = (int) ($value->reviews_count ?? $reviews->count());
    $displayRating = $ratingCount > 0 ? $averageRating : 4;
    $displayRatingCount = $ratingCount > 0 ? $ratingCount : 0;
    $roundedRating = (int) round($displayRating);

    $isOutOfStock = (float) ($value->available_stock ?? 0) <= 0;
    $hasSizes = $value->relationLoaded('prosizes') && $value->prosizes->isNotEmpty();
    $hasColors = $value->relationLoaded('procolors') && $value->procolors->isNotEmpty();
    $needsDetailSelection = (bool) ($value->has_variant ?? false) || $hasSizes || $hasColors;
@endphp

<div class="product_item wist_item wow zoomIn" data-wow-duration="1.5s" data-wow-delay="0.{{ $key ?? 0 }}s">
    <div class="product-card-media">
        @if ($hasDiscount)
            <div class="sale-badge">
                <span>{{ number_format($discount, 0) }}% <small>OFF</small></span>
            </div>
        @endif

        <a class="pro_img" href="{{ route('product', $value->slug) }}">
            <img
                src="{{ asset($cardImg) }}"
                alt="{{ $value->name }}"
                loading="lazy"
                onerror="this.onerror=null;this.src='{{ asset('public/frontEnd/images/no-image.jpg') }}';"
            />
        </a>

        <div class="pro_btn">
            <div class="cart_btn compare_button">
                <a href="{{ route('compare.show') }}" class="compare_store product-card-icon-btn" data-id="{{ $value->id }}" aria-label="Add to compare">
                    <i class="far fa-heart"></i>
                </a>
            </div>

            <div class="cart_btn order_button">
                @if ($isOutOfStock)
                    <a href="{{ route('product', $value->slug) }}" class="addcartbutton out-of-stock" data-stock="0" aria-label="Stock out">
                        <span>Stock Out</span>
                    </a>
                @elseif ($needsDetailSelection)
                    <a href="{{ route('product', $value->slug) }}" class="addcartbutton" aria-label="Add to cart">
                        <span>Add to Cart</span>
                    </a>
                @else
                    <a class="addcartbutton" data-id="{{ $value->id }}" data-name="{{ $value->name }}" data-price="{{ $newPrice }}" aria-label="Add to cart">
                        <span>Add to Cart</span>
                    </a>
                @endif
            </div>

            <div class="cart_btn view_button">
                <a href="{{ route('product', $value->slug) }}" class="product-card-icon-btn product-card-view" aria-label="View product">
                    <i class="far fa-eye"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="product-card-body">
        <div class="product-card-rating" aria-label="{{ number_format($displayRating, 1) }} out of 5">
            @for ($i = 1; $i <= 5; $i++)
                <i class="{{ $i <= $roundedRating ? 'fas' : 'far' }} fa-star"></i>
            @endfor
            <span>({{ $displayRatingCount }})</span>
        </div>

        <div class="pro_name">
            <a href="{{ route('product', $value->slug) }}">{{ Str::limit($value->name, 48) }}</a>
        </div>

        <div class="pro_price">
            <p>
                <span>&#2547;{{ number_format($newPrice, 2) }}</span>
                @if ($oldPrice > 0)
                    <del>&#2547;{{ number_format($oldPrice, 2) }}</del>
                @endif
            </p>
        </div>

        <div class="product-card-stock {{ $isOutOfStock ? 'is-out' : '' }}">
            {{ $isOutOfStock ? 'Stock out' : 'In stock' }}
        </div>

    </div>
</div>
