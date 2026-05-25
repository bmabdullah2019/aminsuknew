@extends('backEnd.layouts.master')
@section('title','Cash Flow')
@section('content')
<div class="container-fluid">
    <div class="row"><div class="col-12"><div class="page-title-box">
        <div class="page-title-right"><a href="{{ route('admin.accounts.reports.cash-flow') }}" class="btn btn-sm btn-secondary"><i class="mdi mdi-arrow-left"></i> Back</a></div>
        <h4 class="page-title">Cash Flow: {{ \Carbon\Carbon::parse($fromDate)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($toDate)->format('d/m/Y') }}</h4>
    </div></div></div>
    @if(isset($selectedHeads) && $selectedHeads->count())
    <div class="row mb-3">
        <div class="col-12">
            <div class="alert alert-light border mb-0">
                <strong>Selected Account Heads:</strong>
                {{ $selectedHeads->map(fn($head) => $head->HeadCode . ' - ' . $head->HeadName)->implode(', ') }}
            </div>
        </div>
    </div>
    @endif
    <div class="card">
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-4"><div class="card bg-info text-white text-center p-3"><h6>Opening</h6><h4>{{ number_format($data['openingBalance'], 2) }}</h4></div></div>
                <div class="col-md-4"><div class="card bg-warning text-dark text-center p-3"><h6>Net Movement</h6><h4>{{ number_format($data['closingBalance'] - $data['openingBalance'], 2) }}</h4></div></div>
                <div class="col-md-4"><div class="card bg-success text-white text-center p-3"><h6>Closing</h6><h4>{{ number_format($data['closingBalance'], 2) }}</h4></div></div>
            </div>
            <div class="table-responsive report-sticky-container">
                <table class="table table-bordered table-sm">
                    <thead class="table-dark"><tr><th>Code</th><th>Particular Account</th><th class="text-end">Received (Dr)</th><th class="text-end">Paid (Cr)</th></tr></thead>
                    <tbody>
                        @foreach($data['transactions'] as $t)
                        <tr>
                            <td>{{ $t->HeadCode }}</td>
                            <td>{{ $t->HeadName }}</td>
                            <td class="text-end">{{ number_format($t->Debit, 2) }}</td>
                            <td class="text-end">{{ number_format($t->Credit, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold table-info">
                            <td colspan="2">Total</td>
                            <td class="text-end">{{ number_format($data['transactions']->sum('Debit'), 2) }}</td>
                            <td class="text-end">{{ number_format($data['transactions']->sum('Credit'), 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
