@extends('backEnd.layouts.master')
@section('title','Stock Report')
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
  .stock-sticky-container {
      max-height: 65vh;
  }
</style>
@endsection 
<div class="container-fluid">
    
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Stock Report</h4>
            </div>
        </div>
    </div>       
    <!-- end page title -->
   <div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form class="no-print" method="GET" action="{{ route('admin.stock_report') }}">
                    <div class="row">   
                        <div class="col-sm-2">
                            <div class="form-group">
                               <label for="keyword" class="form-label">Keyword</label>
                                <input type="text" value="{{request()->get('keyword')}}" class="form-control" name="keyword">
                            </div>
                        </div>
                        <!--col-sm-3-->
                        <div class="col-sm-2">
                            <div class="form-group mb-3">
                                <label for="category_id" class="form-label">Categories </label>
                                <select class="form-control select2 @error('category_id') is-invalid @enderror" name="category_id" value="{{ old('category_id') }}" >
                                    <option value="">Select..</option>
                                    @foreach($categories as $category)
                                    <option value="{{$category->id}}" @if(request()->get('category_id') == $category->id) selected @endif>{{$category->name}}</option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>
                        <!-- col end -->
                        <div class="col-sm-2">
                            <div class="form-group mb-3">
                                <label for="warehouse_id" class="form-label">Warehouse</label>
                                <select class="form-control select2 @error('warehouse_id') is-invalid @enderror" name="warehouse_id">
                                    <option value="">All Warehouses</option>
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
                                <label for="stock_status" class="form-label">Stock Status</label>
                                <select class="form-control select2 @error('stock_status') is-invalid @enderror" name="stock_status">
                                    <option value="">All</option>
                                    <option value="in_stock" @if(request()->get('stock_status') === 'in_stock') selected @endif>In Stock</option>
                                    <option value="low_stock" @if(request()->get('stock_status') === 'low_stock') selected @endif>Low Stock</option>
                                    <option value="out_of_stock" @if(request()->get('stock_status') === 'out_of_stock') selected @endif>Out of Stock</option>
                                </select>
                                @error('stock_status')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="form-group mb-3">
                                <label for="period" class="form-label">Period</label>
                                <select class="form-control select2 @error('period') is-invalid @enderror" name="period">
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
                                <input type="date" value="{{request()->get('start_date')}}"  class="form-control flatdate" name="start_date">
                            </div>
                        </div>
                        <!--col-sm-3--> 
                        <div class="col-sm-2">
                            <div class="form-group">
                               <label for="end_date" class="form-label">End Date</label>
                                <input type="date" value="{{request()->get('end_date')}}"  class="form-control flatdate" name="end_date">
                            </div>
                        </div>
                        <!--col-sm-3-->
                        <div class="col-sm-12">
                            <div class="form-group mb-3">
                                <button class="btn btn-primary">Submit</button>
                                <a href="{{route('admin.stock_report')}}" class="btn btn-danger">Reset</a>
                            </div>
                        </div>
                        <!-- col end -->
                    </div>  
                </form>
                <div class="row mb-3">
                    <div class="col-sm-6 no-print">
                         {{$products->links('pagination::bootstrap-4')}}
                    </div>
                    <div class="col-sm-6">
                        <div class="export-print text-end">
                            <button onclick="printFunction()"class="no-print btn btn-success"><i class="fa fa-print"></i> Print</button>
                            <a href="{{ route('admin.stock_report', array_merge(request()->query(), ['export' => 'xlsx'])) }}" class="no-print btn btn-info">
                                <i class="fas fa-file-export"></i> Export
                            </a>
                        </div>
                    </div>
                </div>
                <div id="content-to-export" class="table-responsive report-sticky-container stock-sticky-container">
                    <table class="table nowrap w-100">
                    <thead>
                        <tr>
                            <th style="width:5%">SL</th>
                            <th style="width:30%">Product Name</th>
                            <th style="width:10%">Price</th>
                            <th style="width:10%">Stock</th>
                            <th style="width:10%">Total</th>
                        </tr>
                    </thead>               
                 
                    <tbody>
                        @forelse($products as $key=>$value)
                        <tr>
                            <td>{{$loop->iteration}}</td>
                            <td>{{$value->name}}</td>
                            <td>{{$value->new_price}}</td>
                            <td>{{$value->stock}}</td>
                            <td>{{$value->stock*$value->new_price}}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No stock report data found for the selected filters.</td>
                        </tr>
                        @endforelse
                     </tbody>
                     <tfoot>
                              <tr>
                                  <td colspan="3" class="text-end"><strong>Total</strong></td>
                                  <td><strong>{{number_format((float) $total_stock, 2)}} Pcs</strong></td>
                                  <td><strong>{{number_format((float) $total_price, 2)}} Tk</strong></td>
                              </tr>
                              <tr>
                                  <td colspan="6" class="text-center">
                                      <h5><strong>Total Products = {{number_format((int) $total_products)}}</strong></h5>
                                      <h5><strong>Total Purchase = {{number_format((float) $total_purchase, 2)}}</strong></h5>
                                      <h5><strong>Total Stock = {{number_format((float) $total_stock, 2)}} Pcs</strong></h5>
                                      <h5><strong>Total Price = {{number_format((float) $total_price, 2)}} Tk</strong></h5>
                                  </td>
                              </tr>
                          </tfoot>
                    </table>
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

