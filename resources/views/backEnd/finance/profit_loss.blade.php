@extends('backEnd.layouts.master')
@section('title', 'Profit & Loss Statement')
@section('content')

<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Profit & Loss Statement</h6>
                
                <form action="{{ route('admin.profit-loss.dashboard') }}" method="GET" class="d-flex w-50">
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
                        <!-- Revenue Section -->
                        <thead class="bg-light">
                            <tr>
                                <th colspan="2" class="text-uppercase font-weight-bold text-primary">1. Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($revenue as $item)
                            <tr>
                                <td class="ps-4">{{ $item['name'] }} <small class="text-muted">({{ $item['code'] }})</small></td>
                                <td class="text-end">৳{{ number_format($item['balance'], 2) }}</td>
                            </tr>
                            @endforeach
                            <tr class="fw-bold bg-light">
                                <td class="text-end">Total Revenue:</td>
                                <td class="text-end">৳{{ number_format($totalRevenue, 2) }}</td>
                            </tr>
                        </tbody>

                        <!-- COGS Section -->
                        <thead class="bg-light mt-4 d-table-row-group">
                            <tr>
                                <th colspan="2" class="text-uppercase font-weight-bold text-danger">2. Cost of Goods Sold (COGS)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cogs as $item)
                            <tr>
                                <td class="ps-4">{{ $item['name'] }} <small class="text-muted">({{ $item['code'] }})</small></td>
                                <td class="text-end text-danger">- ৳{{ number_format($item['balance'], 2) }}</td>
                            </tr>
                            @endforeach
                            <tr class="fw-bold bg-light">
                                <td class="text-end">Total COGS:</td>
                                <td class="text-end text-danger">- ৳{{ number_format($totalCogs, 2) }}</td>
                            </tr>
                        </tbody>

                        <!-- GROSS PROFIT -->
                        <thead class="table-primary mt-4 d-table-row-group" style="background-color: #cfe2ff;">
                            <tr>
                                <th class="text-uppercase font-weight-bold fs-5">Gross Profit (Revenue - COGS)</th>
                                <th class="text-end fs-5 text-primary">৳{{ number_format($grossProfit, 2) }}</th>
                            </tr>
                        </thead>

                        <!-- Expenses Section -->
                        <thead class="bg-light mt-4 d-table-row-group">
                            <tr>
                                <th colspan="2" class="text-uppercase font-weight-bold text-warning">3. Operating Expenses</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expenses as $item)
                            <tr>
                                <td class="ps-4">{{ $item['name'] }} <small class="text-muted">({{ $item['code'] }})</small></td>
                                <td class="text-end text-warning">- ৳{{ number_format($item['balance'], 2) }}</td>
                            </tr>
                            @endforeach
                            <tr class="fw-bold bg-light">
                                <td class="text-end">Total Expenses:</td>
                                <td class="text-end text-warning">- ৳{{ number_format($totalExpenses, 2) }}</td>
                            </tr>
                        </tbody>

                        <!-- NET PROFIT -->
                        <thead class="table-success mt-4 d-table-row-group" style="border-top: 3px solid #198754;">
                            <tr>
                                <th class="text-uppercase font-weight-bold fs-4">Net Profit (Gross Profit - Expenses)</th>
                                <th class="text-end fs-4 {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}">
                                    ৳{{ number_format($netProfit, 2) }}
                                </th>
                            </tr>
                        </thead>

                    </table>
                </div>

                <div class="text-end mt-4">
                    <button class="btn btn-outline-secondary" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                    <a href="{{ route('admin.profit-loss.export', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}" class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
