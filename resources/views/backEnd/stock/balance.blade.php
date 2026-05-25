@extends('backEnd.layouts.master')
@section('title','Stock Balance')
@section('content')
<div class="container-fluid stock-balance-page">
    
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right stock-balance-top-actions">
                    <a href="{{route('admin.stock.movements')}}" class="btn btn-info rounded-pill"><i class="fe-list"></i> Movement History</a>
                    <a href="{{route('admin.stock.alerts')}}" class="btn btn-warning rounded-pill"><i class="fe-alert-triangle"></i> Stock Alerts</a>
                </div>
                <h4 class="page-title">Stock Balance</h4>
            </div>
        </div>
    </div>       
    <!-- end page title --> 
   <div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
                        <div class="card bg-primary stock-stat-card stock-stat-card--primary">
                            <div class="card-body">
                                <h5 class="card-title">{{ number_format($stats['total_items']) }}</h5>
                                <p class="card-text">Total Items</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
                        <div class="card bg-success stock-stat-card stock-stat-card--success">
                            <div class="card-body">
                                <h5 class="card-title">৳{{ number_format($stats['total_value'], 2) }}</h5>
                                <p class="card-text">Total Value</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
                        <div class="card bg-warning stock-stat-card stock-stat-card--warning">
                            <div class="card-body">
                                <h5 class="card-title">{{ number_format($stats['low_stock_count']) }}</h5>
                                <p class="card-text">Low Stock Items</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
                        <div class="card bg-danger stock-stat-card stock-stat-card--danger">
                            <div class="card-body">
                                <h5 class="card-title">{{ number_format($stats['out_of_stock_count']) }}</h5>
                                <p class="card-text">Out of Stock Items</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Advanced Search Form -->
                <form method="GET" action="{{route('admin.stock.balance')}}" class="mb-4">
                    <div class="row">
                        <div class="col-md-6 col-lg-3">
                            <div class="form-group">
                                <label class="form-label">Search Products</label>
                                <input type="text" name="search" value="{{request('search')}}"
                                       class="form-control" placeholder="Product name, SKU, code...">
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <div class="form-group">
                                <label class="form-label">Warehouse</label>
                                <select name="warehouse_id" class="form-control">
                                    <option value="">All Warehouses</option>
                                    @foreach($warehouses as $wh)
                                        <option value="{{$wh->id}}" {{request('warehouse_id')==$wh->id?'selected':''}}>{{$wh->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <div class="form-group">
                                <label class="form-label">Stock Status</label>
                                <select name="status" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="low" {{request('status')=='low'?'selected':''}}>Low Stock</option>
                                    <option value="out" {{request('status')=='out'?'selected':''}}>Out of Stock</option>
                                    <option value="in" {{request('status')=='in'?'selected':''}}>In Stock</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <div class="form-group">
                                <label class="form-label">Min Available Qty</label>
                                <input type="number" name="min_available" value="{{request('min_available')}}"
                                       class="form-control" placeholder="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <div class="form-group">
                                <label class="form-label">Max Available Qty</label>
                                <input type="number" name="max_available" value="{{request('max_available')}}"
                                       class="form-control" placeholder="1000" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-1">
                            <div class="form-group">
                                <label class="form-label">Sort By</label>
                                <select name="sort_by" class="form-control">
                                    <option value="created_at" {{request('sort_by')=='created_at'?'selected':''}}>Date</option>
                                    <option value="physical_quantity" {{request('sort_by')=='physical_quantity'?'selected':''}}>Physical Qty</option>
                                    <option value="available_quantity" {{request('sort_by')=='available_quantity'?'selected':''}}>Available Qty</option>
                                    <option value="total_value" {{request('sort_by')=='total_value'?'selected':''}}>Value</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 col-lg-3">
                            <div class="form-group">
                                <label class="form-label">Items Per Page</label>
                                <select name="per_page" class="form-control">
                                    <option value="25" {{request('per_page')=='25'?'selected':''}}>25 per page</option>
                                    <option value="50" {{request('per_page')=='50'?'selected':''}}>50 per page</option>
                                    <option value="100" {{request('per_page')=='100'?'selected':''}}>100 per page</option>
                                    <option value="200" {{request('per_page')=='200'?'selected':''}}>200 per page</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-9">
                            <div class="stock-balance-filter-actions mt-4">
                                <button type="submit" class="btn btn-primary rounded-pill">
                                    <i class="fas fa-search"></i> Search & Filter
                                </button>
                                <a href="{{route('admin.stock.balance')}}" class="btn btn-secondary rounded-pill">
                                    <i class="fas fa-times"></i> Clear Filters
                                </a>
                                <a href="{{route('admin.stock.set')}}" class="btn btn-success rounded-pill">
                                    <i class="fas fa-plus"></i> Set Stock
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-striped align-middle w-100">
                    <thead>
                        <tr>
                            <th style="width:2%">SL</th>
                            <th style="width:15%">Product</th>
                            <th style="width:12%">Warehouse</th>
                            <th style="width:10%">Physical Qty</th>
                            <th style="width:10%">Reserved Qty</th>
                            <th style="width:10%">Available Qty</th>
                            <th style="width:10%">Reorder Point</th>
                            <th style="width:10%">Avg Cost</th>
                            <th style="width:12%">Total Value</th>
                            <th style="width:9%">Status</th>
                        </tr>
                    </thead>               
                
                    <tbody>
                        @forelse($stocks as $key=>$stock)
                        <tr>
                            <td>{{$loop->iteration}}</td>
                            <td>
                                <strong>{{$stock->product->name ?? 'N/A'}}</strong><br>
                                <small class="text-muted">SKU: {{$stock->product->sku ?? 'N/A'}}</small>
                            </td>
                            <td>{{$stock->warehouse->name ?? 'N/A'}}</td>
                            <td><strong>{{number_format($stock->physical_quantity, 2)}}</strong></td>
                            <td>{{number_format($stock->reserved_quantity, 2)}}</td>
                            <td>
                                <strong class="{{$stock->available_quantity <= $stock->reorder_point ? 'text-danger' : 'text-success'}}">
                                    {{number_format($stock->available_quantity, 2)}}
                                </strong>
                            </td>
                            <td>{{number_format($stock->reorder_point, 2)}}</td>
                            <td>৳{{number_format($stock->average_cost ?? 0, 2)}}</td>
                            <td><strong>৳{{number_format($stock->total_value ?? 0, 2)}}</strong></td>
                            <td>
                                @if($stock->available_quantity <= 0)
                                    <span class="badge bg-soft-danger text-danger">Out of Stock</span>
                                @elseif($stock->available_quantity <= $stock->reorder_point)
                                    <span class="badge bg-soft-warning text-warning">Low Stock</span>
                                @else
                                    <span class="badge bg-soft-success text-success">In Stock</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center">No stock records found</td>
                        </tr>
                        @endforelse
                     </tbody>
                    </table>
                </div>
                <div class="custom-paginate">
                    {{ $stocks->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
   </div>
</div>
@endsection

@push('css')
<style>
.stock-balance-page .stock-balance-top-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.stock-balance-page .stock-balance-top-actions .btn {
    white-space: nowrap;
}

.stock-balance-page .stock-stat-card {
    border: 0;
    height: 100%;
}

.stock-balance-page .stock-stat-card .card-title,
.stock-balance-page .stock-stat-card .card-text {
    color: #111827 !important;
}

.stock-balance-page .stock-stat-card--warning .card-title,
.stock-balance-page .stock-stat-card--warning .card-text {
    color: #1f2937 !important;
}

.stock-balance-page .stock-balance-filter-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.stock-balance-page .stock-balance-filter-actions .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 38px;
    white-space: nowrap;
}

.stock-balance-page .table-responsive {
    border-radius: 0.5rem;
}

@media (max-width: 991.98px) {
    .stock-balance-page .page-title-right {
        float: none;
        margin-top: 0.75rem;
    }

    .stock-balance-page .stock-balance-filter-actions .btn {
        flex: 1 1 calc(50% - 0.5rem);
        min-width: 0;
    }
}

@media (max-width: 575.98px) {
    .stock-balance-page .stock-balance-top-actions .btn,
    .stock-balance-page .stock-balance-filter-actions .btn {
        width: 100%;
        flex: 1 1 100%;
    }

    .stock-balance-page .table th,
    .stock-balance-page .table td {
        font-size: 0.82rem;
        padding: 0.45rem;
    }
}
</style>
@endpush
