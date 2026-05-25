@extends('backEnd.layouts.master')
@section('title','Balance Sheet')
@section('content')
<div class="container-fluid">
    <div class="row"><div class="col-12"><div class="page-title-box">
        <div class="page-title-right"><a href="{{ route('admin.accounts.reports.balance-sheet') }}" class="btn btn-sm btn-secondary"><i class="mdi mdi-arrow-left"></i> Back</a></div>
        <h4 class="page-title">Balance Sheet as of {{ \Carbon\Carbon::parse($asOfDate)->format('d/m/Y') }}</h4>
    </div></div></div>
    <div class="row">
        {{-- Assets --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white"><h5 class="mb-0">{{ $assetLabel }}</h5></div>
                <div class="card-body">
                    <div class="table-responsive report-sticky-container">
                        <table class="table table-sm">
                            <tbody>
                                @foreach($data['assets'] as $a)
                                <tr><td>{{ $a->HeadCode }} - {{ $a->HeadName }}</td><td class="text-end">{{ number_format($a->Balance, 2) }}</td></tr>
                                @endforeach
                            </tbody>
                            <tfoot><tr class="fw-bold table-success"><td>Total {{ $assetLabel }}</td><td class="text-end">{{ number_format($data['assets']->sum('Balance'), 2) }}</td></tr></tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        {{-- Liabilities + Equity --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-danger text-white"><h5 class="mb-0">{{ $liabilityLabel }}</h5></div>
                <div class="card-body">
                    <div class="table-responsive report-sticky-container">
                        <table class="table table-sm">
                            <tbody>
                                @foreach($data['liabilities'] as $l)
                                <tr><td>{{ $l->HeadCode }} - {{ $l->HeadName }}</td><td class="text-end">{{ number_format($l->Balance, 2) }}</td></tr>
                                @endforeach
                            </tbody>
                            <tfoot><tr class="fw-bold table-danger"><td>Total {{ $liabilityLabel }}</td><td class="text-end">{{ number_format($data['liabilities']->sum('Balance'), 2) }}</td></tr></tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card mt-3">
                <div class="card-header bg-primary text-white"><h5 class="mb-0">{{ $equityLabel }}</h5></div>
                <div class="card-body">
                    <div class="table-responsive report-sticky-container">
                        <table class="table table-sm">
                            <tbody>
                                @foreach($data['equity'] as $eq)
                                <tr><td>{{ $eq->HeadCode }} - {{ $eq->HeadName }}</td><td class="text-end">{{ number_format($eq->Balance, 2) }}</td></tr>
                                @endforeach
                                <tr class="table-warning"><td>Retained Earnings</td><td class="text-end">{{ number_format($data['retainedEarnings'], 2) }}</td></tr>
                            </tbody>
                            <tfoot><tr class="fw-bold table-primary"><td>Total {{ $equityLabel }}</td><td class="text-end">{{ number_format($data['equity']->sum('Balance') + $data['retainedEarnings'], 2) }}</td></tr></tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card mt-3 bg-dark text-white">
                <div class="card-body text-center">
                    <h5>Total {{ $liabilityLabel }} + {{ $equityLabel }}: <strong>{{ number_format($data['liabilities']->sum('Balance') + $data['equity']->sum('Balance') + $data['retainedEarnings'], 2) }}</strong></h5>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
