@php
    $cartItems = Cart::instance('shopping')->content();
    $cartCount = Cart::instance('shopping')->count();
    $subtotal = Cart::instance('shopping')->subtotal();
    $subtotal = str_replace(',', '', $subtotal);
    $subtotal = str_replace('.00', '', $subtotal);
@endphp

<div class="sellzy-cart-action" id="cart-qty">
    <a href="{{ route('cart.show') }}" class="sellzy-action sellzy-cart-link">
        <span class="sellzy-action-icon">
            <i class="fa-solid fa-cart-shopping"></i>
            <b class="sellzy-cart-badge cart-qty-count">{{ $cartCount }}</b>
        </span>
        <span class="sellzy-cart-copy">
            <small>Cart</small>
            <b class="cart-qty-count">{{ $cartCount }}</b>- Items
        </span>
    </a>

    <div class="cshort-summary sellzy-cart-summary">
        @if($cartItems->count())
            <ul>
                @foreach($cartItems as $value)
                    @php
                        $cartImage = (string) ($value->options->image ?? 'public/frontEnd/images/no-image.jpg');
                        if (\Illuminate\Support\Str::startsWith($cartImage, 'storage/')) {
                            $cartImage = 'public/' . $cartImage;
                        } elseif (\Illuminate\Support\Str::startsWith($cartImage, 'uploads/')) {
                            $cartImage = 'public/' . $cartImage;
                        }
                    @endphp
                    <li>
                        <a href="{{ route('product', $value->options->slug) }}">
                            <img src="{{ asset($cartImage) }}" alt="{{ $value->name }}">
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('product', $value->options->slug) }}">{{ Str::limit($value->name, 30) }}</a>
                    </li>
                    <li>Qty: {{ $value->qty }}</li>
                    <li>
                        <p>৳{{ $value->price }}</p>
                        <button type="button" class="remove-cart cart_remove" data-id="{{ $value->rowId }}">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </li>
                @endforeach
            </ul>
            <p><strong>সর্বমোট : ৳{{ $subtotal }}</strong></p>
            <a href="{{ route('customer.checkout') }}" class="go_cart">অর্ডার করুন</a>
        @else
            <div class="sellzy-mini-cart-empty">
                <span><i class="fa-solid fa-cart-shopping"></i></span>
                <p>Your cart is empty</p>
            </div>
            <a href="{{ route('shop') }}" class="go_cart">Shop Now</a>
        @endif
    </div>
</div>
