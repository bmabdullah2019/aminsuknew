@extends('backEnd.layouts.master')
@section('title','Voucher Details')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.accounts.voucher.index') }}" class="btn btn-sm btn-secondary"><i class="mdi mdi-arrow-left"></i> Back</a>
                    @if($voucher->ApprovalStatus === 'draft')
                        <form action="{{ route('admin.accounts.voucher.approve', $voucher->TranId) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to approve this voucher?');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success"><i class="mdi mdi-check"></i> Approve</button>
                        </form>
                        <form action="{{ route('admin.accounts.voucher.reject', $voucher->TranId) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to reject this voucher?');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-danger"><i class="mdi mdi-close"></i> Reject</button>
                        </form>
                    @endif
                    <a href="{{ route('admin.accounts.voucher.edit', $voucher->TranId) }}" class="btn btn-sm btn-primary"><i class="mdi mdi-pencil"></i> Edit</a>
                </div>
                <h4 class="page-title">Voucher: {{ $voucher->TranNo }}</h4>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-3"><strong>Voucher No:</strong> {{ $voucher->TranNo }}</div>
                <div class="col-md-3"><strong>Date:</strong> {{ $voucher->TranDate ? \Carbon\Carbon::parse($voucher->TranDate)->format('d/m/Y') : '' }}</div>
                <div class="col-md-3"><strong>Amount:</strong> {{ number_format($voucher->TranAmount, 2) }}</div>
                <div class="col-md-3"><strong>Status:</strong> 
                    @if($voucher->ApprovalStatus === 'approved')
                        <span class="badge bg-success">Approved</span>
                    @elseif($voucher->ApprovalStatus === 'rejected')
                        <span class="badge bg-danger">Rejected</span>
                    @else
                        <span class="badge bg-warning text-dark">Draft</span>
                    @endif
                </div>
            </div>
            @if($voucher->Remarks)
            <div class="mb-4"><strong>Remarks:</strong> {{ $voucher->Remarks }}</div>
            @endif
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr><th>Account Code</th><th>Account Name</th><th>Subsidiary</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr>
                    </thead>
                    <tbody>
                        @foreach($details as $d)
                        <tr>
                            <td>{{ $d->HeadCode }}</td>
                            <td>{{ $d->HeadName }}</td>
                            <td>{{ $d->SubName ?? '-' }}</td>
                            <td class="text-end">{{ number_format($d->Debit, 2) }}</td>
                            <td class="text-end">{{ number_format($d->Credit, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="3" class="text-end">Total:</td>
                            <td class="text-end">{{ number_format($details->sum('Debit'), 2) }}</td>
                            <td class="text-end">{{ number_format($details->sum('Credit'), 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
