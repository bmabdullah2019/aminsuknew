@extends('frontEnd.layouts.master')
@section('title','Shopping Cart')
@push('seo')
    <meta name="robots" content="noindex, nofollow" />
    <meta name="description" content="Shopping Cart - {{ $generalsetting->name ?? config('app.name') }}" />
@endpush
@section('content')
<section class="breadcrumb-section">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="custom-breadcrumb">
                    <ul>
                        <li><a href="{{route('home')}}">Home</a></li>
                        <li><a><i class="fa-solid fa-angles-right"></i></a></li>
                        <li><a href="">Shopping Cart</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="vcart-section wc-commerce-page">
    @php
        $subtotal = (float) str_replace([',', '.00'], '', Cart::instance('shopping')->subtotal());
        view()->share('subtotal', $subtotal);
        $shipping = (float) (Session::get('shipping') ?: 0);
        $discount = (float) (Session::get('discount') ?: 0);
        $cartCount = Cart::instance('shopping')->count();
        $grandTotal = max(0, ($subtotal + $shipping) - $discount);
    @endphp

    <div class="container">
        <div class="wc-commerce-hero">
            <div>
                <span class="wc-commerce-kicker">Shopping Cart</span>
                <h1>Review your order</h1>
                <p>Update quantities, confirm items, then continue to secure checkout.</p>
            </div>
            <a href="{{route('shop')}}" class="wc-commerce-link">
                <i class="fa-solid fa-arrow-left"></i> Continue Shopping
            </a>
        </div>

        <div class="row g-4 align-items-start" id="cartlist">
            <div class="col-lg-8">
                <div class="wc-commerce-card">
                    <div class="wc-commerce-card-head">
                        <div>
                            <h2>Cart Items</h2>
                            <p>{{$cartCount}} item{{$cartCount === 1 ? '' : 's'}} in your bag</p>
                        </div>
                    </div>

                    @forelse($data as $value)
                        @php
                            $qty = (int) $value->qty;
                            $unitPrice = (float) $value->price;
                            $lineTotal = $unitPrice * $qty;
                            $cartImage = (string) ($value->options->image ?? 'public/frontEnd/images/no-image.jpg');
                            if (\Illuminate\Support\Str::startsWith($cartImage, 'storage/')) {
                                $cartImage = 'public/' . $cartImage;
                            } elseif (\Illuminate\Support\Str::startsWith($cartImage, 'uploads/')) {
                                $cartImage = 'public/' . $cartImage;
                            }
                        @endphp
                        <div class="wc-cart-line cart-item" data-product-id="{{$value->id}}" data-rowid="{{$value->rowId}}" data-qty="{{$qty}}">
                            <a href="{{ route('product', $value->options->slug) }}" class="wc-cart-thumb">
                                <img src="{{asset($cartImage)}}" alt="{{$value->name}}" />
                            </a>
                            <div class="wc-cart-info">
                                <a href="{{ route('product', $value->options->slug) }}" class="wc-cart-name cart_name">{{$value->name}}</a>
                                <div class="wc-cart-meta">
                                    @if($value->options->product_size)
                                        <span>Size: {{$value->options->product_size}}</span>
                                    @endif
                                    @if($value->options->product_color)
                                        <span>Color: {{$value->options->product_color}}</span>
                                    @endif
                                    <span>Unit: BDT {{number_format($unitPrice, 2)}}</span>
                                </div>
                            </div>
                            <div class="qty-cart vcart-qty wc-cart-qty">
                                <div class="quantity">
                                    <button type="button" class="minus cart_decrement" data-id="{{$value->rowId}}" aria-label="Decrease quantity">-</button>
                                    <input type="text" value="{{$qty}}" readonly data-qty="{{$qty}}" />
                                    <button type="button" class="plus cart_increment" data-id="{{$value->rowId}}" aria-label="Increase quantity">+</button>
                                </div>
                            </div>
                            <div class="wc-cart-price">
                                <strong>BDT {{number_format($lineTotal, 2)}}</strong>
                                <button type="button" class="remove-cart cart_remove" data-id="{{$value->rowId}}" aria-label="Remove {{$value->name}}">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="wc-empty-state">
                            <span><i class="fa-solid fa-cart-shopping"></i></span>
                            <h2>Your cart is empty</h2>
                            <p>Add products to your cart before checkout.</p>
                            <a href="{{route('shop')}}" class="wc-commerce-primary">Start Shopping</a>
                        </div>
                    @endforelse
                </div>

            </div>

            <div class="col-lg-4">
                <div class="cart-summary wc-commerce-summary">
                    <div class="wc-commerce-card-head">
                        <div>
                            <h2>Order Summary</h2>
                            <p>Calculated from current cart</p>
                        </div>
                    </div>
                    <div class="wc-summary-list">
                        <div><span>Items</span><strong>{{$cartCount}} qty</strong></div>
                        <div><span>Subtotal</span><strong>BDT {{number_format($subtotal, 2)}}</strong></div>
                        <div><span>Shipping</span><strong>BDT {{number_format($shipping, 2)}}</strong></div>
                        <div><span>Discount</span><strong>- BDT {{number_format($discount, 2)}}</strong></div>
                        <div class="wc-summary-total"><span>Total</span><strong>BDT {{number_format($grandTotal, 2)}}</strong></div>
                    </div>
                    <div class="checkout-button wc-commerce-actions">
                        <a href="{{route('customer.checkout')}}" class="wc-commerce-primary {{$cartCount <= 0 ? 'disabled' : ''}}">Proceed to Checkout</a>
                        <a href="{{route('shop')}}" class="wc-commerce-secondary">Continue Shopping</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
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
<script src="{{asset('public/frontEnd/')}}/js/parsley.min.js"></script>
<script src="{{asset('public/frontEnd/')}}/js/form-validation.init.js"></script>
<script>
$(document).ready(function() {
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }

    function setCookie(name, value, days) {
        const expires = new Date();
        expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = `${name}=${value};expires=${expires.toUTCString()};path=/;SameSite=Lax`;
    }

    function generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    function getDeviceId() {
        let deviceId = getCookie('partial_device_id_v1');
        if (!deviceId) {
            deviceId = generateUUID();
            setCookie('partial_device_id_v1', deviceId, 365);
        }
        return deviceId;
    }

    function getCartProducts() {
        const products = [];
        $('.cart-item').each(function() {
            products.push({
                id: $(this).data('product-id'),
                qty: parseInt($(this).data('qty'), 10) || 1
            });
        });
        return products;
    }

    function countFilledFields() {
        let count = 0;
        const name = ($('input[name="name"]').val() || '').trim();
        const phone = ($('input[name="phone"]').val() || '').trim();
        const address = ($('textarea[name="address"]').val() || '').trim();
        if (name !== '') count++;
        if (phone !== '') count++;
        if (address !== '') count++;
        return count;
    }

    function savePartialOrder() {
        if (countFilledFields() < 2) {
            return;
        }

        const products = getCartProducts();
        if (products.length === 0) {
            return;
        }

        $.ajax({
            url: '{{ route("partial.checkout.save") }}',
            method: 'POST',
            data: {
                device_id: getDeviceId(),
                name: $('input[name="name"]').val(),
                phone: $('input[name="phone"]').val(),
                address: $('textarea[name="address"]').val(),
                products: products
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}'
            }
        });
    }

    getDeviceId();
    setInterval(savePartialOrder, 10000);
    $(document).on('click', '.cart_remove', function() {
        setTimeout(savePartialOrder, 500);
    });
});
</script>
@endpush
