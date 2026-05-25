@extends('frontEnd.layouts.master')
@section('title','Order Success')

@push('css')
<style>
    .order-success-wrapper {
        background: var(--wc-surface-soft, #f8fcfc);
        padding: 40px 0;
        font-family: inherit;
    }
    .success-card {
        background: var(--wc-surface, #fff);
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        padding: 30px;
        margin-bottom: 20px;
    }
    .success-header {
        text-align: center;
        margin-bottom: 30px;
    }
    .success-header h2 {
        color: var(--wc-primary, #008f88);
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 10px;
    }
    .success-header p {
        color: var(--wc-muted, #666);
        font-size: 16px;
    }
    .reconfirm-box {
        background: var(--color-primary-light, #eaf7f7);
        border: 1px dashed var(--wc-primary, #008f88);
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        margin-bottom: 30px;
    }
    .reconfirm-box h4 {
        color: var(--wc-primary-strong, #1b2c40);
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 5px;
    }
    .reconfirm-box p {
        color: var(--wc-text, #333);
        margin-bottom: 15px;
        font-size: 14px;
    }
    .btn-reconfirm {
        background: #ff4d4d;
        color: #fff;
        padding: 10px 25px;
        border-radius: 5px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        border: none;
    }
    .btn-reconfirm:hover {
        background: #e60000;
        color: #fff;
    }
    .policy-box {
        text-align: center;
        margin-bottom: 30px;
    }
    .policy-box h4 {
        font-size: 18px;
        font-weight: 700;
        color: var(--wc-text);
        margin-bottom: 15px;
    }
    .policy-box p {
        font-size: 14px;
        color: var(--wc-muted);
        line-height: 1.6;
        margin-bottom: 10px;
    }
    .policy-box h5 {
        font-size: 16px;
        font-weight: 600;
        color: var(--wc-text);
        margin-top: 15px;
        margin-bottom: 10px;
    }
    .contact-links {
        display: flex;
        justify-content: center;
        gap: 15px;
        margin-top: 20px;
        flex-wrap: wrap;
    }
    .contact-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 100px;
        height: 100px;
        border: 1px solid var(--wc-border, #eee);
        border-radius: 10px;
        background: var(--wc-surface, #fff);
        text-decoration: none;
        color: var(--wc-text);
        gap: 10px;
        transition: all 0.3s;
    }
    .contact-btn:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        border-color: var(--wc-primary);
        color: var(--wc-primary);
    }
    .contact-btn i {
        font-size: 28px;
    }
    .contact-btn.fb { color: #1877F2; }
    .contact-btn.msg { color: #00B2FF; }
    .contact-btn.call { color: var(--wc-primary); }
    .contact-btn.wa { color: #25D366; }
    
    .order-details-card {
        background: var(--wc-surface, #fff);
        border: 1px solid var(--wc-border, #eee);
        border-radius: 12px;
        padding: 25px;
    }
    .order-header-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        border-bottom: 1px solid var(--wc-border);
        padding-bottom: 15px;
    }
    .order-header-info h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
    }
    .order-header-info span {
        color: var(--wc-muted);
        font-size: 14px;
    }
    .total-badge {
        background: var(--wc-primary, #4DBC60);
        color: #fff;
        padding: 5px 15px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 14px;
    }
    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 25px;
    }
    .info-block h5 {
        font-size: 14px;
        color: var(--wc-muted);
        margin-bottom: 15px;
        font-weight: 600;
    }
    .info-table {
        width: 100%;
        font-size: 14px;
    }
    .info-table td {
        padding: 5px 0;
        vertical-align: top;
    }
    .info-table td:first-child {
        color: var(--wc-muted);
        width: 100px;
    }
    .info-table td:last-child {
        font-weight: 600;
        color: var(--wc-text);
    }
    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    .items-table th {
        background: var(--wc-surface-soft, #f9f9f9);
        padding: 12px;
        text-align: left;
        font-size: 13px;
        color: var(--wc-muted);
        font-weight: 600;
    }
    .items-table td {
        padding: 15px 12px;
        border-bottom: 1px solid var(--wc-border);
        vertical-align: middle;
    }
    .item-product {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .item-product img {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid var(--wc-border);
    }
    .summary-table {
        width: 100%;
        margin-top: 20px;
    }
    .summary-table td {
        padding: 10px 12px;
        border-bottom: 1px solid var(--wc-border);
    }
    .summary-table tr:last-child td {
        border-bottom: none;
        font-weight: 700;
        font-size: 16px;
    }
    .summary-table td:last-child {
        text-align: right;
        font-weight: 600;
    }
    .action-buttons {
        display: flex;
        gap: 15px;
        margin-top: 20px;
    }
    .btn-action {
        flex: 1;
        padding: 12px;
        text-align: center;
        background: var(--wc-primary);
        color: #fff;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: background 0.3s;
        border: none;
    }
    .btn-action:hover {
        background: var(--wc-primary-strong);
        color: #fff;
    }
    .guarantee-box {
        text-align: center;
        margin-top: 40px;
        padding-top: 30px;
        border-top: 1px dashed var(--wc-border);
    }
    .guarantee-box h4 {
        color: var(--wc-primary);
        font-weight: 700;
        margin-bottom: 20px;
    }
    .guarantee-grid {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 15px;
    }
    .guarantee-item {
        background: var(--wc-surface-soft);
        border: 1px solid var(--color-primary-light, #e0f2f1);
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 13px;
        color: var(--wc-text);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .guarantee-item i {
        color: var(--wc-primary);
    }
    
    @media (max-width: 768px) {
        .info-grid {
            grid-template-columns: 1fr;
        }
        .action-buttons {
            flex-direction: column;
        }
        .items-table th:nth-child(2), .items-table td:nth-child(2) {
            display: none;
        }
        .success-header h2 {
            font-size: 20px;
        }
        .order-header-info {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
    }
</style>
@endpush

@section('content')
@php
    $pixelPurchaseContentIds = $order->orderdetails->pluck('product_id')->map(fn ($id) => (string) $id)->values()->all();
    $pixelPurchaseItemCount = (int) $order->orderdetails->sum('qty');
    $pixelPurchaseValue = number_format((float) $order->amount, 2, '.', '');
    $pixelPurchaseCurrency = strtoupper((string) ($order->currency ?: 'BDT'));
    $pixelPurchaseEventId = 'order-' . (int) $order->id;
    
    // GTM DataLayer Items
    $gtmItems = $order->orderdetails->map(function ($item) {
        return [
            'item_id' => (string) $item->product_id,
            'item_name' => $item->product_name,
            'price' => (float) $item->sale_price,
            'quantity' => (int) $item->qty,
            'item_variant' => trim(($item->product_color ? $item->product_color . ' ' : '') . ($item->product_size ?: ''))
        ];
    })->values()->all();
@endphp

<section class="order-success-wrapper">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                
                <!-- Success Message Card -->
                <div class="success-card">
                    <div class="success-header">
                        <h2>🎉 আলহামদুলিল্লাহ, আপনার অর্ডারটি সফলভাবে গ্রহণ করা হয়েছে</h2>
                        <p>{{ $generalsetting->name }} পরিবারের সাথে থাকার জন্য জাজাকাল্লাহু খাইরান 🤲</p>
                    </div>

                    <div class="reconfirm-box">
                        <h4><i class="fa-solid fa-phone-volume"></i> কলের অপেক্ষা নয়, এখনই রি-কনফার্ম করুন আপনি নিজেই!</h4>
                        <p>দ্রুত ডেলিভারি পেতে নিচের বাটনে ক্লিক করুন।</p>
                        <a href="https://api.whatsapp.com/send?phone={{ $contact->whatsapp ?? '' }}&text=I%20want%20to%20confirm%20my%20order%20ID:%20{{$order->invoice_id}}" target="_blank" class="btn-reconfirm">
                            <i class="fa-brands fa-whatsapp"></i> Re-Confirm Order
                        </a>
                        <p class="mt-2 mb-0" style="font-size:12px; color:var(--wc-muted);"><i class="fa-solid fa-check-circle text-success"></i> কনফার্ম করলেই অর্ডার দ্রুত কুরিয়ারে যাবে, ইনশাআল্লাহ। <br> <i class="fa-solid fa-truck"></i> অথবা রি-কনফার্ম করতে আমাদের কলসেন্টার থেকে কলের অপেক্ষা করুন।</p>
                    </div>

                    <div class="policy-box">
                        <h4>পণ্য বিনিময় এবং ফেরত নীতিমালা (Exchange & Refund Policy)</h4>
                        <p>আমাদের আন্তরিকতা আপনাকে সর্বোচ্চ নিশ্চিন্ত করার জন্য প্রতিশ্রুতিবদ্ধ। আমাদের পণ্য ও ডেলিভারি সেবার মাধ্যমে আমরা আপনাকে সর্বোচ্চ মানের অভিজ্ঞতা প্রদানের চেষ্টা করি। যদি কোনো কারণে আপনি পণ্য ফেরত বা বিনিময় করতে চান, আমরা একটি সহজ এবং কার্যকরী পদ্ধতি তৈরি করেছি। অনুগ্রহ করে নিচের নীতিমালা অনুসরণ করুন:</p>
                        
                        <h5>১. বিনিময়ের শর্তাবলী</h5>
                        <p style="font-size: 13px;">পণ্যটি যদি ছেঁড়া বা ভাঙা থাকে, বা পণ্যের গুণগত মান নিয়ে আপনি অসন্তুষ্ট হয়ে থাকেন সেক্ষত্রে আপনি পণ্য বিনিময়ের অনুরোধ করতে পারেন।<br>
                        পণ্য গ্রহণের ২৪ ঘণ্টার মধ্যে বিনিময়ের জন্য অনুরোধ করা যাবে।</p>
                    </div>

                    <div class="text-center mt-4 border-top pt-4">
                        <h4 style="font-size:16px; font-weight:600; color:var(--wc-text);">যোগাযোগ করুন</h4>
                        <p style="font-size:13px; color:var(--wc-muted);">যেকোনো প্রয়োজনে আমাদের সাথে যোগাযোগ করুন</p>
                        
                        <div class="contact-links">
                            @if(!empty($contact->facebook))
                            <a href="{{ $contact->facebook }}" target="_blank" class="contact-btn fb">
                                <i class="fa-brands fa-facebook"></i>
                                <span>Facebook</span>
                            </a>
                            <a href="https://m.me/{{ preg_replace('/^https?:\/\/(www\.)?facebook\.com\//', '', $contact->facebook) }}" target="_blank" class="contact-btn msg">
                                <i class="fa-brands fa-facebook-messenger"></i>
                                <span>Messenger</span>
                            </a>
                            @endif
                            <a href="tel:{{ $contact->hotline }}" class="contact-btn call">
                                <i class="fa-solid fa-phone"></i>
                                <span>Call</span>
                            </a>
                            <a href="https://api.whatsapp.com/send?phone={{ $contact->whatsapp ?? '' }}&text=Hello" target="_blank" class="contact-btn wa">
                                <i class="fa-brands fa-whatsapp"></i>
                                <span>WhatsApp</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Order Details Card -->
                <div class="order-details-card mb-4">
                    <div class="order-header-info">
                        <div>
                            <h3>Order Id: {{ $order->invoice_id }}</h3>
                            <span>{{ $order->created_at->format('M d, Y') }} ({{ $order->created_at->diffForHumans() }})</span>
                        </div>
                        <div class="total-badge">
                            Total ৳{{ number_format($order->amount, 0) }}
                        </div>
                    </div>

                    <div class="info-grid border-bottom pb-4 mb-4">
                        <div class="info-block">
                            <h5>Customer Information</h5>
                            <table class="info-table">
                                <tr>
                                    <td>Name:</td>
                                    <td>{{ $order->shipping ? $order->shipping->name : '' }}</td>
                                </tr>
                                <tr>
                                    <td>Phone:</td>
                                    <td>{{ $order->shipping ? $order->shipping->phone : '' }}</td>
                                </tr>
                                <tr>
                                    <td>Address:</td>
                                    <td>{{ $order->shipping ? $order->shipping->address : '' }}</td>
                                </tr>
                                <tr>
                                    <td>Area:</td>
                                    <td>{{ $order->shipping ? $order->shipping->area : '' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="info-block">
                            <h5>Order Information</h5>
                            <table class="info-table">
                                <tr>
                                    <td>Payment:</td>
                                    <td><span class="badge" style="background:var(--color-warning); color:#fff; padding:3px 8px; border-radius:4px;">{{ optional($order->payment)->payment_method ?: 'Cash on delivery' }}</span></td>
                                </tr>
                                <tr>
                                    <td>Delivery:</td>
                                    <td>{{ $order->shipping ? $order->shipping->area : 'Inside Dhaka' }}</td>
                                </tr>
                                <tr>
                                    <td>Delivery Charge:</td>
                                    <td>৳ {{ $order->shipping_charge }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <h5 style="font-size:15px; font-weight:600; margin-bottom:15px;">Order items</h5>
                    <div class="table-responsive">
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->orderdetails as $key=>$value)
                                <tr>
                                    <td>
                                        <div class="item-product">
                                            @php
                                                $itemImage = asset($value->product?->display_image ?? 'public/frontEnd/images/no-image.jpg');
                                            @endphp
                                            <img src="{{ $itemImage }}" alt="{{ $value->product_name }}">
                                            <div>
                                                <span style="font-weight:600; color:var(--wc-text);">{{ $value->product_name }}</span>
                                                @if($value->product_size || $value->product_color)
                                                <div style="font-size:12px; color:var(--wc-muted); margin-top:3px;">
                                                    @if($value->product_size) Size: {{ $value->product_size }} @endif
                                                    @if($value->product_size && $value->product_color) | @endif
                                                    @if($value->product_color) Color: {{ $value->product_color }} @endif
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">{{ $value->qty }}</td>
                                    <td class="text-end">৳{{ $value->sale_price * $value->qty }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <table class="summary-table">
                        <tr>
                            <td>Subtotal</td>
                            <td>৳{{ $order->amount + $order->discount - $order->shipping_charge }}</td>
                        </tr>
                        <tr>
                            <td>Delivery charge</td>
                            <td>৳{{ $order->shipping_charge }}</td>
                        </tr>
                        @if($order->discount)
                        <tr>
                            <td>Discount</td>
                            <td style="color:var(--color-primary);">(-) ৳{{ $order->discount }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td>Total</td>
                            <td style="color:var(--wc-primary);">৳{{ $order->amount }}</td>
                        </tr>
                    </table>

                    <div class="action-buttons">
                        <a href="{{ route('customer.order_track') }}" class="btn-action">
                            <i class="fa-solid fa-truck-fast"></i> Track your order
                        </a>
                        <a href="{{ route('contact') }}" class="btn-action" style="background:var(--wc-primary-strong);">
                            <i class="fa-solid fa-headset"></i> Question? Contact us
                        </a>
                    </div>


                </div>

                <!-- Also Like Section -->
                @php
                    $relatedProducts = \App\Models\Product::where('status', 1)->inRandomOrder()->limit(6)->get();
                @endphp
                @if($relatedProducts->count() > 0)
                <div class="mt-5 mb-4 position-relative">
                    <h4 style="font-weight:700; margin-bottom:20px; color:var(--wc-text);">Products you may also like</h4>
                    <div class="owl-carousel related_slider">
                        @foreach($relatedProducts as $product)
                        <div class="product-item-wrap">
                            <div class="product_item h-100" style="border-radius:10px; overflow:hidden; border:1px solid var(--wc-border);">
                                <div class="pro_img text-center p-2">
                                    <a href="{{ route('product', $product->slug) }}">
                                        @php $prodImage = asset($product->display_image); @endphp
                                        <img src="{{ $prodImage }}" alt="{{ $product->name }}" style="max-width:100%; height:150px; object-fit:contain;">
                                    </a>
                                </div>
                                <div class="product-card-body p-3 text-center border-top">
                                    <h5 class="pro_name" style="font-size:14px; margin-bottom:5px; height:auto;">
                                        <a href="{{ route('product', $product->slug) }}" style="color:var(--wc-text); text-decoration:none;">{{ Str::limit($product->name, 40) }}</a>
                                    </h5>
                                    <div class="pro_price" style="margin-bottom:10px;">
                                        @if($product->old_price > $product->new_price)
                                            <del style="font-size:12px; color:var(--wc-muted);">৳{{ $product->old_price }}</del>
                                        @endif
                                        <p style="font-size:15px; font-weight:700; color:var(--wc-primary); margin:0;">৳{{ $product->new_price }}</p>
                                    </div>
                                    <button class="addcartbutton w-100" data-id="{{ $product->id }}" style="border-radius:5px; padding:6px; font-size:12px; border:none; background:var(--wc-surface-soft); color:var(--wc-primary); font-weight:600;">
                                        <i class="fa-solid fa-cart-shopping"></i> Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
                
            </div>
        </div>
    </div>
</section>
@endsection

@push('script')
<x-gtm-datalayer :order="$order" />
<script>

    $(document).ready(function() {
        if ($('.related_slider').length) {
            $(".related_slider").owlCarousel({
                margin: 15,
                items: 4,
                loop: false,
                dots: false,
                nav: true,
                navText: ['<i class="fa fa-angle-left"></i>', '<i class="fa fa-angle-right"></i>'],
                autoplay: true,
                autoplayTimeout: 5000,
                autoplayHoverPause: true,
                responsive: {
                    0: { items: 2 },
                    600: { items: 3 },
                    1000: { items: 4 },
                    1200: { items: 6 }
                }
            });
        }
    });
</script>

@endpush
