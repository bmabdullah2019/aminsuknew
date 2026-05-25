@extends('backEnd.layouts.master')
@section('title','Cash Flow')
@section('content')
<div class="container-fluid">
    <div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Cash Flow Statement</h4></div></div></div>
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.accounts.reports.cash-flow-report') }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">{{ $cashLabel }} / {{ $bankLabel }} Accounts</label>
                    <select id="cashHeadSelect" class="form-control" multiple size="5">
                        @foreach($cashHeads as $h)
                        <option value="{{ $h->HeadId }}" selected>{{ $h->HeadCode }} - {{ $h->HeadName }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="HeadIds" id="headIdsInput">
                </div>
                <div class="col-md-3"><label class="form-label fw-bold">From Date</label><input type="date" name="FromDate" class="form-control" value="{{ date('Y-m-01') }}" required></div>
                <div class="col-md-3"><label class="form-label fw-bold">To Date</label><input type="date" name="ToDate" class="form-control" value="{{ date('Y-m-d') }}" required></div>
                <div class="col-md-2 align-self-end"><button class="btn btn-primary" type="submit" onclick="document.getElementById('headIdsInput').value = Array.from(document.getElementById('cashHeadSelect').selectedOptions).map(o=>o.value).join(',')"><i class="mdi mdi-magnify"></i> View</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
