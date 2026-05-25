@extends('backEnd.layouts.master')
@section('title', 'Damage Report')

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
        <h4 class="page-title mb-0">Damage Report</h4>
        <div class="no-print d-flex gap-2">
          <button onclick="window.print()" class="btn btn-sm btn-outline-primary"><i class="fe-printer me-1"></i>Print / PDF</button>
          <a class="btn btn-sm btn-outline-secondary"
             href="{{ route('admin.reports.damage.print', $queryParams) }}"
             target="_blank">Print View</a>
        </div>
      </div>
    </div>
  </div>

  <div class="card no-print mb-3">
    <div class="card-body">
      <form method="GET" action="{{ route('admin.reports.damage') }}" class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label">From</label>
          <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $filters['start_date'] ?? '' }}">
        </div>
        <div class="col-md-3">
          <label class="form-label">To</label>
          <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $filters['end_date'] ?? '' }}">
        </div>
        <div class="col-md-2">
          <label class="form-label">Warehouse ID</label>
          <input type="number" name="warehouse_id" class="form-control form-control-sm" value="{{ $filters['warehouse_id'] ?? '' }}">
        </div>
        <div class="col-md-2">
          <label class="form-label">Product ID</label>
          <input type="number" name="product_id" class="form-control form-control-sm" value="{{ $filters['product_id'] ?? '' }}">
        </div>
        <div class="col-md-2">
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
            <th>Date</th>
            <th>Product</th>
            <th>Warehouse</th>
            <th class="text-end">Qty</th>
            <th class="text-end">Unit Cost</th>
            <th class="text-end">Loss Amount</th>
          </tr>
          </thead>
          <tbody>
          @forelse($rows as $row)
            <tr>
              <td>{{ optional($row->entry_date)->format('Y-m-d') }}</td>
              <td>{{ $row->product?->name }}</td>
              <td>{{ $row->warehouse?->name }}</td>
              <td class="text-end">{{ number_format((float)($row->quantity ?? 0), 2) }}</td>
              <td class="text-end">BDT {{ number_format((float)($row->unit_cost ?? 0), 2) }}</td>
              <td class="text-end">BDT {{ number_format((float)($row->total_loss_amount ?? 0), 2) }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center text-muted">No data</td>
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
