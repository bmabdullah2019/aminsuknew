@extends('backEnd.layouts.master')
@section('title','Loss Details')
@section('content')
<div class="container-fluid">
    
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{route('admin.loss.index')}}" class="btn btn-danger rounded-pill"><i class="fe-arrow-left"></i> Back</a>
                    @if($loss->status == 'pending')
                        <a href="{{route('admin.loss.edit',$loss->id)}}" class="btn btn-primary rounded-pill"><i class="fe-edit"></i> Edit</a>
                        <form method="post" action="{{route('admin.loss.approve',$loss->id)}}" class="d-inline">
                            @csrf
                            <button type="button" class="btn btn-success rounded-pill change-confirm"><i class="fe-check"></i> Approve</button>
                        </form>
                    @endif
                </div>
                <h4 class="page-title">Stock Loss Details</h4>
            </div>
        </div>
    </div>       
    <!-- end page title --> 
   <div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Loss Information</h5>
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">Loss Number</th>
                        <td><strong>{{$loss->loss_number}}</strong></td>
                    </tr>
                    <tr>
                        <th>Date</th>
                        <td>{{$loss->loss_date->format('d M Y')}}</td>
                    </tr>
                    <tr>
                        <th>Warehouse</th>
                        <td>{{$loss->warehouse->name ?? 'N/A'}} ({{$loss->warehouse->code ?? 'N/A'}})</td>
                    </tr>
                    <tr>
                        <th>Loss Type</th>
                        <td>
                            <span class="badge bg-soft-info text-info">
                                {{ucwords(str_replace('_', ' ', $loss->loss_type))}}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            @if($loss->status == 'pending')
                                <span class="badge bg-soft-warning text-warning">Pending</span>
                            @elseif($loss->status == 'approved')
                                <span class="badge bg-soft-success text-success">Approved</span>
                            @else
                                <span class="badge bg-soft-secondary text-secondary">{{ucfirst($loss->status)}}</span>
                            @endif
                        </td>
                    </tr>
                    @if($loss->approved_at)
                    <tr>
                        <th>Approved At</th>
                        <td>{{$loss->approved_at->format('d M Y H:i')}}</td>
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
                                <th>Quantity Lost</th>
                                <th>Unit Cost</th>
                                <th>Total Value</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($loss->items as $item)
                            <tr>
                                <td>{{$item->product->name ?? 'N/A'}}</td>
                                <td>{{$item->sku ?? 'N/A'}}</td>
                                <td><strong class="text-danger">{{number_format($item->quantity, 2)}}</strong></td>
                                <td>৳{{number_format($item->unit_cost ?? 0, 2)}}</td>
                                <td><strong class="text-danger">৳{{number_format($item->quantity * ($item->unit_cost ?? 0), 2)}}</strong></td>
                                <td>{{$item->notes ?: 'N/A'}}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4">Total Loss Value</th>
                                <th class="text-danger">৳{{number_format($loss->total_value ?? 0, 2)}}</th>
                                <th></th>
                            </tr>
                        </tfoot>
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
                    <span class="h4 text-primary">{{$loss->items->count()}}</span>
                </div>
                <div class="mb-3">
                    <strong>Total Quantity Lost:</strong><br>
                    <span class="h4 text-danger">{{number_format($loss->items->sum('quantity'), 2)}}</span>
                </div>
                <div class="mb-3">
                    <strong>Total Loss Value:</strong><br>
                    <span class="h4 text-danger">৳{{number_format($loss->total_value ?? 0, 2)}}</span>
                </div>
                @if($loss->notes)
                <div class="mb-3">
                    <strong>Notes:</strong><br>
                    <small>{{$loss->notes}}</small>
                </div>
                @endif
            </div>
        </div>
    </div>
   </div>
</div>
@endsection

