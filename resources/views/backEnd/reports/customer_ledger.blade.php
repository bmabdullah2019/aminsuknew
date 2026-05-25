@extends('backEnd.layouts.master')
@section('title', 'Customer Ledger')

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
        <h4 class="page-title mb-0">Customer Ledger</h4>
        <div class="no-print d-flex gap-2">
          <button onclick="window.print()" class="btn btn-sm btn-outline-primary"><i class="fe-printer me-1"></i>Print / PDF</button>
          <a class="btn btn-sm btn-outline-secondary"
             href="{{ route('admin.reports.customer-ledger.print', request()->query()) }}"
             target="_blank">Print View</a>
        </div>
      </div>
    </div>
  </div>

  <div class="card no-print mb-3">
    <div class="card-body">
      <form method="GET" action="{{ route('admin.reports.customer-ledger') }}" class="row g-2 align-items-end">
        <div class="col-md-2">
          <label class="form-label">From</label>
          <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $filters['start_date'] ?? '' }}">
        </div>
        <div class="col-md-2">
          <label class="form-label">To</label>
          <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $filters['end_date'] ?? '' }}">
        </div>
        <div class="col-md-4">
          <label class="form-label">Customer</label>
          <select class="form-select form-select-sm" name="customer_id">
            <option value="">Select customer</option>
            @foreach($customers as $c)
              <option value="{{ $c->id }}" @selected((string) ($filters['customer_id'] ?? '') === (string) $c->id)>
                {{ $c->name }}{{ $c->phone ? ' - '.$c->phone : '' }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
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
          <button class="btn btn-sm btn-primary">Generate</button>
        </div>
      </form>
    </div>
  </div>

  @if($customer)
    <div class="alert alert-info">
      <strong>{{ $customer->name }}</strong>
      @if($customer->phone) | {{ $customer->phone }} @endif
      | Opening: BDT {{ number_format((float)($opening ?? 0), 2) }}
      | Closing: BDT {{ number_format((float)($closing ?? 0), 2) }}
    </div>
  @else
    <div class="alert alert-warning">Please select a customer to view ledger.</div>
  @endif

  @if($customer)
    <div class="card">
      <div class="card-body">
        <div class="table-responsive report-sticky-container">
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
      </div>
    </div>
  @endif
</div>
@endsection
