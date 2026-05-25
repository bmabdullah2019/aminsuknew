<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0">Suppliers with Outstanding Dues</h5>
            <div>
                <a href="{{ route('admin.supplier.reports', array_merge(request()->query(), ['type' => 'dues', 'export' => 'xlsx'])) }}" class="btn btn-sm btn-outline-primary me-2">
                    <i class="fe-download"></i> Export
                </a>
                <button class="btn btn-sm btn-outline-secondary" onclick="printReport()">
                    <i class="fe-printer"></i> Print
                </button>
            </div>
        </div>

        @if($suppliers->count() > 0)
        <div class="table-responsive report-sticky-container">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Supplier Code</th>
                        <th>Supplier Name</th>
                        <th>Contact</th>
                        <th class="text-end">Outstanding Amount</th>
                        <th>Payment Terms</th>
                        <th>Last Payment</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalDues = 0; @endphp
                    @foreach($suppliers as $supplier)
                        @php $totalDues += $supplier->total_dues; @endphp
                        <tr>
                            <td><strong>{{$supplier->supplier_code}}</strong></td>
                            <td>
                                <a href="{{route('admin.supplier.show', $supplier->id)}}" class="text-primary">
                                    {{$supplier->name}}
                                </a>
                            </td>
                            <td>
                                @if($supplier->email)
                                    <div><i class="fe-mail"></i> {{$supplier->email}}</div>
                                @endif
                                @if($supplier->phone)
                                    <div><i class="fe-phone"></i> {{$supplier->phone}}</div>
                                @endif
                            </td>
                            <td class="text-end">
                                <span class="badge bg-danger fs-6">
                                    BDT {{ number_format($supplier->total_dues ?? 0, 2) }}
                                </span>
                            </td>
                            <td>{{$supplier->payment_terms_days}} days</td>
                            <td>
                                @if(!empty($supplier->last_completed_payment_date))
                                    {{ \Carbon\Carbon::parse($supplier->last_completed_payment_date)->format('d M Y') }}
                                @else
                                    <span class="text-muted">Never</span>
                                @endif
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
                                <a href="{{ route('admin.supplier.payments.create', $supplier->id) }}?amount={{ number_format((float) ($supplier->total_dues ?? 0), 2, '.', '') }}" class="btn btn-sm btn-success">
                                    <i class="fe-dollar-sign"></i> Pay
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-dark">
                    <tr>
                        <th colspan="3" class="text-end">TOTAL OUTSTANDING</th>
                        <th class="text-end">BDT {{ number_format($totalDues ?? 0, 2) }}</th>
                        <th colspan="4"></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center">
            {{ $suppliers->appends(request()->query())->links() }}
        </div>
        @else
        <div class="text-center py-5">
            <i class="fe-check-circle text-success" style="font-size: 4rem;"></i>
            <h4 class="mt-3">All Clear!</h4>
            <p class="text-muted">No suppliers have outstanding dues at this time.</p>
        </div>
        @endif
    </div>
</div>

<script>
function printReport() {
    window.print();
}
</script>

