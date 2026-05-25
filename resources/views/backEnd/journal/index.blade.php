@extends('backEnd.layouts.master')
@section('title','Journal Entries')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.journal.create') }}" class="btn btn-sm btn-primary">
                        <i class="mdi mdi-plus"></i> New Journal Entry
                    </a>
                </div>
                <h4 class="page-title">Journal Entries</h4>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Branch</label>
                    <select name="branch_id" class="form-control">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ (string) request('branch_id') === (string) $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }} ({{ $branch->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Reference Type</label>
                    <input type="text" name="reference_type" class="form-control" value="{{ request('reference_type') }}" placeholder="order, purchase, expense">
                </div>
                <div class="col-md-2 align-self-end">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Date</th>
                            <th>Branch</th>
                            <th>Reference</th>
                            <th>Description</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Credit</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($journalEntries as $entry)
                            @php
                                $debitTotal = (float) $entry->items->sum('debit');
                                $creditTotal = (float) $entry->items->sum('credit');
                            @endphp
                            <tr>
                                <td>{{ optional($entry->date)->format('Y-m-d') }}</td>
                                <td>{{ $entry->branch?->name ?? 'N/A' }}</td>
                                <td>{{ $entry->reference_type ?: 'N/A' }}{{ $entry->reference_id ? ' #' . $entry->reference_id : '' }}</td>
                                <td>{{ $entry->description ?: 'N/A' }}</td>
                                <td class="text-end">{{ number_format($debitTotal, 2) }}</td>
                                <td class="text-end">{{ number_format($creditTotal, 2) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.journal.show', $entry->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="mdi mdi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No journal entries found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($journalEntries->hasPages())
                <div class="d-flex justify-content-center">
                    {{ $journalEntries->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

