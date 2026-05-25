@extends('backEnd.layouts.master')
@section('title','Transfer Details')
@section('content')
<div class="container-fluid">
    
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{route('admin.transfer.index')}}" class="btn btn-danger rounded-pill"><i class="fe-arrow-left"></i> Back</a>
                    @if($transfer->status == 'pending')
                        <form method="post" action="{{route('admin.transfer.approve',$transfer->id)}}" class="d-inline">
                            @csrf
                            <button type="button" class="btn btn-success rounded-pill change-confirm"><i class="fe-check"></i> Approve</button>
                        </form>
                    @endif
                    @if($transfer->status == 'approved')
                        <form method="post" action="{{route('admin.transfer.dispatch',$transfer->id)}}" class="d-inline">
                            @csrf
                            <button type="button" class="btn btn-primary rounded-pill change-confirm"><i class="fe-truck"></i> Dispatch</button>
                        </form>
                    @endif
                    @if($transfer->status == 'dispatched')
                        <form method="post" action="{{route('admin.transfer.receive',$transfer->id)}}" class="d-inline">
                            @csrf
                            <button type="button" class="btn btn-info rounded-pill change-confirm"><i class="fe-package"></i> Receive</button>
                        </form>
                    @endif
                </div>
                <h4 class="page-title">Transfer Details</h4>
            </div>
        </div>
    </div>       
    <!-- end page title --> 
   <div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Transfer Information</h5>
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">Transfer Number</th>
                        <td><strong>{{$transfer->transfer_number}}</strong></td>
                    </tr>
                    <tr>
                        <th>Transfer Date</th>
                        <td>{{$transfer->transfer_date->format('d M Y')}}</td>
                    </tr>
                    <tr>
                        <th>From Warehouse</th>
                        <td>{{$transfer->fromWarehouse->name ?? 'N/A'}} ({{$transfer->fromWarehouse->code ?? 'N/A'}})</td>
                    </tr>
                    <tr>
                        <th>To Warehouse</th>
                        <td>{{$transfer->toWarehouse->name ?? 'N/A'}} ({{$transfer->toWarehouse->code ?? 'N/A'}})</td>
                    </tr>
                    <tr>
                        <th>Reason</th>
                        <td>{{$transfer->reason}}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            @if($transfer->status == 'pending')
                                <span class="badge bg-soft-warning text-warning">Pending</span>
                            @elseif($transfer->status == 'approved')
                                <span class="badge bg-soft-info text-info">Approved</span>
                            @elseif($transfer->status == 'dispatched')
                                <span class="badge bg-soft-primary text-primary">Dispatched</span>
                            @elseif($transfer->status == 'completed')
                                <span class="badge bg-soft-success text-success">Completed</span>
                            @elseif($transfer->status == 'cancelled')
                                <span class="badge bg-soft-danger text-danger">Cancelled</span>
                            @endif
                        </td>
                    </tr>
                    @if($transfer->estimated_arrival)
                    <tr>
                        <th>Expected Arrival</th>
                        <td>{{$transfer->estimated_arrival->format('d M Y')}}</td>
                    </tr>
                    @endif
                    @if($transfer->dispatched_at)
                    <tr>
                        <th>Dispatched At</th>
                        <td>{{$transfer->dispatched_at->format('d M Y H:i')}}</td>
                    </tr>
                    @endif
                    @if($transfer->received_at)
                    <tr>
                        <th>Received At</th>
                        <td>{{$transfer->received_at->format('d M Y H:i')}}</td>
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
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transfer->items as $item)
                            <tr>
                                <td>{{$item->product->name ?? 'N/A'}}</td>
                                <td>{{$item->sku ?? 'N/A'}}</td>
                                <td>{{number_format($item->quantity, 2)}}</td>
                                <td>৳{{number_format($item->unit_cost ?? 0, 2)}}</td>
                                <td>৳{{number_format($item->quantity * ($item->unit_cost ?? 0), 2)}}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4">Total Items</th>
                                <th>{{$transfer->items->count()}}</th>
                            </tr>
                            <tr>
                                <th colspan="4">Total Quantity</th>
                                <th>{{number_format($transfer->items->sum('quantity'), 2)}}</th>
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
                    <span class="h4 text-primary">{{$transfer->items->count()}}</span>
                </div>
                <div class="mb-3">
                    <strong>Total Quantity:</strong><br>
                    <span class="h4 text-info">{{number_format($transfer->items->sum('quantity'), 2)}}</span>
                </div>
                @if($transfer->notes)
                <div class="mb-3">
                    <strong>Notes:</strong><br>
                    <small>{{$transfer->notes}}</small>
                </div>
                @endif
            </div>
        </div>
    </div>
   </div>
</div>
@endsection

