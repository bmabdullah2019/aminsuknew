@extends('backEnd.layouts.master')
@section('title', 'Month wise Sales Comparative (Print)')

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

  <div class="row">
    <div class="col-12">
      <h4 class="mb-2">Month wise Sales Comparative Report</h4>
      <div class="text-muted mb-3">
        Period: {{ $fromMonth->format('Y-m') }} to {{ $toMonth->format('Y-m') }}
        @if(!empty($branchId))
          | Branch ID: {{ $branchId }}
        @endif
      </div>

      <div class="table-responsive">
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

