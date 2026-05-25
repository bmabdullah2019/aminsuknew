@extends('backEnd.layouts.master')
@section('title', 'Sales Return Statement')

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
        <h4 class="page-title mb-0">Sales Return Statement</h4>
        <div class="no-print d-flex gap-2">
          <button onclick="window.print()" class="btn btn-sm btn-outline-primary"><i class="fe-printer me-1"></i>Print / PDF</button>
          <a class="btn btn-sm btn-outline-secondary"
             href="{{ route('admin.reports.sales-returns.print', request()->query()) }}"
             target="_blank">Print View</a>
        </div>
      </div>
    </div>
  </div>

  <div class="card no-print mb-3">
    <div class="card-body">
      <form method="GET" action="{{ route('admin.reports.sales-returns') }}" class="row g-2 align-items-end">
        <div class="col-md-2">
          <label class="form-label">From</label>
          <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $filters['start_date'] ?? '' }}">
        </div>
        <div class="col-md-2">
          <label class="form-label">To</label>
          <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $filters['end_date'] ?? '' }}">
        </div>
        <div class="col-md-3">
          <label class="form-label">Branch</label>
          <select class="form-select form-select-sm" name="branch_id">
            <option value="">All</option>
            @foreach($branches as $b)
              <option value="{{ $b->id }}" @selected((string) ($filters['branch_id'] ?? '') === (string) $b->id)>
                {{ $b->code ? $b->code.' - ' : '' }}{{ $b->name }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Status</label>
          <input type="text" name="status" class="form-control form-control-sm" value="{{ $filters['status'] ?? '' }}" placeholder="approved...">
        </div>
        <div class="col-md-2">
          <label class="form-label">Source</label>
          <input type="text" name="return_source" class="form-control form-control-sm" value="{{ $filters['return_source'] ?? '' }}" placeholder="customer...">
        </div>
        <div class="col-md-1">
          <button class="btn btn-sm btn-primary w-100">Go</button>
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
            <th>Return No</th>
            <th>Invoice</th>
            <th>Customer</th>
            <th>Status</th>
            <th class="text-end">Return Value</th>
            <th class="text-end">Refund</th>
          </tr>
          </thead>
          <tbody>
          @forelse($rows as $row)
            <tr>
              <td>{{ optional($row->created_at)->format('Y-m-d') }}</td>
              <td>{{ $row->return_number }}</td>
              <td>{{ $row->order?->invoice_id }}</td>
              <td>{{ $row->customer?->name }}</td>
              <td>{{ $row->return_status }}</td>
              <td class="text-end">BDT {{ number_format((float)($row->total_return_value ?? 0), 2) }}</td>
              <td class="text-end">BDT {{ number_format((float)($row->refund_amount ?? 0), 2) }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center text-muted">No data</td>
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
