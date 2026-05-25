@extends('frontEnd.layouts.master')
@section('title','Order Track Result')
@push('css')
<style>
/* ── Order Tracking Result ──────────────────────────────── */
.ot-section {
    padding: 48px 0 72px;
    background: #f4f7fb;
    min-height: 80vh;
}

.ot-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 22px;
    border-radius: 8px;
    background: #078b7e;
    color: #fff !important;
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    margin-bottom: 24px;
    transition: background .2s, box-shadow .2s;
}
.ot-back-btn i { color: #fff !important; }
.ot-back-btn:hover {
    background: #05706a;
    color: #fff !important;
    box-shadow: 0 4px 16px rgba(7,139,126,.35);
    text-decoration: none;
}
.ot-back-btn:focus,
.ot-back-btn:active {
    color: #fff !important;
    text-decoration: none;
}

.ot-card {
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 4px 32px rgba(7,139,126,.10), 0 1px 4px rgba(0,0,0,.04);
    overflow: hidden;
    margin-bottom: 32px;
}

/* ── Header ── */
.ot-card-header {
    background: linear-gradient(120deg,#078b7e 0%,#05b89f 100%);
    padding: 26px 32px 20px;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}
.ot-card-header h2 {
    color:#fff !important; font-size:20px; font-weight:700; margin:0 0 4px;
}
.ot-invoice-id { color:#fff !important; font-size:14px; font-weight:500; }
.ot-invoice-id i { color:#fff !important; }
.ot-status-pill {
    display:inline-flex; align-items:center; gap:7px;
    padding:7px 16px; border-radius:999px; font-size:13px; font-weight:700;
    white-space:nowrap; background:rgba(255,255,255,.22); color:#fff;
    border:1.5px solid rgba(255,255,255,.38);
}
.ot-status-pill.is-cancelled { background:rgba(239,68,68,.18); border-color:rgba(239,68,68,.5); color:#fecaca; }
.ot-status-pill.is-warn      { background:rgba(251,191,36,.18); border-color:rgba(251,191,36,.5); color:#fef08a; }

/* ── Meta Row ── */
.ot-meta-row {
    display:flex; flex-wrap:wrap; gap:0;
    border-bottom:1px solid #eef2f7;
}
.ot-meta-item {
    flex:1 1 140px; padding:16px 22px;
    border-right:1px solid #eef2f7;
}
.ot-meta-item:last-child { border-right:none; }
.ot-meta-item .lbl {
    font-size:11px; font-weight:700; color:#94a3b8;
    text-transform:uppercase; letter-spacing:.7px; margin-bottom:4px;
}
.ot-meta-item .val { font-size:14px; font-weight:700; color:#1b2c40; }

/* ══════════════════════════════════════════════════════════
   HORIZONTAL TIMELINE  (left → right, one row)
══════════════════════════════════════════════════════════ */
.ot-progress-wrap {
    padding: 32px 32px 28px;
}
.ot-progress-wrap > h3 {
    font-size:15px; font-weight:700; color:#1b2c40; margin:0 0 28px;
}

/* The outer track container */
.ot-h-timeline {
    display: flex !important;
    flex-direction: row !important;
    align-items: flex-start !important;
    justify-content: space-between !important;
    position: relative !important;
    padding: 0 !important;
    margin: 0 !important;
    list-style: none !important;
    width: 100% !important;
    overflow-x: auto !important;
}

/* Grey connector line that spans full width through all circles */
.ot-h-timeline::before {
    content: '' !important;
    position: absolute !important;
    top: 24px !important;           /* vertically centre of 52px circle = 26px, minus 2px */
    left: 26px !important;
    right: 26px !important;
    height: 3px !important;
    background: #e2e8f0 !important;
    z-index: 0 !important;
}

/* Teal filled portion — width driven by CSS var */
.ot-h-timeline::after {
    content: '' !important;
    position: absolute !important;
    top: 24px !important;
    left: 26px !important;
    width: var(--ot-fill-pct, 0%) !important;
    height: 3px !important;
    background: linear-gradient(90deg,#078b7e,#05b89f) !important;
    z-index: 1 !important;
    transition: width .7s ease !important;
}

/* Individual step column */
.ot-h-step {
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    flex: 1 !important;
    min-width: 80px !important;
    position: relative !important;
    z-index: 2 !important;
    padding: 0 4px 0 !important;
    text-align: center !important;
}

/* Circle */
.ot-h-icon {
    width: 52px !important;
    height: 52px !important;
    border-radius: 50% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 18px !important;
    border: 2.5px solid #e2e8f0 !important;
    background: #f8fafc !important;
    color: #cbd5e1 !important;
    position: relative !important;
    z-index: 2 !important;
    transition: all .3s ease !important;
    margin-bottom: 12px !important;
    flex-shrink: 0 !important;
}

/* Done state */
.ot-h-step.is-done .ot-h-icon {
    background: #078b7e !important;
    border-color: #078b7e !important;
    color: #fff !important;
    box-shadow: 0 4px 14px rgba(7,139,126,.38) !important;
}

/* Active (current) state */
.ot-h-step.is-active .ot-h-icon {
    background: #fff !important;
    border-color: #078b7e !important;
    color: #078b7e !important;
    box-shadow: 0 0 0 6px rgba(7,139,126,.15) !important;
    animation: ot-hpulse 2s infinite !important;
}

@keyframes ot-hpulse {
    0%,100% { box-shadow: 0 0 0 6px rgba(7,139,126,.15); }
    50%      { box-shadow: 0 0 0 12px rgba(7,139,126,.06); }
}

/* Special (cancelled) */
.ot-h-step.is-special .ot-h-icon {
    background: #fee2e2 !important;
    border-color: #ef4444 !important;
    color: #ef4444 !important;
}

/* Warn (returned/refunded) */
.ot-h-step.is-warn .ot-h-icon {
    background: #fef9c3 !important;
    border-color: #f59e0b !important;
    color: #d97706 !important;
}

/* Tick badge for done steps */
.ot-h-step.is-done .ot-h-icon::after {
    content: '\f00c' !important;
    font-family: 'Font Awesome 6 Free' !important;
    font-weight: 900 !important;
    font-size: 10px !important;
    position: absolute !important;
    top: -4px !important;
    right: -4px !important;
    width: 17px !important;
    height: 17px !important;
    border-radius: 50% !important;
    background: #fff !important;
    color: #078b7e !important;
    border: 1.5px solid #078b7e !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    line-height: 15px !important;
    text-align: center !important;
}

/* Step label & date (below the circle) */
.ot-h-label {
    font-size: 13px !important;
    font-weight: 700 !important;
    color: #94a3b8 !important;
    transition: color .3s !important;
    line-height: 1.3 !important;
    margin-bottom: 3px !important;
}
.ot-h-step.is-done .ot-h-label,
.ot-h-step.is-active .ot-h-label { color: #1b2c40 !important; }
.ot-h-step.is-special .ot-h-label { color: #ef4444 !important; }
.ot-h-step.is-warn .ot-h-label    { color: #d97706 !important; }

.ot-h-date {
    font-size: 11px !important;
    color: #94a3b8 !important;
    font-weight: 500 !important;
    line-height: 1.3 !important;
}
.ot-h-step.is-done .ot-h-date,
.ot-h-step.is-active .ot-h-date { color: #64748b !important; }

/* ── Products Table ── */
.ot-products-wrap { padding: 0 32px 32px; }
.ot-products-wrap > h3 {
    font-size:15px; font-weight:700; color:#1b2c40;
    margin:0 0 16px; border-top:1px solid #eef2f7; padding-top:22px;
}
.ot-table { width:100%; border-collapse:collapse; font-size:14px; }
.ot-table th {
    background:#f4f7fb; color:#64748b; font-weight:700;
    text-transform:uppercase; font-size:11px; letter-spacing:.6px;
    padding:10px 14px; border:none; text-align:left;
}
.ot-table th:last-child { text-align:right; }
.ot-table td { padding:12px 14px; border-bottom:1px solid #f1f5f9; color:#334155; vertical-align:middle; }
.ot-table tbody tr:last-child td { border-bottom:none; }
.ot-table .td-r { text-align:right; font-weight:700; color:#078b7e; }
.ot-table tfoot td { padding:10px 14px; font-weight:700; border-top:2px solid #eef2f7; background:#f8fafc; }
.ot-table tfoot .lc { color:#64748b; font-weight:600; }
.ot-table tfoot .vc { text-align:right; color:#1b2c40; }
.ot-table tfoot .tv { color:#078b7e; font-size:16px; }

/* ── Responsive ── */
@media (max-width: 767px) {
    .ot-card-header { padding:20px 18px 16px; }
    .ot-progress-wrap { padding:24px 16px 20px; overflow-x:auto; }
    .ot-h-icon { width:42px !important; height:42px !important; font-size:15px !important; }
    .ot-h-label { font-size:11px !important; }
    .ot-h-date  { font-size:10px !important; }
    .ot-h-step  { min-width:60px !important; }
    .ot-h-timeline::before { top:19px !important; }
    .ot-h-timeline::after  { top:19px !important; }
    .ot-products-wrap { padding:0 16px 24px; }
}
</style>
@endpush

@section('content')
<section class="ot-section">
    <div class="container">

        <a href="{{ route('customer.order_track') }}" class="ot-back-btn">
            <i class="fas fa-arrow-left"></i> Track Another Order
        </a>

        @foreach($orders as $order)
        @php
            /*
             * DB Status IDs:
             *  1=Pending  2=Confirmed  3=Processing  4=Shipped  5=Delivered
             *  6=Cancelled  7=Returned  8=Refunded
             */
            $cur       = (int) $order->order_status;
            $statusName = optional($order->status)->name ?? 'Pending';

            $steps = [
                ['id'=>1,'label'=>'Order Placed','icon'=>'fas fa-shopping-cart'],
                ['id'=>2,'label'=>'Confirmed',   'icon'=>'fas fa-check-circle'],
                ['id'=>3,'label'=>'Processing',  'icon'=>'fas fa-box-open'],
                ['id'=>4,'label'=>'Shipped',     'icon'=>'fas fa-truck'],
                ['id'=>5,'label'=>'Delivered',   'icon'=>'fas fa-home'],
            ];

            $isSpecial = ($cur === 6);
            $isWarn    = in_array($cur, [7,8]);

            $progressStep = min($cur, 5);
            // Fill %: 0 steps done = 0%, all done = 100%
            // line runs from left edge of step1-circle to right edge of step5-circle
            // each completed gap = 25% of that span
            $fillPct = $progressStep >= 5
                ? '100%'
                : ($progressStep > 1 ? round(($progressStep-1)/4*100).'%' : '0%');
            if ($isSpecial) $fillPct = '0%';
        @endphp

        <div class="ot-card">

            {{-- Header --}}
            <div class="ot-card-header">
                <div>
                    <h2>Order Tracking</h2>
                    <div class="ot-invoice-id">
                        <i class="fas fa-receipt" style="margin-right:5px;opacity:.7;"></i>
                        {{ $order->invoice_id }}
                    </div>
                </div>
                <div class="ot-status-pill {{ $isSpecial ? 'is-cancelled' : ($isWarn ? 'is-warn' : '') }}">
                    @if($isSpecial)<i class="fas fa-times-circle"></i>
                    @elseif($isWarn)<i class="fas fa-undo-alt"></i>
                    @else<i class="fas fa-circle-dot"></i>@endif
                    {{ $statusName }}
                </div>
            </div>

            {{-- Meta --}}
            <div class="ot-meta-row">
                <div class="ot-meta-item">
                    <div class="lbl">Order Date</div>
                    <div class="val">{{ optional($order->created_at)->format('d M Y') }}</div>
                </div>
                @if($order->shipping)
                <div class="ot-meta-item">
                    <div class="lbl">Customer</div>
                    <div class="val">{{ $order->shipping->name }}</div>
                </div>
                <div class="ot-meta-item">
                    <div class="lbl">Phone</div>
                    <div class="val">{{ $order->shipping->phone }}</div>
                </div>
                <div class="ot-meta-item">
                    <div class="lbl">Area</div>
                    <div class="val">{{ $order->shipping->area }}</div>
                </div>
                @endif
                <div class="ot-meta-item">
                    <div class="lbl">Total</div>
                    <div class="val" style="color:#078b7e;">{{ number_format($order->amount) }} ৳</div>
                </div>
            </div>

            {{-- ══ Horizontal Timeline ══ --}}
            <div class="ot-progress-wrap">
                <h3><i class="fas fa-route" style="color:#078b7e;margin-right:8px;"></i>Delivery Progress</h3>

                {{-- Normal flow (1–5) --}}
                @if(!$isSpecial && !$isWarn)
                <div class="ot-h-timeline" style="--ot-fill-pct:{{ $fillPct }};">
                    @foreach($steps as $step)
                    @php
                        $done   = ($cur >= $step['id']);
                        $active = ($cur === $step['id']);
                        $cls    = $done ? 'is-done' : '';
                        if ($active) $cls .= ' is-active';
                    @endphp
                    <div class="ot-h-step {{ $cls }}">
                        <div class="ot-h-icon"><i class="{{ $step['icon'] }}"></i></div>
                        <div class="ot-h-label">{{ $step['label'] }}</div>
                        <div class="ot-h-date">
                            @if($done)
                                @if($step['id'] === 1)
                                    {{ optional($order->created_at)->format('d M Y') }}
                                @elseif($step['id'] === $cur)
                                    {{ optional($order->updated_at)->format('d M Y') }}
                                @else
                                    ✓ Done
                                @endif
                            @else
                                —
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Cancelled --}}
                @elseif($isSpecial)
                <div class="ot-h-timeline" style="--ot-fill-pct:0%;">
                    <div class="ot-h-step is-done">
                        <div class="ot-h-icon"><i class="fas fa-shopping-cart"></i></div>
                        <div class="ot-h-label">Order Placed</div>
                        <div class="ot-h-date">{{ optional($order->created_at)->format('d M Y') }}</div>
                    </div>
                    <div class="ot-h-step is-special">
                        <div class="ot-h-icon"><i class="fas fa-times"></i></div>
                        <div class="ot-h-label">{{ $statusName }}</div>
                        <div class="ot-h-date">{{ optional($order->updated_at)->format('d M Y') }}</div>
                    </div>
                </div>

                {{-- Returned / Refunded --}}
                @else
                <div class="ot-h-timeline" style="--ot-fill-pct:100%;">
                    <div class="ot-h-step is-done">
                        <div class="ot-h-icon"><i class="fas fa-shopping-cart"></i></div>
                        <div class="ot-h-label">Order Placed</div>
                        <div class="ot-h-date">{{ optional($order->created_at)->format('d M Y') }}</div>
                    </div>
                    <div class="ot-h-step is-done">
                        <div class="ot-h-icon"><i class="fas fa-home"></i></div>
                        <div class="ot-h-label">Delivered</div>
                        <div class="ot-h-date">✓ Done</div>
                    </div>
                    <div class="ot-h-step is-warn">
                        <div class="ot-h-icon"><i class="fas fa-undo-alt"></i></div>
                        <div class="ot-h-label">{{ $statusName }}</div>
                        <div class="ot-h-date">{{ optional($order->updated_at)->format('d M Y') }}</div>
                    </div>
                </div>
                @endif

            </div>{{-- /.ot-progress-wrap --}}

            {{-- ══ Steadfast Courier Status ══ --}}
            @if(isset($steadfastStatuses[$order->id]) || $order->steadfast_tracking_code)
            @php
                $sfData = $steadfastStatuses[$order->id] ?? null;
                $sfStatus = $sfData['delivery_status'] ?? $order->steadfast_status ?? null;
                $sfTrackingCode = $order->steadfast_tracking_code ?? null;

                $sfBadgeColor = match($sfStatus) {
                    'delivered' => '#078b7e',
                    'pending', 'in_review' => '#f59e0b',
                    'cancelled' => '#ef4444',
                    'partial_delivered' => '#0ea5e9',
                    'hold' => '#6b7280',
                    default => '#64748b',
                };
                $sfBadgeBg = match($sfStatus) {
                    'delivered' => '#ecfdf5',
                    'pending', 'in_review' => '#fefce8',
                    'cancelled' => '#fef2f2',
                    'partial_delivered' => '#f0f9ff',
                    'hold' => '#f3f4f6',
                    default => '#f8fafc',
                };
            @endphp
            <div style="padding:0 32px 24px;">
                <div style="background:linear-gradient(135deg,#f0fdf9 0%,#ecfeff 100%);border:1.5px solid #d1fae5;border-radius:14px;padding:20px 24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
                    <div>
                        <div style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px;">
                            <i class="fas fa-satellite-dish" style="color:#078b7e;margin-right:6px;"></i>Live Courier Status
                        </div>
                        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                            <span style="display:inline-flex;align-items:center;gap:7px;padding:7px 18px;border-radius:999px;font-size:14px;font-weight:800;background:{{ $sfBadgeBg }};color:{{ $sfBadgeColor }};border:1.5px solid {{ $sfBadgeColor }}30;">
                                @if($sfStatus === 'delivered')<i class="fas fa-check-circle"></i>
                                @elseif($sfStatus === 'cancelled')<i class="fas fa-times-circle"></i>
                                @elseif($sfStatus === 'pending' || $sfStatus === 'in_review')<i class="fas fa-clock"></i>
                                @else<i class="fas fa-truck"></i>@endif
                                {{ $sfStatus ? ucwords(str_replace('_', ' ', $sfStatus)) : 'Checking...' }}
                            </span>
                            @if($sfTrackingCode)
                            <span style="font-size:13px;color:#475569;font-weight:600;">
                                Tracking: <code style="font-weight:800;color:#078b7e;background:#e0f7f5;padding:3px 10px;border-radius:6px;font-size:13px;">{{ $sfTrackingCode }}</code>
                            </span>
                            @endif
                        </div>
                    </div>
                    <div style="font-size:11px;color:#94a3b8;font-weight:600;text-align:right;">
                        <i class="fas fa-bolt" style="color:#f59e0b;"></i> Real-time from Steadfast Courier
                    </div>
                </div>
            </div>
            @endif

            {{-- Products Table --}}
            <div class="ot-products-wrap">
                <h3><i class="fas fa-box" style="color:#078b7e;margin-right:8px;"></i>Order Items</h3>
                <table class="ot-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->orderdetails as $i => $product)
                        <tr>
                            <td style="color:#94a3b8;width:36px;">{{ $i+1 }}</td>
                            <td>{{ $product->product_name }}</td>
                            <td>{{ $product->qty }}</td>
                            <td class="td-r">{{ number_format($product->sale_price * $product->qty) }} ৳</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="lc">Delivery Charge</td>
                            <td class="vc">{{ number_format($order->shipping_charge) }} ৳</td>
                        </tr>
                        @if($order->discount > 0)
                        <tr>
                            <td colspan="3" class="lc">Discount</td>
                            <td class="vc" style="color:#ef4444;">- {{ number_format($order->discount) }} ৳</td>
                        </tr>
                        @endif
                        <tr>
                            <td colspan="3" class="lc" style="font-size:15px;">Total</td>
                            <td class="vc tv">{{ number_format($order->amount) }} ৳</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        </div>{{-- /.ot-card --}}
        @endforeach

    </div>
</section>
@endsection
