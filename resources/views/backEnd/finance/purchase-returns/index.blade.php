@extends('backEnd.layouts.master')
@section('title','Purchase Returns')
@section('css')
<link href="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<link href="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css" rel="stylesheet" type="text/css" />
@endsection

@section('content')
<div class="container-fluid">
    
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    @can('purchase-return-create')
                    <a href="{{route('admin.finance.purchase-returns.create')}}" class="btn btn-danger rounded-pill"><i class="fe-plus"></i> New Return</a>
                    @endcan
                </div>
                <h4 class="page-title">Purchase Returns</h4>
            </div>
        </div>
    </div>       
    <!-- end page title --> 

   <div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <!-- Filters Section -->
                <div class="row mb-3">
                    <div class="col-sm-8">
                        <ul class="action2-btn">
                            <li><a href="{{route('admin.finance.purchase-returns.index',['status'=>'pending'])}}" class="btn rounded-pill btn-warning"><i class="fe-clock"></i> Pending</a></li>
                            <li><a href="{{route('admin.finance.purchase-returns.index',['status'=>'approved'])}}" class="btn rounded-pill btn-success"><i class="fe-check"></i> Approved</a></li>
                            <li><a href="{{route('admin.finance.purchase-returns.index',['status'=>'rejected'])}}" class="btn rounded-pill btn-danger"><i class="fe-x"></i> Rejected</a></li>
                            <li><a href="{{route('admin.finance.purchase-returns.index',['status'=>'processed'])}}" class="btn rounded-pill btn-info"><i class="fe-check-circle"></i> Processed</a></li>
                            <li><a href="{{route('admin.finance.purchase-returns.index')}}" class="btn rounded-pill btn-secondary"><i class="fe-list"></i> All</a></li>
                        </ul>
                    </div>
                    <div class="col-sm-4">
                        <form method="GET" action="{{route('admin.finance.purchase-returns.index')}}" class="custom_form">
                            <div class="form-group">
                                <input type="text" name="search" value="{{request('search')}}" placeholder="Search return number...">
                                <button class="btn rounded-pill btn-info">Search</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Return #</th>
                            <th>Supplier</th>
                            <th>Branch</th>
                            <th>Return Date</th>
                            <th>Amount</th>
                            <th>Reason</th>
                            <th>Items</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>               
                
                    <tbody>
                        @forelse($purchaseReturns as $return)
                        <tr>
                            <td><strong>{{$return->return_number}}</strong></td>
                            <td>
                                <a href="{{route('admin.supplier.show', $return->supplier_id)}}" class="text-primary">
                                    {{$return->supplier->name ?? 'N/A'}}
                                </a>
                            </td>
                            <td>
                                <span class="badge bg-soft-secondary text-dark">
                                    {{$return->branch->name ?? 'N/A'}}
                                </span>
                            </td>
                            <td>{{$return->return_date->format('d M Y')}}</td>
                            <td>
                                <strong>৳{{number_format($return->total_return_amount, 2)}}</strong>
                            </td>
                            <td>
                                <span class="badge bg-soft-info text-info">{{str_replace('_', ' ', ucfirst($return->return_reason))}}</span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark">{{$return->items->count()}} items</span>
                            </td>
                            <td>
                                @if($return->status == 'pending')
                                    <span class="badge bg-soft-warning text-warning">Pending Approval</span>
                                @elseif($return->status == 'approved')
                                    <span class="badge bg-soft-success text-success">Approved</span>
                                @elseif($return->status == 'processed')
                                    <span class="badge bg-soft-info text-info">Processed</span>
                                @elseif($return->status == 'rejected')
                                    <span class="badge bg-soft-danger text-danger">Rejected</span>
                                @endif
                            </td>
                            <td>
                                <div class="td-content" title="{{$return->creator->name ?? 'System'}}">
                                    {{$return->creator->name ?? 'System'}}
                                </div>
                            </td>
                            <td>
                                <div class="button-list">
                                    <a href="{{route('admin.finance.purchase-returns.show', $return->id)}}" title="View Details" class="btn btn-sm btn-info">
                                        <i class="fe-eye"></i>
                                    </a>
                                    @can('purchase-return-edit')
                                    @if($return->status == 'pending')
                                    <a href="{{route('admin.finance.purchase-returns.edit', $return->id)}}" title="Edit" class="btn btn-sm btn-warning">
                                        <i class="fe-edit"></i>
                                    </a>
                                    @endif
                                    @endcan
                                    @can('purchase-return-delete')
                                    @if($return->status == 'pending')
                                    <form method="POST" action="{{route('admin.finance.purchase-returns.destroy', $return->id)}}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Delete" class="btn btn-sm btn-danger delete-confirm">
                                            <i class="fe-trash-2"></i>
                                        </button>
                                    </form>
                                    @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center">No purchase returns found</td>
                        </tr>
                        @endforelse
                     </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="custom-paginate">
                    {{$purchaseReturns->appends(request()->query())->links()}}
                </div>
            </div>
        </div>
    </div>
   </div>
</div>
@endsection

@push('script')
<script>
    // Delete confirmation
    document.addEventListener('click', function(e) {
        if(e.target.classList.contains('delete-confirm')) {
            if(!confirm('Are you sure you want to delete this return? This action cannot be undone.')) {
                e.preventDefault();
            }
        }
    });
</script>
@endpush
