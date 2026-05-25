@extends('frontEnd.layouts.master')
@section('title','Checkout')
@section('content')
<section class="breadcrumb-section">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="custom-breadcrumb">
                    <ul>
                        <li><a href="{{route('home')}}">Home</a></li>
                        <li><a><i class="fa-solid fa-angles-right"></i></a></li>
                        <li><a href="{{route('cart.show')}}">Shopping Cart</a></li>
                        <li><a><i class="fa-solid fa-angles-right"></i></a></li>
                        <li><a href="">Checkout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="customer-section wc-commerce-page wc-checkout-page">
    <div class="container">
        @php
            $selectedAreaId = old('area', $summary['shipping_charge_id'] ?? optional($shippingcharge->first())->id);
            $lineMap = collect($summary['lines'] ?? [])->keyBy('row_id');
            $subtotalMinor = (int) ($summary['subtotal_minor'] ?? 0);
            $shippingMinor = (int) ($summary['shipping_minor'] ?? 0);
            $discountMinor = (int) ($summary['discount_minor'] ?? 0);
            $finalMinor = (int) ($summary['final_minor'] ?? 0);
            $cartItems = Cart::instance('shopping')->content();
            $cartCount = Cart::instance('shopping')->count();

            if ($subtotalMinor <= 0) {
                foreach ($cartItems as $item) {
                    $subtotalMinor += (int) round(((float) $item->price) * 100) * (int) $item->qty;
                }

                if ($shippingMinor <= 0) {
                    $shippingMinor = (int) round(((float) Session::get('shipping', 0)) * 100);
                }

                $discountMinor = (int) round(((float) Session::get('discount', 0)) * 100);
                $finalMinor = max(0, $subtotalMinor + $shippingMinor - $discountMinor);
            }

            $shippingService = app(\App\Services\ShippingService::class);
            $isWeightBased = false;
            if ($cartItems->isNotEmpty()) {
                $productIds = $cartItems->pluck('id')->map(fn ($id) => (int) $id)->unique()->values();
                $isWeightBased = \App\Models\Product::whereIn('id', $productIds)
                    ->where('shipping_type', 'weight_based')
                    ->exists();
            }

            $ratesData = [];
            foreach ($shippingcharge as $charge) {
                $zone = $shippingService->resolveZoneFromShippingChargeId($charge->id);
                $zoneName = $zone ? $zone->name : '';
                
                if ($isWeightBased) {
                    $minor = $shippingService->calculateForCart($cartItems, $charge->id) ?? 0;
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

        <div class="wc-commerce-hero">
            <div>
                <span class="wc-commerce-kicker">Checkout</span>
                <h1>Complete your order</h1>
                <p>Fill delivery details, choose payment, and place the order securely.</p>
            </div>
            <a href="{{route('cart.show')}}" class="wc-commerce-link">
                <i class="fa-solid fa-arrow-left"></i> Back to Cart
            </a>
        </div>

        <div class="wc-checkout-steps" aria-label="Checkout progress">
            <span class="active">1. Delivery</span>
            <span>2. Payment</span>
            <span>3. Confirmation</span>
        </div>

        <div class="customer-content checkout-shipping">
            <form action="{{route('customer.ordersave')}}" method="POST" data-parsley-validate="">
                @csrf
                <div class="row g-4 align-items-start">
                    <div class="col-lg-8">
                        <div class="wc-commerce-card wc-checkout-form-card">
                            <div class="wc-commerce-card-head">
                                <div>
                                    <h2>Delivery Details</h2>
                                    <p>Use a reachable phone number and complete address.</p>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="wc-field">
                                        <label for="checkout_name">Name *</label>
                                        <input id="checkout_name" type="text" name="name" class="form-control" value="{{ old('name', Auth::guard('customer')->user()->name ?? '') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="wc-field">
                                        <label for="checkout_phone">Phone *</label>
                                        <input id="checkout_phone" type="text" name="phone" class="form-control" value="{{ old('phone', Auth::guard('customer')->user()->phone ?? '') }}" required>
                                        <small>Repeated cancelled orders may be restricted for security.</small>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="wc-field">
                                        <label for="checkout_address">Address *</label>
                                        <textarea id="checkout_address" name="address" class="form-control" rows="3" required>{{ old('address', Auth::guard('customer')->user()->address ?? '') }}</textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="wc-field">
                                        <label for="area">Shipping *</label>
                                        <select id="area" name="area" class="form-control area" required>
                                            <option value="">Select Shipping</option>
                                            @foreach($shippingcharge as $charge)
                                                <option
                                                    value="{{$charge->id}}"
                                                    data-amount-minor="{{ (int) round(((float) $charge->amount) * 100) }}"
                                                    {{ (int) $selectedAreaId === (int) $charge->id ? 'selected' : '' }}
                                                >
                                                    {{$charge->name}} - BDT {{ number_format((float) $charge->amount, 2) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="wc-field">
                                        <label for="checkout_note">Order Note</label>
                                        <textarea id="checkout_note" name="note" class="form-control" rows="3" placeholder="Any special instructions...">{{ old('note') }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="wc-commerce-card wc-payment-card">
                            <div class="wc-commerce-card-head">
                                <div>
                                    <h2>Payment Method</h2>
                                    <p>Select how you want to pay.</p>
                                </div>
                            </div>

                            <div class="wc-payment-options">
                                <label class="wc-payment-option" for="cod">
                                    <input class="form-check-input" type="radio" name="payment_method" id="cod" value="cod" {{ old('payment_method', 'cod') === 'cod' ? 'checked' : '' }}>
                                    <span><i class="fa-solid fa-money-bill-wave"></i></span>
                                    <strong>Cash on Delivery</strong>
                                </label>
                                @if($bkash_gateway)
                                    <label class="wc-payment-option" for="bkash">
                                        <input class="form-check-input" type="radio" name="payment_method" id="bkash" value="bkash" {{ old('payment_method') === 'bkash' ? 'checked' : '' }}>
                                        <span><i class="fa-solid fa-mobile-screen-button"></i></span>
                                        <strong>bKash</strong>
                                    </label>
                                @endif
                                @if($shurjopay_gateway)
                                    <label class="wc-payment-option" for="shurjopay">
                                        <input class="form-check-input" type="radio" name="payment_method" id="shurjopay" value="shurjopay" {{ old('payment_method') === 'shurjopay' ? 'checked' : '' }}>
                                        <span><i class="fa-solid fa-credit-card"></i></span>
                                        <strong>ShurjoPay</strong>
                                    </label>
                                @endif
                            </div>

                            <div class="wc-secure-note">
                                <i class="fa-solid fa-shield-halved"></i>
                                <span>Online payments are processed securely. Only tokenized payment references are stored.</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="checkout-summary cart-summary wc-commerce-summary">
                            <div class="wc-commerce-card-head">
                                <div>
                                    <h2>Order Summary</h2>
                                    <p>{{$cartCount}} item{{$cartCount === 1 ? '' : 's'}} ready for checkout</p>
                                </div>
                            </div>

                            <div class="wc-checkout-items">
                                @forelse($cartItems as $value)
                                    @php
                                        $qty = (int) $value->qty;
                                        $line = $lineMap->get((string) $value->rowId);
                                        $unitMinor = (int) ($line['unit_minor'] ?? round(((float) $value->price) * 100));
                                        $lineTotalMinor = (int) ($line['line_total_minor'] ?? ($unitMinor * $qty));
                                        $cartImage = (string) ($value->options->image ?? 'public/frontEnd/images/no-image.jpg');
                                        if (\Illuminate\Support\Str::startsWith($cartImage, 'storage/')) {
                                            $cartImage = 'public/' . $cartImage;
                                        } elseif (\Illuminate\Support\Str::startsWith($cartImage, 'uploads/')) {
                                            $cartImage = 'public/' . $cartImage;
                                        }
                                    @endphp
                                    <div class="wc-checkout-item cart-item" data-product-id="{{ $value->id }}" data-qty="{{ $qty }}">
                                        <img src="{{asset($cartImage)}}" alt="{{$value->name}}">
                                        <div>
                                            <strong>{{\Illuminate\Support\Str::limit($value->name, 34)}}</strong>
                                            <span>Qty: {{$qty}}</span>
                                        </div>
                                        <b>BDT {{ number_format($lineTotalMinor / 100, 2) }}</b>
                                    </div>
                                @empty
                                    <div class="wc-empty-state compact">
                                        <span><i class="fa-solid fa-cart-shopping"></i></span>
                                        <p>Your cart is empty.</p>
                                    </div>
                                @endforelse
                            </div>

                            <div class="wc-summary-list">
                                <div>
                                    <span>Subtotal</span>
                                    <strong id="checkout-subtotal" data-minor="{{ $subtotalMinor }}">BDT {{ number_format($subtotalMinor / 100, 2) }}</strong>
                                </div>
                                <div>
                                    <span>Shipping</span>
                                    <strong id="checkout-shipping" data-minor="{{ $shippingMinor }}">BDT {{ number_format($shippingMinor / 100, 2) }}</strong>
                                </div>
                                <div>
                                    <span>Discount</span>
                                    <strong id="checkout-discount" data-minor="{{ $discountMinor }}">- BDT {{ number_format($discountMinor / 100, 2) }}</strong>
                                </div>
                                <div class="wc-summary-total">
                                    <span>Final Total</span>
                                    <strong id="checkout-final-total" data-minor="{{ $finalMinor }}">BDT {{ number_format($finalMinor / 100, 2) }}</strong>
                                </div>
                            </div>

                            <button type="submit" class="wc-commerce-primary wc-place-order" {{$cartCount <= 0 ? 'disabled' : ''}}>
                                Place Order
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection

@push('script')
<script src="{{asset('public/frontEnd/')}}/js/parsley.min.js"></script>
<script src="{{asset('public/frontEnd/')}}/js/form-validation.init.js"></script>
<script>
$(document).ready(function() {
    const csrfToken = $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}';
    const $areaSelect = $('select[name="area"]');
    const $subtotalEl = $('#checkout-subtotal');
    const $shippingEl = $('#checkout-shipping');
    const $discountEl = $('#checkout-discount');
    const $finalEl = $('#checkout-final-total');
    const moneyFormatter = new Intl.NumberFormat('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    function readMinor($el) {
        const value = parseInt($el.data('minor'), 10);
        return Number.isNaN(value) ? 0 : Math.max(0, value);
    }

    function formatMinorToBdt(minor) {
        return `BDT ${moneyFormatter.format((Math.max(0, minor) / 100))}`;
    }

    function renderCheckoutTotals(shippingMinor) {
        if (!$shippingEl.length || !$finalEl.length) {
            return;
        }

        const subtotalMinor = readMinor($subtotalEl);
        const discountMinor = readMinor($discountEl);
        const safeShippingMinor = Number.isNaN(shippingMinor) ? 0 : Math.max(0, shippingMinor);
        const finalMinor = Math.max(0, subtotalMinor + safeShippingMinor - discountMinor);

        $shippingEl.data('minor', safeShippingMinor).text(formatMinorToBdt(safeShippingMinor));
        $finalEl.data('minor', finalMinor).text(formatMinorToBdt(finalMinor));
    }

    function renderServerTotals(payload) {
        if (!payload || typeof payload !== 'object') {
            return;
        }

        if (typeof payload.subtotal_minor !== 'undefined') {
            const subtotalMinor = parseInt(payload.subtotal_minor, 10);
            if (!Number.isNaN(subtotalMinor)) {
                $subtotalEl.data('minor', subtotalMinor).text(formatMinorToBdt(subtotalMinor));
            }
        }

        if (typeof payload.discount_minor !== 'undefined') {
            const discountMinor = parseInt(payload.discount_minor, 10);
            if (!Number.isNaN(discountMinor)) {
                $discountEl.data('minor', discountMinor).text(`- ${formatMinorToBdt(discountMinor)}`);
            }
        }

        const shippingMinor = parseInt(payload.shipping_minor, 10);
        if (!Number.isNaN(shippingMinor)) {
            renderCheckoutTotals(shippingMinor);
        }
    }

    function getSelectedShippingMinor() {
        if (!$areaSelect.length) {
            return 0;
        }

        const selectedMinor = parseInt($areaSelect.find('option:selected').data('amountMinor'), 10);
        return Number.isNaN(selectedMinor) ? 0 : Math.max(0, selectedMinor);
    }

    function syncShippingToSession(areaId) {
        if (!areaId) {
            return;
        }

        $.ajax({
            url: '{{ route("shipping.charge") }}',
            method: 'GET',
            data: { id: areaId }
        });
    }

    function calculateShippingFromServer(areaId) {
        return $.ajax({
            url: '{{ route("api.shipping.calculate") }}',
            method: 'POST',
            dataType: 'json',
            data: { area: areaId },
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        });
    }

    function updateCheckoutTotalsFromArea() {
        if (!$areaSelect.length) {
            return;
        }

        const areaId = parseInt($areaSelect.val(), 10);
        renderCheckoutTotals(getSelectedShippingMinor());
        if (!Number.isNaN(areaId) && areaId > 0) {
            calculateShippingFromServer(areaId)
                .done(renderServerTotals)
                .fail(function() {
                    syncShippingToSession(areaId);
                });
        }
    }

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
        if (($('input[name="name"]').val() || '').trim() !== '') count++;
        if (($('input[name="phone"]').val() || '').trim() !== '') count++;
        if (($('textarea[name="address"]').val() || '').trim() !== '') count++;
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
            headers: { 'X-CSRF-TOKEN': csrfToken }
        });
    }

    function loadPartialOrder() {
        $.ajax({
            url: '{{ route("partial.checkout.load") }}',
            method: 'GET',
            data: { device_id: getDeviceId() },
            success: function(response) {
                if (response && response.name) {
                    $('input[name="name"]').val(response.name);
                    $('input[name="phone"]').val(response.phone);
                    $('textarea[name="address"]').val(response.address);
                }
            }
        });
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
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
        
        var currentSelectVal = $areaSelect.val() || '{{ $selectedAreaId }}';
        
        // Rebuild options
        $areaSelect.empty();
        
        // Add default placeholder option
        $areaSelect.append($('<option value="">Select Shipping</option>'));
        
        var anySelected = false;
        var firstAllowedId = null;
        
        $.each(rates, function(id, data) {
            if (data.is_allowed) {
                if (firstAllowedId === null) {
                    firstAllowedId = id;
                }
                
                var label = data.name;
                var rateVal = parseFloat(data.rate);
                var formattedRate = (rateVal % 1 === 0) ? parseInt(rateVal) : rateVal.toFixed(2);
                
                if (isWeightBased) {
                    label = data.name + " - BDT " + formattedRate;
                } else {
                    label = data.name;
                }
                
                var option = $('<option></option>')
                    .val(id)
                    .text(label)
                    .attr('data-amount-minor', Math.round(rateVal * 100));
                
                if (id == currentSelectVal) {
                    option.attr('selected', 'selected');
                    anySelected = true;
                }
                $areaSelect.append(option);
            }
        });
        
        if (!anySelected && firstAllowedId !== null && currentSelectVal !== '') {
            $areaSelect.val(firstAllowedId);
            updateCheckoutTotalsFromArea();
        }
    }

    loadPartialOrder();
    updateShippingDropdown();
    updateCheckoutTotalsFromArea();
    setInterval(savePartialOrder, 10000);

    const debouncedSave = debounce(savePartialOrder, 2000);
    $('input[name="name"], input[name="phone"], textarea[name="address"]').on('keyup', debouncedSave);
    $('select[name="area"], textarea[name="note"]').on('change', debouncedSave);
    $areaSelect.on('change', updateCheckoutTotalsFromArea);
});
</script>
@endpush
