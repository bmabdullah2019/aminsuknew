@extends('backEnd.layouts.master')
@section('title','Stock Adjustment Manage')
@section('content')
<div class="container-fluid">
    
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                    <div class="page-title-right">
                    <a href="{{route('admin.adjustment.create')}}" class="btn btn-danger rounded-pill"><i class="fe-plus"></i> Create Adjustment</a>
                </div>
                <h4 class="page-title">Stock Adjustment Manage</h4>
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
                            <li><a href="{{route('admin.adjustment.index',['status'=>'pending'])}}" class="btn rounded-pill btn-warning"><i class="fe-clock"></i> Pending</a></li>
                            <li><a href="{{route('admin.adjustment.index',['status'=>'approved'])}}" class="btn rounded-pill btn-success"><i class="fe-check"></i> Approved</a></li>
                            <li><a href="{{route('admin.adjustment.index')}}" class="btn rounded-pill btn-info"><i class="fe-list"></i> All</a></li>
                        </ul>
                    </div>
                    <div class="col-sm-4">
                        <form method="GET" action="{{route('admin.adjustment.index')}}" class="custom_form">
                            <div class="form-group">
                                <input type="text" name="search" value="{{request('search')}}" placeholder="Search by adjustment number...">
                                <button class="btn rounded-pill btn-info">Search</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="table-responsive report-sticky-container">
                    <table class="table nowrap w-100">
                    <thead>
                        <tr>
                            <th style="width:2%">SL</th>
                            <th style="width:12%">Adjustment Number</th>
                            <th style="width:12%">Date</th>
                            <th style="width:15%">Warehouse</th>
                            <th style="width:12%">Type</th>
                            <th style="width:10%">Items</th>
                            <th style="width:10%">Status</th>
                            <th style="width:17%">Action</th>
                        </tr>
                    </thead>               
                
                    <tbody>
                        @forelse($adjustments as $key=>$adjustment)
                        <tr>
                            <td>{{$loop->iteration}}</td>
                            <td><strong>{{$adjustment->adjustment_number}}</strong></td>
                            <td>{{$adjustment->adjustment_date->format('d M Y')}}</td>
                            <td>{{$adjustment->warehouse->name ?? 'N/A'}}</td>
                            <td>
                                @if($adjustment->adjustment_type == 'increase')
                                    <span class="badge bg-soft-success text-success">Increase</span>
                                @else
                                    <span class="badge bg-soft-danger text-danger">Decrease</span>
                                @endif
                            </td>
                            <td>{{($adjustment->items_count ?? 0)}} items</td>
                            <td>
                                @if($adjustment->status == 'pending')
                                    <span class="badge bg-soft-warning text-warning">Pending</span>
                                @elseif($adjustment->status == 'approved')
                                    <span class="badge bg-soft-success text-success">Approved</span>
                                @else
                                    <span class="badge bg-soft-secondary text-secondary">{{ucfirst($adjustment->status)}}</span>
                                @endif
                            </td>
                            <td>
                                <div class="button-list custom-btn-list">
                                    <a href="{{route('admin.adjustment.show',$adjustment->id)}}" title="View"><i class="fe-eye"></i></a>
                                    @if($adjustment->status == 'pending')
                                        <a href="{{route('admin.adjustment.edit',$adjustment->id)}}" title="Edit"><i class="fe-edit"></i></a>
                                        <form method="post" action="{{route('admin.adjustment.approve',$adjustment->id)}}" class="d-inline">
                                            @csrf
                                            <button type="button" class="change-confirm" title="Approve"><i class="fe-check"></i></button>
                                        </form>
                                        <form method="post" action="{{route('admin.adjustment.destroy',$adjustment->id)}}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="delete-confirm" title="Delete"><i class="fe-trash-2"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">No adjustments found</td>
                        </tr>
                        @endforelse
                     </tbody>
                    </table>
                </div>
                <div class="custom-paginate">
                    {{$adjustments->links()}}
                </div>
            </div>
        </div>
    </div>
   </div>
</div>
@endsection

