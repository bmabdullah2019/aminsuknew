@extends('backEnd.layouts.master')
@section('title','Loss Entries Management')
@section('content')
<div class="container-fluid">

    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.profit-loss.create-loss') }}" class="btn btn-sm btn-primary">
                        <i class="mdi mdi-plus"></i> Report New Loss
                    </a>
                </div>
                <h4 class="page-title">Loss Entries Management</h4>
                <p class="text-muted">Track and manage inventory losses</p>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $pendingLosses }}</h4>
                    <small>Pending Approvals</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $approvedLosses }}</h4>
                    <small>Approved Losses</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">BDT {{ number_format($totalLossValue, 2) }}</h4>
                    <small>Total Loss Value</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $losses->total() }}</h4>
                    <small>Total Entries</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.profit-loss.losses') }}">
                        @php
                            $selectedEntryType = request('entry_type') === 'stolen' ? 'theft' : request('entry_type');
                        @endphp
                        <div class="row">
                            <div class="col-md-2">
                                <label for="start_date">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="{{ request('start_date') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="end_date">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="{{ request('end_date') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="entry_type">Loss Type</label>
                                <select class="form-control" id="entry_type" name="entry_type">
                                    <option value="">All Types</option>
                                    <option value="damage" {{ $selectedEntryType === 'damage' ? 'selected' : '' }}>Damage</option>
                                    <option value="expired" {{ $selectedEntryType === 'expired' ? 'selected' : '' }}>Expired</option>
                                    <option value="theft" {{ $selectedEntryType === 'theft' ? 'selected' : '' }}>Theft</option>
                                    <option value="other" {{ $selectedEntryType === 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="">All Status</option>
                                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="product_id">Product</label>
                                <select class="form-control" id="product_id" name="product_id">
                                    <option value="">All Products</option>
                                    @foreach($products as $product)
                                    <option value="{{ $product->id }}" {{ (string) request('product_id') === (string) $product->id ? 'selected' : '' }}>
                                        {{ $product->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="warehouse_id">Warehouse</label>
                                <select class="form-control" id="warehouse_id" name="warehouse_id">
                                    <option value="">All Warehouses</option>
                                    @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" {{ (string) request('warehouse_id') === (string) $warehouse->id ? 'selected' : '' }}>
                                        {{ $warehouse->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="mdi mdi-filter"></i> Filter
                                    </button>
                                    <a href="{{ route('admin.profit-loss.losses') }}" class="btn btn-secondary">
                                        <i class="mdi mdi-refresh"></i> Clear
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-centered table-striped">
                            <thead>
                                <tr>
                                    <th>Entry #</th>
                                    <th>Date</th>
                                    <th>Product</th>
                                    <th>Warehouse</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>Loss Value</th>
                                    <th>Status</th>
                                    <th>Reported By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($losses as $loss)
                                <tr>
                                    <td><strong>{{ $loss->entry_number }}</strong></td>
                                    <td>{{ $loss->entry_date->format('M d, Y') }}</td>
                                    <td>
                                        <strong>{{ optional($loss->product)->name ?? 'N/A' }}</strong>
                                        <br>
                                        <small class="text-muted">{{ optional($loss->product)->product_code ?? optional($loss->product)->sku ?? 'N/A' }}</small>
                                    </td>
                                    <td>
                                        <strong>{{ optional($loss->warehouse)->name ?? 'N/A' }}</strong>
                                        <br>
                                        <small class="text-muted">{{ optional($loss->warehouse)->city ?? 'N/A' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $loss->entry_type_color }}">
                                            {{ ucfirst($loss->entry_type === 'stolen' ? 'theft' : $loss->entry_type) }}
                                        </span>
                                    </td>
                                    <td>{{ number_format($loss->quantity, 2) }}</td>
                                    <td class="text-danger"><strong>BDT {{ number_format($loss->total_loss_amount, 2) }}</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $loss->status_color }}">
                                            {{ ucfirst($loss->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ optional($loss->reporter)->name ?? 'System' }}
                                        <br>
                                        <small class="text-muted">{{ $loss->entry_date->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('admin.profit-loss.show-loss', $loss) }}" class="btn btn-sm btn-info">
                                                <i class="mdi mdi-eye"></i>
                                            </a>
                                            @if($loss->status === 'pending')
                                            <form method="POST" action="{{ route('admin.profit-loss.approve-loss', $loss) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to approve this loss entry?')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="mdi mdi-check"></i>
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="rejectLoss({{ $loss->id }})">
                                                <i class="mdi mdi-close"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <i class="mdi mdi-information-outline font-24 text-muted"></i>
                                        <p class="text-muted mb-0">No loss entries found</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($losses->hasPages())
                    <div class="d-flex justify-content-center mt-3">
                        {{ $losses->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectLossModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="rejectLossForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Reject Loss Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="reject_reason" class="form-label">Reason for Rejection</label>
                        <textarea class="form-control" id="reject_reason" name="reason" rows="3" required minlength="5" placeholder="Please provide a reason for rejecting this loss entry..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Loss Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const rejectLossRouteTemplate = @json(route('admin.profit-loss.reject-loss', ['loss' => '__LOSS__']));

function rejectLoss(lossId) {
    const form = document.getElementById('rejectLossForm');
    form.action = rejectLossRouteTemplate.replace('__LOSS__', lossId);
    new bootstrap.Modal(document.getElementById('rejectLossModal')).show();
}
</script>
@endsection
