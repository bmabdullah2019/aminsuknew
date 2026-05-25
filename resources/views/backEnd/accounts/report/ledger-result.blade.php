@extends('backEnd.layouts.master')
@section('title','Ledger Report')
@section('content')
<div class="container-fluid">
    <div class="row"><div class="col-12"><div class="page-title-box">
        <div class="page-title-right"><a href="{{ route('admin.accounts.reports.ledger') }}" class="btn btn-sm btn-secondary"><i class="mdi mdi-arrow-left"></i> Back</a></div>
        <h4 class="page-title">Ledger: {{ $head->HeadCode }} - {{ $head->HeadName }}</h4>
    </div></div></div>
    <div class="card">
        <div class="card-header bg-dark text-white">
            <strong>Period:</strong> {{ \Carbon\Carbon::parse($fromDate)->format('d/m/Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d/m/Y') }}
            &nbsp;|&nbsp; <strong>Opening Balance:</strong> {{ number_format($opening, 2) }}
        </div>
        <div class="card-body">
            <div class="table-responsive report-sticky-container">
                <table class="table table-bordered table-sm">
                    <thead class="table-dark">
                        <tr><th>Date</th><th>Voucher</th><th>Particular</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th class="text-end">Balance</th></tr>
                    </thead>
                    <tbody>
                        <tr class="table-warning fw-bold"><td colspan="3">Opening Balance</td><td class="text-end">-</td><td class="text-end">-</td><td class="text-end">{{ number_format($opening, 2) }}</td></tr>
                        @foreach($transactions as $t)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($t->TranDate)->format('d/m/Y') }}</td>
                            <td>{{ $t->TranNo }}</td>
                            <td>{{ $t->ParticularCode }} - {{ $t->ParticularName }}@if($t->SubName) ({{ $t->SubName }})@endif</td>
                            <td class="text-end">{{ $t->Debit > 0 ? number_format($t->Debit, 2) : '-' }}</td>
                            <td class="text-end">{{ $t->Credit > 0 ? number_format($t->Credit, 2) : '-' }}</td>
                            <td class="text-end fw-bold">{{ number_format($t->RunningBalance, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-info fw-bold">
                            <td colspan="3">Total</td>
                            <td class="text-end">{{ number_format($transactions->sum('Debit'), 2) }}</td>
                            <td class="text-end">{{ number_format($transactions->sum('Credit'), 2) }}</td>
                            <td class="text-end">{{ $transactions->isNotEmpty() ? number_format($transactions->last()->RunningBalance, 2) : number_format($opening, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
