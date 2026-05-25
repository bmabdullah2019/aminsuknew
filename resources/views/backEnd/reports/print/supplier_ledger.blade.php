@extends('backEnd.layouts.master')
@section('title', 'Supplier Ledger (Print)')

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

  <h4 class="mb-2">Supplier Ledger</h4>
  <div class="text-muted mb-2">
    From {{ $filters['start_date'] ?? '' }} to {{ $filters['end_date'] ?? '' }}
  </div>

  <div class="table-responsive">
    <table class="table table-sm table-bordered align-middle mb-0">
      <thead class="table-light">
      <tr>
        <th class="text-center" style="width: 70px;">SL</th>
        <th>Code</th>
        <th>Supplier Name</th>
        <th class="text-end">Opening</th>
        <th class="text-end">Purchase</th>
        <th class="text-end">Payment</th>
        <th class="text-end">Adjustment</th>
        <th class="text-end">Return</th>
        <th class="text-end">Balance</th>
      </tr>
      </thead>
      <tbody>
      @forelse($rows as $index => $row)
        <tr>
          <td class="text-center">{{ $index + 1 }}</td>
          <td>{{ $row['code'] !== '' ? $row['code'] : '-' }}</td>
          <td>{{ $row['name'] }}</td>
          <td class="text-end">{{ number_format((float) $row['opening'], 2) }}</td>
          <td class="text-end">{{ number_format((float) $row['purchase'], 2) }}</td>
          <td class="text-end">{{ number_format((float) $row['payment'], 2) }}</td>
          <td class="text-end">{{ number_format((float) $row['adjustment'], 2) }}</td>
          <td class="text-end">{{ number_format((float) $row['return'], 2) }}</td>
          <td class="text-end"><strong>{{ number_format((float) $row['balance'], 2) }}</strong></td>
        </tr>
      @empty
        <tr>
          <td colspan="9" class="text-center text-muted">No data</td>
        </tr>
      @endforelse
      @if($rows->count() > 0)
      <tr class="table-light fw-bold">
        <td colspan="3" class="text-end">Grand Total</td>
        <td class="text-end">{{ number_format((float) $totals['opening'], 2) }}</td>
        <td class="text-end">{{ number_format((float) $totals['purchase'], 2) }}</td>
        <td class="text-end">{{ number_format((float) $totals['payment'], 2) }}</td>
        <td class="text-end">{{ number_format((float) $totals['adjustment'], 2) }}</td>
        <td class="text-end">{{ number_format((float) $totals['return'], 2) }}</td>
        <td class="text-end">{{ number_format((float) $totals['balance'], 2) }}</td>
      </tr>
      @endif
      </tbody>
    </table>
  </div>

  @if(!empty($detail['supplier']) && ($detail['lines']->count() > 0 || (float) ($detail['opening'] ?? 0) !== 0.0))
    <h5 class="mt-4 mb-2">Exact Ledger Entries</h5>
    <div class="text-muted mb-2">
      {{ $detail['supplier']->supplier_code ? $detail['supplier']->supplier_code.' - ' : '' }}{{ $detail['supplier']->name }}
      | Opening: {{ number_format((float) ($detail['opening'] ?? 0), 2) }}
      | Closing: {{ number_format((float) ($detail['closing'] ?? 0), 2) }}
    </div>
    <div class="table-responsive">
      <table class="table table-sm table-bordered align-middle mb-0">
        <thead class="table-light">
        <tr>
          <th>Date</th>
          <th>Type</th>
          <th>Reference</th>
          <th>Description</th>
          <th class="text-end">Debit</th>
          <th class="text-end">Credit</th>
          <th class="text-end">Running Balance</th>
          <th>Created By</th>
        </tr>
        </thead>
        <tbody>
        @forelse($detail['lines'] as $line)
          <tr>
            <td>{{ $line['date'] }}</td>
            <td>{{ ucfirst(str_replace('_', ' ', $line['transaction_type'])) }}</td>
            <td>{{ $line['reference'] !== '' ? $line['reference'] : '-' }}</td>
            <td>{{ $line['description'] !== '' ? $line['description'] : '-' }}</td>
            <td class="text-end">{{ number_format((float) $line['debit'], 2) }}</td>
            <td class="text-end">{{ number_format((float) $line['credit'], 2) }}</td>
            <td class="text-end"><strong>{{ number_format((float) $line['balance'], 2) }}</strong></td>
            <td>{{ $line['creator'] !== '' ? $line['creator'] : '-' }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="8" class="text-center text-muted">No ledger transactions found for selected range.</td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>
  @endif
</div>
@endsection

