@extends('backEnd.layouts.master')
@section('title', 'Damage Report (Print)')

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

  <h4 class="mb-2">Damage Report</h4>
  <div class="text-muted mb-3">From {{ $filters['start_date'] ?? '' }} to {{ $filters['end_date'] ?? '' }}</div>

  <div class="table-responsive">
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
</div>
@endsection
