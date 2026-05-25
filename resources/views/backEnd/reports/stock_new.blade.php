@extends('backEnd.layouts.master')
@section('title', 'Stock Report')
@section('css')
<link href="{{ asset('public/backEnd') }}/assets/libs/select2/css/select2.min.css" rel="stylesheet">
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
                <h4 class="page-title">Stock Report (Warehouse-wise)</h4>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-3 no-print">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports-new.stock') }}" class="row g-2">
                <div class="col-md-3">
                    <label class="form-label">Warehouse</label>
                    <select name="warehouse_id" class="form-control form-control-sm select2">
                        <option value="">All Warehouses</option>
                        @foreach(\App\Models\Warehouse::where('is_active', true)->orderBy('name')->get() as $wh)
                            <option value="{{ $wh->id }}" {{ (string)($filter->warehouseId) === (string)$wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    <a href="{{ route('admin.reports-new.stock') }}" class="btn btn-sm btn-secondary">Reset</a>
                    <button onclick="window.print()" type="button" class="btn btn-sm btn-success no-print">Print</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary --}}
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body py-2">
                    <small>Total Physical Qty</small>
                    <h6 class="mb-0">{{ number_format($summary['total_physical_qty'], 2) }}</h6>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body py-2">
                    <small>Total Available Qty</small>
                    <h6 class="mb-0">{{ number_format($summary['total_available_qty'], 2) }}</h6>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body py-2">
                    <small>Stock Value (Cost)</small>
                    <h6 class="mb-0">{{ number_format($summary['total_stock_value'], 2) }}</h6>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="text-end no-print">
                <a href="{{ route('admin.reports-new.low-stock') }}" class="btn btn-warning btn-sm">⚠ View Low Stock</a>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="card-body">
            <div class="table-responsive report-sticky-container" id="content-to-export">
                <table class="table table-sm table-bordered table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Warehouse</th>
                            <th class="text-end">Physical Qty</th>
                            <th class="text-end">Reserved</th>
                            <th class="text-end">Available</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stock as $row)
                            @php
                                $avail = (float)$row->available_quantity;
                                $rowClass = $avail <= 0 ? 'table-danger' : ($avail <= 5 ? 'table-warning' : '');
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $row->product_name }}</td>
                                <td>{{ $row->warehouse_name }}</td>
                                <td class="text-end">{{ number_format((float)$row->physical_quantity, 2) }}</td>
                                <td class="text-end">{{ number_format((float)$row->reserved_quantity, 2) }}</td>
                                <td class="text-end fw-bold">{{ number_format($avail, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No warehouse stock data found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-secondary fw-bold">
                            <td colspan="3" class="text-end">Totals</td>
                            <td class="text-end">{{ number_format($stock->sum('physical_quantity'), 2) }}</td>
                            <td class="text-end">{{ number_format($stock->sum('reserved_quantity'), 2) }}</td>
                            <td class="text-end">{{ number_format($stock->sum('available_quantity'), 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
@section('script')
<script src="{{ asset('public/backEnd/') }}/assets/libs/select2/js/select2.min.js"></script>
<script>
    $(document).ready(function () {
        $('.select2').select2();
    });
</script>
@endsection
