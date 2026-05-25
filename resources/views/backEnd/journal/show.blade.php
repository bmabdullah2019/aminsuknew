@extends('backEnd.layouts.master')
@section('title','Journal Entry Details')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.journal.index') }}" class="btn btn-sm btn-secondary">
                        <i class="mdi mdi-arrow-left"></i> Back
                    </a>
                </div>
                <h4 class="page-title">Journal Entry #{{ $journal->id }}</h4>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <strong>Date:</strong><br>
                    {{ optional($journal->date)->format('Y-m-d') }}
                </div>
                <div class="col-md-3">
                    <strong>Branch:</strong><br>
                    {{ $journal->branch?->name ?? 'N/A' }}
                </div>
                <div class="col-md-3">
                    <strong>Reference:</strong><br>
                    {{ $journal->reference_type ?: 'N/A' }}{{ $journal->reference_id ? ' #' . $journal->reference_id : '' }}
                </div>
                <div class="col-md-3">
                    <strong>Description:</strong><br>
                    {{ $journal->description ?: 'N/A' }}
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @php
                $debitTotal = (float) $journal->items->sum('debit');
                $creditTotal = (float) $journal->items->sum('credit');
            @endphp
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Account Code</th>
                            <th>Account Name</th>
                            <th>Type</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($journal->items as $item)
                            <tr>
                                <td>{{ $item->account?->code }}</td>
                                <td>{{ $item->account?->name }}</td>
                                <td>{{ ucfirst($item->account?->type ?? '-') }}</td>
                                <td class="text-end">{{ number_format((float) $item->debit, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $item->credit, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Total</th>
                            <th class="text-end">{{ number_format($debitTotal, 2) }}</th>
                            <th class="text-end">{{ number_format($creditTotal, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

