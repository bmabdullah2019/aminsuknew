@extends('backEnd.layouts.master')
@section('title','Warehouse Details')
@section('content')
<div class="container-fluid">
    
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{route('admin.warehouse.index')}}" class="btn btn-danger rounded-pill"><i class="fe-arrow-left"></i> Back</a>
                    <a href="{{route('admin.warehouse.edit',$warehouse->id)}}" class="btn btn-primary rounded-pill"><i class="fe-edit"></i> Edit</a>
                </div>
                <h4 class="page-title">Warehouse Details</h4>
            </div>
        </div>
    </div>       
    <!-- end page title --> 
   <div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Basic Information</h5>
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">Code</th>
                        <td><strong>{{$warehouse->code}}</strong></td>
                    </tr>
                    <tr>
                        <th>Name</th>
                        <td>{{$warehouse->name}}</td>
                    </tr>
                    <tr>
                        <th>Type</th>
                        <td><span class="badge bg-soft-info text-info">{{ucfirst($warehouse->type)}}</span></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            @if($warehouse->is_active)
                                <span class="badge bg-soft-success text-success">Active</span>
                            @else
                                <span class="badge bg-soft-danger text-danger">Inactive</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Manager</th>
                        <td>{{$warehouse->manager ? $warehouse->manager->name : 'Not Assigned'}}</td>
                    </tr>
                    <tr>
                        <th>Address</th>
                        <td>{{$warehouse->full_address ?: 'N/A'}}</td>
                    </tr>
                    <tr>
                        <th>Phone</th>
                        <td>{{$warehouse->phone ?: 'N/A'}}</td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td>{{$warehouse->email ?: 'N/A'}}</td>
                    </tr>
                    <tr>
                        <th>Capacity</th>
                        <td>{{$warehouse->capacity_sqft ? number_format($warehouse->capacity_sqft) . ' sqft' : 'N/A'}}</td>
                    </tr>
                    <tr>
                        <th>Opening Date</th>
                        <td>{{$warehouse->opening_date ? $warehouse->opening_date->format('d M Y') : 'N/A'}}</td>
                    </tr>
                    <tr>
                        <th>Notes</th>
                        <td>{{$warehouse->notes ?: 'N/A'}}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Statistics</h5>
                <div class="mb-3">
                    <strong>Total Stock Value:</strong><br>
                    <span class="h4 text-primary">৳{{number_format($warehouse->total_stock_value ?? 0, 2)}}</span>
                </div>
                <div class="mb-3">
                    <strong>Low Stock Items:</strong><br>
                    <span class="h4 text-warning">{{$warehouse->low_stock_count ?? 0}}</span>
                </div>
                <div class="mb-3">
                    <strong>Total Products:</strong><br>
                    <span class="h4 text-info">{{$warehouse->stock()->count()}}</span>
                </div>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-body">
                <h5 class="card-title">Quick Actions</h5>
                <div class="d-grid gap-2">
                    <a href="{{route('admin.stock.balance',['warehouse_id'=>$warehouse->id])}}" class="btn btn-primary">View Stock</a>
                    <a href="{{route('admin.grn.create',['warehouse_id'=>$warehouse->id])}}" class="btn btn-success">Create GRN</a>
                    <a href="{{route('admin.transfer.create',['from_warehouse_id'=>$warehouse->id])}}" class="btn btn-info">Transfer Out</a>
                </div>
            </div>
        </div>
    </div>
   </div>
</div>
@endsection

