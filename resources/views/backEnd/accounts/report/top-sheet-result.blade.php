@extends('backEnd.layouts.master')
@section('title','Account Group Summary')
@section('content')
<div class="container-fluid">
    <div class="row"><div class="col-12"><div class="page-title-box">
        <div class="page-title-right"><a href="{{ route('admin.accounts.reports.top-sheet') }}" class="btn btn-sm btn-secondary"><i class="mdi mdi-arrow-left"></i> Back</a></div>
        <h4 class="page-title">Account Group Summary: {{ $head->HeadName }} ({{ \Carbon\Carbon::parse($fromDate)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($toDate)->format('d/m/Y') }})</h4>
    </div></div></div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-dark"><tr><th>Code</th><th>Account</th><th class="text-end">Opening</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th class="text-end">Closing</th></tr></thead>
                    <tbody>
                        @foreach($data as $d)
                        <tr>
                            <td>{{ $d->HeadCode }}</td>
                            <td>{{ $d->HeadName }}</td>
                            <td class="text-end">{{ number_format($d->Opening, 2) }}</td>
                            <td class="text-end">{{ number_format($d->Debit, 2) }}</td>
                            <td class="text-end">{{ number_format($d->Credit, 2) }}</td>
                            <td class="text-end fw-bold">{{ number_format($d->Opening + $d->Debit - $d->Credit, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold table-info">
                            <td colspan="2">Total</td>
                            <td class="text-end">{{ number_format($data->sum('Opening'), 2) }}</td>
                            <td class="text-end">{{ number_format($data->sum('Debit'), 2) }}</td>
                            <td class="text-end">{{ number_format($data->sum('Credit'), 2) }}</td>
                            <td class="text-end">{{ number_format($data->sum('Opening') + $data->sum('Debit') - $data->sum('Credit'), 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
