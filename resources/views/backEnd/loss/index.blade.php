@extends('backEnd.layouts.master')
@section('title','Stock Loss Manage')
@section('content')
<div class="container-fluid">
    
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{route('admin.loss.create')}}" class="btn btn-danger rounded-pill"><i class="fe-plus"></i> Record Loss</a>
                </div>
                <h4 class="page-title">Stock Loss Manage</h4>
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
                            <li><a href="{{route('admin.loss.index',['status'=>'pending'])}}" class="btn rounded-pill btn-warning"><i class="fe-clock"></i> Pending</a></li>
                            <li><a href="{{route('admin.loss.index',['status'=>'approved'])}}" class="btn rounded-pill btn-success"><i class="fe-check"></i> Approved</a></li>
                            <li><a href="{{route('admin.loss.index')}}" class="btn rounded-pill btn-info"><i class="fe-list"></i> All</a></li>
                        </ul>
                    </div>
                    <div class="col-sm-4">
                        <form method="GET" action="{{route('admin.loss.index')}}" class="custom_form">
                            <div class="form-group">
                                <input type="text" name="search" value="{{request('search')}}" placeholder="Search by loss number...">
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
                            <th style="width:12%">Loss Number</th>
                            <th style="width:12%">Date</th>
                            <th style="width:15%">Warehouse</th>
                            <th style="width:12%">Loss Type</th>
                            <th style="width:10%">Items</th>
                            <th style="width:12%">Total Value</th>
                            <th style="width:10%">Status</th>
                            <th style="width:15%">Action</th>
                        </tr>
                    </thead>               
                
                    <tbody>
                        @forelse($losses as $key=>$loss)
                        <tr>
                            <td>{{$loop->iteration}}</td>
                            <td><strong>{{$loss->loss_number}}</strong></td>
                            <td>{{$loss->loss_date->format('d M Y')}}</td>
                            <td>{{$loss->warehouse->name ?? 'N/A'}}</td>
                            <td>
                                <span class="badge bg-soft-info text-info">
                                    {{ucwords(str_replace('_', ' ', $loss->loss_type))}}
                                </span>
                            </td>
                            <td>{{($loss->items_count ?? 0)}} items</td>
                            <td><strong class="text-danger">৳{{number_format($loss->total_value ?? 0, 2)}}</strong></td>
                            <td>
                                @if($loss->status == 'pending')
                                    <span class="badge bg-soft-warning text-warning">Pending</span>
                                @elseif($loss->status == 'approved')
                                    <span class="badge bg-soft-success text-success">Approved</span>
                                @else
                                    <span class="badge bg-soft-secondary text-secondary">{{ucfirst($loss->status)}}</span>
                                @endif
                            </td>
                            <td>
                                <div class="button-list custom-btn-list">
                                    <a href="{{route('admin.loss.show',$loss->id)}}" title="View"><i class="fe-eye"></i></a>
                                    @if($loss->status == 'pending')
                                        <a href="{{route('admin.loss.edit',$loss->id)}}" title="Edit"><i class="fe-edit"></i></a>
                                        <form method="post" action="{{route('admin.loss.approve',$loss->id)}}" class="d-inline">
                                            @csrf
                                            <button type="button" class="change-confirm" title="Approve"><i class="fe-check"></i></button>
                                        </form>
                                        <form method="post" action="{{route('admin.loss.destroy',$loss->id)}}" class="d-inline">
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
                            <td colspan="9" class="text-center">No losses found</td>
                        </tr>
                        @endforelse
                     </tbody>
                    </table>
                </div>
                <div class="custom-paginate">
                    {{$losses->links()}}
                </div>
            </div>
        </div>
    </div>
   </div>
</div>
@endsection

