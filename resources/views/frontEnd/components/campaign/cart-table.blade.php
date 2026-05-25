@php
    $items = $data ?? Cart::instance('shopping')->content();

    $shippingService = app(\App\Services\ShippingService::class);
    $shippingCharges = \App\Models\ShippingCharge::where('status', 1)->get();
    
    // Determine if weight-based
    $isWeightBased = false;
    if ($items->isNotEmpty()) {
        $productIds = $items->pluck('id')->map(fn ($id) => (int) $id)->unique()->values();
        $isWeightBased = \App\Models\Product::whereIn('id', $productIds)
            ->where('shipping_type', 'weight_based')
            ->exists();
    }

    $ratesData = [];
    foreach ($shippingCharges as $charge) {
        $zone = $shippingService->resolveZoneFromShippingChargeId($charge->id);
        $zoneName = $zone ? $zone->name : '';
        
        if ($isWeightBased) {
            $minor = $shippingService->calculateForCart($items, $charge->id) ?? 0;
            $rate = \App\Support\Money::toMajorFloat($minor);
            $ratesData[$charge->id] = [
                'rate' => $rate,
                'name' => $zoneName ?: $charge->name,
                'is_allowed' => (bool) \Illuminate\Support\Str::contains(\Illuminate\Support\Str::lower($zoneName), 'dhaka')
            ];
        } else {
            $lowerName = \Illuminate\Support\Str::lower($charge->name);
            $isInsideDhaka = \Illuminate\Support\Str::contains($lowerName, ['inside', 'vitor', 'ভিতরে']) && !\Illuminate\Support\Str::contains($lowerName, ['outside', 'baire', 'বাহিরে', 'বাইরে']);
            $isOutsideDhaka = \Illuminate\Support\Str::contains($lowerName, ['outside', 'baire', 'বাহিরে', 'বাইরে']);
            
            $ratesData[$charge->id] = [
                'rate' => (float) $charge->amount,
                'name' => $charge->name,
                'is_allowed' => (bool) ($isInsideDhaka || $isOutsideDhaka)
            ];
        }
    }
@endphp

<!-- Cart Shipping Metadata -->
<div id="cart-shipping-metadata" 
     data-is-weight-based="{{ $isWeightBased ? 'true' : 'false' }}" 
     data-rates='@json($ratesData)' 
     style="display: none;"></div>

<!-- Cart Items Table -->
<table class="cart_table table table-bordered table-striped text-center mb-0">
    <thead>
        <tr>
            <th style="width: 40%;">প্রোডাক্ট</th>
            <th style="width: 20%;">পরিমাণ</th>
            <th style="width: 20%;">মূল্য</th>
        </tr>
    </thead>
    <tbody>
        @forelse($items as $item)
        <tr data-product-id="{{ (int) $item->id }}">
            <td class="text-left">
                @php
                    $cartImage = (string) ($item->options->image ?? 'public/frontEnd/images/no-image.jpg');
                    if (\Illuminate\Support\Str::startsWith($cartImage, 'storage/')) {
                        $cartImage = 'public/' . $cartImage;
                    } elseif (\Illuminate\Support\Str::startsWith($cartImage, 'uploads/')) {
                        $cartImage = 'public/' . $cartImage;
                    }
                @endphp
                <a class="campaign-cart-product" href="{{ route('product', $item->options->slug) }}">
                    <img
                        class="campaign-cart-product-image"
                        src="{{ asset($cartImage) }}"
                        alt="{{ $item->name }}"
                    >
                    <span>{{ Str::limit($item->name, 20) }}</span>
                </a>
                @php
                    $product = App\Models\Product::find($item->id);
                @endphp

                @if($product && ($product->sizes->isNotEmpty() || $product->colors->isNotEmpty()))
                <div class="row g-1 mt-2 campaign-cart-selectors">
                    @if($product->sizes->isNotEmpty())
                    <div class="col-6">
                        <select id="size-selector-{{ $item->rowId }}" class="form-select form-select-sm cart-size-selector" data-id="{{ $item->rowId }}">
                            <option>Select Size</option>
                            @foreach($product->sizes as $size)
                            <option value="{{ $size->sizeName }}" {{ $size->sizeName == $item->options->product_size ? 'selected' : '' }}>
                                {{ $size->sizeName }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    @if($product->colors->isNotEmpty())
                    <div class="col-6">
                        <select id="color-selector-{{ $item->rowId }}" class="form-select form-select-sm cart-color-selector" data-id="{{ $item->rowId }}">
                            <option>Select Color</option>
                            @foreach($product->colors as $color)
                            <option value="{{ $color->colorName }}" {{ $color->colorName == $item->options->product_color ? 'selected' : '' }}>
                                {{ $color->colorName }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                </div>
                @endif
            </td>
            <td width="15%" class="cart_qty">
                <div class="qty-cart vcart-qty">
                    <div class="quantity">
                        <button class="minus cart_decrement" data-id="{{ $item->rowId }}">-</button>
                        <input type="text" value="{{ $item->qty }}" readonly />
                        <button class="plus cart_increment" data-id="{{ $item->rowId }}">+</button>
                    </div>
                </div>
            </td>
            <td>৳{{ $item->price * $item->qty }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="3" class="text-center text-muted py-4">কোন পণ্য নেই</td>
        </tr>
        @endforelse
    </tbody>
    <tfoot>
        @php
            $subtotal = Cart::instance('shopping')->subtotal();
            $subtotal = str_replace(',', '', $subtotal);
            $subtotal = str_replace('.00', '', $subtotal);
            $shipping = Session::get('shipping') ?: 0;
        @endphp
        <tr>
            <th colspan="2" class="text-end px-4">মোট</th>
            <td>
                <span id="net_total"><span class="alinur">৳ </span><strong>{{ $subtotal }}</strong></span>
            </td>
        </tr>
        <tr>
            <th colspan="2" class="text-end px-4">ডেলিভারি চার্জ</th>
            <td>
                @if($isWeightBased)
                    <span id="cart_shipping_cost" class="text-muted" style="font-size:.82rem">এলাকা অনুযায়ী</span>
                @else
                    <span id="cart_shipping_cost"><span class="alinur">৳ </span><strong>{{ $shipping }}</strong></span>
                @endif
            </td>
        </tr>
        <tr>
            <th colspan="2" class="text-end px-4">সর্বমোট</th>
            <td>
                @if($isWeightBased)
                    <span id="grand_total"><span class="alinur">৳ </span><strong id="cart-grand-total-val">{{ $subtotal }}</strong></span>
                @else
                    <span id="grand_total"><span class="alinur">৳ </span><strong id="cart-grand-total-val">{{ $subtotal + $shipping }}</strong></span>
                @endif
            </td>
        </tr>
    </tfoot>
</table>
