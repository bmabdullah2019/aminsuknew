<!-- Order Form Section -->
<section class="form_sec">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="form_inn">
                    <div class="col-sm-12">
                        <h2 class="campaign_offer">অফারটি সীমিত সময়ের জন্য, তাই অফার শেষ হওয়ার আগেই অর্ডার করুন</h2>
                        @if($campaign->note)
                        <p class="my-1 text-center">{!! $campaign->note !!}</p>
                        @endif
                    </div>

                    <div class="row order_by">
                        <!-- Cart Details Column -->
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
                                <!-- Product Selection -->
                                @if($products->count() > 1)
                                <div class="card mb-2">
                                    <div class="card-header">
                                        <h5 class="potro_font">একটি পণ্য সিলেক্ট করুন</h5>
                                    </div>  
                                    <div class="card-body">
                                        <div class="row g-2">
                                            @foreach($products as $product)
                                            @php
                                                $isSelected = in_array((int) $product->id, $selectedProductIds, true);
                                            @endphp
                                            <div class="col-md-3 col-6">
                                                <div class="border shadow">
                                                    <input type="checkbox" class="form-check-input campaign-product-input" name="products[]" id="product_{{ $product->id }}" value="{{ $product->id }}" {{ $isSelected ? 'checked' : '' }} style="display: none;" onchange="updateCart('{{ $product->id }}')">
                                                    <label for="product_{{ $product->id }}" class="card shadow-sm product-card {{ $isSelected ? 'selected' : '' }}" style="cursor: pointer;">
                                                        <img src="{{ asset($product->display_image) }}" class="card-img-top" alt="{{ $product->name }}" style="height: 100px; object-fit: cover;">
                                                        <div class="card-body p-1 text-center">
                                                            <div class="card-title">{{ Str::limit($product->name, 20) }}</div>
                                                            <div class="card-text mb-1">৳{{ $product->new_price }} <del>৳{{ $product->old_price }}</del></div>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <!-- Cart Items Table -->
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="potro_font">পণ্যের বিবরণ</h5>
                                    </div>
                                    <div class="card-body cartlist table-responsive">
                                        @include('frontEnd.components.campaign.cart-table')
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Order Form Column -->
                        <div class="col-lg-5 cus-order-2">
                            <div class="checkout-shipping" id="order_form">
                                <form action="{{ route('customer.ordersave') }}" method="POST" data-parsley-validate="">
                                    @csrf
                                    <input type="hidden" name="payment_method" value="cod">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="potro_font">আপনার ইনফরমেশন দিন</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-sm-12">
                                                    <div class="form-group mb-3">
                                                        <label for="name">আপনার নাম লিখুন *</label>
                                                        <input type="text" id="name" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" placeholder="নাম" required>
                                                        @error('name')
                                                        <span class="invalid-feedback">{{ $message }}</span>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="col-sm-12">
                                                    <div class="form-group mb-3">
                                                        <label for="phone">আপনার মোবাইল লিখুন *</label>
                                                        <input type="text" minlength="11" maxlength="11" pattern="0[0-9]+" id="phone" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone') }}" placeholder="+৮৮ বাদে ১১ সংখ্যা" required>
                                                        @error('phone')
                                                        <span class="invalid-feedback">{{ $message }}</span>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="col-sm-12">
                                                    <div class="form-group mb-3">
                                                        <label for="address">আপনার ঠিকানা লিখুন *</label>
                                                        <input type="text" id="address" class="form-control @error('address') is-invalid @enderror" placeholder="জেলা, থানা, গ্রাম" name="address" value="{{ old('address') }}" required>
                                                        @error('address')
                                                        <span class="invalid-feedback">{{ $message }}</span>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="col-sm-12">
                                                    <div class="form-group mb-3">
                                                        <label for="area">শিপিং সিলেক্ট করুন *</label>
                                                        @php
                                                            $lpShipSvc  = app(\App\Services\ShippingService::class);
                                                            $lpCart     = Cart::instance('shopping')->content();
                                                            $lpIsWeight = $lpShipSvc->isCartWeightBased($lpCart);
                                                            $lpSubtotal = $lpCart->sum(fn($i) => (float)$i->price * (int)$i->qty);
                                                        @endphp
                                                        <select id="area" class="form-control @error('area') is-invalid @enderror" name="area" required>
                                                            @foreach($shippingcharge as $shipping)
                                                            <option value="{{ $shipping->id }}" data-amount="{{ $shipping->amount }}">
                                                                @if($lpIsWeight)
                                                                    {{ $shipping->name }}
                                                                @else
                                                                    {{ $shipping->name }} - ৳{{ number_format((float)$shipping->amount, 0) }}
                                                                @endif
                                                            </option>
                                                            @endforeach
                                                        </select>
                                                        @error('area')
                                                        <span class="invalid-feedback">{{ $message }}</span>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <!-- Price Breakdown -->
                                                <div class="col-sm-12">
                                                    <div style="background:#f8f9fa;border-radius:6px;padding:12px 16px;font-size:.88rem;margin-bottom:12px">
                                                        <div style="display:flex;justify-content:space-between;margin-bottom:5px">
                                                            <span>পণ্য মূল্য</span>
                                                            <strong id="lp-subtotal">৳{{ number_format($lpSubtotal, 0) }}</strong>
                                                        </div>
                                                        <div style="display:flex;justify-content:space-between;margin-bottom:5px" id="lp-shipping-row">
                                                            <span id="lp-shipping-label">ডেলিভারি চার্জ</span>
                                                            <strong id="lp-shipping">৳0</strong>
                                                        </div>
                                                        <div style="display:flex;justify-content:space-between;padding-top:8px;border-top:1px solid #dee2e6">
                                                            <strong>সর্বমোট</strong>
                                                            <strong id="lp-total" style="color:#dc3545">৳{{ number_format($lpSubtotal, 0) }}</strong>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-sm-12">
                                                    <div class="form-group">
                                                        <button class="order_place" type="submit">অর্ডার কন্ফার্ম করুন</button>
                                                    </div>
                                                </div>

                                                <script>
                                                (function(){
                                                    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
                                                    var csrf = csrfMeta ? csrfMeta.content : '{{ csrf_token() }}';
                                                    var calcUrl = '{{ route("api.shipping.calculate") }}';
                                                    var areaEl  = document.getElementById('area');
                                                    var isWB    = {{ $lpIsWeight ? 'true' : 'false' }};

                                                    function fmt(v) {
                                                        return '৳' + Math.round(parseFloat(v)).toLocaleString('en-BD');
                                                    }
                                                    function applyDisplay(wb) {
                                                        var el = document.getElementById('lp-shipping');
                                                        if (el) el.style.display = wb ? 'none' : '';
                                                    }
                                                    function recalc() {
                                                        if (!areaEl || !areaEl.value) return;
                                                        var fd = new FormData();
                                                        fd.append('_token', csrf);
                                                        fd.append('area', areaEl.value);
                                                        fetch(calcUrl, {
                                                            method: 'POST',
                                                            headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'},
                                                            body: fd
                                                        })
                                                        .then(function(r){ return r.json(); })
                                                        .then(function(res) {
                                                            if (!res.success) return;
                                                            isWB = !!res.is_weight_based;
                                                            var subEl  = document.getElementById('lp-subtotal');
                                                            var shipEl = document.getElementById('lp-shipping');
                                                            var totEl  = document.getElementById('lp-total');
                                                            if (subEl)  subEl.textContent  = fmt(res.subtotal_minor / 100);
                                                            if (shipEl) shipEl.textContent = fmt(res.shipping_minor / 100);
                                                            if (totEl)  totEl.textContent  = fmt(res.final_minor / 100);
                                                            applyDisplay(isWB);
                                                        })
                                                        .catch(function(){});
                                                    }
                                                    if (areaEl) {
                                                        areaEl.addEventListener('change', recalc);
                                                        applyDisplay(isWB);
                                                        if (areaEl.value) recalc();
                                                    }
                                                })();
                                                </script>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            @if($campaign->billing_details)
                            <p class="my-1 text-center">{!! $campaign->billing_details !!}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
