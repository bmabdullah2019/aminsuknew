@extends('backEnd.layouts.master')
@section('title','Return Management Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right d-flex gap-2">
                    <a href="{{ route('admin.returns.create') }}" class="btn btn-sm btn-primary">
                        <i class="mdi mdi-plus"></i> Create Return
                    </a>
                    <a href="{{ route('admin.returns.index') }}" class="btn btn-sm btn-outline-primary">
                        <i class="mdi mdi-format-list-bulleted"></i> All Returns
                    </a>
                </div>
                <h4 class="page-title">Return Management Dashboard</h4>
                <p class="text-muted mb-0">Comprehensive return processing and analytics</p>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.returns.dashboard') }}" class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label mb-1">Start Date</label>
                            <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label mb-1">End Date</label>
                            <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
                        </div>
                        <div class="col-md-4 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
                            <a href="{{ route('admin.returns.dashboard') }}" class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ number_format((int) ($stats['total_returns'] ?? 0)) }}</h4>
                    <small>Total Returns</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">BDT {{ number_format((float) ($stats['total_return_value'] ?? 0), 2) }}</h4>
                    <small>Total Return Value</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">BDT {{ number_format((float) ($stats['total_refund_amount'] ?? 0), 2) }}</h4>
                    <small>Total Refunds</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ number_format((float) ($stats['return_rate'] ?? 0), 2) }}%</h4>
                    <small>Return Rate</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Returns by Status</h6>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="130"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Quick Actions</h6>
                </div>
                <div class="card-body d-grid gap-2">
                    <a href="{{ route('admin.returns.create') }}" class="btn btn-primary">
                        <i class="mdi mdi-plus"></i> Create Return
                    </a>
                    <a href="{{ route('admin.returns.analytics', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-outline-info">
                        <i class="mdi mdi-chart-line"></i> View Analytics
                    </a>
                    <a href="{{ route('admin.returns.export', ['format' => 'csv', 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-outline-success">
                        <i class="mdi mdi-download"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Recent Returns</h6>
                </div>
                <div class="card-body">
                    @forelse($recentReturns as $return)
                    <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-2">
                        <div>
                            <div class="fw-semibold">{{ $return->return_number }}</div>
                            <small class="text-muted">{{ optional($return->customer)->name ?? 'Unknown Customer' }}</small><br>
                            <small class="text-muted">{{ optional($return->returnReason)->reason_name ?? 'Unknown Reason' }}</small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-{{ $return->status_color }}">{{ $return->status_label }}</span><br>
                            <small class="text-muted">{{ optional($return->created_at)->diffForHumans() }}</small>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted mb-0">No recent returns found.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Pending Approvals</h6>
                </div>
                <div class="card-body">
                    @forelse($pendingReturns as $return)
                    <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-2">
                        <div>
                            <div class="fw-semibold">{{ $return->return_number }}</div>
                            <small class="text-muted">{{ optional($return->customer)->name ?? 'Unknown Customer' }}</small><br>
                            <small class="text-muted">{{ optional($return->returnReason)->reason_name ?? 'Unknown Reason' }}</small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-warning">Pending</span><br>
                            <small class="text-muted">BDT {{ number_format((float) $return->total_return_value, 2) }}</small>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted mb-0">No pending approvals.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Returns by Reason</h6>
                </div>
                <div class="card-body">
                    <canvas id="reasonsChart" height="100"></canvas>
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
    const statusData = @json($returnsByStatus);
    const reasonData = @json($returnsByReason);

    const statusChartCtx = document.getElementById('statusChart');
    if (statusChartCtx) {
        new Chart(statusChartCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(statusData),
                datasets: [{
                    data: Object.values(statusData),
                    backgroundColor: [
                        'rgba(255, 193, 7, 0.85)',
                        'rgba(13, 110, 253, 0.85)',
                        'rgba(25, 135, 84, 0.85)',
                        'rgba(111, 66, 193, 0.85)',
                        'rgba(220, 53, 69, 0.85)',
                        'rgba(108, 117, 125, 0.85)',
                        'rgba(32, 201, 151, 0.85)'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    const reasonsChartCtx = document.getElementById('reasonsChart');
    if (reasonsChartCtx) {
        new Chart(reasonsChartCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: Object.keys(reasonData),
                datasets: [{
                    label: 'Number of Returns',
                    data: Object.values(reasonData),
                    backgroundColor: 'rgba(54, 162, 235, 0.8)',
                    borderColor: 'rgb(54, 162, 235)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
})();
</script>
@endpush
