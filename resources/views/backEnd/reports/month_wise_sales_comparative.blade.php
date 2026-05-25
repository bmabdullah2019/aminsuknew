@extends('backEnd.layouts.master')
@section('title', 'Month wise Sales Comparative Report')

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
        <h4 class="page-title mb-0">Month wise Sales Comparative Report</h4>
        <div class="no-print d-flex gap-2">
          <button onclick="window.print()" class="btn btn-sm btn-outline-primary"><i class="fe-printer me-1"></i>Print / PDF</button>
          <a class="btn btn-sm btn-outline-secondary"
             href="{{ route('admin.reports.month-wise-sales-comparative.print', $queryParams) }}"
             target="_blank">Print View</a>
        </div>
      </div>
    </div>
  </div>

  <div class="card no-print mb-3">
    <div class="card-body">
      <form method="GET" action="{{ route('admin.reports.month-wise-sales-comparative') }}" class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label">From Month</label>
          <input type="month" class="form-control form-control-sm" name="from_month" value="{{ $fromMonth->format('Y-m') }}">
        </div>
        <div class="col-md-3">
          <label class="form-label">To Month</label>
          <input type="month" class="form-control form-control-sm" name="to_month" value="{{ $toMonth->format('Y-m') }}">
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
            <th>Month</th>
            <th class="text-end">Orders</th>
            <th class="text-end">Gross Sales</th>
            <th class="text-end">Paid</th>
            <th class="text-end">Refund</th>
          </tr>
          </thead>
          <tbody>
          @forelse($rows as $r)
            <tr>
              <td>{{ $r['ym'] }}</td>
              <td class="text-end">{{ number_format($r['order_count']) }}</td>
              <td class="text-end">BDT {{ number_format($r['gross_sales'], 2) }}</td>
              <td class="text-end">BDT {{ number_format($r['paid_amount'], 2) }}</td>
              <td class="text-end">BDT {{ number_format($r['refund_amount'], 2) }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center text-muted">No data</td>
            </tr>
          @endforelse
          </tbody>
          <tfoot class="table-light">
          <tr>
            <th>Total</th>
            <th class="text-end">{{ number_format($summary['order_count'] ?? 0) }}</th>
            <th class="text-end">BDT {{ number_format($summary['gross_sales'] ?? 0, 2) }}</th>
            <th class="text-end">BDT {{ number_format($summary['paid_amount'] ?? 0, 2) }}</th>
            <th class="text-end">BDT {{ number_format($summary['refund_amount'] ?? 0, 2) }}</th>
          </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
