@extends('backEnd.layouts.master')
@section('title', $supplier->name . ' - Supplier Ledger')
@section('content')
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{route('admin.supplier.show', $supplier->id)}}" class="btn btn-secondary rounded-pill">
                        <i class="fe-arrow-left"></i> Back to Supplier
                    </a>
                </div>
                <h4 class="page-title">Supplier Ledger: {{$supplier->name}}</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-sm-8">
                            <form method="GET" action="{{route('admin.supplier.ledger', $supplier->id)}}" class="row g-2">
                                <div class="col-md-3">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" name="start_date" value="{{request('start_date')}}"
                                           class="form-control form-control-sm">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">End Date</label>
                                    <input type="date" name="end_date" value="{{request('end_date')}}"
                                           class="form-control form-control-sm">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Transaction Type</label>
                                    <select name="transaction_type" class="form-control form-control-sm">
                                        <option value="">All Types</option>
                                        <option value="opening_balance" {{request('transaction_type') == 'opening_balance' ? 'selected' : ''}}>Opening Balance</option>
                                        <option value="purchase" {{request('transaction_type') == 'purchase' ? 'selected' : ''}}>Purchase</option>
                                        <option value="payment" {{request('transaction_type') == 'payment' ? 'selected' : ''}}>Payment</option>
                                        <option value="purchase_return" {{request('transaction_type') == 'purchase_return' ? 'selected' : ''}}>Purchase Return</option>
                                        <option value="adjustment" {{request('transaction_type') == 'adjustment' ? 'selected' : ''}}>Adjustment</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div>
                                        <button type="submit" class="btn btn-primary btn-sm me-2">Filter</button>
                                        <a href="{{route('admin.supplier.ledger', $supplier->id)}}" class="btn btn-secondary btn-sm">Clear</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="col-sm-4 text-end">
                            <div class="mb-2">
                                <strong>Current Balance: </strong>
                                <span class="badge bg-{{ $supplier->current_balance >= 0 ? 'danger' : 'success' }} fs-6">
                                    BDT {{ number_format(abs($supplier->current_balance), 2) }}
                                    {{ $supplier->current_balance >= 0 ? '(Owe)' : '(Credit)' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Transaction Type</th>
                                    <th>Reference</th>
                                    <th>Description</th>
                                    <th class="text-end">Debit (BDT)</th>
                                    <th class="text-end">Credit (BDT)</th>
                                    <th class="text-end">Balance (BDT)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ledger as $entry)
                                <tr>
                                    <td>
                                        <div>
                                            <strong>{{ \Carbon\Carbon::parse($entry->transaction_date)->format('d M Y') }}</strong>
                                        </div>
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($entry->created_at)->format('H:i') }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $entry->transaction_type_label }}</span>
                                    </td>
                                    <td>
                                        @if($entry->reference_number)
                                            <span class="badge bg-info">{{ $entry->reference_number }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ Str::limit($entry->description, 50) }}</div>
                                        @if($entry->reference_type && $entry->reference_id)
                                            <small class="text-muted">
                                                {{ ucfirst($entry->reference_type) }} #{{ $entry->reference_id }}
                                            </small>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($entry->debit > 0)
                                            <span class="text-danger fw-bold">{{ number_format($entry->debit, 2) }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($entry->credit > 0)
                                            <span class="text-success fw-bold">{{ number_format($entry->credit, 2) }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <strong class="{{ $entry->running_balance >= 0 ? 'text-danger' : 'text-success' }}">
                                            {{ number_format(abs($entry->running_balance), 2) }}
                                            {{ $entry->running_balance >= 0 ? '(Dr)' : '(Cr)' }}
                                        </strong>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center">No ledger entries found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center">
                        {{ $ledger->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


