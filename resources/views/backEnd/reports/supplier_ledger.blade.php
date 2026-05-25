@extends('backEnd.layouts.master')
@section('title', 'Supplier Ledger')

@section('css')
<style>
  @media print {
    header, footer, .no-print, .left-side-menu, .navbar-custom { display: none !important; }
  }
  .ledger-filter-wrap { background: #f8f9fb; border: 1px solid #e5e8ec; border-radius: 4px; }
</style>
@endsection

@section('content')
<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <div class="page-title-box d-flex align-items-center justify-content-between">
        <h4 class="page-title mb-0">Supplier Ledger</h4>
        <div class="no-print d-flex gap-2">
          <button onclick="window.print()" class="btn btn-sm btn-outline-primary"><i class="fe-printer me-1"></i>Print / PDF</button>
          <a class="btn btn-sm btn-outline-secondary"
             href="{{ route('admin.reports.supplier-ledger.print', request()->query()) }}"
             target="_blank">Print View</a>
        </div>
      </div>
    </div>
  </div>

  <div class="card no-print mb-3">
    <div class="card-body ledger-filter-wrap">
      <form method="GET" action="{{ route('admin.reports.supplier-ledger') }}" class="row g-2 align-items-end">
        <div class="col-md-4">
          <label class="form-label">Supplier</label>
          <select class="form-select form-select-sm" name="supplier_id">
            <option value="">All Suppliers</option>
            @foreach($suppliers as $s)
              <option value="{{ $s->id }}" @selected((string) ($filters['supplier_id'] ?? '') === (string) $s->id)>
                {{ $s->supplier_code ? $s->supplier_code.' - ' : '' }}{{ $s->name }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">From</label>
          <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $filters['start_date'] ?? '' }}">
        </div>
        <div class="col-md-2">
          <label class="form-label">To</label>
          <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $filters['end_date'] ?? '' }}">
        </div>
        <div class="col-md-2">
          <label class="form-label">Branch</label>
          <select class="form-select form-select-sm" name="branch_id">
            <option value="">All Branches</option>
            @foreach($branches as $b)
              <option value="{{ $b->id }}" @selected((string) ($filters['branch_id'] ?? '') === (string) $b->id)>
                {{ $b->code ? $b->code.' - ' : '' }}{{ $b->name }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <button class="btn btn-sm btn-danger w-100">Show Report</button>
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
              <td class="text-end fw-bold">{{ number_format((float) $row['balance'], 2) }}</td>
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
    </div>
  </div>

  @if(!empty($detail['supplier']) && ($detail['lines']->count() > 0 || (float) ($detail['opening'] ?? 0) !== 0.0))
    <div class="card mt-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Exact Ledger Entries</h5>
        <small class="text-muted">
          {{ $detail['supplier']->supplier_code ? $detail['supplier']->supplier_code.' - ' : '' }}{{ $detail['supplier']->name }}
        </small>
      </div>
      <div class="card-body">
        <div class="mb-2">
          <strong>Opening:</strong> {{ number_format((float) ($detail['opening'] ?? 0), 2) }}
          <span class="mx-2">|</span>
          <strong>Closing:</strong> {{ number_format((float) ($detail['closing'] ?? 0), 2) }}
        </div>
        <div class="table-responsive report-sticky-container">
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
                <td class="text-end fw-bold">{{ number_format((float) $line['balance'], 2) }}</td>
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
      </div>
    </div>
  @endif
</div>
@endsection
