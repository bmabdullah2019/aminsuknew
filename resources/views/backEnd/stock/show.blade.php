@extends('backEnd.layouts.master')
@section('title','Stock Details')
@section('content')
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{route('admin.stock.edit', [$stock->warehouse_id, $stock->product_id])}}" class="btn btn-warning rounded-pill"><i class="fe-edit"></i> Edit Stock</a>
                    <a href="{{route('admin.stock.inventory')}}" class="btn btn-info rounded-pill"><i class="fe-grid"></i> Advanced Inventory</a>
                    <a href="{{route('admin.stock.balance')}}" class="btn btn-outline-primary rounded-pill"><i class="fe-list"></i> Stock Balance</a>
                    <a href="{{route('admin.stock.movements')}}" class="btn btn-info rounded-pill"><i class="fe-activity"></i> Movements</a>
                </div>
                <h4 class="page-title">Stock Details</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <!-- Product Information -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Product Information</h5>
                    <div class="text-center mb-3">
                        @if($stock->product->images->first())
                            <img src="{{ asset($stock->product->images->first()->image) }}"
                                 alt="{{ $stock->product->name }}" class="img-fluid rounded" style="max-height: 150px;">
                        @else
                            <div class="bg-light rounded d-flex align-items-center justify-content-center mx-auto"
                                 style="width: 150px; height: 150px;">
                                <i class="fe-image text-muted" style="font-size: 3rem;"></i>
                            </div>
                        @endif
                    </div>

                    <table class="table table-sm">
                        <tr>
                            <th>Name:</th>
                            <td>{{ $stock->product->name }}</td>
                        </tr>
                        <tr>
                            <th>SKU:</th>
                            <td>{{ $stock->product->sku ?: 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Code:</th>
                            <td>{{ $stock->product->product_code ?: 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Category:</th>
                            <td>{{ $stock->product->category->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Brand:</th>
                            <td>{{ $stock->product->brand->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Price:</th>
                            <td>৳{{ number_format($stock->product->new_price, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Warehouse Information -->
            <div class="card mt-3">
                <div class="card-body">
                    <h5 class="card-title">Warehouse Information</h5>
                    <table class="table table-sm">
                        <tr>
                            <th>Warehouse:</th>
                            <td>{{ $stock->warehouse->name }}</td>
                        </tr>
                        <tr>
                            <th>Code:</th>
                            <td>{{ $stock->warehouse->code }}</td>
                        </tr>
                        <tr>
                            <th>Location:</th>
                            <td>{{ $stock->warehouse->address ?: 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Stock Details -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Stock Details</h5>

                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h4 class="mt-0">{{ number_format($stock->physical_quantity, 2) }}</h4>
                                    <p class="mb-0">Physical Stock</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h4 class="mt-0">{{ number_format($stock->available_quantity, 2) }}</h4>
                                    <p class="mb-0">Available Stock</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h4 class="mt-0">{{ number_format($stock->reserved_quantity, 2) }}</h4>
                                    <p class="mb-0">Reserved Stock</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h4 class="mt-0">৳{{ number_format($stock->total_value, 2) }}</h4>
                                    <p class="mb-0">Total Value</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th>Reorder Point:</th>
                                <td>{{ number_format($stock->reorder_point, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Reorder Quantity:</th>
                                <td>{{ number_format($stock->reorder_quantity, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Average Cost:</th>
                                <td>৳{{ number_format($stock->average_cost ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Last Movement:</th>
                                <td>
                                    @if($movements->count() > 0)
                                        {{ $movements->first()->created_at->format('M d, Y H:i') }}
                                    @else
                                        Never
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Last Audit:</th>
                                <td>
                                    {{ $stock->last_audit_date ? $stock->last_audit_date->format('M d, Y H:i') : 'Never' }}
                                </td>
                            </tr>
                            <tr>
                                <th>Created:</th>
                                <td>{{ $stock->created_at->format('M d, Y H:i') }}</td>
                            </tr>
                            <tr>
                                <th>Updated:</th>
                                <td>{{ $stock->updated_at->format('M d, Y H:i') }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Stock Status -->
                    <div class="mt-3">
                        <h6>Stock Status:</h6>
                        @if($stock->available_quantity <= 0)
                            <span class="badge bg-danger fs-6">Out of Stock</span>
                        @elseif($stock->available_quantity <= $stock->reorder_point)
                            <span class="badge bg-warning text-dark fs-6">Low Stock Alert</span>
                        @else
                            <span class="badge bg-success fs-6">In Stock</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Recent Movements -->
            <div class="card mt-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Recent Stock Movements</h5>
                        <a href="{{ route('admin.stock.movements', ['product_id' => $stock->product_id, 'warehouse_id' => $stock->warehouse_id]) }}"
                           class="btn btn-sm btn-outline-primary">
                            <i class="fe-eye"></i> View All
                        </a>
                    </div>

                    @if($movements->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Quantity</th>
                                        <th>Balance</th>
                                        <th>Reason</th>
                                        <th>User</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($movements->take(10) as $movement)
                                    <tr>
                                        <td>{{ $movement->created_at->format('M d, H:i') }}</td>
                                        <td>
                                            @if(str_contains($movement->type, 'in') || str_contains($movement->type, 'received'))
                                                <span class="badge bg-success">In</span>
                                            @else
                                                <span class="badge bg-danger">Out</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="{{ str_contains($movement->type, 'in') || str_contains($movement->type, 'received') ? 'text-success' : 'text-danger' }}">
                                                {{ str_contains($movement->type, 'in') || str_contains($movement->type, 'received') ? '+' : '-' }}
                                                {{ number_format(abs($movement->quantity), 2) }}
                                            </span>
                                        </td>
                                        <td>{{ number_format($movement->balance_after, 2) }}</td>
                                        <td>
                                            <small>{{ Str::limit($movement->notes, 30) }}</small>
                                        </td>
                                        <td>{{ $movement->creator->name ?? 'System' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fe-activity font-24 text-muted"></i>
                            <div class="mt-2">No stock movements found</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('css')
<style>
.card {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: 1px solid #e9ecef;
}

.table th {
    background-color: #f8f9fa;
    font-weight: 600;
}

.badge {
    font-size: 0.75rem;
}

.card-title {
    color: #495057;
    margin-bottom: 1rem;
}
</style>
@endpush
