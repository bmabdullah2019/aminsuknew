@extends('backEnd.layouts.master')
@section('title','Balance Sheet')
@section('content')
<div class="container-fluid">
    <div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Balance Sheet</h4></div></div></div>
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.accounts.reports.balance-sheet-report') }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">As of Date <span class="text-danger">*</span></label>
                    <input type="date" name="AsOfDate" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="col-md-2 align-self-end"><button class="btn btn-primary" type="submit"><i class="mdi mdi-magnify"></i> View</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
