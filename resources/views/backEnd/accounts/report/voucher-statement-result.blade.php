@extends('backEnd.layouts.master')
@section('title','Voucher Register')
@section('content')
<div class="container-fluid">
    <div class="row"><div class="col-12"><div class="page-title-box">
        <div class="page-title-right"><a href="{{ route('admin.accounts.reports.voucher-statement') }}" class="btn btn-sm btn-secondary"><i class="mdi mdi-arrow-left"></i> Back</a></div>
        <h4 class="page-title">Voucher Register: {{ \Carbon\Carbon::parse($fromDate)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($toDate)->format('d/m/Y') }}</h4>
    </div></div></div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive report-sticky-container">
                <table class="table table-bordered table-sm table-hover">
                    <thead class="table-dark"><tr><th>Date</th><th>Voucher No</th><th class="text-end">Amount</th><th>Remarks</th><th>Action</th></tr></thead>
                    <tbody>
                        @forelse($data as $d)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($d->TranDate)->format('d/m/Y') }}</td>
                            <td>{{ $d->TranNo }}</td>
                            <td class="text-end">{{ number_format($d->TranAmount, 2) }}</td>
                            <td>{{ $d->Remarks }}</td>
                            <td><a href="{{ route('admin.accounts.voucher.show', $d->TranId) }}" class="btn btn-sm btn-outline-info"><i class="mdi mdi-eye"></i></a></td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted">No vouchers found.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold table-info">
                            <td colspan="2">Total ({{ $data->count() }} vouchers)</td>
                            <td class="text-end">{{ number_format($data->sum('TranAmount'), 2) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
