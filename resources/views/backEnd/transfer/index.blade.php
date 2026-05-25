@extends('backEnd.layouts.master')
@section('title','Warehouse Transfer Management')
@section('content')
<div class="container-fluid">
    
    <!-- Page Title Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h2 class="page-title mb-1">Warehouse Transfer Management</h2>
                    <p class="text-muted mb-0">Manage inter-warehouse stock transfers and monitor transfer status</p>
                </div>
                <a href="{{route('admin.transfer.create')}}" class="btn btn-primary btn-lg rounded-3">
                    <i class="fe-plus-circle me-2"></i>New Transfer Request
                </a>
            </div>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Pending Requests</h6>
                        <h3 class="text-warning mb-0">{{ $transfers->where('status', 'pending')->count() }}</h3>
                    </div>
                    <div><i class="fe-clock text-warning" style="font-size: 40px; opacity: 0.2;"></i></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Approved</h6>
                        <h3 class="text-info mb-0">{{ $transfers->where('status', 'approved')->count() }}</h3>
                    </div>
                    <div><i class="fe-check text-info" style="font-size: 40px; opacity: 0.2;"></i></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">In Transit</h6>
                        <h3 class="text-primary mb-0">{{ $transfers->where('status', 'dispatched')->count() }}</h3>
                    </div>
                    <div><i class="fe-truck text-primary" style="font-size: 40px; opacity: 0.2;"></i></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Completed</h6>
                        <h3 class="text-success mb-0">{{ $transfers->where('status', 'completed')->count() }}</h3>
                    </div>
                    <div><i class="fe-check-circle text-success" style="font-size: 40px; opacity: 0.2;"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="GET" action="{{route('admin.transfer.index')}}" id="filterForm">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fw-600">Search Transfer</label>
                                <input type="text" name="search" class="form-control" 
                                    value="{{request('search')}}" placeholder="Enter transfer number...">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-600">From Warehouse</label>
                                <select name="from_warehouse_id" class="form-select">
                                    <option value="">All Warehouses</option>
                                    @foreach($warehouses as $warehouse)
                                        <option value="{{$warehouse->id}}" 
                                            {{ request('from_warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                            {{$warehouse->name}}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-600">To Warehouse</label>
                                <select name="to_warehouse_id" class="form-select">
                                    <option value="">All Warehouses</option>
                                    @foreach($warehouses as $warehouse)
                                        <option value="{{$warehouse->id}}" 
                                            {{ request('to_warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                            {{$warehouse->name}}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fe-search me-2"></i>Filter
                                </button>
                                <a href="{{route('admin.transfer.index')}}" class="btn btn-light w-100 ms-2">
                                    <i class="fe-x me-2"></i>Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Filter Tabs -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{route('admin.transfer.index')}}" 
                    class="btn {{ !request('status') ? 'btn-primary' : 'btn-outline-primary' }} rounded-pill">
                    <i class="fe-list me-2"></i>All Transfers
                </a>
                <a href="{{route('admin.transfer.index',['status'=>'pending'])}}" 
                    class="btn {{ request('status') == 'pending' ? 'btn-warning' : 'btn-outline-warning' }} rounded-pill">
                    <i class="fe-clock me-2"></i>Pending <span class="badge bg-warning ms-2">{{ $transfers->where('status', 'pending')->count() }}</span>
                </a>
                <a href="{{route('admin.transfer.index',['status'=>'approved'])}}" 
                    class="btn {{ request('status') == 'approved' ? 'btn-info' : 'btn-outline-info' }} rounded-pill">
                    <i class="fe-check me-2"></i>Approved <span class="badge bg-info ms-2">{{ $transfers->where('status', 'approved')->count() }}</span>
                </a>
                <a href="{{route('admin.transfer.index',['status'=>'dispatched'])}}" 
                    class="btn {{ request('status') == 'dispatched' ? 'btn-primary' : 'btn-outline-primary' }} rounded-pill">
                    <i class="fe-truck me-2"></i>In Transit <span class="badge bg-primary ms-2">{{ $transfers->where('status', 'dispatched')->count() }}</span>
                </a>
                <a href="{{route('admin.transfer.index',['status'=>'completed'])}}" 
                    class="btn {{ request('status') == 'completed' ? 'btn-success' : 'btn-outline-success' }} rounded-pill">
                    <i class="fe-check-circle me-2"></i>Completed <span class="badge bg-success ms-2">{{ $transfers->where('status', 'completed')->count() }}</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Transfers Table -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%">
                                    <input type="checkbox" class="form-check-input" id="selectAll">
                                </th>
                                <th style="width: 12%">
                                    <strong>Transfer #</strong>
                                </th>
                                <th style="width: 10%">
                                    <strong>Date</strong>
                                </th>
                                <th style="width: 12%">
                                    <strong>From</strong>
                                </th>
                                <th style="width: 12%">
                                    <strong>To</strong>
                                </th>
                                <th style="width: 8%">
                                    <strong>Items</strong>
                                </th>
                                <th style="width: 12%">
                                    <strong>Status</strong>
                                </th>
                                <th style="width: 15%">
                                    <strong>Actions</strong>
                                </th>
                            </tr>
                        </thead>               
                        <tbody>
                            @forelse($transfers as $transfer)
                            <tr class="align-middle">
                                <td>
                                    <input type="checkbox" class="form-check-input transfer-checkbox">
                                </td>
                                <td>
                                    <a href="{{route('admin.transfer.show',$transfer->id)}}" class="text-decoration-none fw-600">
                                        {{$transfer->transfer_number}}
                                    </a>
                                </td>
                                <td>
                                    <small class="text-muted">{{$transfer->transfer_date->format('d M Y')}}</small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">{{$transfer->fromWarehouse->name ?? 'N/A'}}</span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">{{$transfer->toWarehouse->name ?? 'N/A'}}</span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{$transfer->items->count()}}</span>
                                </td>
                                <td>
                                    @if($transfer->status == 'pending')
                                        <span class="badge bg-warning text-dark"><i class="fe-clock me-1"></i>Pending</span>
                                    @elseif($transfer->status == 'approved')
                                        <span class="badge bg-info"><i class="fe-check-circle me-1"></i>Approved</span>
                                    @elseif($transfer->status == 'dispatched')
                                        <span class="badge bg-primary"><i class="fe-truck me-1"></i>In Transit</span>
                                    @elseif($transfer->status == 'completed')
                                        <span class="badge bg-success"><i class="fe-check-circle me-1"></i>Completed</span>
                                    @elseif($transfer->status == 'cancelled')
                                        <span class="badge bg-danger"><i class="fe-x-circle me-1"></i>Cancelled</span>
                                    @else
                                        <span class="badge bg-secondary">{{ucfirst($transfer->status)}}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{route('admin.transfer.show',$transfer->id)}}" 
                                            class="btn btn-outline-primary" title="View Details">
                                            <i class="fe-eye"></i>
                                        </a>
                                        
                                        @if($transfer->status == 'pending')
                                            <form method="post" action="{{route('admin.transfer.approve',$transfer->id)}}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success btn-sm" 
                                                    title="Approve Transfer" onclick="return confirm('Approve this transfer?')">
                                                    <i class="fe-check"></i>
                                                </button>
                                            </form>
                                            <form method="post" action="{{route('admin.transfer.cancel',$transfer->id)}}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-danger btn-sm" 
                                                    title="Cancel Transfer" onclick="return confirm('Cancel this transfer?')">
                                                    <i class="fe-x"></i>
                                                </button>
                                            </form>
                                        @elseif($transfer->status == 'approved')
                                            <form method="post" action="{{route('admin.transfer.dispatch',$transfer->id)}}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-primary btn-sm" 
                                                    title="Dispatch Transfer" onclick="return confirm('Dispatch this transfer?')">
                                                    <i class="fe-send"></i>
                                                </button>
                                            </form>
                                        @elseif($transfer->status == 'dispatched')
                                            <form method="post" action="{{route('admin.transfer.receive',$transfer->id)}}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success btn-sm" 
                                                    title="Receive Transfer" onclick="return confirm('Mark as received?')">
                                                    <i class="fe-check"></i>
                                                </button>
                                            </form>
                                        @endif

                                        <button type="button" class="btn btn-outline-secondary btn-sm" 
                                            onclick="editTransfer({{$transfer->id}})" title="Edit">
                                            <i class="fe-edit-2"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fe-box" style="font-size: 48px; opacity: 0.3;"></i>
                                        <p class="mt-3 mb-0">No warehouse transfers found</p>
                                        <small>Try adjusting your filters</small>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        Showing {{ $transfers->count() }} of total transfers
                    </small>
                    <div class="custom-paginate">
                        {{$transfers->links()}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .page-title {
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 0;
    }
    
    .text-muted {
        color: #6c757d;
    }
    
    .btn-primary, .btn-info, .btn-success, .btn-warning {
        font-weight: 600;
    }
    
    .card {
        border-radius: 8px;
        transition: box-shadow 0.3s ease;
    }
    
    .card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
    }
    
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    .badge {
        font-weight: 600;
        padding: 0.5rem 0.75rem;
    }
    
    .btn-group-sm > .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.85rem;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAllCheckbox = document.getElementById('selectAll');
        const transferCheckboxes = document.querySelectorAll('.transfer-checkbox');
        
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                transferCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
            });
        }
    });
    
    function editTransfer(id) {
        window.location.href = '{{ url("admin/transfer") }}/' + id + '/edit';
    }
</script>
@endsection

