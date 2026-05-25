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
                                                        <select id="area" class="form-control @error('area') is-invalid @enderror" name="area" required>
                                                            @foreach($shippingcharge as $shipping)
                                                            <option value="{{ $shipping->id }}">{{ $shipping->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        @error('area')
                                                        <span class="invalid-feedback">{{ $message }}</span>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="col-sm-12">
                                                    <div class="form-group">
                                                        <button class="order_place" type="submit">অর্ডার কন্ফার্ম করুন</button>
                                                    </div>
                                                </div>
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
