@extends('backEnd.layouts.master')
@section('title','Purchase Returns - ' . $supplier->name)
@section('content')
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.supplier.show', $supplier->id) }}" class="btn btn-sm btn-secondary">
                        <i class="mdi mdi-arrow-left"></i> Back to Supplier
                    </a>
                    <a href="{{ route('admin.supplier.purchase-returns.create', $supplier->id) }}" class="btn btn-sm btn-primary">
                        <i class="mdi mdi-plus"></i> Create Return
                    </a>
                </div>
                <h4 class="page-title">Purchase Returns - {{ $supplier->name }}</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <!-- Filter Form -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Filters</h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-2">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date"
                                   value="{{ request('start_date') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date"
                                   value="{{ request('end_date') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="return_reason" class="form-label">Reason</label>
                            <select class="form-control" id="return_reason" name="return_reason">
                                <option value="">All Reasons</option>
                                <option value="damaged" {{ request('return_reason') == 'damaged' ? 'selected' : '' }}>Damaged Goods</option>
                                <option value="wrong_item" {{ request('return_reason') == 'wrong_item' ? 'selected' : '' }}>Wrong Item</option>
                                <option value="quality_issue" {{ request('return_reason') == 'quality_issue' ? 'selected' : '' }}>Quality Issue</option>
                                <option value="over_supply" {{ request('return_reason') == 'over_supply' ? 'selected' : '' }}>Over Supply</option>
                                <option value="other" {{ request('return_reason') == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="mdi mdi-filter"></i> Filter
                                </button>
                                <a href="{{ route('admin.supplier.purchase-returns', $supplier->id) }}" class="btn btn-secondary">
                                    <i class="mdi mdi-refresh"></i> Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Returns Summary -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $returnSummary['total_returns'] ?? 0 }}</h4>
                    <small>Total Returns</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $returnSummary['draft_count'] ?? 0 }}</h4>
                    <small>Draft</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $returnSummary['approved_count'] ?? 0 }}</h4>
                    <small>Approved</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $returnSummary['completed_count'] ?? 0 }}</h4>
                    <small>Completed</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">BDT {{ number_format($returnSummary['total_value'] ?? 0, 2) }}</h4>
                    <small>Total Value</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Returns Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Purchase Return History</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Return #</th>
                                    <th>Date</th>
                                    <th>Branch</th>
                                    <th>Amount</th>
                                    <th>Reason</th>
                                    <th class="text-end">Items</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($returns as $return)
                                <tr>
                                    <td><strong>{{ $return->return_number }}</strong></td>
                                    <td>{{ $return->return_date->format('d M Y') }}</td>
                                    <td>{{ $return->branch->code ?? 'N/A' }}</td>
                                    <td><span class="badge bg-danger">BDT {{ number_format($return->total_amount, 2) }}</span></td>
                                    <td>{{ $return->return_reason_label }}</td>
                                    <td class="text-end">{{ $supportsReturnItems ? (int) ($return->items->count() ?? 0) : 0 }}</td>
                                    <td>
                                        @if($return->status === 'completed')
                                            <span class="badge bg-success">Completed</span>
                                        @elseif($return->status === 'approved')
                                            <span class="badge bg-warning">Approved</span>
                                        @else
                                            <span class="badge bg-secondary">Draft</span>
                                        @endif
                                    </td>
                                    <td>{{ $return->creator->name ?? 'Unknown' }}</td>
                                    <td class="text-center">
                                        @if($return->status === 'draft' && auth()->user()?->can('supplier-return-approve'))
                                        <form method="POST" action="{{ route('admin.supplier.purchase-returns.approve', [$supplier->id, $return->id]) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-warning">Approve</button>
                                        </form>
                                        @endif

                                        @if($return->status === 'approved' && auth()->user()?->can('supplier-return-approve'))
                                        <form method="POST" action="{{ route('admin.supplier.purchase-returns.complete', [$supplier->id, $return->id]) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">Complete</button>
                                        </form>
                                        @endif

                                        @if(!in_array($return->status, ['draft', 'approved'], true))
                                        <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted">
                                        <i class="mdi mdi-information-outline"></i>
                                        No purchase returns found for this supplier.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($returns->hasPages())
                    <div class="d-flex justify-content-center mt-3">
                        {{ $returns->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
