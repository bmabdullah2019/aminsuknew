@extends('backEnd.layouts.master')
@section('title', 'Cash Flow Statement')
@section('content')

<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Statement of Cash Flows</h6>
                
                <form action="{{ route('admin.cash-flow.dashboard') ?? '#' }}" method="GET" class="d-flex w-50">
                    <input type="date" name="start_date" class="form-control me-2" value="{{ $startDate->format('Y-m-d') }}" required>
                    <input type="date" name="end_date" class="form-control me-2" value="{{ $endDate->format('Y-m-d') }}" required>
                    <button type="submit" class="btn btn-primary d-flex align-items-center gap-1">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </form>
            </div>
            
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        
                        <!-- Opening Balance -->
                        <thead class="table-secondary d-table-row-group">
                            <tr>
                                <th class="text-uppercase font-weight-bold fs-5">Opening Cash Balance</th>
                                <th class="text-end fs-5">৳{{ number_format($openingBalance, 2) }}</th>
                            </tr>
                        </thead>

                        <!-- Cash Inflows -->
                        <thead class="bg-light mt-4 d-table-row-group">
                            <tr>
                                <th colspan="2" class="text-uppercase font-weight-bold text-success">Cash Inflows (Receipts)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($inflows as $source => $amount)
                            <tr>
                                <td class="ps-4">{{ $source }}</td>
                                <td class="text-end text-success">+ ৳{{ number_format($amount, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted">No inflows recorded this period.</td>
                            </tr>
                            @endforelse
                            <tr class="fw-bold bg-light">
                                <td class="text-end">Total Cash Inflows:</td>
                                <td class="text-end text-success">+ ৳{{ number_format($totalInflows, 2) }}</td>
                            </tr>
                        </tbody>

                        <!-- Cash Outflows -->
                        <thead class="bg-light mt-4 d-table-row-group">
                            <tr>
                                <th colspan="2" class="text-uppercase font-weight-bold text-danger">Cash Outflows (Payments)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($outflows as $destination => $amount)
                            <tr>
                                <td class="ps-4">{{ $destination }}</td>
                                <td class="text-end text-danger">- ৳{{ number_format($amount, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted">No outflows recorded this period.</td>
                            </tr>
                            @endforelse
                            <tr class="fw-bold bg-light">
                                <td class="text-end">Total Cash Outflows:</td>
                                <td class="text-end text-danger">- ৳{{ number_format($totalOutflows, 2) }}</td>
                            </tr>
                        </tbody>

                        <!-- NET CASH FLOW -->
                        <thead class="mt-4 d-table-row-group {{ $netCashFlow >= 0 ? 'table-success' : 'table-danger' }}" style="background-color: #f1f8e9;">
                            <tr>
                                <th class="text-uppercase font-weight-bold fs-5">Net Increase/(Decrease) in Cash</th>
                                <th class="text-end font-weight-bold fs-5 {{ $netCashFlow >= 0 ? 'text-success' : 'text-danger' }}">
                                    ৳{{ number_format($netCashFlow, 2) }}
                                </th>
                            </tr>
                        </thead>

                        <!-- ENDING BALANCE -->
                        <thead class="bg-primary text-white mt-4 d-table-row-group" style="border-top: 4px solid #fff;">
                            <tr>
                                <th class="text-uppercase font-weight-bold fs-4">Closing Cash Balance</th>
                                <th class="text-end font-weight-bold fs-4">
                                    ৳{{ number_format($endingBalance, 2) }}
                                </th>
                            </tr>
                        </thead>

                    </table>
                </div>

                <div class="text-end mt-4">
                    <button class="btn btn-outline-secondary" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Statement
                    </button>
                    <a href="{{ route('admin.cash-flow.export', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) ?? '#' }}" class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
