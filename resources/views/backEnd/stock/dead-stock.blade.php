@extends('backEnd.layouts.master')
@section('title','Dead Stock Report')
@section('content')
<div class="container-fluid">
    
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{route('admin.stock.balance')}}" class="btn btn-info rounded-pill"><i class="fe-list"></i> Stock Balance</a>
                </div>
                <h4 class="page-title">Dead Stock Report</h4>
            </div>
        </div>
    </div>       
    <!-- end page title --> 
   <div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Dead Stock:</strong> Products that have not been sold or moved in the last 90 days but still have stock available.
                </div>
                <form method="GET" action="{{route('admin.stock.dead-stock')}}" class="custom_form d-flex gap-3 mb-3">
                    <select name="warehouse_id" class="form-control w-auto"><option value="">All Warehouses</option>@foreach($warehouses as $wh)<option value="{{$wh->id}}" {{request('warehouse_id')==$wh->id?'selected':''}}>{{$wh->name}}</option>@endforeach</select>
                    <input type="number" name="days" value="{{request('days', 90)}}" class="form-control w-auto" placeholder="Days threshold" min="1">
                    <input type="text" name="search" value="{{request('search')}}" placeholder="Search by product name..." class="form-control flex-grow-1">
                    <button class="btn rounded-pill btn-info">Filter</button>
                </form>
                <div class="table-responsive report-sticky-container">
                    <table class="table nowrap w-100">
                    <thead>
                        <tr>
                            <th style="width:2%">SL</th>
                            <th style="width:20%">Product</th>
                            <th style="width:12%">Warehouse</th>
                            <th style="width:10%">Current Stock</th>
                            <th style="width:10%">Avg Cost</th>
                            <th style="width:12%">Total Value</th>
                            <th style="width:12%">Last Movement</th>
                            <th style="width:12%">Days Since Last Sale</th>
                            <th style="width:10%">Action</th>
                        </tr>
                    </thead>               
                
                    <tbody>
                        @forelse($deadStocks as $key=>$stock)
                        <tr>
                            <td>{{$loop->iteration}}</td>
                            <td>
                                <strong>{{$stock->product->name ?? 'N/A'}}</strong><br>
                                <small class="text-muted">SKU: {{$stock->product->sku ?? 'N/A'}}</small>
                            </td>
                            <td>{{$stock->warehouse->name ?? 'N/A'}}</td>
                            <td><strong>{{number_format($stock->physical_quantity, 2)}}</strong></td>
                            <td>৳{{number_format($stock->average_cost ?? 0, 2)}}</td>
                            <td><strong class="text-danger">৳{{number_format($stock->total_value ?? 0, 2)}}</strong></td>
                            <td>
                                @if($stock->last_movement_date)
                                    {{$stock->last_movement_date->format('d M Y')}}
                                @else
                                    <span class="text-muted">Never</span>
                                @endif
                            </td>
                            <td>
                                @if($stock->days_since_last_sale)
                                    <span class="badge bg-danger">{{$stock->days_since_last_sale}} days</span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{route('admin.stock.show',[$stock->warehouse_id, $stock->product_id])}}" class="btn btn-sm btn-info" title="View Stock">
                                    <i class="fe-eye"></i>
                                </a>
                                <a href="{{route('admin.adjustment.create',['warehouse_id'=>$stock->warehouse_id, 'product_id'=>$stock->product_id])}}" class="btn btn-sm btn-warning" title="Adjust Stock">
                                    <i class="fe-edit"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center">No dead stock found</td>
                        </tr>
                        @endforelse
                     </tbody>
                    </table>
                </div>
                <div class="custom-paginate">
                    {{$deadStocks->links()}}
                </div>
            </div>
        </div>
    </div>
   </div>
</div>
@endsection

