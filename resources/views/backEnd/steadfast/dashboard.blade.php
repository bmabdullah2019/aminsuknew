@extends('backEnd.layouts.master')
@section('title', 'Steadfast Courier Dashboard')
@section('css')
<style>
.sf-dashboard .sf-stat-card {
    border: none;
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.sf-dashboard .sf-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}
.sf-dashboard .sf-balance-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
}
.sf-dashboard .sf-balance-card .sf-balance-value {
    font-size: 2.2rem;
    font-weight: 800;
    letter-spacing: -0.5px;
}
.sf-dashboard .sf-balance-card .sf-balance-label {
    font-size: 0.9rem;
    opacity: 0.85;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.sf-dashboard .sf-quick-nav .card {
    border-radius: 12px;
    border: 1px solid rgba(0,0,0,0.06);
    transition: all 0.2s ease;
    cursor: pointer;
    text-decoration: none;
}
.sf-dashboard .sf-quick-nav .card:hover {
    background: #f8f9ff;
    border-color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102,126,234,0.15);
}
.sf-dashboard .sf-quick-nav .card i {
    font-size: 1.5rem;
    color: #667eea;
}
.sf-dashboard .sf-status-form .form-select,
.sf-dashboard .sf-status-form .form-control {
    border-radius: 8px;
    border: 1px solid #dee2e6;
    padding: 10px 14px;
}
.sf-dashboard .sf-status-result {
    border-radius: 12px;
    border: 1px solid #e8e8e8;
    overflow: hidden;
}
.sf-dashboard .sf-status-badge {
    display: inline-block;
    padding: 6px 16px;
    border-radius: 50px;
    font-weight: 700;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.sf-dashboard .sf-status-badge.delivered { background: #d4edda; color: #155724; }
.sf-dashboard .sf-status-badge.pending { background: #fff3cd; color: #856404; }
.sf-dashboard .sf-status-badge.cancelled { background: #f8d7da; color: #721c24; }
.sf-dashboard .sf-status-badge.in_review { background: #cce5ff; color: #004085; }
.sf-dashboard .sf-status-badge.hold { background: #e2e3e5; color: #383d41; }
.sf-dashboard .sf-status-badge.partial_delivered { background: #d1ecf1; color: #0c5460; }
.sf-dashboard .sf-status-badge.unknown { background: #e2e3e5; color: #383d41; }
.sf-not-configured {
    background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
    border: 1px solid #feb2b2;
    border-radius: 12px;
    padding: 30px;
    text-align: center;
}
.sf-tracking-table .tracking-code {
    font-family: 'Courier New', monospace;
    font-weight: 700;
    color: #667eea;
    font-size: 0.9rem;
}
.sf-tracking-table .sync-btn {
    padding: 4px 10px;
    font-size: 0.75rem;
    border-radius: 6px;
}
</style>
@endsection

@section('content')
<div class="container-fluid sf-dashboard">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.courierapi.manage') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fe-settings"></i> API Settings
                    </a>
                </div>
                <h4 class="page-title"><i class="fe-truck me-2"></i>Steadfast Courier Dashboard</h4>
            </div>
        </div>
    </div>

    @if(!$configured)
    <div class="row">
        <div class="col-12">
            <div class="sf-not-configured">
                <i class="fe-alert-triangle" style="font-size:2.5rem;color:#e53e3e;"></i>
                <h5 class="mt-3">Steadfast API Not Configured</h5>
                <p class="text-muted mb-3">Please configure your API Key and Secret Key in Courier API settings first.</p>
                <a href="{{ route('admin.courierapi.manage') }}" class="btn btn-primary">
                    <i class="fe-settings"></i> Go to Settings
                </a>
            </div>
        </div>
    </div>
    @else

    {{-- Balance + Quick Nav --}}
    <div class="row mb-3">
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card sf-stat-card sf-balance-card mb-0 h-100">
                <div class="card-body d-flex flex-column justify-content-center">
                    <div class="sf-balance-label mb-1">Current Balance</div>
                    <div class="sf-balance-value">৳ {{ $balance !== null ? number_format((float)$balance, 2) : '—' }}</div>
                    <small class="mt-2" style="opacity:0.7;">Live from Steadfast API</small>
                </div>
            </div>
        </div>

        <div class="col-lg-8 col-md-6">
            <div class="row sf-quick-nav g-3">
                <div class="col-6 col-lg-3">
                    <a href="{{ route('admin.steadfast.return-requests') }}" class="card mb-0 h-100 text-decoration-none">
                        <div class="card-body text-center py-3">
                            <i class="fe-rotate-ccw d-block mb-2"></i>
                            <div class="fw-semibold text-dark">Return Requests</div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-lg-3">
                    <a href="{{ route('admin.steadfast.payments') }}" class="card mb-0 h-100 text-decoration-none">
                        <div class="card-body text-center py-3">
                            <i class="fe-credit-card d-block mb-2"></i>
                            <div class="fw-semibold text-dark">Payments</div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-lg-3">
                    <a href="{{ route('admin.steadfast.police-stations') }}" class="card mb-0 h-100 text-decoration-none">
                        <div class="card-body text-center py-3">
                            <i class="fe-map-pin d-block mb-2"></i>
                            <div class="fw-semibold text-dark">Police Stations</div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-lg-3">
                    <a href="{{ route('admin.courierapi.manage') }}" class="card mb-0 h-100 text-decoration-none">
                        <div class="card-body text-center py-3">
                            <i class="fe-settings d-block mb-2"></i>
                            <div class="fw-semibold text-dark">API Config</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Status Checker --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0"><i class="fe-search me-2"></i>Quick Delivery Status Check</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.steadfast.check-status.post') }}" class="sf-status-form">
                        @csrf
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Search By</label>
                                <select name="search_type" class="form-select">
                                    <option value="invoice" @if(isset($searchType) && $searchType === 'invoice') selected @endif>Invoice ID</option>
                                    <option value="consignment_id" @if(isset($searchType) && $searchType === 'consignment_id') selected @endif>Consignment ID</option>
                                    <option value="tracking_code" @if(isset($searchType) && $searchType === 'tracking_code') selected @endif>Tracking Code</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Value</label>
                                <input type="text" name="search_value" class="form-control" placeholder="Enter invoice, consignment ID or tracking code..." value="{{ $searchValue ?? '' }}" required>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100" style="border-radius:8px;padding:10px 14px;">
                                    <i class="fe-search me-1"></i> Check Status
                                </button>
                            </div>
                        </div>
                    </form>

                    @if(isset($result))
                    <div class="sf-status-result mt-4 p-4">
                        @if(isset($result['delivery_status']))
                        <div class="text-center">
                            <div class="mb-2 text-muted">Delivery Status</div>
                            <span class="sf-status-badge {{ $result['delivery_status'] }}">
                                {{ str_replace('_', ' ', $result['delivery_status']) }}
                            </span>
                        </div>
                        @elseif(isset($result['error']))
                        <div class="alert alert-danger mb-0">
                            <i class="fe-alert-circle me-2"></i>{{ $result['error'] }}
                        </div>
                        @else
                        <div class="alert alert-warning mb-0">
                            <i class="fe-alert-triangle me-2"></i>
                            {{ $result['message'] ?? 'No result found. Please verify your search criteria.' }}
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Tracked Orders --}}
    @if(isset($recentOrders) && $recentOrders->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="fe-package me-2"></i>Recent Steadfast Consignments</h5>
                    <span class="badge bg-primary rounded-pill">{{ $recentOrders->count() }} orders</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive sf-tracking-table">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Invoice</th>
                                    <th>Tracking Code</th>
                                    <th>Consignment ID</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Steadfast Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentOrders as $order)
                                <tr>
                                    <td><span class="fw-semibold">{{ $order->invoice_id }}</span></td>
                                    <td><span class="tracking-code">{{ $order->steadfast_tracking_code ?? '—' }}</span></td>
                                    <td>{{ $order->steadfast_consignment_id ?? '—' }}</td>
                                    <td>{{ $order->shipping->name ?? '—' }}</td>
                                    <td>৳ {{ number_format((float)$order->amount, 2) }}</td>
                                    <td>
                                        @if($order->steadfast_status)
                                        <span class="sf-status-badge {{ $order->steadfast_status }}">
                                            {{ str_replace('_', ' ', $order->steadfast_status) }}
                                        </span>
                                        @else
                                        <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-outline-primary sync-btn sf-sync-btn"
                                                data-order-id="{{ $order->id }}"
                                                title="Sync delivery status">
                                            <i class="fe-refresh-cw"></i> Sync
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @endif
</div>
@endsection

@section('script')
<script>
$(document).ready(function(){
    $(document).on('click', '.sf-sync-btn', function(){
        var $btn = $(this);
        var orderId = $btn.data('order-id');
        var $row = $btn.closest('tr');
        $btn.prop('disabled', true).html('<i class="fe-loader"></i> Syncing...');

        $.ajax({
            url: @json(route('admin.steadfast.sync-status')),
            method: 'POST',
            data: {
                order_id: orderId,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    var status = res.delivery_status;
                    $row.find('.sf-status-badge').remove();
                    $row.find('td:eq(5)').html(
                        '<span class="sf-status-badge ' + status + '">' +
                        status.replace(/_/g, ' ') + '</span>'
                    );
                } else {
                    toastr.error(res.message || 'Sync failed');
                }
            },
            error: function(xhr) {
                toastr.error('Sync request failed');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fe-refresh-cw"></i> Sync');
            }
        });
    });
});
</script>
@endsection
