@extends('backEnd.layouts.master')
@section('title','Voucher Register')
@section('content')
<div class="container-fluid">
    <div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Voucher Register</h4></div></div></div>
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.accounts.reports.voucher-statement-report') }}" class="row g-3">
                <div class="col-md-4"><label class="form-label fw-bold">From</label><input type="date" name="FromDate" class="form-control" value="{{ date('Y-m-01') }}" required></div>
                <div class="col-md-4"><label class="form-label fw-bold">To</label><input type="date" name="ToDate" class="form-control" value="{{ date('Y-m-d') }}" required></div>
                <div class="col-md-2 align-self-end"><button class="btn btn-primary" type="submit"><i class="mdi mdi-magnify"></i> View</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
