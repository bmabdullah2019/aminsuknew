@extends('backEnd.layouts.master')
@section('title','Product-wise Profit Analysis')
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
                                <select name="limit" class="form-select form-select-sm">
                                    <option value="10" {{ $limit == 10 ? 'selected' : '' }}>Top 10</option>
                                    <option value="25" {{ $limit == 25 ? 'selected' : '' }}>Top 25</option>
                                    <option value="50" {{ $limit == 50 ? 'selected' : '' }}>Top 50</option>
                                    <option value="100" {{ $limit == 100 ? 'selected' : '' }}>Top 100</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                            </div>
                        </div>
                    </form>
                </div>
                <h4 class="page-title">Product-wise Profit Analysis</h4>
                <p class="text-muted">{{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</p>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <!-- Summary Cards -->
    <div class="row mb-4">
        @php
            $totalRevenue = $productWiseProfit->sum('sales_revenue');
            $totalProfit = $productWiseProfit->sum('net_profit');
            $totalLosses = $productWiseProfit->sum('inventory_losses');
            $avgMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;
        @endphp

        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $productWiseProfit->count() }}</h4>
                    <small>Products Analyzed</small>
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

    <!-- Top Profitable Products Chart -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Top {{ $limit }} Most Profitable Products</h6>
                </div>
                <div class="card-body">
                    <canvas id="productProfitChart" width="400" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Detailed Product Analysis</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Product</th>
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
                                @foreach($productWiseProfit as $productData)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-light rounded me-3">
                                                <i class="mdi mdi-package-variant font-20 text-primary"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $productData['product']->name }}</h6>
                                                <small class="text-muted">{{ $productData['product']->product_code }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <strong>BDT {{ number_format($productData['sales_revenue'], 2) }}</strong>
                                    </td>
                                    <td class="text-end text-danger">
                                        BDT {{ number_format($productData['cost_of_goods_sold'], 2) }}
                                    </td>
                                    <td class="text-end">
                                        <span class="{{ $productData['gross_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                            BDT {{ number_format($productData['gross_profit'], 2) }}
                                        </span>
                                    </td>
                                    <td class="text-end text-warning">
                                        BDT {{ number_format($productData['operating_expenses'], 2) }}
                                    </td>
                                    <td class="text-end text-danger">
                                        BDT {{ number_format($productData['inventory_losses'], 2) }}
                                    </td>
                                    <td class="text-end">
                                        <strong class="{{ $productData['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                            BDT {{ number_format($productData['net_profit'], 2) }}
                                        </strong>
                                    </td>
                                    <td class="text-end">{{ number_format($productData['units_sold']) }}</td>
                                    <td class="text-end">
                                        <span class="badge bg-{{ $productData['profit_margin'] >= 20 ? 'success' : ($productData['profit_margin'] >= 10 ? 'warning' : 'danger') }}">
                                            {{ number_format($productData['profit_margin'], 1) }}%
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($productData['profit_margin'] >= 20)
                                            <i class="mdi mdi-star text-warning font-18" title="High Performer"></i>
                                        @elseif($productData['profit_margin'] >= 10)
                                            <i class="mdi mdi-thumb-up text-info font-18" title="Good Performer"></i>
                                        @else
                                            <i class="mdi mdi-thumb-down text-danger font-18" title="Low Performer"></i>
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
    const productData = @json($productWiseProfit);

    // Product Profit Chart
    const ctx = document.getElementById('productProfitChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: productData.map(item => item.product.name.length > 20 ?
                item.product.name.substring(0, 20) + '...' : item.product.name),
            datasets: [{
                label: 'Net Profit (BDT)',
                data: productData.map(item => item.net_profit),
                backgroundColor: productData.map(item =>
                    item.net_profit >= 0 ? 'rgba(75, 192, 192, 0.8)' : 'rgba(255, 99, 132, 0.8)'),
                borderColor: productData.map(item =>
                    item.net_profit >= 0 ? 'rgb(75, 192, 192)' : 'rgb(255, 99, 132)'),
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                x: {
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


