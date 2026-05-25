@extends('backEnd.layouts.master')
@section('title','Costing Method Comparison')
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
                                <button type="submit" class="btn btn-sm btn-primary">Compare</button>
                            </div>
                        </div>
                    </form>
                </div>
                <h4 class="page-title">Costing Method Comparison</h4>
                <p class="text-muted">FIFO vs Weighted Average Cost impact analysis</p>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <!-- Comparison Summary -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">FIFO Method Results</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h4 class="text-primary">BDT {{ number_format($fifoReport->gross_profit ?? 0, 2) }}</h4>
                            <small>Gross Profit</small>
                        </div>
                        <div class="col-6">
                            <h4 class="text-primary">BDT {{ number_format($fifoReport->net_profit ?? 0, 2) }}</h4>
                            <small>Net Profit</small>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="row">
                            <div class="col-6">
                                <small>COGS: BDT {{ number_format($fifoReport->cost_of_goods_sold ?? 0, 2) }}</small>
                            </div>
                            <div class="col-6">
                                <small>Expenses: BDT {{ number_format($fifoReport->operating_expenses ?? 0, 2) }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">Weighted Average Cost Results</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h4 class="text-success">BDT {{ number_format($wacReport->gross_profit ?? 0, 2) }}</h4>
                            <small>Gross Profit</small>
                        </div>
                        <div class="col-6">
                            <h4 class="text-success">BDT {{ number_format($wacReport->net_profit ?? 0, 2) }}</h4>
                            <small>Net Profit</small>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="row">
                            <div class="col-6">
                                <small>COGS: BDT {{ number_format($wacReport->cost_of_goods_sold ?? 0, 2) }}</small>
                            </div>
                            <div class="col-6">
                                <small>Expenses: BDT {{ number_format($wacReport->operating_expenses ?? 0, 2) }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Difference Analysis -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Method Comparison Analysis</h6>
                </div>
                <div class="card-body">
                    @php
                        $fifoProfit = $fifoReport->net_profit ?? 0;
                        $wacProfit = $wacReport->net_profit ?? 0;
                        $profitDifference = $fifoProfit - $wacProfit;
                        $percentageDifference = $wacProfit != 0 ? (($fifoProfit - $wacProfit) / abs($wacProfit)) * 100 : 0;
                    @endphp

                    <div class="row">
                        <div class="col-md-4">
                            <div class="card {{ $profitDifference >= 0 ? 'border-success' : 'border-danger' }}">
                                <div class="card-body text-center">
                                    <h5 class="{{ $profitDifference >= 0 ? 'text-success' : 'text-danger' }}">
                                        BDT {{ number_format(abs($profitDifference), 2) }}
                                    </h5>
                                    <small>Profit Difference</small>
                                    <p class="mb-0 mt-2">
                                        <span class="badge bg-{{ $profitDifference >= 0 ? 'success' : 'danger' }}">
                                            {{ $profitDifference >= 0 ? 'FIFO Higher' : 'WAC Higher' }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card border-info">
                                <div class="card-body text-center">
                                    <h5 class="text-info">{{ number_format(abs($percentageDifference), 1) }}%</h5>
                                    <small>Percentage Difference</small>
                                    <p class="mb-0 mt-2">
                                        <small class="text-muted">Relative profit impact</small>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card border-warning">
                                <div class="card-body text-center">
                                    <h5 class="text-warning">
                                        {{ $profitDifference >= 0 ? 'FIFO' : 'WAC' }}
                                    </h5>
                                    <small>Recommended Method</small>
                                    <p class="mb-0 mt-2">
                                        <small class="text-muted">Based on current data</small>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Comparison Table -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Detailed Method Comparison</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>Metric</th>
                                    <th class="text-end">FIFO Method</th>
                                    <th class="text-end">Weighted Average Cost</th>
                                    <th class="text-end">Difference</th>
                                    <th class="text-center">Impact</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Sales Revenue</strong></td>
                                    <td class="text-end">BDT {{ number_format($fifoReport->sales_revenue ?? 0, 2) }}</td>
                                    <td class="text-end">BDT {{ number_format($wacReport->sales_revenue ?? 0, 2) }}</td>
                                    <td class="text-end">-</td>
                                    <td class="text-center"><span class="badge bg-secondary">Same</span></td>
                                </tr>
                                <tr>
                                    <td>Cost of Goods Sold</td>
                                    <td class="text-end text-danger">BDT {{ number_format($fifoReport->cost_of_goods_sold ?? 0, 2) }}</td>
                                    <td class="text-end text-danger">BDT {{ number_format($wacReport->cost_of_goods_sold ?? 0, 2) }}</td>
                                    <td class="text-end text-danger">
                                        BDT {{ number_format(($fifoReport->cost_of_goods_sold ?? 0) - ($wacReport->cost_of_goods_sold ?? 0), 2) }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-warning">
                                            {{ (($fifoReport->cost_of_goods_sold ?? 0) - ($wacReport->cost_of_goods_sold ?? 0)) >= 0 ? 'FIFO Higher' : 'WAC Higher' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Gross Profit</strong></td>
                                    <td class="text-end text-success">BDT {{ number_format($fifoReport->gross_profit ?? 0, 2) }}</td>
                                    <td class="text-end text-success">BDT {{ number_format($wacReport->gross_profit ?? 0, 2) }}</td>
                                    <td class="text-end text-success">
                                        BDT {{ number_format(($fifoReport->gross_profit ?? 0) - ($wacReport->gross_profit ?? 0), 2) }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success">
                                            {{ (($fifoReport->gross_profit ?? 0) - ($wacReport->gross_profit ?? 0)) >= 0 ? 'FIFO Better' : 'WAC Better' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Operating Expenses</td>
                                    <td class="text-end text-warning">BDT {{ number_format($fifoReport->operating_expenses ?? 0, 2) }}</td>
                                    <td class="text-end text-warning">BDT {{ number_format($wacReport->operating_expenses ?? 0, 2) }}</td>
                                    <td class="text-end">-</td>
                                    <td class="text-center"><span class="badge bg-secondary">Same</span></td>
                                </tr>
                                <tr>
                                    <td>Inventory Losses</td>
                                    <td class="text-end text-danger">BDT {{ number_format($fifoReport->inventory_losses ?? 0, 2) }}</td>
                                    <td class="text-end text-danger">BDT {{ number_format($wacReport->inventory_losses ?? 0, 2) }}</td>
                                    <td class="text-end">-</td>
                                    <td class="text-center"><span class="badge bg-secondary">Same</span></td>
                                </tr>
                                <tr class="table-primary">
                                    <td><strong>Net Profit</strong></td>
                                    <td class="text-end"><strong>BDT {{ number_format($fifoReport->net_profit ?? 0, 2) }}</strong></td>
                                    <td class="text-end"><strong>BDT {{ number_format($wacReport->net_profit ?? 0, 2) }}</strong></td>
                                    <td class="text-end"><strong>BDT {{ number_format($profitDifference, 2) }}</strong></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary">
                                            {{ $profitDifference >= 0 ? 'FIFO Higher' : 'WAC Higher' }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Method Explanation -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">FIFO Method Explanation</h6>
                </div>
                <div class="card-body">
                    <h6>How FIFO Works:</h6>
                    <ul>
                        <li>Uses cost of oldest inventory first</li>
                        <li>Matches physical inventory flow</li>
                        <li>Better during inflation periods</li>
                        <li>More complex record keeping</li>
                        <li>May show higher profits in inflationary times</li>
                    </ul>

                    <h6>Advantages:</h6>
                    <ul>
                        <li>Realistic inventory valuation</li>
                        <li>Better tax advantages</li>
                        <li>Matches actual cost flow</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Weighted Average Cost Explanation</h6>
                </div>
                <div class="card-body">
                    <h6>How WAC Works:</h6>
                    <ul>
                        <li>Averages all purchase costs</li>
                        <li>Single cost per product</li>
                        <li>Smoother profit calculations</li>
                        <li>Simpler inventory management</li>
                        <li>Stable cost reporting</li>
                    </ul>

                    <h6>Advantages:</h6>
                    <ul>
                        <li>Easier to maintain</li>
                        <li>Smooth earnings pattern</li>
                        <li>Less record keeping</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Recommendations -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Recommendations</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="mdi mdi-lightbulb-on"></i> Method Selection Guidelines:</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Choose FIFO when:</h6>
                                <ul>
                                    <li>Inflation is significant</li>
                                    <li>Tax minimization is important</li>
                                    <li>Detailed inventory tracking is feasible</li>
                                    <li>Physical inventory flow matches cost flow</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Choose Weighted Average when:</h6>
                                <ul>
                                    <li>Stable earnings are preferred</li>
                                    <li>Simplified accounting is desired</li>
                                    <li>Price fluctuations need smoothing</li>
                                    <li>Complex inventory tracking is challenging</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <p class="mb-2"><strong>Current Analysis Result:</strong></p>
                        @if($profitDifference >= 0)
                        <div class="alert alert-success">
                            <strong>FIFO method</strong> currently shows higher profitability (BDT {{ number_format($profitDifference, 2) }} difference).
                            Consider using FIFO for better financial reporting and potential tax advantages.
                        </div>
                        @else
                        <div class="alert alert-success">
                            <strong>Weighted Average Cost method</strong> currently shows higher profitability (BDT {{ number_format(abs($profitDifference), 2) }} difference).
                            This method provides smoother earnings and simpler inventory management.
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

