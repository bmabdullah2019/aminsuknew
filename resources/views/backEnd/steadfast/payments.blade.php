@extends('backEnd.layouts.master')
@section('title', 'Steadfast Payments')
@section('css')
<style>
.sf-payments .sf-payment-card { border-radius: 12px; border: 1px solid #e8e8e8; transition: all 0.2s ease; }
.sf-payments .sf-payment-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); border-color: #667eea; }
</style>
@endsection
@section('content')
<div class="container-fluid sf-payments">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.steadfast.dashboard') }}" class="btn btn-outline-secondary btn-sm"><i class="fe-arrow-left"></i> Back</a>
                </div>
                <h4 class="page-title"><i class="fe-credit-card me-2"></i>Steadfast Payments</h4>
            </div>
        </div>
    </div>
    @if(!$configured)
    <div class="alert alert-danger"><i class="fe-alert-triangle me-2"></i>API not configured. <a href="{{ route('admin.courierapi.manage') }}">Configure now</a></div>
    @else
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Payment History</h5>
            @if(is_array($payments))<span class="badge bg-primary rounded-pill">{{ count($payments) }}</span>@endif
        </div>
        <div class="card-body p-0">
            @if(empty($payments))
            <div class="text-center py-5 text-muted"><i class="fe-inbox" style="font-size:2rem;"></i><p class="mt-2">No payments found.</p></div>
            @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>ID</th><th>Amount</th><th>Method</th><th>Date</th><th>Action</th></tr></thead>
                    <tbody>
                        @foreach($payments as $p)
                        <tr>
                            <td><strong>{{ $p['id'] ?? $p['payment_id'] ?? '—' }}</strong></td>
                            <td><span class="fw-bold text-success">৳ {{ isset($p['amount']) ? number_format((float)$p['amount'], 2) : '—' }}</span></td>
                            <td>{{ $p['method'] ?? $p['payment_method'] ?? '—' }}</td>
                            <td>{{ isset($p['created_at']) ? \Carbon\Carbon::parse($p['created_at'])->format('d M Y') : ($p['date'] ?? '—') }}</td>
                            <td><a href="{{ route('admin.steadfast.payments.show', $p['id'] ?? $p['payment_id'] ?? 0) }}" class="btn btn-sm btn-outline-primary"><i class="fe-eye"></i> Details</a></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection
