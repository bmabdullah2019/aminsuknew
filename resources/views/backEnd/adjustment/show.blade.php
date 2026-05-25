@extends('backEnd.layouts.master')
@section('title','Adjustment Details')
@section('content')
<div class="container-fluid">
    
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                    <div class="page-title-right">
                    <a href="{{route('admin.adjustment.index')}}" class="btn btn-danger rounded-pill"><i class="fe-arrow-left"></i> Back</a>
                    @if($adjustment->status == 'pending')
                        <a href="{{route('admin.adjustment.edit',$adjustment->id)}}" class="btn btn-primary rounded-pill"><i class="fe-edit"></i> Edit</a>
                        <form method="post" action="{{route('admin.adjustment.approve',$adjustment->id)}}" class="d-inline">
                            @csrf
                            <button type="button" class="btn btn-success rounded-pill change-confirm"><i class="fe-check"></i> Approve</button>
                        </form>
                    @endif
                </div>
                <h4 class="page-title">Stock Adjustment Details</h4>
            </div>
        </div>
    </div>       
    <!-- end page title --> 
   <div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Adjustment Information</h5>
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">Adjustment Number</th>
                        <td><strong>{{$adjustment->adjustment_number}}</strong></td>
                    </tr>
                    <tr>
                        <th>Date</th>
                        <td>{{$adjustment->adjustment_date->format('d M Y')}}</td>
                    </tr>
                    <tr>
                        <th>Warehouse</th>
                        <td>{{$adjustment->warehouse->name ?? 'N/A'}} ({{$adjustment->warehouse->code ?? 'N/A'}})</td>
                    </tr>
                    <tr>
                        <th>Type</th>
                        <td>
                            @if($adjustment->adjustment_type == 'increase')
                                <span class="badge bg-soft-success text-success">Increase</span>
                            @else
                                <span class="badge bg-soft-danger text-danger">Decrease</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Reason</th>
                        <td>{{$adjustment->reason}}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            @if($adjustment->status == 'pending')
                                <span class="badge bg-soft-warning text-warning">Pending</span>
                            @elseif($adjustment->status == 'approved')
                                <span class="badge bg-soft-success text-success">Approved</span>
                            @else
                                <span class="badge bg-soft-secondary text-secondary">{{ucfirst($adjustment->status)}}</span>
                            @endif
                        </td>
                    </tr>
                    @if($adjustment->approved_at)
                    <tr>
                        <th>Approved At</th>
                        <td>{{$adjustment->approved_at->format('d M Y H:i')}}</td>
                    </tr>
                    @endif
                </table>

                <h5 class="card-title mt-4">Items</h5>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Quantity</th>
                                <th>Unit Cost</th>
                                <th>Total Value</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($adjustment->items as $item)
                            <tr>
                                <td>{{$item->product->name ?? 'N/A'}}</td>
                                <td>{{$item->sku ?? 'N/A'}}</td>
                                <td>
                                    <span class="{{$adjustment->adjustment_type == 'increase' ? 'text-success' : 'text-danger'}}">
                                        {{$adjustment->adjustment_type == 'increase' ? '+' : '-'}}{{number_format($item->quantity, 2)}}
                                    </span>
                                </td>
                                <td>৳{{number_format($item->unit_cost ?? 0, 2)}}</td>
                                <td>৳{{number_format($item->quantity * ($item->unit_cost ?? 0), 2)}}</td>
                                <td>{{$item->notes ?: 'N/A'}}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Summary</h5>
                <div class="mb-3">
                    <strong>Total Items:</strong><br>
                    <span class="h4 text-primary">{{$adjustment->items->count()}}</span>
                </div>
                <div class="mb-3">
                    <strong>Total Quantity:</strong><br>
                    <span class="h4 text-info">{{number_format($adjustment->items->sum('quantity'), 2)}}</span>
                </div>
                @if($adjustment->notes)
                <div class="mb-3">
                    <strong>Notes:</strong><br>
                    <small>{{$adjustment->notes}}</small>
                </div>
                @endif
            </div>
        </div>
    </div>
   </div>
</div>
@endsection

