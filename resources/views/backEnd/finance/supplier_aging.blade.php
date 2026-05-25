@extends('backEnd.layouts.master')
@section('title', 'Supplier Aging Report')
@section('content')

<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Supplier Aging Report</h6>
                <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
            
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th>Supplier Name</th>
                                <th>1-30 Days</th>
                                <th>31-60 Days</th>
                                <th>61-90 Days</th>
                                <th>90+ Days</th>
                                <th class="text-end fw-bold">Total Due</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $total_30 = 0;
                                $total_60 = 0;
                                $total_90 = 0;
                                $total_90p = 0;
                                $total_all = 0;
                            @endphp
                            @forelse($suppliers as $supplier_row)
                                @php
                                    $due_30 = max($supplier_row->due_30, 0);
                                    $due_60 = max($supplier_row->due_60, 0);
                                    $due_90 = max($supplier_row->due_90, 0);
                                    $due_90_plus = max($supplier_row->due_90_plus, 0);
                                    $total_due = max($supplier_row->total_due, 0);

                                    $total_30 += $due_30;
                                    $total_60 += $due_60;
                                    $total_90 += $due_90;
                                    $total_90p += $due_90_plus;
                                    $total_all += $total_due;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-bold text-primary">{{ $supplier_row->supplier->name ?? 'Unknown' }}</div>
                                        <small class="text-muted">{{ $supplier_row->supplier->phone ?? '' }}</small>
                                    </td>
                                    <td>৳{{ number_format($due_30, 2) }}</td>
                                    <td>৳{{ number_format($due_60, 2) }}</td>
                                    <td class="text-warning">৳{{ number_format($due_90, 2) }}</td>
                                    <td class="text-danger fw-bold">৳{{ number_format($due_90_plus, 2) }}</td>
                                    <td class="text-end fw-bold">৳{{ number_format($total_due, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">No outstanding supplier payables currently.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-group-divider bg-light fw-bold fs-6">
                            <tr>
                                <td class="text-end">Grand Total:</td>
                                <td>৳{{ number_format($total_30, 2) }}</td>
                                <td>৳{{ number_format($total_60, 2) }}</td>
                                <td class="text-warning">৳{{ number_format($total_90, 2) }}</td>
                                <td class="text-danger">৳{{ number_format($total_90p, 2) }}</td>
                                <td class="text-end text-primary">৳{{ number_format($total_all, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
