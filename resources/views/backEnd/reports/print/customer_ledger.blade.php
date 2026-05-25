@extends('backEnd.layouts.master')
@section('title', 'Customer Ledger (Print)')

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

  <h4 class="mb-2">Customer Ledger</h4>
  <div class="text-muted mb-3">From {{ $filters['start_date'] ?? '' }} to {{ $filters['end_date'] ?? '' }}</div>

  @if(!$customer)
    <div class="alert alert-warning">No customer selected.</div>
  @else
    <div class="mb-3">
      <strong>{{ $customer->name }}</strong>
      @if($customer->phone) | {{ $customer->phone }} @endif
      | Opening: BDT {{ number_format((float)($opening ?? 0), 2) }}
      | Closing: BDT {{ number_format((float)($closing ?? 0), 2) }}
    </div>

    <div class="table-responsive">
      <table class="table table-sm table-bordered align-middle mb-0">
        <thead class="table-light">
        <tr>
          <th>Date</th>
          <th>Ref</th>
          <th>Type</th>
          <th>Description</th>
          <th class="text-end">Debit</th>
          <th class="text-end">Credit</th>
          <th class="text-end">Balance</th>
        </tr>
        </thead>
        <tbody>
        @php $bal = (float)($opening ?? 0); @endphp
        @forelse($lines as $l)
          @php $bal += (float)$l['debit'] - (float)$l['credit']; @endphp
          <tr>
            <td>{{ \Illuminate\Support\Carbon::parse($l['date'])->format('Y-m-d') }}</td>
            <td>{{ $l['ref'] }}</td>
            <td>{{ ucfirst($l['type']) }}</td>
            <td>{{ $l['description'] }}</td>
            <td class="text-end">BDT {{ number_format((float)$l['debit'], 2) }}</td>
            <td class="text-end">BDT {{ number_format((float)$l['credit'], 2) }}</td>
            <td class="text-end">BDT {{ number_format($bal, 2) }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="text-center text-muted">No data</td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>
  @endif
</div>
@endsection

