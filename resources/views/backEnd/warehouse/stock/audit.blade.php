@extends('backEnd.layouts.master')
@section('title','Stock Audit')
@section('content')
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{route('admin.stock.balance')}}" class="btn btn-outline-primary rounded-pill"><i class="fe-list"></i> Stock Balance</a>
                    <a href="{{route('admin.stock.inventory')}}" class="btn btn-info rounded-pill"><i class="fe-grid"></i> Advanced Inventory</a>
                    <a href="{{route('admin.stock.movements')}}" class="btn btn-info rounded-pill"><i class="fe-activity"></i> Movements</a>
                </div>
                <h4 class="page-title">Stock Audit</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    @if($warehouse)
                        <!-- Warehouse specific audit -->
                        <div class="alert alert-info">
                            <h5><i class="fe-home"></i> Auditing Warehouse: {{$warehouse->name}}</h5>
                            <p class="mb-0">Code: {{$warehouse->code}} | Location: {{$warehouse->address or 'N/A'}}</p>
                        </div>

                        <div class="table-responsive report-sticky-container">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Product</th>
                                        <th>SKU</th>
                                        <th>Physical Stock</th>
                                        <th>Available Stock</th>
                                        <th>Reserved Stock</th>
                                        <th>Last Movement</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($stock as $item)
                                    <tr>
                                        <td>
                                            <strong>{{$item->product->name}}</strong>
                                        </td>
                                        <td>{{$item->product->sku ?? 'N/A'}}</td>
                                        <td><strong>{{number_format($item->physical_quantity, 2)}}</strong></td>
                                        <td class="text-success"><strong>{{number_format($item->available_quantity, 2)}}</strong></td>
                                        <td class="text-warning">{{number_format($item->reserved_quantity, 2)}}</td>
                                        <td>
                                            @if($item->movements->count() > 0)
                                                {{$item->movements->first()->created_at->format('M d, Y H:i')}}
                                            @else
                                                Never
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->available_quantity <= 0)
                                                <span class="badge bg-danger">Out of Stock</span>
                                            @elseif($item->available_quantity <= $item->reorder_point)
                                                <span class="badge bg-warning text-dark">Low Stock</span>
                                            @else
                                                <span class="badge bg-success">In Stock</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="fe-package font-24 text-muted"></i>
                                            <div class="mt-2">No stock items found in this warehouse</div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            <a href="{{route('admin.stock.audit')}}" class="btn btn-secondary">
                                <i class="fe-arrow-left"></i> Back to Warehouse Selection
                            </a>
                        </div>
                    @else
                        <!-- Warehouse selection -->
                        <div class="row">
                            <div class="col-md-8">
                                <h5>Select Warehouse for Audit</h5>
                                <p class="text-muted">Choose a warehouse to perform a physical stock count audit.</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="btn-group">
                                    <a href="{{route('admin.stock.balance')}}" class="btn btn-outline-primary">
                                        <i class="fe-list"></i> Stock Balance
                                    </a>
                                    <a href="{{route('admin.stock.inventory')}}" class="btn btn-primary">
                                        <i class="fe-grid"></i> Advanced Inventory
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            @foreach($warehouses as $wh)
                            <div class="col-md-4 mb-3">
                                <div class="card h-100 border">
                                    <div class="card-body text-center">
                                        <div class="avatar-lg bg-primary rounded-circle mx-auto mb-3">
                                            <i class="fe-home avatar-title font-22 text-white"></i>
                                        </div>
                                        <h5 class="card-title">{{$wh->name}}</h5>
                                        <p class="text-muted mb-2">Code: {{$wh->code}}</p>
                                        <p class="text-muted small mb-3">
                                            {{$wh->address or 'No address specified'}}
                                        </p>
                                        <a href="{{route('admin.stock.audit', ['warehouse_id' => $wh->id])}}"
                                           class="btn btn-primary btn-sm">
                                            <i class="fe-check-circle"></i> Start Audit
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        @if($warehouses->isEmpty())
                        <div class="text-center py-5">
                            <i class="fe-home font-48 text-muted"></i>
                            <h4 class="mt-3">No Warehouses Found</h4>
                            <p class="text-muted">You need to create warehouses before performing stock audits.</p>
                            <a href="{{route('admin.warehouse.create')}}" class="btn btn-primary">
                                <i class="fe-plus"></i> Create Warehouse
                            </a>
                        </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('css')
<style>
.table th, .table td {
    vertical-align: middle;
    padding: 0.75rem;
}

.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.avatar-lg {
    width: 3rem;
    height: 3rem;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
@endpush
