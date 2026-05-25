@extends('backEnd.layouts.master')
@section('title','Profit & Loss Statement')
@section('content')
<div class="container-fluid">
    <div class="row"><div class="col-12"><div class="page-title-box">
        <div class="page-title-right"><a href="{{ route('admin.accounts.reports.income-statement') }}" class="btn btn-sm btn-secondary"><i class="mdi mdi-arrow-left"></i> Back</a></div>
        <h4 class="page-title">Profit &amp; Loss Statement: {{ \Carbon\Carbon::parse($fromDate)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($toDate)->format('d/m/Y') }}</h4>
    </div></div></div>
    <div class="row">
        <div class="col-md-6">
            <div class="card"><div class="card-header bg-success text-white"><h5 class="mb-0">{{ $incomeLabel }}</h5></div>
                <div class="card-body">
                    <div class="table-responsive report-sticky-container">
                        <table class="table table-sm">
                            <tbody>
                                @foreach($data['income'] as $i)
                                <tr><td>{{ $i->HeadCode }} - {{ $i->HeadName }}</td><td class="text-end">{{ number_format($i->Balance, 2) }}</td></tr>
                                @endforeach
                            </tbody>
                            <tfoot><tr class="fw-bold table-success"><td>Total {{ $incomeLabel }}</td><td class="text-end">{{ number_format($data['income']->sum('Balance'), 2) }}</td></tr></tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card"><div class="card-header bg-danger text-white"><h5 class="mb-0">{{ $expenseLabel }}</h5></div>
                <div class="card-body">
                    <div class="table-responsive report-sticky-container">
                        <table class="table table-sm">
                            <tbody>
                                @foreach($data['expense'] as $e)
                                <tr><td>{{ $e->HeadCode }} - {{ $e->HeadName }}</td><td class="text-end">{{ number_format($e->Balance, 2) }}</td></tr>
                                @endforeach
                            </tbody>
                            <tfoot><tr class="fw-bold table-danger"><td>Total {{ $expenseLabel }}</td><td class="text-end">{{ number_format($data['expense']->sum('Balance'), 2) }}</td></tr></tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card mt-3 {{ $data['netProfit'] >= 0 ? 'bg-success' : 'bg-danger' }} text-white">
        <div class="card-body text-center">
            <h3>Net {{ $data['netProfit'] >= 0 ? 'Profit' : 'Loss' }}: <strong>{{ number_format(abs($data['netProfit']), 2) }}</strong></h3>
        </div>
    </div>
</div>
@endsection
