@extends('backEnd.layouts.master')
@section('title','Returns Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right d-flex gap-2">
                    <a href="{{ route('admin.returns.dashboard') }}" class="btn btn-sm btn-outline-primary">
                        <i class="mdi mdi-view-dashboard"></i> Dashboard
                    </a>
                    <a href="{{ route('admin.returns.create') }}" class="btn btn-sm btn-primary">
                        <i class="mdi mdi-plus"></i> Create Return
                    </a>
                </div>
                <h4 class="page-title">Returns Management</h4>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.returns.index') }}" class="row g-2">
                        <div class="col-md-3">
                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                placeholder="Return #, customer, invoice"
                                value="{{ request('search') }}"
                            >
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                @foreach(['draft', 'pending', 'approved', 'processing', 'completed', 'cancelled', 'rejected'] as $status)
                                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                                    {{ ucfirst($status) }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="return_source" class="form-select">
                                <option value="">All Sources</option>
                                @foreach(['customer', 'warehouse', 'supplier', 'qc'] as $source)
                                <option value="{{ $source }}" {{ request('return_source') === $source ? 'selected' : '' }}>
                                    {{ strtoupper($source) }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="return_reason_id" class="form-select">
                                <option value="">All Reasons</option>
                                @foreach($returnReasons as $reason)
                                <option value="{{ $reason->id }}" {{ (string) request('return_reason_id') === (string) $reason->id ? 'selected' : '' }}>
                                    {{ $reason->reason_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1">
                            <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                        </div>
                        <div class="col-md-1">
                            <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                        </div>
                        <div class="col-md-1 d-flex gap-1">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="mdi mdi-filter"></i>
                            </button>
                            <a href="{{ route('admin.returns.index') }}" class="btn btn-light w-100">
                                <i class="mdi mdi-refresh"></i>
                            </a>
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
                    <form method="POST" action="{{ route('admin.returns.bulk-action') }}" id="bulkActionForm">
                        @csrf

                        <div class="row g-2 mb-3 align-items-start">
                            <div class="col-md-2">
                                <select name="action" id="bulkAction" class="form-select" required>
                                    <option value="">Bulk Action</option>
                                    <option value="approve">Approve</option>
                                    <option value="reject">Reject</option>
                                    <option value="process">Process</option>
                                    <option value="complete">Complete</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <input
                                    type="text"
                                    name="notes"
                                    id="bulkNotes"
                                    class="form-control"
                                    placeholder="Action notes (required for reject)"
                                >
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-warning w-100">
                                    Apply
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th width="40">
                                            <input type="checkbox" id="selectAllReturns">
                                        </th>
                                        <th>Return #</th>
                                        <th>Order</th>
                                        <th>Customer</th>
                                        <th>Reason</th>
                                        <th>Source</th>
                                        <th>Status</th>
                                        <th class="text-end">Return Value</th>
                                        <th class="text-end">Refund</th>
                                        <th>Date</th>
                                        <th width="120">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($returns as $return)
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="return_ids[]" value="{{ $return->id }}" class="return-checkbox">
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.returns.show', $return) }}">
                                                {{ $return->return_number }}
                                            </a>
                                        </td>
                                        <td>{{ optional($return->order)->invoice_id ?? 'N/A' }}</td>
                                        <td>{{ optional($return->customer)->name ?? 'N/A' }}</td>
                                        <td>{{ optional($return->returnReason)->reason_name ?? 'Unknown' }}</td>
                                        <td>{{ strtoupper($return->return_source) }}</td>
                                        <td>
                                            <span class="badge bg-{{ $return->status_color }}">{{ $return->status_label }}</span>
                                        </td>
                                        <td class="text-end">BDT {{ number_format((float) $return->total_return_value, 2) }}</td>
                                        <td class="text-end">BDT {{ number_format((float) $return->refund_amount, 2) }}</td>
                                        <td>{{ optional($return->created_at)->format('d M Y') }}</td>
                                        <td>
                                            <a href="{{ route('admin.returns.show', $return) }}" class="btn btn-sm btn-info" title="View">
                                                <i class="mdi mdi-eye"></i>
                                            </a>
                                            @if(in_array($return->return_status, ['draft', 'pending'], true))
                                            <a href="{{ route('admin.returns.edit', $return) }}" class="btn btn-sm btn-warning" title="Edit">
                                                <i class="mdi mdi-pencil"></i>
                                            </a>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="11" class="text-center text-muted">No returns found</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </form>

                    <div class="d-flex justify-content-center mt-3">
                        {{ $returns->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
(function () {
    const selectAll = document.getElementById('selectAllReturns');
    const checkboxes = document.querySelectorAll('.return-checkbox');
    const actionInput = document.getElementById('bulkAction');
    const notesInput = document.getElementById('bulkNotes');
    const form = document.getElementById('bulkActionForm');

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach((checkbox) => {
                checkbox.checked = selectAll.checked;
            });
        });
    }

    if (actionInput && notesInput) {
        actionInput.addEventListener('change', function () {
            notesInput.required = actionInput.value === 'reject';
        });
    }

    if (form) {
        form.addEventListener('submit', function (event) {
            const selectedCount = Array.from(checkboxes).filter((checkbox) => checkbox.checked).length;
            if (selectedCount === 0) {
                event.preventDefault();
                alert('Select at least one return for bulk action.');
                return;
            }

            if (actionInput.value === 'reject' && !notesInput.value.trim()) {
                event.preventDefault();
                alert('Notes are required when rejecting returns.');
            }
        });
    }
})();
</script>
@endpush
