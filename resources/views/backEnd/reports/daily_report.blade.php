@extends('backEnd.layouts.master')
@section('title', 'Daily Report')

@section('css')
<style>
  @media print {
    header, footer, .no-print, .left-side-menu, .navbar-custom { display: none !important; }
  }
</style>
@endsection

@section('content')
<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <div class="page-title-box d-flex align-items-center justify-content-between">
        <h4 class="page-title mb-0">Daily Report</h4>
        <div class="no-print d-flex gap-2">
          <button onclick="window.print()" class="btn btn-sm btn-outline-primary"><i class="fe-printer me-1"></i>Print / PDF</button>
          <a class="btn btn-sm btn-outline-secondary"
             href="{{ route('admin.reports.daily.print', $queryParams) }}"
             target="_blank">Print View</a>
        </div>
      </div>
    </div>
  </div>

  <div class="card no-print mb-3">
    <div class="card-body">
      <form method="GET" action="{{ route('admin.reports.daily') }}" class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label">Date</label>
          <input type="date" class="form-control form-control-sm" name="date" value="{{ $date }}">
        </div>
        <div class="col-md-3">
          <label class="form-label">Branch</label>
          <select class="form-select form-select-sm" name="branch_id">
            <option value="">All</option>
            @foreach($branches as $b)
              <option value="{{ $b->id }}" @selected((string) $branchId === (string) $b->id)>
                {{ $b->code ? $b->code.' - ' : '' }}{{ $b->name }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <button class="btn btn-sm btn-primary">Generate</button>
          <a class="btn btn-sm btn-light" href="{{ route('admin.reports.daily') }}">Reset</a>
        </div>
      </form>
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
