@extends('backEnd.layouts.master')
@section('title','Expenses Management')
@section('content')
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    @can('expense-create')
                        <a href="{{ route('admin.expense.create') }}" class="btn btn-sm btn-primary">
                            <i class="mdi mdi-plus"></i> Add Expense
                        </a>
                    @endcan
                </div>
                <h4 class="page-title">Expenses Management</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $summary['total_expenses'] }}</h4>
                    <small>Total Expenses</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $summary['pending_count'] }}</h4>
                    <small>Pending</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $summary['approved_count'] }}</h4>
                    <small>Approved</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $summary['paid_count'] }}</h4>
                    <small>Paid</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $summary['rejected_count'] }}</h4>
                    <small>Rejected</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">BDT {{ number_format($summary['paid_total'], 2) }}</h4>
                    <small>Total Paid</small>
                </div>
            </div>
        </div>
    </div>

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
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-control" id="category_id" name="category_id">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="warehouse_id" class="form-label">Warehouse</label>
                            <select class="form-control" id="warehouse_id" name="warehouse_id">
                                <option value="">All Warehouses</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                        {{ $warehouse->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="payment_method" class="form-label">Payment Method</label>
                            <select class="form-control" id="payment_method" name="payment_method">
                                <option value="">All Methods</option>
                                <option value="cash" {{ request('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="bank_transfer" {{ request('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="cheque" {{ request('payment_method') == 'cheque' ? 'selected' : '' }}>Cheque</option>
                                <option value="card" {{ request('payment_method') == 'card' ? 'selected' : '' }}>Card</option>
                                <option value="other" {{ request('payment_method') == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>

                        @if($suppliers->isNotEmpty())
                            <div class="col-md-3">
                                <label for="supplier_id" class="form-label">Supplier</label>
                                <select class="form-control" id="supplier_id" name="supplier_id">
                                    <option value="">All Suppliers</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ (string) request('supplier_id') === (string) $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}{{ $supplier->supplier_code ? ' (' . $supplier->supplier_code . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @if($purchaseOrders->isNotEmpty())
                            <div class="col-md-3">
                                <label for="purchase_order_id" class="form-label">Purchase Order</label>
                                <select class="form-control" id="purchase_order_id" name="purchase_order_id">
                                    <option value="">All Purchase Orders</option>
                                    @foreach($purchaseOrders as $purchaseOrder)
                                        <option value="{{ $purchaseOrder->id }}" {{ (string) request('purchase_order_id') === (string) $purchaseOrder->id ? 'selected' : '' }}>
                                            {{ $purchaseOrder->po_number }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @if($grns->isNotEmpty())
                            <div class="col-md-3">
                                <label for="grn_id" class="form-label">GRN</label>
                                <select class="form-control" id="grn_id" name="grn_id">
                                    <option value="">All GRNs</option>
                                    @foreach($grns as $grn)
                                        <option value="{{ $grn->id }}" {{ (string) request('grn_id') === (string) $grn->id ? 'selected' : '' }}>
                                            {{ $grn->grn_number }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="col-md-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search"
                                   placeholder="Expense # or description" value="{{ request('search') }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="mdi mdi-filter"></i> Filter
                                </button>
                                <a href="{{ route('admin.expense.index') }}" class="btn btn-secondary">
                                    <i class="mdi mdi-refresh"></i> Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Expenses Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Expenses</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Expense #</th>
                                    <th>Date</th>
                                    <th>Category</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expenses as $expense)
                                <tr>
                                    <td>
                                        <strong>{{ $expense->expense_number }}</strong>
                                    </td>
                                    <td>{{ $expense->expense_date->format('d M Y') }}</td>
                                    <td>
                                        <span class="badge bg-info">{{ optional($expense->category)->name ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <strong>BDT {{ number_format($expense->total_amount, 2) }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $expense->payment_method)) }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $expense->status_badge }}">{{ ucfirst($expense->status) }}</span>
                                    </td>
                                    <td>{{ optional($expense->creator)->name ?? 'Unknown' }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('admin.expense.show', $expense) }}" class="btn btn-sm btn-outline-info" title="View">
                                                <i class="mdi mdi-eye"></i>
                                            </a>
                                            @if($expense->status === 'pending')
                                                @can('expense-edit')
                                                    <a href="{{ route('admin.expense.edit', $expense) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                                        <i class="mdi mdi-pencil"></i>
                                                    </a>
                                                @endcan
                                            @endif
                                            @if($expense->status === 'pending')
                                                @can('expense-approve')
                                                    <button type="button" class="btn btn-sm btn-outline-success" title="Approve"
                                                            onclick="approveExpense({{ $expense->id }})">
                                                        <i class="mdi mdi-check"></i>
                                                    </button>
                                                @endcan
                                                @can('expense-reject')
                                                    <button type="button" class="btn btn-sm btn-outline-danger" title="Reject"
                                                            onclick="rejectExpense({{ $expense->id }})">
                                                        <i class="mdi mdi-close"></i>
                                                    </button>
                                                @endcan
                                            @elseif($expense->status === 'approved')
                                                @can('expense-mark-paid')
                                                    <button type="button" class="btn btn-sm btn-outline-success" title="Mark as Paid"
                                                            onclick="markAsPaid({{ $expense->id }})">
                                                        <i class="mdi mdi-cash"></i>
                                                    </button>
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        <i class="mdi mdi-information-outline"></i>
                                        No expenses found matching your criteria.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($expenses->hasPages())
                    <div class="d-flex justify-content-center mt-3">
                        {{ $expenses->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approval Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Expense Approval</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approvalForm">
                @csrf
                <div class="modal-body">
                    <p>Are you sure you want to approve this expense?</p>
                    <input type="hidden" id="expenseId" name="expense_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div class="modal fade" id="rejectionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Expense Rejection</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectionForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required minlength="5"></textarea>
                    </div>
                    <input type="hidden" id="rejectExpenseId" name="expense_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const expenseApproveRouteTemplate = @json(route('admin.expense.approve', ['expense' => '__EXPENSE__']));
const expenseRejectRouteTemplate = @json(route('admin.expense.reject', ['expense' => '__EXPENSE__']));
const expenseMarkPaidRouteTemplate = @json(route('admin.expense.mark-paid', ['expense' => '__EXPENSE__']));

function openModalById(modalId) {
    const modalEl = document.getElementById(modalId);
    if (!modalEl) {
        return;
    }

    if (window.bootstrap && window.bootstrap.Modal) {
        const modalInstance = (typeof window.bootstrap.Modal.getInstance === 'function')
            ? (window.bootstrap.Modal.getInstance(modalEl) || new window.bootstrap.Modal(modalEl))
            : new window.bootstrap.Modal(modalEl);
        modalInstance.show();
        return;
    }

    if (typeof window.jQuery !== 'undefined' && typeof window.jQuery.fn.modal === 'function') {
        window.jQuery('#' + modalId).modal('show');
    }
}

function closeModalById(modalId) {
    const modalEl = document.getElementById(modalId);
    if (!modalEl) {
        return;
    }

    if (window.bootstrap && window.bootstrap.Modal) {
        const modalInstance = (typeof window.bootstrap.Modal.getInstance === 'function')
            ? (window.bootstrap.Modal.getInstance(modalEl) || new window.bootstrap.Modal(modalEl))
            : new window.bootstrap.Modal(modalEl);
        modalInstance.hide();
        return;
    }

    if (typeof window.jQuery !== 'undefined' && typeof window.jQuery.fn.modal === 'function') {
        window.jQuery('#' + modalId).modal('hide');
    }
}

function approveExpense(expenseId) {
    if (!confirm('Are you sure you want to approve this expense?')) {
        return;
    }

    const formData = new FormData();
    formData.append('_token', document.querySelector('meta[name=\"csrf-token\"]').content);
    formData.append('expense_id', expenseId);

    fetch(expenseApproveRouteTemplate.replace('__EXPENSE__', expenseId), {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Approval failed'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing the request.');
    });
}

function rejectExpense(expenseId) {
    document.getElementById('rejectExpenseId').value = expenseId;
    openModalById('rejectionModal');
}

function markAsPaid(expenseId) {
    if (confirm('Are you sure you want to mark this expense as paid?')) {
        fetch(expenseMarkPaidRouteTemplate.replace('__EXPENSE__', expenseId), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while processing the request.');
        });
    }
}

// Handle rejection form submission
document.getElementById('rejectionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch(expenseRejectRouteTemplate.replace('__EXPENSE__', formData.get('expense_id')), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModalById('rejectionModal');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing the request.');
    });
});
</script>
@endsection



