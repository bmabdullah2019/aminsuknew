@extends('backEnd.layouts.master')
@section('title', 'Low Stock Report')
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
            <div class="page-title-box d-flex align-items-center gap-2">
                <h4 class="page-title mb-0">Low Stock Report</h4>
                <span class="badge bg-warning text-dark">⚠ Items at or below reorder level</span>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-3 no-print">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports-new.low-stock') }}" class="row g-2">
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
                    <a href="{{ route('admin.reports-new.low-stock') }}" class="btn btn-sm btn-secondary">Reset</a>
                    <button onclick="window.print()" type="button" class="btn btn-sm btn-success no-print">Print</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary --}}
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body py-2">
                    <small>Total Physical Stock</small>
                    <h6 class="mb-0">{{ number_format($summary['total_physical_qty'], 2) }}</h6>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body py-2">
                    <small>Total Available Stock</small>
                    <h6 class="mb-0">{{ number_format($summary['total_available_qty'], 2) }}</h6>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-secondary text-white">
                <div class="card-body py-2">
                    <small>Stock Value (Cost)</small>
                    <h6 class="mb-0">{{ number_format($summary['total_stock_value'], 2) }}</h6>
                </div>
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
                            <tr class="{{ (float)$row->available_quantity <= 0 ? 'table-danger' : 'table-warning' }}">
                               <td>{{ $loop->iteration }}</td>
                                <td>{{ $row->product_name }}</td>
                                <td>{{ $row->warehouse_name }}</td>
                                <td class="text-end">{{ number_format((float)$row->physical_quantity, 2) }}</td>
                                <td class="text-end">{{ number_format((float)$row->reserved_quantity, 2) }}</td>
                                <td class="text-end fw-bold">{{ number_format((float)$row->available_quantity, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No low stock items found. All products are sufficiently stocked!
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
