@extends('backEnd.layouts.master')
@section('title','Profit & Loss Trends')
@section('content')
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <form method="GET" class="d-inline">
                        <div class="input-group">
                            <select name="months" class="form-select form-select-sm">
                                <option value="3" {{ $months == 3 ? 'selected' : '' }}>Last 3 Months</option>
                                <option value="6" {{ $months == 6 ? 'selected' : '' }}>Last 6 Months</option>
                                <option value="12" {{ $months == 12 ? 'selected' : '' }}>Last 12 Months</option>
                                <option value="24" {{ $months == 24 ? 'selected' : '' }}>Last 24 Months</option>
                            </select>
                            <button type="submit" class="btn btn-sm btn-primary">Update</button>
                        </div>
                    </form>
                </div>
                <h4 class="page-title">Profit & Loss Trends</h4>
                <p class="text-muted">Historical financial performance analysis</p>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <!-- Key Metrics Summary -->
    <div class="row mb-4">
        @php
            $trendCollection = collect($trends);
            $latestTrend = $trendCollection->last() ?? [
                'sales_revenue' => 0,
                'net_profit' => 0,
                'profit_margin' => 0,
                'inventory_losses' => 0,
                'month' => 'N/A',
            ];
            $previousTrend = $trendCollection->slice(-2, 1)->first();
            $revenueChange = $previousTrend
                ? (($latestTrend['sales_revenue'] - $previousTrend['sales_revenue']) / max(abs($previousTrend['sales_revenue']), 1)) * 100
                : 0;
            $profitChange = $previousTrend
                ? (($latestTrend['net_profit'] - $previousTrend['net_profit']) / max(abs($previousTrend['net_profit']), 1)) * 100
                : 0;
        @endphp

        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">BDT {{ number_format($latestTrend['sales_revenue'], 2) }}</h4>
                    <small>Latest Revenue</small>
                    <div class="mt-2">
                        <small class="text-{{ $revenueChange >= 0 ? 'light' : 'warning' }}">
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
                    <h4 class="mb-0">BDT {{ number_format($latestTrend['net_profit'], 2) }}</h4>
                    <small>Latest Net Profit</small>
                    <div class="mt-2">
                        <small class="text-{{ $profitChange >= 0 ? 'light' : 'warning' }}">
                            <i class="mdi mdi-{{ $profitChange >= 0 ? 'trending-up' : 'trending-down' }}"></i>
                            {{ number_format(abs($profitChange), 1) }}%
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ number_format($latestTrend['profit_margin'], 1) }}%</h4>
                    <small>Avg Profit Margin</small>
                    <div class="mt-2">
                        <small class="text-light">Last {{ $months }} months</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">BDT {{ number_format($latestTrend['inventory_losses'], 2) }}</h4>
                    <small>Latest Losses</small>
                    <div class="mt-2">
                        <small class="text-light">{{ $latestTrend['month'] }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Trends Chart -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Profit & Loss Trends - Last {{ $months }} Months</h6>
                </div>
                <div class="card-body">
                    <canvas id="profitLossTrendsChart" width="400" height="150"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue vs Profit Chart -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Revenue vs Net Profit Comparison</h6>
                </div>
                <div class="card-body">
                    <canvas id="revenueProfitChart" width="400" height="120"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Trend Summary</h6>
                </div>
                <div class="card-body">
                    @php
                        $totalRevenue = array_sum(array_column($trends, 'sales_revenue'));
                        $totalProfit = array_sum(array_column($trends, 'net_profit'));
                        $totalLosses = array_sum(array_column($trends, 'inventory_losses'));
                        $avgMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;
                    @endphp

                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Total Revenue:</span>
                            <strong>BDT {{ number_format($totalRevenue, 2) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Total Net Profit:</span>
                            <strong class="{{ $totalProfit >= 0 ? 'text-success' : 'text-danger' }}">
                                BDT {{ number_format($totalProfit, 2) }}
                            </strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Total Losses:</span>
                            <strong class="text-danger">BDT {{ number_format($totalLosses, 2) }}</strong>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span>Average Margin:</span>
                            <strong class="{{ $avgMargin >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($avgMargin, 1) }}%
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Trends Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Monthly Trends Data</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Month</th>
                                    <th class="text-end">Sales Revenue</th>
                                    <th class="text-end">Gross Profit</th>
                                    <th class="text-end">Net Profit</th>
                                    <th class="text-end">Inventory Losses</th>
                                    <th class="text-end">Profit Margin</th>
                                    <th class="text-center">Trend</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($trends as $index => $trend)
                                <tr>
                                    <td>
                                        <strong>{{ $trend['month'] }}</strong>
                                        @if($loop->last)
                                        <span class="badge bg-primary ms-2">Latest</span>
                                        @endif
                                    </td>
                                    <td class="text-end">BDT {{ number_format($trend['sales_revenue'], 2) }}</td>
                                    <td class="text-end">BDT {{ number_format($trend['gross_profit'], 2) }}</td>
                                    <td class="text-end">
                                        <span class="{{ $trend['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                            BDT {{ number_format($trend['net_profit'], 2) }}
                                        </span>
                                    </td>
                                    <td class="text-end text-danger">BDT {{ number_format($trend['inventory_losses'], 2) }}</td>
                                    <td class="text-end">
                                        <span class="badge bg-{{ $trend['profit_margin'] >= 20 ? 'success' : ($trend['profit_margin'] >= 10 ? 'warning' : 'danger') }}">
                                            {{ number_format($trend['profit_margin'], 1) }}%
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($index > 0 && isset($trends[$index - 1]))
                                            @php
                                                $prevProfit = $trends[$index - 1]['net_profit'];
                                                $currentProfit = $trend['net_profit'];
                                                $change = $prevProfit != 0 ? (($currentProfit - $prevProfit) / abs($prevProfit)) * 100 : 0;
                                            @endphp
                                            @if($change > 0)
                                                <i class="mdi mdi-trending-up text-success" title="+{{ number_format($change, 1) }}%"></i>
                                            @elseif($change < 0)
                                                <i class="mdi mdi-trending-down text-danger" title="{{ number_format($change, 1) }}%"></i>
                                            @else
                                                <i class="mdi mdi-trending-neutral text-muted" title="No change"></i>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
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
    const trendsData = @json($trends);

    // Main Profit & Loss Trends Chart
    const trendsCtx = document.getElementById('profitLossTrendsChart').getContext('2d');
    new Chart(trendsCtx, {
        type: 'line',
        data: {
            labels: trendsData.map(item => item.month),
            datasets: [{
                label: 'Sales Revenue',
                data: trendsData.map(item => item.sales_revenue),
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                tension: 0.4,
                yAxisID: 'y'
            }, {
                label: 'Net Profit',
                data: trendsData.map(item => item.net_profit),
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.4,
                yAxisID: 'y'
            }, {
                label: 'Inventory Losses',
                data: trendsData.map(item => item.inventory_losses),
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                tension: 0.4,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Financial Performance Trends'
                },
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Revenue / Profit (BDT)'
                    },
                    ticks: {
                        callback: function(value) {
                            return 'BDT ' + value.toLocaleString();
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Losses (BDT)'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                    ticks: {
                        callback: function(value) {
                            return 'BDT ' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Revenue vs Profit Comparison Chart
    const revenueProfitCtx = document.getElementById('revenueProfitChart').getContext('2d');
    new Chart(revenueProfitCtx, {
        type: 'bar',
        data: {
            labels: trendsData.map(item => item.month),
            datasets: [{
                label: 'Sales Revenue',
                data: trendsData.map(item => item.sales_revenue),
                backgroundColor: 'rgba(54, 162, 235, 0.8)',
                borderColor: 'rgb(54, 162, 235)',
                borderWidth: 1
            }, {
                label: 'Net Profit',
                data: trendsData.map(item => item.net_profit),
                backgroundColor: 'rgba(75, 192, 192, 0.8)',
                borderColor: 'rgb(75, 192, 192)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
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
});
</script>
@endsection


