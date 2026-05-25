@extends('backEnd.layouts.master')
@section('title', 'Purchase Report')
@section('css')
<link href="{{ asset('public/backEnd') }}/assets/libs/select2/css/select2.min.css" rel="stylesheet">
<link href="{{ asset('public/backEnd/') }}/assets/libs/flatpickr/flatpickr.min.css" rel="stylesheet">
<style>
    @media print {
        header, footer, .no-print, .left-side-menu, .navbar-custom { display: none !important; }
    }
</style>
@endsection
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Purchase Report</h4>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-3 no-print">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports-new.purchase') }}" class="row g-2">
                <div class="col-md-2">
                    <label class="form-label">Keyword</label>
                    <input type="text" name="keyword" class="form-control form-control-sm" value="{{ $filter->keyword }}" placeholder="Product/Supplier...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Period</label>
                    <select name="period" class="form-control form-control-sm select2">
                        @foreach(['custom' => 'Custom', 'daily' => 'Daily', 'monthly' => 'Monthly', 'yearly' => 'Yearly'] as $val => $label)
                            <option value="{{ $val }}" @selected($filter->period === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control form-control-sm flatdate" value="{{ $filter->startDate }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control form-control-sm flatdate" value="{{ $filter->endDate }}">
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    <a href="{{ route('admin.reports-new.purchase') }}" class="btn btn-sm btn-secondary">Reset</a>
                    <button onclick="window.print()" type="button" class="btn btn-sm btn-success no-print">Print</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body py-2">
                    <small>Total Purchase Orders</small>
                    <h6 class="mb-0">{{ number_format($summary['total_orders']) }}</h6>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body py-2">
                    <small>Total Items Purchased</small>
                    <h6 class="mb-0">{{ number_format($summary['total_quantity']) }}</h6>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body py-2">
                    <small>Total Purchase Amount</small>
                    <h6 class="mb-0">{{ number_format($summary['total_purchase_amount'], 2) }}</h6>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="card-body">
            <div class="row mb-2 no-print">
                <div class="col-6">{{ $purchases->links('pagination::bootstrap-4') }}</div>
            </div>
            <div class="table-responsive report-sticky-container purchase-report-table" id="content-to-export">
                <table class="table table-sm table-bordered table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Purchase No</th>
                            <th>Date</th>
                            <th>Supplier</th>
                            <th>Warehouse</th>
                            <th>Product</th>
                            <th class="text-end">Unit Cost</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchases as $row)
                            @php
                                $variantLabel = trim(implode(' / ', array_filter([
                                    (string) ($row->color ?? ''),
                                    (string) ($row->size ?? ''),
                                    (string) ($row->age ?? ''),
                                ])));
                                $productLabel = $row->product_name ?: $row->item_description ?: '-';
                                $skuLabel = $row->product_sku ?: $row->item_sku ?: '';
                            @endphp
                            <tr>
                                <td>{{ $purchases->firstItem() + $loop->index }}</td>
                                <td>{{ $row->order_number ?? '-' }}</td>
                                <td>{{ !empty($row->purchase_date) ? \Carbon\Carbon::parse($row->purchase_date)->format('d M Y') : \Carbon\Carbon::parse($row->created_at)->format('d M Y') }}</td>
                                <td>{{ $row->supplier_name ?? '-' }}</td>
                                <td>{{ $row->warehouse_name ?? '-' }}</td>
                                <td>
                                    {{ $productLabel }}
                                    @if($skuLabel !== '')
                                        <br><small class="text-muted">{{ $skuLabel }}</small>
                                    @endif
                                    @if($variantLabel !== '')
                                        <br><small class="text-muted">{{ $variantLabel }}</small>
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format((float) $row->unit_cost, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $row->quantity, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $row->total_cost, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    No purchase data found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-secondary fw-bold">
                            <td colspan="7" class="text-end">Totals</td>
                            <td class="text-end">{{ $purchases->sum('quantity') }}</td>
                            <td class="text-end">{{ number_format($purchases->sum('total_cost'), 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="no-print mt-2">{{ $purchases->links('pagination::bootstrap-4') }}</div>
        </div>
    </div>
</div>
<style>
    /* Dark sticky header with white text */
    .purchase-report-table thead.table-dark th {
        background: #212529 !important;
        color: #ffffff !important;
        border-bottom-color: #444 !important;
    }

    /* Dark sticky footer with white text */
    .purchase-report-table tfoot td {
        background-color: #212529 !important;
        color: #ffffff !important;
        border-top: 2px solid #444 !important;
    }
</style>
@endsection
@section('script')
<script src="{{ asset('public/backEnd/') }}/assets/libs/select2/js/select2.min.js"></script>
<script src="{{ asset('public/backEnd/') }}/assets/libs/flatpickr/flatpickr.min.js"></script>
<script>
    $(document).ready(function () {
        $('.select2').select2();
        flatpickr('.flatdate', {});
    });
</script>
@endsection
