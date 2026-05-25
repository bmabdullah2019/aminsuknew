@extends('backEnd.layouts.master')
@section('title', 'Money Receipt')

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
        <h4 class="page-title mb-0">Money Receipt</h4>
        <div class="no-print d-flex gap-2">
          <button onclick="window.print()" class="btn btn-sm btn-outline-primary"><i class="fe-printer me-1"></i>Print / PDF</button>
        </div>
      </div>
    </div>
  </div>

  <div class="card no-print mb-3">
    <div class="card-body">
      <form method="GET" action="{{ route('admin.reports.money-receipt') }}" class="row g-2 align-items-end">
        <div class="col-md-2">
          <label class="form-label">From</label>
          <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $filters['start_date'] ?? '' }}">
        </div>
        <div class="col-md-2">
          <label class="form-label">To</label>
          <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $filters['end_date'] ?? '' }}">
        </div>
        <div class="col-md-3">
          <label class="form-label">Customer</label>
          <select class="form-select form-select-sm" name="customer_id">
            <option value="">All</option>
            @foreach($customers as $c)
              <option value="{{ $c->id }}" @selected((string) ($filters['customer_id'] ?? '') === (string) $c->id)>
                {{ $c->name }}{{ $c->phone ? ' - '.$c->phone : '' }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Status</label>
          <select class="form-select form-select-sm" name="status">
            <option value="">All</option>
            @foreach(['paid'=>'Paid','pending'=>'Pending','failed'=>'Failed','cancelled'=>'Cancelled'] as $k=>$v)
              <option value="{{ $k }}" @selected((string) ($filters['status'] ?? '') === (string) $k)>{{ $v }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-1">
          <label class="form-label">Method</label>
          <input type="text" name="method" class="form-control form-control-sm" value="{{ $filters['method'] ?? '' }}">
        </div>
        <div class="col-md-2">
          <label class="form-label">TRX</label>
          <input type="text" name="trx_id" class="form-control form-control-sm" value="{{ $filters['trx_id'] ?? '' }}">
        </div>
        <div class="col-md-12">
          <button class="btn btn-sm btn-primary">Search</button>
          <a class="btn btn-sm btn-light" href="{{ route('admin.reports.money-receipt') }}">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <div class="table-responsive report-sticky-container">
        <table class="table table-sm table-bordered align-middle mb-0">
          <thead class="table-light">
          <tr>
            <th>Date</th>
            <th>MR No</th>
            <th>Invoice</th>
            <th>Customer</th>
            <th>Method</th>
            <th>Status</th>
            <th class="text-end">Amount</th>
            <th class="no-print">Action</th>
          </tr>
          </thead>
          <tbody>
          @forelse($rows as $p)
            @php
              $amt = ((int)($p->amount_minor ?? 0) > 0) ? ((int)$p->amount_minor / 100) : (float)($p->amount ?? 0);
            @endphp
            <tr>
              <td>{{ optional($p->created_at)->format('Y-m-d') }}</td>
              <td>MR-{{ $p->id }}</td>
              <td>{{ $p->order?->invoice_id }}</td>
              <td>{{ $p->customer?->name }}</td>
              <td>{{ $p->payment_method }}</td>
              <td>{{ $p->payment_status }}</td>
              <td class="text-end">BDT {{ number_format($amt, 2) }}</td>
              <td class="no-print">
                <a class="btn btn-sm btn-outline-secondary"
                   href="{{ route('admin.reports.money-receipt.print', $p->id) }}"
                   target="_blank">Print</a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center text-muted">No data</td>
            </tr>
          @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-3">
        {{ $rows->links() }}
      </div>
    </div>
  </div>
</div>
@endsection
