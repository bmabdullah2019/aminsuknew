@extends('backEnd.layouts.master')
@section('title','Dashboard')

@section('css')
<link href="{{asset('public/backEnd/')}}/assets/libs/flatpickr/flatpickr.min.css" rel="stylesheet" type="text/css" />
<style>
    .minimal-dashboard {
        color: #111827;
    }

    .minimal-dashboard .dashboard-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
    }

    .minimal-dashboard .dashboard-eyebrow {
        color: #8992a3;
        font-size: 0.78rem;
        font-weight: 500;
        margin-bottom: 2px;
    }

    .minimal-dashboard .dashboard-title {
        color: #111827;
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0;
    }

    .minimal-dashboard .dashboard-pill {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        min-height: 34px;
        padding: 0 12px;
        border: 1px solid #edf0f5;
        border-radius: 8px;
        background: #ffffff;
        color: #677083;
        font-size: 0.78rem;
        font-weight: 500;
    }

    .minimal-dashboard .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(1, minmax(0, 1fr));
        gap: 14px;
    }

    .minimal-dashboard .dashboard-grid.metrics {
        margin-bottom: 14px;
    }

    .minimal-dashboard .metric-card,
    .minimal-dashboard .panel-card {
        border: 1px solid #edf0f5;
        border-radius: 8px;
        background: #ffffff;
        box-shadow: none;
    }

    .minimal-dashboard .metric-card {
        padding: 15px;
    }

    .minimal-dashboard .metric-head {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
    }

    .minimal-dashboard .metric-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border: 1px solid #eeeaff;
        border-radius: 8px;
        background: #f6f4ff;
        color: #6d4aff;
    }

    .minimal-dashboard .metric-label,
    .minimal-dashboard .muted-label {
        color: #737d90;
        font-size: 0.8rem;
        font-weight: 500;
        margin: 0;
    }

    .minimal-dashboard .metric-value {
        color: #111827;
        font-size: 1.45rem;
        font-weight: 600;
        margin: 0;
    }

    .minimal-dashboard .metric-meta {
        color: #20a873;
        font-size: 0.78rem;
        font-weight: 600;
        margin: 8px 0 0;
    }

    .minimal-dashboard .panel-card {
        overflow: hidden;
        height: 100%;
    }

    .minimal-dashboard .panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        min-height: 52px;
        padding: 14px 16px;
        border-bottom: 1px solid #edf0f5;
    }

    .minimal-dashboard .panel-title {
        color: #111827;
        font-size: 0.98rem;
        font-weight: 600;
        margin: 0;
    }

    .minimal-dashboard .panel-body {
        padding: 16px;
    }

    .minimal-dashboard #sales-analytics {
        min-height: 315px;
    }

    .minimal-dashboard .status-row {
        margin-bottom: 18px;
    }

    .minimal-dashboard .status-row:last-child {
        margin-bottom: 0;
    }

    .minimal-dashboard .status-top {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        color: #4b5563;
        font-size: 0.82rem;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .minimal-dashboard .status-track {
        height: 12px;
        border-radius: 4px;
        background: #f2f4f8;
        overflow: hidden;
    }

    .minimal-dashboard .status-fill {
        height: 100%;
        border-radius: inherit;
        background: #6d4aff;
    }

    .minimal-dashboard .status-fill.success {
        background: #20c997;
    }

    .minimal-dashboard .status-fill.warning {
        background: #ffbd4a;
    }

    .minimal-dashboard .table {
        color: #1f2937;
    }

    .minimal-dashboard .table th {
        white-space: nowrap;
    }

    .minimal-dashboard .product-thumb,
    .minimal-dashboard .customer-initial {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        object-fit: cover;
        flex: 0 0 auto;
    }

    .minimal-dashboard .customer-initial {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f6f4ff;
        color: #6d4aff;
        font-weight: 600;
    }

    .minimal-dashboard .empty-state {
        display: grid;
        place-items: center;
        min-height: 150px;
        color: #8b95a6;
        font-weight: 500;
    }

    @media (min-width: 992px) {
        .minimal-dashboard .dashboard-grid {
            grid-template-columns: minmax(0, 2fr) minmax(300px, 0.9fr);
        }
    }

    @media (min-width: 1200px) {
        .minimal-dashboard .dashboard-grid.metrics {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }

    @media (max-width: 767px) {
        .minimal-dashboard .dashboard-toolbar {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>
@endsection

@section('content')
@php
    $salesSeries = collect($monthly_sale ?? [])->reverse()->values();
    $salesLabels = $salesSeries->map(fn ($sale) => \Carbon\Carbon::parse($sale->date)->format('M d'))->all();
    $salesAmounts = $salesSeries->map(fn ($sale) => (float) $sale->amount)->all();
    $maxStatus = max((int) $total_order, (int) $today_order, (int) $today_delivery, (int) $last_week, 1);
    $todayOrderPercent = min(100, round(((int) $today_order / $maxStatus) * 100));
    $deliveryPercent = min(100, round(((int) $today_delivery / $maxStatus) * 100));
    $weekPercent = min(100, round(((int) $last_week / $maxStatus) * 100));
    $integrity = $integrity_summary ?? [
        'ok' => false,
        'issues_total' => 0,
        'branches_total' => 0,
        'inventory_warehouse_stock_mismatch' => 0,
        'checked_at' => null,
    ];
@endphp

<div class="container-fluid minimal-dashboard">
    <div class="dashboard-toolbar">
        <div>
            <p class="dashboard-eyebrow">Admin Overview</p>
            <h4 class="dashboard-title">Dashboard</h4>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="dashboard-pill">
                <i class="fe-calendar"></i>
                {{now()->format('M d, Y')}}
            </span>
            <a href="{{route('admin.orders', ['slug' => 'pending'])}}" class="btn btn-primary btn-sm">
                <i class="fe-shopping-bag me-1"></i> Orders
            </a>
        </div>
    </div>

    <div class="dashboard-grid metrics">
        <div class="metric-card">
            <div class="metric-head">
                <span class="metric-icon"><i class="fe-shopping-cart"></i></span>
                <p class="metric-label">Total Orders</p>
            </div>
            <h3 class="metric-value">{{$total_order}}</h3>
            <p class="metric-meta">+{{$today_order}} today</p>
        </div>
        <div class="metric-card">
            <div class="metric-head">
                <span class="metric-icon"><i class="fe-dollar-sign"></i></span>
                <p class="metric-label">Today Revenue</p>
            </div>
            <h3 class="metric-value">Tk {{number_format($today_revenue ?? 0, 0)}}</h3>
            <p class="metric-meta">Active sales value</p>
        </div>
        <div class="metric-card">
            <div class="metric-head">
                <span class="metric-icon"><i class="fe-package"></i></span>
                <p class="metric-label">Products</p>
            </div>
            <h3 class="metric-value">{{$total_product}}</h3>
            <p class="metric-meta">{{isset($low_stock_count) ? $low_stock_count : 0}} low stock</p>
        </div>
        <div class="metric-card">
            <div class="metric-head">
                <span class="metric-icon"><i class="fe-users"></i></span>
                <p class="metric-label">Customers</p>
            </div>
            <h3 class="metric-value">{{$total_customer}}</h3>
            <p class="metric-meta">Registered users</p>
        </div>
    </div>

    <div class="dashboard-grid mb-3">
        <div class="panel-card">
            <div class="panel-header">
                <h5 class="panel-title">Sales Analytics</h5>
                <span class="dashboard-pill">
                    <i class="fe-clock"></i>
                    Last 30 days
                </span>
            </div>
            <div class="panel-body">
                <div id="sales-analytics"></div>
            </div>
        </div>

        <div class="panel-card">
            <div class="panel-header">
                <h5 class="panel-title">Store Traffic</h5>
                <span class="dashboard-pill">Live</span>
            </div>
            <div class="panel-body">
                <div class="status-row">
                    <div class="status-top">
                        <span>Today Orders</span>
                        <span>{{$today_order}}</span>
                    </div>
                    <div class="status-track">
                        <div class="status-fill" style="width: {{$todayOrderPercent}}%"></div>
                    </div>
                </div>
                <div class="status-row">
                    <div class="status-top">
                        <span>Today Delivery</span>
                        <span>{{$today_delivery}}</span>
                    </div>
                    <div class="status-track">
                        <div class="status-fill success" style="width: {{$deliveryPercent}}%"></div>
                    </div>
                </div>
                <div class="status-row">
                    <div class="status-top">
                        <span>This Week</span>
                        <span>{{$last_week}}</span>
                    </div>
                    <div class="status-track">
                        <div class="status-fill warning" style="width: {{$weekPercent}}%"></div>
                    </div>
                </div>

                <hr class="my-3">

                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="muted-label">Branch Integrity</p>
                        <h5 class="mb-0 {{$integrity['ok'] ? 'text-success' : 'text-danger'}}">
                            {{$integrity['ok'] ? 'Healthy' : 'Needs Review'}}
                        </h5>
                    </div>
                    <span class="badge {{$integrity['ok'] ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'}}">
                        {{$integrity['issues_total']}} issues
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-7">
            <div class="panel-card">
                <div class="panel-header">
                    <h5 class="panel-title">Today Orders</h5>
                    <a href="{{route('admin.orders', ['slug' => 'all'])}}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="table-responsive border-0">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($latest_order as $order)
                            <tr>
                                <td><span class="fw-semibold text-primary">{{$order->invoice_id}}</span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{asset($order->product?->image?->image ?? '/public/frontEnd/images/no-image.jpg')}}" alt="Product" class="product-thumb">
                                        <div>
                                            <p class="mb-0 fw-semibold">{{$order->customer?$order->customer->name:'Guest'}}</p>
                                            <small class="text-muted">{{$order->customer?$order->customer->phone:''}}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="fw-semibold">Tk {{number_format($order->amount, 2)}}</td>
                                <td><span class="wc-status-badge">{{ucfirst($order->order_status)}}</span></td>
                                <td><small class="text-muted">{{$order->created_at->format('h:i A')}}</small></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">No orders found today.</div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="panel-card">
                <div class="panel-header">
                    <h5 class="panel-title">Low Stock</h5>
                    <a href="{{route('admin.products.index')}}" class="btn btn-sm btn-outline-primary">Manage</a>
                </div>
                <div class="table-responsive border-0">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Stock</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($low_stock_products))
                                @forelse($low_stock_products as $product)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="{{asset($product->image ? $product->image->image : '/public/frontEnd/images/no-image.jpg')}}" alt="{{$product->name}}" class="product-thumb">
                                            <div>
                                                <p class="mb-0 fw-semibold">{{Str::limit($product->name, 22)}}</p>
                                                <small class="text-muted">{{$product->sku ?? 'N/A'}}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="fw-semibold">{{$product->stock ?? 0}}</td>
                                    <td>
                                        @if($product->stock <= 0)
                                            <span class="badge bg-danger-subtle text-danger">Out</span>
                                        @elseif($product->stock <= 5)
                                            <span class="badge bg-warning-subtle text-warning">Critical</span>
                                        @else
                                            <span class="badge bg-info-subtle text-info">Low</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3">
                                        <div class="empty-state">All products are stocked.</div>
                                    </td>
                                </tr>
                                @endforelse
                            @else
                            <tr>
                                <td colspan="3">
                                    <div class="empty-state">Stock data is not available.</div>
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="panel-card">
        <div class="panel-header">
            <h5 class="panel-title">Recent Customers</h5>
            <a href="{{route('admin.customers.index')}}" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="table-responsive border-0">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Joined</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($latest_customer as $customer)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="customer-initial">{{strtoupper(substr($customer->name, 0, 1))}}</span>
                                <span class="fw-semibold">{{$customer->name}}</span>
                            </div>
                        </td>
                        <td>{{$customer->email ?? 'Not provided'}}</td>
                        <td>{{$customer->phone}}</td>
                        <td><small class="text-muted">{{$customer->created_at->format('M d, Y')}}</small></td>
                        <td>
                            @if($customer->status == 'active')
                                <span class="badge bg-success-subtle text-success">Active</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger">{{ucfirst($customer->status)}}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">No recent customers found.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{asset('public/backEnd/')}}/assets/libs/apexcharts/apexcharts.min.js"></script>
<script>
    (function () {
        var salesEl = document.querySelector("#sales-analytics");

        if (!salesEl || typeof ApexCharts === "undefined") {
            return;
        }

        var salesChart = new ApexCharts(salesEl, {
            series: [{
                name: "Revenue",
                data: @json($salesAmounts)
            }],
            chart: {
                height: 315,
                type: "area",
                toolbar: { show: false },
                zoom: { enabled: false },
                fontFamily: "Outfit, Segoe UI, sans-serif"
            },
            colors: ["#6d4aff"],
            dataLabels: { enabled: false },
            stroke: {
                width: 2.5,
                curve: "smooth"
            },
            fill: {
                type: "gradient",
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.18,
                    opacityTo: 0.02,
                    stops: [0, 90, 100]
                }
            },
            grid: {
                borderColor: "#edf0f5",
                strokeDashArray: 4,
                padding: { left: 8, right: 12 }
            },
            xaxis: {
                categories: @json($salesLabels),
                labels: {
                    style: { colors: "#8b95a6", fontSize: "12px" }
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return "Tk " + Math.round(value);
                    },
                    style: { colors: "#8b95a6", fontSize: "12px" }
                }
            },
            tooltip: {
                y: {
                    formatter: function (value) {
                        return "Tk " + Number(value).toLocaleString();
                    }
                }
            }
        });

        salesChart.render();
    })();
</script>
@endsection
