@extends('backEnd.layouts.master')
@section('title','Return Analytics')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right d-flex gap-2">
                    <a href="{{ route('admin.returns.dashboard') }}" class="btn btn-sm btn-outline-primary">
                        <i class="mdi mdi-view-dashboard"></i> Dashboard
                    </a>
                    <a
                        href="{{ route('admin.returns.export', array_merge(request()->query(), ['format' => 'csv'])) }}"
                        class="btn btn-sm btn-success"
                    >
                        <i class="mdi mdi-download"></i> Export CSV
                    </a>
                </div>
                <h4 class="page-title">Return Analytics</h4>
                <p class="text-muted mb-0">Comprehensive return analysis and reporting</p>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.returns.analytics') }}" class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label mb-1">Start Date</label>
                            <input type="date" class="form-control" name="start_date" value="{{ $filters['start_date'] ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label mb-1">End Date</label>
                            <input type="date" class="form-control" name="end_date" value="{{ $filters['end_date'] ?? '' }}">
                        </div>
                        <div class="col-md-4 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
                            <a href="{{ route('admin.returns.analytics') }}" class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body text-center">
                    <div class="h4 mb-0">{{ number_format((int) ($stats['total_returns'] ?? 0)) }}</div>
                    <small>Total Returns</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body text-center">
                    <div class="h4 mb-0">BDT {{ number_format((float) ($stats['total_return_value'] ?? 0), 2) }}</div>
                    <small>Total Return Value</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body text-center">
                    <div class="h4 mb-0">BDT {{ number_format((float) ($stats['total_refund_amount'] ?? 0), 2) }}</div>
                    <small>Total Refund</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white h-100">
                <div class="card-body text-center">
                    <div class="h4 mb-0">{{ number_format((float) ($stats['return_rate'] ?? 0), 2) }}%</div>
                    <small>Return Rate</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Monthly Trend</h6>
                </div>
                <div class="card-body">
                    <canvas id="monthlyTrendChart" height="120"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Reason Distribution</h6>
                </div>
                <div class="card-body">
                    <canvas id="reasonChart" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Reason-wise Analysis</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Reason</th>
                                    <th>Category</th>
                                    <th class="text-end">Count</th>
                                    <th class="text-end">Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reasonAnalysis as $reason)
                                <tr>
                                    <td>{{ $reason['reason'] }}</td>
                                    <td>{{ ucfirst($reason['category']) }}</td>
                                    <td class="text-end">{{ number_format((int) $reason['count']) }}</td>
                                    <td class="text-end">BDT {{ number_format((float) $reason['value'], 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No data available for selected period</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Top Returned Products</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-end">Returned Qty</th>
                                    <th class="text-end">Refund</th>
                                    <th class="text-end">Return Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($productAnalysis as $item)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ optional($item['product'])->name ?? optional($item['product'])->product_name ?? 'Unknown Product' }}</div>
                                        <small class="text-muted">{{ optional($item['product'])->sku ?? optional($item['product'])->product_code ?? 'N/A' }}</small>
                                    </td>
                                    <td class="text-end">{{ number_format((float) $item['total_returned'], 2) }}</td>
                                    <td class="text-end">BDT {{ number_format((float) $item['total_refund'], 2) }}</td>
                                    <td class="text-end">{{ number_format((float) $item['return_rate'], 2) }}%</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No product return data available</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
    const monthlyData = @json($monthlyTrends);
    const reasonData = @json($reasonAnalysis);

    const monthlyLabels = monthlyData.map((item) => item.month);
    const monthlyCounts = monthlyData.map((item) => Number(item.count || 0));
    const monthlyValues = monthlyData.map((item) => Number(item.value || 0));

    const reasonLabels = reasonData.map((item) => item.reason);
    const reasonCounts = reasonData.map((item) => Number(item.count || 0));

    const monthlyChartCtx = document.getElementById('monthlyTrendChart');
    if (monthlyChartCtx) {
        new Chart(monthlyChartCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: monthlyLabels,
                datasets: [
                    {
                        label: 'Return Count',
                        data: monthlyCounts,
                        borderColor: 'rgb(54, 162, 235)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        tension: 0.3,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Return Value (BDT)',
                        data: monthlyValues,
                        borderColor: 'rgb(255, 159, 64)',
                        backgroundColor: 'rgba(255, 159, 64, 0.2)',
                        tension: 0.3,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    y: {
                        type: 'linear',
                        position: 'left',
                        beginAtZero: true
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    }

    const reasonChartCtx = document.getElementById('reasonChart');
    if (reasonChartCtx) {
        new Chart(reasonChartCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: reasonLabels,
                datasets: [{
                    data: reasonCounts,
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.85)',
                        'rgba(255, 99, 132, 0.85)',
                        'rgba(255, 205, 86, 0.85)',
                        'rgba(75, 192, 192, 0.85)',
                        'rgba(153, 102, 255, 0.85)',
                        'rgba(255, 159, 64, 0.85)'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
})();
</script>
@endpush
