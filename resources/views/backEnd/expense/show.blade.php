@extends('backEnd.layouts.master')
@section('title','Expense Details - ' . $expense->expense_number)
@section('content')
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.expense.index') }}" class="btn btn-sm btn-secondary">
                        <i class="mdi mdi-arrow-left"></i> Back to Expenses
                    </a>
                    @if($expense->status === 'pending')
                        @can('expense-edit')
                            <a href="{{ route('admin.expense.edit', $expense) }}" class="btn btn-sm btn-primary">
                                <i class="mdi mdi-pencil"></i> Edit
                            </a>
                        @endcan
                    @endif
                </div>
                <h4 class="page-title">Expense Details - {{ $expense->expense_number }}</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Expense Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Expense Number</label>
                                <p class="form-control-plaintext"><strong>{{ $expense->expense_number }}</strong></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <p class="form-control-plaintext">
                                    <span class="badge bg-{{ $expense->status_badge }}">{{ ucfirst($expense->status) }}</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Expense Date</label>
                                <p class="form-control-plaintext">{{ $expense->expense_date->format('d M Y') }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <p class="form-control-plaintext">
                                    <span class="badge bg-info">{{ optional($expense->category)->name ?? 'N/A' }}</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Total Amount</label>
                                <p class="form-control-plaintext">
                                    <strong class="text-primary">BDT {{ number_format($expense->total_amount, 2) }}</strong>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <p class="form-control-plaintext">{{ ucfirst(str_replace('_', ' ', $expense->payment_method)) }}</p>
                            </div>
                        </div>
                    </div>

                    @if($expense->bank_name)
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Bank Name</label>
                                <p class="form-control-plaintext">{{ $expense->bank_name }}</p>
                            </div>
                        </div>
                        @if($expense->cheque_number)
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Cheque Number</label>
                                <p class="form-control-plaintext">{{ $expense->cheque_number }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <p class="form-control-plaintext">{{ $expense->description }}</p>
                    </div>

                    @if($expense->notes)
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <p class="form-control-plaintext">{{ $expense->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Warehouse Allocations -->
            @if($expense->allocations->count() > 0)
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Warehouse Allocations</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Warehouse</th>
                                    <th class="text-end">Allocated Amount</th>
                                    <th class="text-end">Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($expense->allocations as $allocation)
                                <tr>
                                    <td>{{ optional($allocation->warehouse)->name ?? 'N/A' }}</td>
                                    <td class="text-end">BDT {{ number_format($allocation->allocated_amount, 2) }}</td>
                                    <td class="text-end">{{ number_format($allocation->percentage, 2) }}%</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="table-dark">
                                    <th>Total</th>
                                    <th class="text-end">BDT {{ number_format($expense->allocated_total, 2) }}</th>
                                    <th class="text-end">100.00%</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            <!-- Expense Timeline -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Timeline</h6>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">Created</h6>
                                <small class="text-muted">{{ $expense->created_at->format('d M Y H:i') }}</small>
                                <p class="mb-0">by {{ optional($expense->creator)->name ?? 'System' }}</p>
                            </div>
                        </div>

                        @if($expense->approved_at)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">Approved</h6>
                                <small class="text-muted">{{ $expense->approved_at->format('d M Y H:i') }}</small>
                                <p class="mb-0">by {{ optional($expense->approver)->name ?? 'N/A' }}</p>
                            </div>
                        </div>
                        @endif

                        @if($expense->paid_at)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-info"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">Paid</h6>
                                <small class="text-muted">{{ $expense->paid_at->format('d M Y H:i') }}</small>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Actions</h6>
                </div>
                <div class="card-body">
                    @if($expense->status === 'pending')
                        @can('expense-approve')
                            <button type="button" class="btn btn-success btn-sm mb-2" onclick="approveExpense({{ $expense->id }})">
                                <i class="mdi mdi-check"></i> Approve
                            </button>
                        @endcan
                        @can('expense-reject')
                            <button type="button" class="btn btn-danger btn-sm mb-2" onclick="rejectExpense({{ $expense->id }})">
                                <i class="mdi mdi-close"></i> Reject
                            </button>
                        @endcan
                    @elseif($expense->status === 'approved')
                        @can('expense-mark-paid')
                            <button type="button" class="btn btn-info btn-sm mb-2" onclick="markAsPaid({{ $expense->id }})">
                                <i class="mdi mdi-cash"></i> Mark as Paid
                            </button>
                        @endcan
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
                <h5 class="modal-title">Approve Expense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approvalForm">
                @csrf
                <div class="modal-body">
                    <p>Are you sure you want to approve this expense?</p>
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
                <h5 class="modal-title">Reject Expense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectionForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" placeholder="Provide rejection reason" required minlength="5"></textarea>
                    </div>
                    <input type="hidden" id="reject_expense_id" name="expense_id">
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

function approveExpense(expenseId) {
    const formData = new FormData();
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
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
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing the request.');
    });
}

function rejectExpense(expenseId) {
    document.getElementById('reject_expense_id').value = expenseId;
    new bootstrap.Modal(document.getElementById('rejectionModal')).show();
}

function markAsPaid(expenseId) {
    if (confirm('Are you sure you want to mark this expense as paid?')) {
        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

        fetch(expenseMarkPaidRouteTemplate.replace('__EXPENSE__', expenseId), {
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
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while processing the request.');
        });
    }
}

// Handle rejection form
document.getElementById('rejectionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const expenseId = formData.get('expense_id');

    fetch(expenseRejectRouteTemplate.replace('__EXPENSE__', expenseId), {
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
            bootstrap.Modal.getInstance(document.getElementById('rejectionModal')).hide();
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

