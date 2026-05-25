@extends('backEnd.layouts.master')
@section('title', 'Sales Return Statement (Print)')

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
    <div class="col-12 text-end">
      <button class="btn btn-sm btn-primary" onclick="window.print()">Print</button>
    </div>
  </div>

  <h4 class="mb-2">Sales Return Statement</h4>
  <div class="text-muted mb-3">From {{ $filters['start_date'] ?? '' }} to {{ $filters['end_date'] ?? '' }}</div>

  <div class="table-responsive">
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
</div>
@endsection

