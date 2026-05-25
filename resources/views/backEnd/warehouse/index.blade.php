@extends('backEnd.layouts.master')
@section('title','Warehouse Manage')
@section('content')
<div class="container-fluid">
    
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{route('admin.warehouse.create')}}" class="btn btn-danger rounded-pill"><i class="fe-plus"></i> Add Warehouse</a>
                </div>
                <h4 class="page-title">Warehouse Manage</h4>
            </div>
        </div>
    </div>       
    <!-- end page title --> 
   <div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-8">
                        <ul class="action2-btn">
                            <li><a href="{{route('admin.warehouse.index',['status'=>'active'])}}" class="btn rounded-pill btn-success"><i class="fe-check"></i> Active</a></li>
                            <li><a href="{{route('admin.warehouse.index',['status'=>'inactive'])}}" class="btn rounded-pill btn-warning"><i class="fe-x"></i> Inactive</a></li>
                            <li><a href="{{route('admin.warehouse.index')}}" class="btn rounded-pill btn-info"><i class="fe-list"></i> All</a></li>
                        </ul>
                    </div>
                    <div class="col-sm-4">
                        <form method="GET" action="{{route('admin.warehouse.index')}}" class="custom_form">
                            <div class="form-group">
                                <input type="text" name="search" value="{{request('search')}}" placeholder="Search by code, name, city...">
                                <button class="btn rounded-pill btn-info">Search</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table nowrap w-100">
                    <thead>
                        <tr>
                            <th style="width:2%">SL</th>
                            <th style="width:10%">Code</th>
                            <th style="width:15%">Name</th>
                            <th style="width:10%">Type</th>
                            <th style="width:15%">Location</th>
                            <th style="width:12%">Manager</th>
                            <th style="width:10%">Capacity</th>
                            <th style="width:10%">Stock Value</th>
                            <th style="width:8%">Status</th>
                            <th style="width:10%">Action</th>
                        </tr>
                    </thead>               
                
                    <tbody>
                        @forelse($warehouses as $key=>$warehouse)
                        <tr>
                            <td>{{$loop->iteration}}</td>
                            <td><strong>{{$warehouse->code}}</strong></td>
                            <td>{{$warehouse->name}}</td>
                            <td><span class="badge bg-soft-info text-info">{{ucfirst($warehouse->type)}}</span></td>
                            <td>
                                @if($warehouse->city)
                                    {{$warehouse->city}}, {{$warehouse->state}}
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>{{$warehouse->manager ? $warehouse->manager->name : 'N/A'}}</td>
                            <td>{{$warehouse->capacity_sqft ? number_format($warehouse->capacity_sqft) . ' sqft' : 'N/A'}}</td>
                            <td>৳{{number_format($warehouse->total_stock_value ?? 0, 2)}}</td>
                            <td>
                                @if($warehouse->is_active)
                                    <span class="badge bg-soft-success text-success">Active</span>
                                @else
                                    <span class="badge bg-soft-danger text-danger">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="button-list custom-btn-list">
                                    <a href="{{route('admin.warehouse.show',$warehouse->id)}}" title="View"><i class="fe-eye"></i></a>
                                    <a href="{{route('admin.warehouse.edit',$warehouse->id)}}" title="Edit"><i class="fe-edit"></i></a>
                                    @if($warehouse->is_active)
                                    <form method="post" action="{{route('admin.warehouse.deactivate',$warehouse->id)}}" class="d-inline">
                                        @csrf
                                        <button type="button" class="change-confirm" title="Deactivate"><i class="fe-thumbs-down"></i></button>
                                    </form>
                                    @else
                                    <form method="post" action="{{route('admin.warehouse.activate',$warehouse->id)}}" class="d-inline">
                                        @csrf
                                        <button type="button" class="change-confirm" title="Activate"><i class="fe-thumbs-up"></i></button>
                                    </form>
                                    @endif
                                    <form method="post" action="{{route('admin.warehouse.destroy',$warehouse->id)}}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="delete-confirm" title="Delete"><i class="fe-trash-2"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center">No warehouses found</td>
                        </tr>
                        @endforelse
                     </tbody>
                    </table>
                </div>
                <div class="custom-paginate">
                    {{$warehouses->links()}}
                </div>
            </div>
        </div>
    </div>
   </div>
</div>
@endsection

