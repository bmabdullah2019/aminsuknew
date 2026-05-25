@extends('backEnd.layouts.master')
@section('title','Ledger Report')
@section('content')
<div class="container-fluid">
    <div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Ledger Report</h4></div></div></div>
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.accounts.reports.ledger-report') }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Account Head <span class="text-danger">*</span></label>
                    <select name="HeadId" class="form-control" required>
                        <option value="">Select Account Head</option>
                        @foreach($heads as $head)
                        <option value="{{ $head->HeadId }}" {{ (string) request('HeadId') === (string) $head->HeadId ? 'selected' : '' }}>
                            {{ $head->HeadCode }} - {{ $head->HeadName }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">From Date <span class="text-danger">*</span></label>
                    <input type="date" name="FromDate" class="form-control" value="{{ request('FromDate', date('Y-m-01')) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">To Date <span class="text-danger">*</span></label>
                    <input type="date" name="ToDate" class="form-control" value="{{ request('ToDate', date('Y-m-d')) }}" required>
                </div>
                <div class="col-md-2 align-self-end"><button class="btn btn-primary" type="submit"><i class="mdi mdi-magnify"></i> View</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
