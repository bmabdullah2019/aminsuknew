@extends('backEnd.layouts.master')
@section('title','Warehouse-wise Profit Analysis')
@section('content')
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <form method="GET" class="d-inline">
                        <div class="row g-2">
                            <div class="col-auto">
                                <input type="date" name="start_date" value="{{ $startDate }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-auto">
                                <input type="date" name="end_date" value="{{ $endDate }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-auto">
                                <select name="costing_method" class="form-select form-select-sm">
                                    <option value="fifo" {{ request('costing_method', 'fifo') == 'fifo' ? 'selected' : '' }}>FIFO</option>
                                    <option value="weighted_average" {{ request('costing_method', 'fifo') == 'weighted_average' ? 'selected' : '' }}>Weighted Average</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                            </div>
                        </div>
                    </form>
                </div>
                <h4 class="page-title">Warehouse-wise Profit Analysis</h4>
                <p class="text-muted">{{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</p>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <!-- Summary Cards -->
    <div class="row mb-4">
        @php
            $totalRevenue = $warehouseWiseProfit->sum('sales_revenue');
            $totalProfit = $warehouseWiseProfit->sum('net_profit');
            $totalLosses = $warehouseWiseProfit->sum('inventory_losses');
            $avgMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;
        @endphp

        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $warehouseWiseProfit->count() }}</h4>
                    <small>Warehouses Analyzed</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">BDT {{ number_format($totalRevenue, 2) }}</h4>
                    <small>Total Revenue</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">BDT {{ number_format($totalProfit, 2) }}</h4>
                    <small>Total Net Profit</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ number_format($avgMargin, 1) }}%</h4>
                    <small>Average Margin</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Warehouse Performance Chart -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Warehouse Profit Performance Comparison</h6>
                </div>
                <div class="card-body">
                    <canvas id="warehouseProfitChart" width="400" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Warehouse Performance Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Detailed Warehouse Analysis</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Warehouse</th>
                                    <th class="text-end">Sales Revenue</th>
                                    <th class="text-end">Cost of Goods Sold</th>
                                    <th class="text-end">Gross Profit</th>
                                    <th class="text-end">Operating Expenses</th>
                                    <th class="text-end">Inventory Losses</th>
                                    <th class="text-end">Net Profit</th>
                                    <th class="text-end">Units Sold</th>
                                    <th class="text-end">Profit Margin</th>
                                    <th class="text-center">Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($warehouseWiseProfit as $warehouseData)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-light rounded me-3">
                                                <i class="mdi mdi-storefront font-20 text-primary"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $warehouseData['warehouse']->name }}</h6>
                                                <small class="text-muted">{{ $warehouseData['warehouse']->city ?? 'N/A' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <strong>BDT {{ number_format($warehouseData['sales_revenue'], 2) }}</strong>
                                    </td>
                                    <td class="text-end text-danger">
                                        BDT {{ number_format($warehouseData['cost_of_goods_sold'], 2) }}
                                    </td>
                                    <td class="text-end">
                                        <span class="{{ $warehouseData['gross_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                            BDT {{ number_format($warehouseData['gross_profit'], 2) }}
                                        </span>
                                    </td>
                                    <td class="text-end text-warning">
                                        BDT {{ number_format($warehouseData['operating_expenses'], 2) }}
                                    </td>
                                    <td class="text-end text-danger">
                                        BDT {{ number_format($warehouseData['inventory_losses'], 2) }}
                                    </td>
                                    <td class="text-end">
                                        <strong class="{{ $warehouseData['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                            BDT {{ number_format($warehouseData['net_profit'], 2) }}
                                        </strong>
                                    </td>
                                    <td class="text-end">{{ number_format($warehouseData['units_sold']) }}</td>
                                    <td class="text-end">
                                        <span class="badge bg-{{ $warehouseData['profit_margin'] >= 20 ? 'success' : ($warehouseData['profit_margin'] >= 10 ? 'warning' : 'danger') }}">
                                            {{ number_format($warehouseData['profit_margin'], 1) }}%
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($warehouseData['profit_margin'] >= 20)
                                            <i class="mdi mdi-trophy text-warning font-18" title="Top Performer"></i>
                                        @elseif($warehouseData['profit_margin'] >= 10)
                                            <i class="mdi mdi-check-circle text-info font-18" title="Good Performer"></i>
                                        @else
                                            <i class="mdi mdi-alert-circle text-danger font-18" title="Needs Attention"></i>
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

    <!-- Warehouse Distribution Chart -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Revenue Distribution by Warehouse</h6>
                </div>
                <div class="card-body">
                    <canvas id="warehouseRevenueChart" width="200" height="200"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Profit Distribution by Warehouse</h6>
                </div>
                <div class="card-body">
                    <canvas id="warehouseProfitDistributionChart" width="200" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const warehouseData = @json($warehouseWiseProfit);

    // Warehouse Profit Bar Chart
    const profitCtx = document.getElementById('warehouseProfitChart').getContext('2d');
    new Chart(profitCtx, {
        type: 'bar',
        data: {
            labels: warehouseData.map(item => item.warehouse.name),
            datasets: [{
                label: 'Sales Revenue (BDT)',
                data: warehouseData.map(item => item.sales_revenue),
                backgroundColor: 'rgba(54, 162, 235, 0.8)',
                borderColor: 'rgb(54, 162, 235)',
                borderWidth: 1
            }, {
                label: 'Net Profit (BDT)',
                data: warehouseData.map(item => item.net_profit),
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

    // Revenue Distribution Pie Chart
    const revenueCtx = document.getElementById('warehouseRevenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'doughnut',
        data: {
            labels: warehouseData.map(item => item.warehouse.name),
            datasets: [{
                data: warehouseData.map(item => item.sales_revenue),
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 205, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)',
                    'rgba(255, 159, 64, 0.8)'
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

    // Profit Distribution Pie Chart
    const profitDistCtx = document.getElementById('warehouseProfitDistributionChart').getContext('2d');
    new Chart(profitDistCtx, {
        type: 'doughnut',
        data: {
            labels: warehouseData.map(item => item.warehouse.name),
            datasets: [{
                data: warehouseData.map(item => item.net_profit),
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 205, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)',
                    'rgba(255, 159, 64, 0.8)'
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


