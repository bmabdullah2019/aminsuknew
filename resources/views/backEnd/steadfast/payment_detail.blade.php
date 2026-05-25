@extends('backEnd.layouts.master')
@section('title', 'Steadfast Payment Detail')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.steadfast.payments') }}" class="btn btn-outline-secondary btn-sm"><i class="fe-arrow-left"></i> Back to Payments</a>
                </div>
                <h4 class="page-title"><i class="fe-credit-card me-2"></i>Payment Detail</h4>
            </div>
        </div>
    </div>
    @if(!$configured)
    <div class="alert alert-danger"><i class="fe-alert-triangle me-2"></i>API not configured.</div>
    @elseif(empty($payment))
    <div class="alert alert-warning"><i class="fe-alert-triangle me-2"></i>Payment data not available.</div>
    @else
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card" style="border-radius:12px;">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-1">Payment ID</h6>
                    <h4 class="fw-bold">{{ $payment['id'] ?? $payment['payment_id'] ?? '—' }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card" style="border-radius:12px;">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-1">Amount</h6>
                    <h4 class="fw-bold text-success">৳ {{ isset($payment['amount']) ? number_format((float)$payment['amount'], 2) : '—' }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card" style="border-radius:12px;">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-1">Date</h6>
                    <h4 class="fw-bold">{{ isset($payment['created_at']) ? \Carbon\Carbon::parse($payment['created_at'])->format('d M Y') : ($payment['date'] ?? '—') }}</h4>
                </div>
            </div>
        </div>
    </div>

    @php $consignments = $payment['consignments'] ?? $payment['data'] ?? []; @endphp
    @if(!empty($consignments) && is_array($consignments))
    <div class="card" style="border-radius:12px;">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0"><i class="fe-package me-2"></i>Consignments ({{ count($consignments) }})</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Consignment ID</th><th>Invoice</th><th>Tracking</th><th>Recipient</th><th>COD</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach($consignments as $c)
                        <tr>
                            <td>{{ $c['consignment_id'] ?? '—' }}</td>
                            <td><strong>{{ $c['invoice'] ?? '—' }}</strong></td>
                            <td><code>{{ $c['tracking_code'] ?? '—' }}</code></td>
                            <td>{{ $c['recipient_name'] ?? '—' }}</td>
                            <td>৳ {{ isset($c['cod_amount']) ? number_format((float)$c['cod_amount'], 2) : '—' }}</td>
                            <td><span class="badge bg-info">{{ str_replace('_',' ',$c['status'] ?? '—') }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
    @endif
</div>
@endsection
