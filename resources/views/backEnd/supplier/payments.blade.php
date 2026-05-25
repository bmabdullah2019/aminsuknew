@extends('backEnd.layouts.master')
@section('title','Supplier Payments - ' . $supplier->name)
@section('content')
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.supplier.show', $supplier->id) }}" class="btn btn-sm btn-secondary">
                        <i class="mdi mdi-arrow-left"></i> Back to Supplier
                    </a>
                    <a href="{{ route('admin.supplier.payments.create', $supplier->id) }}" class="btn btn-sm btn-primary">
                        <i class="mdi mdi-plus"></i> Add Payment
                    </a>
                </div>
                <h4 class="page-title">Supplier Payments - {{ $supplier->name }}</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <!-- Payment Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">BDT {{ number_format($supplier->current_balance, 2) }}</h4>
                    <small>Current Balance</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">BDT {{ number_format($paymentSummary['total_paid'] ?? 0, 2) }}</h4>
                    <small>Total Paid</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $paymentSummary['pending_count'] ?? 0 }}</h4>
                    <small>Pending Payments</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $paymentSummary['cancelled_count'] ?? 0 }}</h4>
                    <small>Cancelled Payments</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Payment History</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Payment #</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Bank</th>
                                    <th>Reference #</th>
                                    <th>Status</th>
                                    <th>Recorded By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payments as $payment)
                                <tr>
                                    <td><strong>{{ $payment->payment_number }}</strong></td>
                                    <td>{{ $payment->payment_date->format('d M Y') }}</td>
                                    <td><span class="badge bg-success">BDT {{ number_format($payment->amount, 2) }}</span></td>
                                    <td>
                                        <span class="badge bg-primary">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</span>
                                    </td>
                                    <td>{{ $payment->bank_name ?: '-' }}</td>
                                    <td>{{ $payment->reference_number ?: '-' }}</td>
                                    <td>
                                        @if($payment->status === 'completed')
                                            <span class="badge bg-success">Completed</span>
                                        @elseif($payment->status === 'pending')
                                            <span class="badge bg-warning">Pending</span>
                                        @else
                                            <span class="badge bg-danger">Cancelled</span>
                                        @endif
                                    </td>
                                    <td>{{ $payment->creator->name ?? 'System' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        <i class="mdi mdi-information-outline"></i>
                                        No payments found for this supplier.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($payments->hasPages())
                    <div class="d-flex justify-content-center mt-3">
                        {{ $payments->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
