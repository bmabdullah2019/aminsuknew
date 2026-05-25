@extends('backEnd.layouts.master')
@section('title','Order Report')
@section('content')
@section('css')
<link href="{{asset('public/backEnd')}}/assets/libs/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
<link href="{{asset('public/backEnd/')}}/assets/libs/flatpickr/flatpickr.min.css" rel="stylesheet" type="text/css" />
<style>
    p{
        margin:0;
    }
   @page { 
        margin: 50px 0px 0px 0px;
    }
   @media print {
    td{
        font-size: 18px;
    }
    p{
        margin:0;
    }
    title {
        font-size: 25px;
    }
    header,footer,.no-print,.left-side-menu,.navbar-custom {
      display: none !important;
    }
  }
</style>
@endsection 
<div class="container-fluid">
    
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Order Report</h4>
            </div>
        </div>
    </div>       
    <!-- end page title --> 
   <div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form class="no-print" method="GET" action="{{ route('admin.order_report') }}">
                    <div class="row">   
                        <div class="col-sm-2">
                            <div class="form-group">
                               <label for="keyword" class="form-label">Keyword</label>
                                <input type="text" value="{{request()->get('keyword')}}" class="form-control form-control-sm" name="keyword">
                            </div>
                        </div>
                        <!--col-sm-3-->
                        <div class="col-sm-2">
                            <div class="form-group mb-3">
                                <label for="warehouse_id" class="form-label">Warehouse</label>
                                <select class="form-control form-control-sm select2 @error('warehouse_id') is-invalid @enderror" name="warehouse_id">
                                    <option value="">All</option>
                                    @foreach($warehouses as $warehouse)
                                        <option value="{{$warehouse->id}}" @if((string) request()->get('warehouse_id') === (string) $warehouse->id) selected @endif>
                                            {{$warehouse->name}}
                                        </option>
                                    @endforeach
                                </select>
                                @error('warehouse_id')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="form-group mb-3">
                                <label for="order_status" class="form-label">Order Status</label>
                                <select class="form-control form-control-sm select2 @error('order_status') is-invalid @enderror" name="order_status">
                                    <option value="" @if(request()->has('order_status') && request()->get('order_status') === '') selected @endif>All Status</option>
                                    @foreach($orderStatuses as $status)
                                        <option value="{{$status->id}}" @if((int) request()->get('order_status', $statusFilter) === (int) $status->id) selected @endif>
                                            {{$status->name}}
                                        </option>
                                    @endforeach
                                </select>
                                @error('order_status')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="form-group mb-3">
                                <label for="period" class="form-label">Period</label>
                                <select class="form-control form-control-sm select2 @error('period') is-invalid @enderror" name="period">
                                    <option value="custom" @if(request()->get('period', 'custom') === 'custom') selected @endif>Custom</option>
                                    <option value="daily" @if(request()->get('period') === 'daily') selected @endif>Daily</option>
                                    <option value="monthly" @if(request()->get('period') === 'monthly') selected @endif>Monthly</option>
                                    <option value="yearly" @if(request()->get('period') === 'yearly') selected @endif>Yearly</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="form-group">
                               <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" value="{{request()->get('start_date')}}"  class="form-control form-control-sm flatdate" name="start_date">
                            </div>
                        </div>
                        <!--col-sm-3--> 
                        <div class="col-sm-2">
                            <div class="form-group">
                               <label for="end_date" class="form-label">End Date</label>
                                <input type="date" value="{{request()->get('end_date')}}" class="form-control form-control-sm flatdate" name="end_date">
                            </div>
                        </div>
                        <!--col-sm-3-->
                        <div class="col-sm-12">
                            <div class="form-group mb-3 d-flex gap-2 mt-2">
                                <button class="btn btn-sm btn-primary">Submit</button>
                                <a href="{{route('admin.order_report')}}" class="btn btn-sm btn-danger">Reset</a>
                            </div>
                        </div>
                        <!-- col end -->
                    </div>  
                </form>
                <div class="row mb-3">
                    <div class="col-sm-6 no-print">
                         {{$orders->links('pagination::bootstrap-4')}}
                    </div>
                    <div class="col-sm-6">
                        <div class="export-print text-end">
                            <button onclick="printFunction()"class="no-print btn btn-success"><i class="fa fa-print"></i> Print</button>
                            <a href="{{ route('admin.order_report', array_merge(request()->query(), ['export' => 'xlsx'])) }}" class="no-print btn btn-info">
                                <i class="fas fa-file-export"></i> Export
                            </a>
                        </div>
                    </div>
                </div>
                <div id="content-to-export">
                    <div class="table-responsive report-sticky-container">
                        <table class="table nowrap w-100">
                        <thead>
                            <tr>
                                <th style="width:5%">Invoice</th>
                                <th style="width:20%">Customer</th>
                                <th style="width:20%">Phone</th>
                                <th style="width:30%">Product</th>
                                <th style="width:10%">Purchase</th>
                                <th style="width:10%">Sale</th>
                                <th style="width:10%">Qty</th>
                                <th style="width:10%">Total</th>
                            </tr>
                        </thead>               
                    
                        <tbody>
                            @forelse($orders as $key=>$value)
                             
                            <tr>
                                <td>{{$value->order?$value->order->invoice_id:''}}</td>
                                <td>{{$value->shipping?$value->shipping->name:''}}</td>
                                <td>{{$value->shipping?$value->shipping->phone:''}}</td>
                                <td>{{$value->product_name}}</td>
                                <td>{{$value->purchase_price}}</td>
                                <td>{{$value->sale_price}}</td>
                                <td>{{$value->qty}}</td>
                                <td>{{$value->qty*$value->sale_price}}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">No report data found for the selected filters.</td>
                            </tr>
                            @endforelse
                         </tbody>
                         <tfoot>
                              <tr>
                                  <td colspan="4" class="text-end"><strong>Grand Total</strong></td>
                                  <td><strong>{{number_format((float) $total_purchase, 2)}}</strong></td>
                                  <td><strong>{{number_format((float) $total_sales, 2)}}</strong></td>
                                  <td><strong>{{number_format((float) $total_item, 2)}}</strong></td>
                                  <td><strong>{{number_format((float) $total_sales, 2)}}</strong></td>
                              </tr>
                              <tr>
                                  <td colspan="8" class="text-center">
                                      <h5><strong>Total Orders = {{number_format((int) $total_orders)}}</strong></h5>
                                      <h5><strong>Total Purchase = {{number_format((float) $total_purchase, 2)}}</strong></h5>
                                      <h5><strong>Total Sales = {{number_format((float) $total_sales, 2)}}</strong></h5>
                                      <h5><strong>Total Profit = {{number_format((float) $total_sales - (float) $total_purchase, 2)}}</strong></h5>
                                  </td>
                              </tr>
                          </tfoot>
                        </table>
                    </div>

                </div>
            </div> <!-- end card body-->
        </div> <!-- end card -->
    </div><!-- end col-->
   </div>
</div>
@endsection
@section('script')
<script src="{{asset('public/backEnd/')}}/assets/libs/select2/js/select2.min.js"></script>
<script src="{{asset('public/backEnd/')}}/assets/js/pages/form-advanced.init.js"></script>
<script src="{{asset('public/backEnd/')}}/assets/libs/flatpickr/flatpickr.min.js"></script>

<script type="text/javascript">
    $(document).ready(function () {
        $('.select2').select2();
        flatpickr(".flatdate", {});
    });
</script>
<script>
    function printFunction() {
        window.print();
    }
</script>

@endsection
