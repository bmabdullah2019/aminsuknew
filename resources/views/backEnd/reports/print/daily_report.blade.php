@extends('backEnd.layouts.master')
@section('title', 'Daily Report (Print)')

@section('css')
<style>
  @media print {
    header, footer, .left-side-menu, .navbar-custom, .no-print { display: none !important; }
    .container-fluid { padding: 0 !important; }
  }
</style>
@endsection

@section('content')
<div class="container-fluid">
  <div class="row no-print">
    <div class="col-12">
      <div class="d-flex justify-content-end">
        <button class="btn btn-sm btn-primary" onclick="window.print()">Print</button>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-12">
      <h4 class="mb-2">Daily Report</h4>
      <div class="text-muted mb-3">
        Date: {{ $date }}
        @if(!empty($branchId))
          | Branch ID: {{ $branchId }}
        @endif
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-3">
      <div class="card">
        <div class="card-body">
          <div class="text-muted">Sales</div>
          <div class="h4 mb-0">BDT {{ number_format($salesTotal, 2) }}</div>
          <small class="text-muted">{{ $salesCount }} orders</small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card">
        <div class="card-body">
          <div class="text-muted">Customer Receipts</div>
          <div class="h4 mb-0">BDT {{ number_format($customerReceipts, 2) }}</div>
          <small class="text-muted">{{ $customerReceiptsCount }} payments</small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card">
        <div class="card-body">
          <div class="text-muted">Supplier Payments</div>
          <div class="h4 mb-0">BDT {{ number_format($supplierPayments, 2) }}</div>
          <small class="text-muted">{{ $supplierPaymentsCount }} payments</small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card">
        <div class="card-body">
          <div class="text-muted">Expenses</div>
          <div class="h4 mb-0">BDT {{ number_format($expensesTotal, 2) }}</div>
          <small class="text-muted">{{ $expensesCount }} expenses</small>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

