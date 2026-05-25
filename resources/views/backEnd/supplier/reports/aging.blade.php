<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0">Supplier Aging Report</h5>
            <div>
                <a href="{{ route('admin.supplier.reports', array_merge(request()->query(), ['type' => 'aging', 'export' => 'xlsx'])) }}" class="btn btn-sm btn-outline-primary me-2">
                    <i class="fe-download"></i> Export
                </a>
                <button class="btn btn-sm btn-outline-secondary" onclick="printReport()">
                    <i class="fe-printer"></i> Print
                </button>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h4 class="mb-0">BDT {{ number_format($data['current'] ?? 0, 2) }}</h4>
                        <small>Current (0-30 days)</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h4 class="mb-0">BDT {{ number_format($data['overdue_1_30'] ?? 0, 2) }}</h4>
                        <small>Overdue (31-60 days)</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h4 class="mb-0">BDT {{ number_format(($data['overdue_31_60'] ?? 0) + ($data['overdue_61_90'] ?? 0), 2) }}</h4>
                        <small>Overdue (31-90 days)</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-dark text-white">
                    <div class="card-body text-center">
                        <h4 class="mb-0">BDT {{ number_format($data['overdue_90_plus'] ?? 0, 2) }}</h4>
                        <small>Overdue (90+ days)</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive report-sticky-container">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Supplier Code</th>
                        <th>Supplier Name</th>
                        <th class="text-end">Current</th>
                        <th class="text-end">1-30 Days</th>
                        <th class="text-end">31-60 Days</th>
                        <th class="text-end">61-90 Days</th>
                        <th class="text-end">90+ Days</th>
                        <th class="text-end">Total Dues</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalCurrent = (float) (($agingRows ?? collect())->sum(function ($row) {
                            return (float) ($row['aging']['current'] ?? 0);
                        }));
                        $total1_30 = (float) (($agingRows ?? collect())->sum(function ($row) {
                            return (float) ($row['aging']['overdue_1_30'] ?? 0);
                        }));
                        $total31_60 = (float) (($agingRows ?? collect())->sum(function ($row) {
                            return (float) ($row['aging']['overdue_31_60'] ?? 0);
                        }));
                        $total61_90 = (float) (($agingRows ?? collect())->sum(function ($row) {
                            return (float) ($row['aging']['overdue_61_90'] ?? 0);
                        }));
                        $total90_plus = (float) (($agingRows ?? collect())->sum(function ($row) {
                            return (float) ($row['aging']['overdue_90_plus'] ?? 0);
                        }));
                        $grandTotal = (float) (($agingRows ?? collect())->sum(function ($row) {
                            return (float) ($row['aging']['total'] ?? 0);
                        }));
                    @endphp

                    @forelse(($agingRows ?? collect()) as $row)
                        @php
                            $supplier = $row['supplier'];
                            $aging = $row['aging'];
                        @endphp
                        <tr>
                            <td><strong>{{$supplier->supplier_code}}</strong></td>
                            <td>
                                <a href="{{route('admin.supplier.show', $supplier->id)}}" class="text-primary">
                                    {{$supplier->name}}
                                </a>
                            </td>
                            <td class="text-end text-success">BDT {{ number_format($aging['current'], 2) }}</td>
                            <td class="text-end text-warning">BDT {{ number_format($aging['overdue_1_30'], 2) }}</td>
                            <td class="text-end text-danger">BDT {{ number_format($aging['overdue_31_60'], 2) }}</td>
                            <td class="text-end text-danger">BDT {{ number_format($aging['overdue_61_90'], 2) }}</td>
                            <td class="text-end text-dark">BDT {{ number_format($aging['overdue_90_plus'], 2) }}</td>
                            <td class="text-end fw-bold">BDT {{ number_format($aging['total'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No suppliers with outstanding aging balances.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-dark">
                    <tr>
                        <th colspan="2" class="text-end">TOTAL</th>
                        <th class="text-end">BDT {{ number_format($totalCurrent, 2) }}</th>
                        <th class="text-end">BDT {{ number_format($total1_30, 2) }}</th>
                        <th class="text-end">BDT {{ number_format($total31_60, 2) }}</th>
                        <th class="text-end">BDT {{ number_format($total61_90, 2) }}</th>
                        <th class="text-end">BDT {{ number_format($total90_plus, 2) }}</th>
                        <th class="text-end">BDT {{ number_format($grandTotal, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script>
function printReport() {
    window.print();
}
</script>

