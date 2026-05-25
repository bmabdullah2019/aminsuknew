@extends('backEnd.layouts.master')
@section('title','Trial Balance')
@section('content')
<div class="container-fluid">
    <div class="row"><div class="col-12"><div class="page-title-box">
        <div class="page-title-right"><a href="{{ route('admin.accounts.reports.trial-balance') }}" class="btn btn-sm btn-secondary"><i class="mdi mdi-arrow-left"></i> Back</a></div>
        <h4 class="page-title">Trial Balance as of {{ \Carbon\Carbon::parse($asOfDate)->format('d/m/Y') }}</h4>
    </div></div></div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive report-sticky-container">
                <table class="table table-bordered table-sm">
                    <thead class="table-dark"><tr><th>Code</th><th>Account Name</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead>
                    <tbody>
                        @foreach($data as $row)
                        <tr>
                            <td>{{ $row->HeadCode }}</td>
                            <td>{{ $row->HeadName }}</td>
                            <td class="text-end">{{ number_format($row->Debit, 2) }}</td>
                            <td class="text-end">{{ number_format($row->Credit, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold table-info">
                            <td colspan="2">Total</td>
                            <td class="text-end">{{ number_format($totalDebit, 2) }}</td>
                            <td class="text-end">{{ number_format($totalCredit, 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-center {{ abs($totalDebit - $totalCredit) < 0.01 ? 'text-success' : 'text-danger' }}">
                                <strong>Difference: {{ number_format(abs($totalDebit - $totalCredit), 2) }}</strong>
                                @if(abs($totalDebit - $totalCredit) < 0.01) <i class="mdi mdi-check-circle"></i> Balanced @endif
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
