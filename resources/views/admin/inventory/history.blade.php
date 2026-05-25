@extends('backEnd.layouts.master')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="mb-0">📜 Stock Movement History</h1>
            <small class="text-muted">Complete audit trail of all stock changes</small>
            <hr>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="inventory-filter-wrap">
                <form method="GET" action="{{ route('admin.inventory.history') }}" class="inventory-filter-form">
                    <select name="warehouse_id" class="form-control">
                        <option value="">All Warehouses</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="product_id" class="form-control">
                        <option value="">All Products</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="type" class="form-control">
                        <option value="">All Types</option>
                        <option value="grn" {{ request('type') == 'grn' ? 'selected' : '' }}>GRN (Received)</option>
                        <option value="sale" {{ request('type') == 'sale' ? 'selected' : '' }}>Sale</option>
                        <option value="transfer_in" {{ request('type') == 'transfer_in' ? 'selected' : '' }}>Transfer In</option>
                        <option value="transfer_out" {{ request('type') == 'transfer_out' ? 'selected' : '' }}>Transfer Out</option>
                        <option value="adjustment_in" {{ request('type') == 'adjustment_in' ? 'selected' : '' }}>Adjustment In</option>
                        <option value="adjustment_out" {{ request('type') == 'adjustment_out' ? 'selected' : '' }}>Adjustment Out</option>
                        <option value="loss" {{ request('type') == 'loss' ? 'selected' : '' }}>Loss</option>
                    </select>

                    <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                    <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">

                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="{{ route('admin.inventory.history') }}" class="btn btn-secondary">Reset</a>
                </form>
            </div>
        </div>
    </div>

    <!-- History Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Date/Time</th>
                                    <th>Warehouse</th>
                                    <th>Product</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>Reference</th>
                                    <th>Balance After</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($movements as $movement)
                                    <tr>
                                        <td>
                                            <small>{{ $movement->created_at->format('M d, Y H:i') }}</small>
                                        </td>
                                        <td>
                                            <small>{{ $movement->warehouse?->name ?? 'N/A' }}</small>
                                        </td>
                                        <td>
                                            <strong>{{ $movement->product?->name ?? 'N/A' }}</strong>
                                            <br>
                                            <small class="text-muted">SKU: {{ $movement->product?->sku ?? '-' }}</small>
                                        </td>
                                        <td>
                                            @switch($movement->type)
                                                @case('grn')
                                                    <span class="badge badge-success">📥 GRN</span>
                                                    @break
                                                @case('sale')
                                                    <span class="badge badge-danger">📤 Sale</span>
                                                    @break
                                                @case('transfer_in')
                                                    <span class="badge badge-info">➡️ Transfer In</span>
                                                    @break
                                                @case('transfer_out')
                                                    <span class="badge badge-warning">⬅️ Transfer Out</span>
                                                    @break
                                                @case('adjustment_in')
                                                    <span class="badge badge-secondary">🔧 Adjust In</span>
                                                    @break
                                                @case('adjustment_out')
                                                    <span class="badge badge-secondary">🔧 Adjust Out</span>
                                                    @break
                                                @case('loss')
                                                    <span class="badge badge-danger">❌ Loss</span>
                                                    @break
                                                @default
                                                    <span class="badge badge-gray">{{ $movement->type }}</span>
                                            @endswitch
                                        </td>
                                        <td>
                                            {{ $movement->quantity }}
                                        </td>
                                        <td>
                                            <small class="badge badge-light">{{ $movement->reference_type }}</small>
                                        </td>
                                        <td>
                                            <strong>{{ $movement->balance_after }}</strong>
                                        </td>
                                        <td>
                                            <small>{{ \Illuminate\Support\Str::limit($movement->notes ?? '-', 40) }}</small>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            No stock movements found
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-3">
                        {{ $movements->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .inventory-filter-wrap {
        overflow-x: auto;
        padding-bottom: 4px;
    }

    .inventory-filter-form {
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        gap: .5rem;
        min-width: max-content;
    }

    .inventory-filter-form .form-control {
        width: auto;
        min-width: 170px;
        flex: 0 0 auto;
    }

    .inventory-filter-form .btn {
        white-space: nowrap;
        flex: 0 0 auto;
    }

    .table-sm td {
        padding: 0.5rem;
        vertical-align: middle;
    }
</style>
@endsection
