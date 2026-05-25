<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="card-title mb-0">Supplier Performance Metrics</h5>
            <div>
                <a href="{{ route('admin.supplier.reports', array_merge(request()->query(), ['type' => 'performance', 'export' => 'xlsx'])) }}" class="btn btn-sm btn-outline-primary me-2">
                    <i class="fe-download"></i> Export
                </a>
                <button class="btn btn-sm btn-outline-secondary" onclick="printReport()">
                    <i class="fe-printer"></i> Print
                </button>
            </div>
        </div>

        <!-- Performance Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h4 class="mb-0">{{ $data['total_suppliers'] ?? 0 }}</h4>
                        <small>Total Suppliers</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h4 class="mb-0">{{ number_format($data['average_performance_score'] ?? 0, 1) }}%</h4>
                        <small>Average Performance</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h4 class="mb-0">{{ $data['suppliers_with_dues'] ?? 0 }}</h4>
                        <small>With Outstanding Dues</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h4 class="mb-0">{{ $data['suppliers_over_credit_limit'] ?? 0 }}</h4>
                        <small>Over Credit Limit</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Performers -->
        @if(($data['top_performers'] ?? collect())->count() > 0)
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="fe-star"></i> Top Performers</h6>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            @foreach(($data['top_performers'] ?? collect()) as $supplier)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{$supplier->supplier_code}}</strong> - {{$supplier->name}}
                                </div>
                                <span class="badge bg-success">{{ number_format($supplier->performance_score, 1) }}%</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Under Performers -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h6 class="mb-0"><i class="fe-alert-triangle"></i> Needs Attention</h6>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            @foreach(($data['under_performers'] ?? collect()) as $supplier)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{$supplier->supplier_code}}</strong> - {{$supplier->name}}
                                    @if($supplier->total_dues > 0)
                                        <br><small class="text-danger">Outstanding: BDT {{ number_format($supplier->total_dues ?? 0, 2) }}</small>
                                    @endif
                                </div>
                                <span class="badge bg-danger">{{ number_format($supplier->performance_score, 1) }}%</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Detailed Performance Table -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Detailed Performance Analysis</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive report-sticky-container">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Supplier Code</th>
                                <th>Supplier Name</th>
                                <th class="text-end">Performance Score</th>
                                <th class="text-end">Current Balance</th>
                                <th>Payment Status</th>
                                <th>Total Purchases</th>
                                <th>Total Returns</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($performanceSuppliers ?? collect()) as $supplier)
                            <tr>
                                <td><strong>{{$supplier->supplier_code}}</strong></td>
                                <td>
                                    <a href="{{route('admin.supplier.show', $supplier->id)}}" class="text-primary">
                                        {{$supplier->name}}
                                    </a>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-{{ $supplier->performance_score >= 80 ? 'success' : ($supplier->performance_score >= 60 ? 'warning' : 'danger') }}">
                                        {{ number_format($supplier->performance_score, 1) }}%
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-{{ $supplier->current_balance >= 0 ? 'danger' : 'success' }}">
                                        BDT {{ number_format(abs($supplier->current_balance), 2) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ match($supplier->payment_status) {
                                        'paid' => 'success',
                                        'current' => 'info',
                                        'due_soon' => 'warning',
                                        'overdue' => 'danger',
                                        'critical' => 'dark',
                                        default => 'secondary'
                                    } }}">
                                        {{ ucfirst(str_replace('_', ' ', $supplier->payment_status)) }}
                                    </span>
                                </td>
                                <td>
                                    BDT {{ number_format((float) ($supplier->total_purchases_amount ?? 0), 2) }}
                                </td>
                                <td>
                                    BDT {{ number_format((float) ($supplier->approved_returns_amount ?? 0), 2) }}
                                </td>
                                <td>
                                    <a href="{{route('admin.supplier.show', $supplier->id)}}" class="btn btn-sm btn-info">
                                        <i class="fe-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">No supplier performance data available.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function printReport() {
    window.print();
}
</script>

