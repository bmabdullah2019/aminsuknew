@extends('backEnd.layouts.master')
@section('title','Product Manage')
@section('content')
<div class="container-fluid">
    
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{route('admin.products.create')}}" class="btn btn-danger rounded-pill"><i class="fe-shopping-cart"></i> Add Product</a>
                </div>
                <h4 class="page-title">Product Manage</h4>
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
                            <li><a href="{{route('admin.products.update_deals',['status'=>1])}}" class="btn rounded-pill btn-success hotdeal_update"><i class="fe-thumbs-up"></i> Deal</a></li>
                            <li><a href="{{route('admin.products.update_deals',['status'=>0])}}" class="btn  rounded-pill btn-danger hotdeal_update"><i class="fe-thumbs-down"></i> Deal</a></li>

                            <li><a href="{{route('admin.products.update_status',['status'=>1])}}" class="btn rounded-pill btn-primary update_status"><i class="fe-thumbs-up"></i> Active</a></li>
                            <li><a href="{{route('admin.products.update_status',['status'=>0])}}" class="btn  rounded-pill btn-warning update_status"><i class="fe-thumbs-down"></i> Inactive</a></li>
                        </ul>
                    </div>
                    <div class="col-sm-4">
                        <form class="custom_form" method="GET" action="{{ route('admin.products.index') }}">
                            <div class="form-group">
                                <input type="text" name="keyword" placeholder="Search" value="{{ request('keyword') }}">
                                <button type="submit" class="btn rounded-pill btn-info">Search</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table nowrap w-100">
                    <thead>
                        <tr>
                            <th style="width:2%"><div class="form-check"><label class="form-check-label"><input type="checkbox" class="form-check-input checkall" value=""></label>
                            <th style="width:2%">SL</th>
                                    </div></th>
                            <th style="width:18%">Action</th>
                            <th style="width:18%">Name</th>
                            <th style="width:10%">Category</th>
                            <th style="width:8%">Image</th>
                            <th style="width:10%">Price</th>
                            <th style="width:8%">Stock</th>
                            <th style="width:14%">Deal & Feature</th>
                            <th style="width:8%">Status</th>
                        </tr>
                    </thead>               
                
                    <tbody>
                        @foreach($data as $key=>$value)
                        <tr>
                            <td><input type="checkbox" class="checkbox" value="{{$value->id}}"></td>
                            <td>{{$loop->iteration}}</td>
                            <td>
                                <div class="button-list custom-btn-list d-flex flex-wrap gap-1">
                                    @if($value->status == 1)
                                    <form method="post" action="{{route('admin.products.inactive')}}" class="d-inline">
                                    @csrf
                                    <input type="hidden" value="{{$value->id}}" name="hidden_id">
                                    <button type="button" class="btn btn-sm btn-light change-confirm" title="Active"><i class="fe-thumbs-down"></i></button></form>
                                    @else
                                    <form method="post" action="{{route('admin.products.active')}}" class="d-inline">
                                        @csrf
                                    <input type="hidden" value="{{$value->id}}" name="hidden_id">
                                    <button type="button" class="btn btn-sm btn-light change-confirm" title="Inactive"><i class="fe-thumbs-up"></i></button></form>
                                    @endif

                                    <a href="{{route('admin.products.edit',$value->id)}}" class="btn btn-sm btn-light" title="Edit"><i class="fe-edit"></i></a>
                                    <a href="{{ route('admin.products.variants.edit', $value->id) }}" class="btn btn-sm btn-light" title="Variants"><i class="fe-layers"></i></a>

                                    <form method="post" action="{{route('admin.products.destroy')}}" class="d-inline">
                                        @csrf
                                    <input type="hidden" value="{{$value->id}}" name="hidden_id">
                                    <button type="submit" class="btn btn-sm btn-light delete-confirm" title="Delete"><i class="fe-trash-2"></i></button></form>
                                     <a href="{{route('admin.products.edit',$value->id)}}" class="btn btn-sm btn-light" title="Copy"><i class="fe-copy"></i></a>
                                </div>
                            </td>
                            <td>{{$value->name}}</td>
                            <td>{{$value->category?$value->category->name:''}}</td>
                            @php
                                $fallbackAdminImage = 'public/backEnd/assets/images/products/product-1.png';
                                $normalizeAdminImage = function (?string $path) {
                                    $normalized = ltrim(str_replace('\\', '/', trim((string) $path)), '/');
                                    if ($normalized === '') {
                                        return null;
                                    }
                                    if (\Illuminate\Support\Str::startsWith($normalized, ['http://', 'https://', 'data:'])) {
                                        return $normalized;
                                    }
                                    if (\Illuminate\Support\Str::startsWith($normalized, 'public/')) {
                                        return $normalized;
                                    }
                                    if (\Illuminate\Support\Str::startsWith($normalized, 'storage/')) {
                                        return 'public/' . $normalized;
                                    }
                                    if (\Illuminate\Support\Str::startsWith($normalized, 'uploads/')) {
                                        return 'public/' . $normalized;
                                    }

                                    return 'public/storage/' . $normalized;
                                };
                                $adminProductImageCandidates = [
                                    $normalizeAdminImage($value->thumbnail ?? null),
                                    $normalizeAdminImage(optional($value->image)->getRawOriginal('image')),
                                    $normalizeAdminImage(optional($value->image)->image),
                                ];
                                $adminProductImage = collect($adminProductImageCandidates)
                                    ->filter()
                                    ->first(function ($path) {
                                        if (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://', 'data:'])) {
                                            return true;
                                        }

                                        return is_file(base_path($path));
                                    }) ?: $fallbackAdminImage;
                            @endphp
                            <td><img src="{{ asset($adminProductImage) }}" class="backend-image" alt="{{ $value->name }}" onerror="this.onerror=null;this.src='{{ asset($fallbackAdminImage) }}';"></td>
                            <td>{{$value->new_price}}</td>
                            <td>
                                @php($availableStock = (float) ($value->available_stock ?? 0))
                                <span class="badge {{ $availableStock > 0 ? 'bg-success' : 'bg-danger' }}">
                                    {{ rtrim(rtrim(number_format($availableStock, 2, '.', ''), '0'), '.') }}
                                </span>
                            </td>
                            <td><p class="m-0">Hot Deals : {{$value->topsale==1?'Yes':'No'}}</p>
                                <p class="m-0">Top Feature : {{$value->feature_product==1?'Yes':'No'}}</p></td>
                            <td>@if($value->status==1)<span class="badge bg-soft-success text-success">Active</span> @else <span class="badge bg-soft-danger text-danger">Inactive</span> @endif</td>
                        </tr>
                        @endforeach
                     </tbody>
                    </table>
                </div>
                <div class="custom-paginate">
                    {{$data->links('pagination::bootstrap-4')}}
                </div>
            </div> <!-- end card body-->
        </div> <!-- end card -->
    </div><!-- end col-->
   </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function(){
    $(".checkall").on('change',function(){
      $(".checkbox").prop('checked',$(this).is(":checked"));
    });
    
    $(document).on('click', '.hotdeal_update', function(e){
        e.preventDefault();
        var url = $(this).attr('href');
        console.log('url',url);
        var product = $('input.checkbox:checked').map(function(){
          return $(this).val();
        });
        var product_ids=product.get();
        if(product_ids.length ==0){
            toastr.error('Please Select A Product First !');
            return ;
        }
        $.ajax({
           type:'GET',
           url:url,
           data:{product_ids},
           success:function(res){
               if(res.status=='success'){
                toastr.success(res.message);
                window.location.reload();
            }else{
                toastr.error('Failed something wrong');
            }
           }
        });
    });
    $(document).on('click', '.update_status', function(e){
        e.preventDefault();
        var url = $(this).attr('href');
        var product = $('input.checkbox:checked').map(function(){
          return $(this).val();
        });
        var product_ids=product.get();
        if(product_ids.length ==0){
            toastr.error('Please Select A Product First !');
            return ;
        }
        $.ajax({
           type:'GET',
           url:url,
           data:{product_ids},
           success:function(res){
               if(res.status=='success'){
                toastr.success(res.message);
                window.location.reload();
            }else{
                toastr.error('Failed something wrong');
            }
           }
        });
    });
    $(document).on('click', '.update_status', function(e){
        e.preventDefault();
        var url = $(this).attr('href');
        var product = $('input.checkbox:checked').map(function(){
          return $(this).val();
        });
        var product_ids=product.get();
        if(product_ids.length ==0){
            toastr.error('Please Select A Product First !');
            return ;
        }
        $.ajax({
           type:'GET',
           url:url,
           data:{product_ids},
           success:function(res){
               if(res.status=='success'){
                toastr.success(res.message);
                window.location.reload();
            }else{
                toastr.error('Failed something wrong');
            }
           }
        });
    });
    
    
})
</script>
@endsection
