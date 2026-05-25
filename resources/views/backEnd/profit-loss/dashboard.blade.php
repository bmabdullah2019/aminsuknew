@extends('backEnd.layouts.master')
@section('title','Profit & Loss Dashboard')
@section('content')
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.profit-loss.reports') }}" class="btn btn-sm btn-primary">
                        <i class="mdi mdi-file-chart"></i> Generate Report
                    </a>
                </div>
                <h4 class="page-title">Profit & Loss Dashboard</h4>
                <p class="text-muted">Real-time financial performance overview</p>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <!-- Key Metrics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">BDT {{ number_format($currentMonthReport->sales_revenue, 2) }}</h4>
                    <small>Sales Revenue</small>
                    <div class="mt-2">
                        @php
                            $revenueChange = $previousMonthReport
                                ? (($currentMonthReport->sales_revenue - $previousMonthReport->sales_revenue) / max(abs($previousMonthReport->sales_revenue), 1)) * 100
                                : 0;
                        @endphp
                        <small class="text-{{ $revenueChange >= 0 ? 'success' : 'danger' }}">
                            <i class="mdi mdi-{{ $revenueChange >= 0 ? 'trending-up' : 'trending-down' }}"></i>
                            {{ number_format(abs($revenueChange), 1) }}%
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">BDT {{ number_format($currentMonthReport->gross_profit, 2) }}</h4>
                    <small>Gross Profit</small>
                    <div class="mt-2">
                        @php
                            $grossProfitChange = $previousMonthReport
                                ? (($currentMonthReport->gross_profit - $previousMonthReport->gross_profit) / max(abs($previousMonthReport->gross_profit), 1)) * 100
                                : 0;
                        @endphp
                        <small class="text-{{ $grossProfitChange >= 0 ? 'light' : 'warning' }}">
                            <i class="mdi mdi-{{ $grossProfitChange >= 0 ? 'trending-up' : 'trending-down' }}"></i>
                            {{ number_format(abs($grossProfitChange), 1) }}%
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">BDT {{ number_format($currentMonthReport->net_profit, 2) }}</h4>
                    <small>Net Profit</small>
                    <div class="mt-2">
                        @php
                            $netProfitChange = $previousMonthReport
                                ? (($currentMonthReport->net_profit - $previousMonthReport->net_profit) / max(abs($previousMonthReport->net_profit), 1)) * 100
                                : 0;
                        @endphp
                        <small class="text-{{ $netProfitChange >= 0 ? 'light' : 'warning' }}">
                            <i class="mdi mdi-{{ $netProfitChange >= 0 ? 'trending-up' : 'trending-down' }}"></i>
                            {{ number_format(abs($netProfitChange), 1) }}%
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ number_format($currentMonthReport->gross_margin_percentage, 1) }}%</h4>
                    <small>Gross Margin</small>
                    <div class="mt-2">
                        <small class="text-light">
                            COGS: BDT {{ number_format($currentMonthReport->cost_of_goods_sold, 2) }}
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loss Summary -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">BDT {{ number_format($currentMonthReport->inventory_losses, 2) }}</h4>
                    <small>Total Losses</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">BDT {{ number_format($currentMonthReport->damage_losses, 2) }}</h4>
                    <small>Damage Losses</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-dark text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">BDT {{ number_format($currentMonthReport->expired_losses, 2) }}</h4>
                    <small>Expired Losses</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-light text-dark">
                <div class="card-body text-center">
                    <h4 class="mb-0">BDT {{ number_format($currentMonthReport->inventory_value_fifo, 2) }}</h4>
                    <small>Inventory Value</small>
                    <small class="text-muted">FIFO Method</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Profit Trends (Last 6 Months)</h6>
                </div>
                <div class="card-body">
                    <canvas id="profitTrendsChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Profit Breakdown</h6>
                </div>
                <div class="card-body">
                    <canvas id="profitBreakdownChart" width="200" height="200"></canvas>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between">
                            <small>Sales Revenue</small>
                            <small>BDT {{ number_format($currentMonthReport->sales_revenue, 2) }}</small>
                        </div>
                        <div class="d-flex justify-content-between">
                            <small class="text-danger">COGS</small>
                            <small class="text-danger">BDT {{ number_format($currentMonthReport->cost_of_goods_sold, 2) }}</small>
                        </div>
                        <div class="d-flex justify-content-between">
                            <small class="text-warning">Expenses</small>
                            <small class="text-warning">BDT {{ number_format($currentMonthReport->operating_expenses, 2) }}</small>
                        </div>
                        <div class="d-flex justify-content-between">
                            <small class="text-danger">Losses</small>
                            <small class="text-danger">BDT {{ number_format($currentMonthReport->inventory_losses, 2) }}</small>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Net Profit</strong>
                            <strong class="{{ $currentMonthReport->net_profit >= 0 ? 'text-success' : 'text-danger' }}">
                                BDT {{ number_format($currentMonthReport->net_profit, 2) }}
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Performers -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Top Profitable Products</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-end">Revenue</th>
                                    <th class="text-end">Profit</th>
                                    <th class="text-end">Margin</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($productWiseProfit as $productData)
                                <tr>
                                    <td>
                                        <strong>{{ $productData['product']->name }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $productData['product']->product_code }}</small>
                                    </td>
                                    <td class="text-end">BDT {{ number_format($productData['sales_revenue'], 2) }}</td>
                                    <td class="text-end">
                                        <span class="{{ $productData['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                            BDT {{ number_format($productData['net_profit'], 2) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-{{ $productData['profit_margin'] >= 20 ? 'success' : ($productData['profit_margin'] >= 10 ? 'warning' : 'danger') }}">
                                            {{ number_format($productData['profit_margin'], 1) }}%
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Warehouse Performance</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Warehouse</th>
                                    <th class="text-end">Revenue</th>
                                    <th class="text-end">Profit</th>
                                    <th class="text-end">Margin</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($warehouseWiseProfit as $warehouseData)
                                <tr>
                                    <td>
                                        <strong>{{ $warehouseData['warehouse']->name }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $warehouseData['warehouse']->city ?? 'N/A' }}</small>
                                    </td>
                                    <td class="text-end">BDT {{ number_format($warehouseData['sales_revenue'], 2) }}</td>
                                    <td class="text-end">
                                        <span class="{{ $warehouseData['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                            BDT {{ number_format($warehouseData['net_profit'], 2) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-{{ $warehouseData['profit_margin'] >= 20 ? 'success' : ($warehouseData['profit_margin'] >= 10 ? 'warning' : 'danger') }}">
                                            {{ number_format($warehouseData['profit_margin'], 1) }}%
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Profit Trends Chart
    const profitTrendsCtx = document.getElementById('profitTrendsChart').getContext('2d');
    const profitTrendsData = @json($profitTrends);

    new Chart(profitTrendsCtx, {
        type: 'line',
        data: {
            labels: profitTrendsData.map(item => item.month),
            datasets: [{
                label: 'Sales Revenue',
                data: profitTrendsData.map(item => item.sales_revenue),
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                tension: 0.4
            }, {
                label: 'Net Profit',
                data: profitTrendsData.map(item => item.net_profit),
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Monthly Profit Trends'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'BDT ' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Profit Breakdown Pie Chart
    const breakdownCtx = document.getElementById('profitBreakdownChart').getContext('2d');
    const currentReport = @json($currentMonthReport);

    new Chart(breakdownCtx, {
        type: 'doughnut',
        data: {
            labels: ['Gross Profit', 'Operating Expenses', 'Inventory Losses'],
            datasets: [{
                data: [
                    Math.max(0, currentReport.gross_profit),
                    currentReport.operating_expenses,
                    currentReport.inventory_losses
                ],
                backgroundColor: [
                    'rgb(75, 192, 192)',
                    'rgb(255, 205, 86)',
                    'rgb(255, 99, 132)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
});
</script>
@endsection

