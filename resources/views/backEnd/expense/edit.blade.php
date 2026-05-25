@extends('backEnd.layouts.master')
@section('title','Edit Expense - ' . $expense->expense_number)
@section('content')
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.expense.show', $expense) }}" class="btn btn-sm btn-secondary">
                        <i class="mdi mdi-arrow-left"></i> Back to Expense
                    </a>
                </div>
                <h4 class="page-title">Edit Expense - {{ $expense->expense_number }}</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Expense Details</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.expense.update', $expense) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="expense_date" class="form-label">Expense Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('expense_date') is-invalid @enderror"
                                           id="expense_date" name="expense_date" value="{{ old('expense_date', $expense->expense_date->format('Y-m-d')) }}" required>
                                    @error('expense_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                    <select class="form-control @error('category_id') is-invalid @enderror"
                                            id="category_id" name="category_id" required>
                                        <option value="">Select Category</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ old('category_id', $expense->category_id) == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        @if($suppliers->isNotEmpty() || $purchaseOrders->isNotEmpty() || $grns->isNotEmpty())
                            <div class="row">
                                @if($suppliers->isNotEmpty())
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="supplier_id" class="form-label">Supplier</label>
                                            <select class="form-control @error('supplier_id') is-invalid @enderror" id="supplier_id" name="supplier_id">
                                                <option value="">Optional</option>
                                                @foreach($suppliers as $supplier)
                                                    <option value="{{ $supplier->id }}" {{ (string) old('supplier_id', $expense->supplier_id) === (string) $supplier->id ? 'selected' : '' }}>
                                                        {{ $supplier->name }}{{ $supplier->supplier_code ? ' (' . $supplier->supplier_code . ')' : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('supplier_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                @endif

                                @if($purchaseOrders->isNotEmpty())
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="purchase_order_id" class="form-label">Purchase Order</label>
                                            <select class="form-control @error('purchase_order_id') is-invalid @enderror" id="purchase_order_id" name="purchase_order_id">
                                                <option value="">Optional</option>
                                                @foreach($purchaseOrders as $purchaseOrder)
                                                    <option
                                                        value="{{ $purchaseOrder->id }}"
                                                        data-supplier-id="{{ (int) ($purchaseOrder->supplier_id ?? 0) }}"
                                                        {{ (string) old('purchase_order_id', $expense->purchase_order_id) === (string) $purchaseOrder->id ? 'selected' : '' }}
                                                    >
                                                        {{ $purchaseOrder->po_number }}{{ $purchaseOrder->status ? ' (' . str_replace('_', ' ', ucfirst($purchaseOrder->status)) . ')' : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('purchase_order_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                @endif

                                @if($grns->isNotEmpty())
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="grn_id" class="form-label">GRN</label>
                                            <select class="form-control @error('grn_id') is-invalid @enderror" id="grn_id" name="grn_id">
                                                <option value="">Optional</option>
                                                @foreach($grns as $grn)
                                                    <option
                                                        value="{{ $grn->id }}"
                                                        data-supplier-id="{{ (int) ($grn->supplier_id ?? 0) }}"
                                                        {{ (string) old('grn_id', $expense->grn_id) === (string) $grn->id ? 'selected' : '' }}
                                                    >
                                                        {{ $grn->grn_number }}{{ $grn->status ? ' (' . ucfirst($grn->status) . ')' : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('grn_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="total_amount" class="form-label">Amount (BDT) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0.01" class="form-control @error('total_amount') is-invalid @enderror"
                                           id="total_amount" name="total_amount" value="{{ old('total_amount', $expense->total_amount) }}" placeholder="0.00" required>
                                    @error('total_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                                    <select class="form-control @error('payment_method') is-invalid @enderror"
                                            id="payment_method" name="payment_method" required>
                                        <option value="">Select Payment Method</option>
                                        <option value="cash" {{ old('payment_method', $expense->payment_method) == 'cash' ? 'selected' : '' }}>Cash</option>
                                        <option value="bank_transfer" {{ old('payment_method', $expense->payment_method) == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                        <option value="cheque" {{ old('payment_method', $expense->payment_method) == 'cheque' ? 'selected' : '' }}>Cheque</option>
                                        <option value="card" {{ old('payment_method', $expense->payment_method) == 'card' ? 'selected' : '' }}>Card</option>
                                        <option value="other" {{ old('payment_method', $expense->payment_method) == 'other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                    @error('payment_method')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Payment method specific fields -->
                        <div class="row" id="bankFields" style="display: none;">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="bank_name" class="form-label">Bank Name</label>
                                    <input type="text" class="form-control @error('bank_name') is-invalid @enderror"
                                           id="bank_name" name="bank_name" value="{{ old('bank_name', $expense->bank_name) }}" placeholder="Enter bank name">
                                    @error('bank_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6" id="chequeField" style="display: none;">
                                <div class="mb-3">
                                    <label for="cheque_number" class="form-label">Cheque Number</label>
                                    <input type="text" class="form-control @error('cheque_number') is-invalid @enderror"
                                           id="cheque_number" name="cheque_number" value="{{ old('cheque_number', $expense->cheque_number) }}" placeholder="Enter cheque number">
                                    @error('cheque_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row" id="cardField" style="display: none;">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="card_number" class="form-label">Card Number (Last 4 digits)</label>
                                    <input type="text" class="form-control @error('card_number') is-invalid @enderror"
                                           id="card_number" name="card_number" value="{{ old('card_number', $expense->card_number) }}" placeholder="1234" maxlength="4" pattern="[0-9]{4}" inputmode="numeric">
                                    @error('card_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                      rows="3" placeholder="Describe the expense" required>{{ old('description', $expense->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Additional Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes"
                                      rows="2" placeholder="Optional additional notes">{{ old('notes', $expense->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @can('expense-allocate')
                            <!-- Warehouse Allocation Section -->
                            @php
                                $oldAllocations = old('warehouse_allocations', []);
                                $hasOldAllocations = is_array($oldAllocations) && count($oldAllocations) > 0;
                            @endphp
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="enable_allocation" name="enable_allocation"
                                           {{ old('enable_allocation') || $hasOldAllocations || $expense->allocations->count() > 0 ? 'checked' : '' }}>
                                    <label class="form-check-label" for="enable_allocation">
                                        Allocate expense to specific warehouses
                                    </label>
                                </div>
                            </div>

                            <div id="allocationSection" style="display: none;">
                                <h6>Warehouse Allocation</h6>
                                <div id="allocations">
                                    @if($hasOldAllocations)
                                        @foreach($oldAllocations as $index => $allocation)
                                            <div class="row mb-2 allocation-row">
                                                <div class="col-md-6">
                                                    <select class="form-control warehouse-select" name="warehouse_allocations[{{ $index }}][warehouse_id]" required>
                                                        <option value="">Select Warehouse</option>
                                                        @foreach($warehouses as $warehouse)
                                                            <option value="{{ $warehouse->id }}" {{ (string) ($allocation['warehouse_id'] ?? '') === (string) $warehouse->id ? 'selected' : '' }}>
                                                                {{ $warehouse->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <input type="number" step="0.01" min="0.01" class="form-control amount-input"
                                                           name="warehouse_allocations[{{ $index }}][amount]"
                                                           placeholder="Amount" value="{{ $allocation['amount'] ?? '' }}" required>
                                                </div>
                                                <div class="col-md-2">
                                                    <button type="button" class="btn btn-sm btn-outline-danger remove-allocation">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    @elseif($expense->allocations->count() > 0)
                                        @foreach($expense->allocations as $index => $allocation)
                                            <div class="row mb-2 allocation-row">
                                                <div class="col-md-6">
                                                    <select class="form-control warehouse-select" name="warehouse_allocations[{{ $index + 1 }}][warehouse_id]" required>
                                                        <option value="">Select Warehouse</option>
                                                        @foreach($warehouses as $warehouse)
                                                            <option value="{{ $warehouse->id }}" {{ $allocation->warehouse_id == $warehouse->id ? 'selected' : '' }}>
                                                                {{ $warehouse->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <input type="number" step="0.01" min="0.01" class="form-control amount-input"
                                                           name="warehouse_allocations[{{ $index + 1 }}][amount]"
                                                           placeholder="Amount" value="{{ $allocation->allocated_amount }}" required>
                                                </div>
                                                <div class="col-md-2">
                                                    <button type="button" class="btn btn-sm btn-outline-danger remove-allocation">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="addAllocation">
                                    <i class="mdi mdi-plus"></i> Add Warehouse
                                </button>
                                @error('warehouse_allocations')
                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                        @endcan

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('admin.expense.show', $expense) }}" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-content-save"></i> Update Expense
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Expense Status -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Expense Status</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>Status:</strong>
                        <span class="badge bg-{{ $expense->status_badge }}">{{ ucfirst($expense->status) }}</span>
                    </div>
                    <div class="mb-2">
                        <strong>Created:</strong> {{ $expense->created_at->format('d M Y H:i') }}
                    </div>
                    <div class="mb-2">
                        <strong>Created By:</strong> {{ optional($expense->creator)->name ?? 'System' }}
                    </div>
                    @if($expense->approved_at)
                        <div class="mb-2">
                            <strong>Approved:</strong> {{ $expense->approved_at->format('d M Y H:i') }}
                        </div>
                        <div class="mb-2">
                            <strong>Approved By:</strong> {{ optional($expense->approver)->name ?? 'N/A' }}
                        </div>
                    @endif
                    @if($expense->paid_at)
                        <div class="mb-2">
                            <strong>Paid:</strong> {{ $expense->paid_at->format('d M Y H:i') }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Current Allocations -->
            @if($expense->allocations->count() > 0)
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Current Allocations</h6>
                </div>
                <div class="card-body">
                    @foreach($expense->allocations as $allocation)
                        <div class="mb-2">
                            <strong>{{ optional($allocation->warehouse)->name ?? 'N/A' }}</strong><br>
                            <span class="badge bg-primary">BDT {{ number_format($allocation->allocated_amount, 2) }}</span>
                            <small class="text-muted">({{ number_format($allocation->percentage, 2) }}%)</small>
                        </div>
                        <hr>
                    @endforeach
                    <div class="mt-2">
                        <strong>Total Allocated:</strong> BDT {{ number_format($expense->allocated_total, 2) }}
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
document.getElementById('payment_method').addEventListener('change', function() {
    const method = this.value;
    const bankFields = document.getElementById('bankFields');
    const cardField = document.getElementById('cardField');
    const chequeField = document.getElementById('chequeField');

    // Hide all
    bankFields.style.display = 'none';
    cardField.style.display = 'none';
    chequeField.style.display = 'none';

    // Show relevant fields
    if (method === 'bank_transfer') {
        bankFields.style.display = 'block';
        chequeField.style.display = 'none';
    } else if (method === 'cheque') {
        bankFields.style.display = 'block';
        chequeField.style.display = 'block';
    } else if (method === 'card') {
        cardField.style.display = 'block';
    }
});

// Initialize payment method fields
document.getElementById('payment_method').dispatchEvent(new Event('change'));

// Warehouse allocation functionality
let allocationCount = (() => {
    const inputs = document.querySelectorAll('#allocations [name^="warehouse_allocations["]');
    let maxIndex = 0;

    inputs.forEach((input) => {
        const match = input.name.match(/warehouse_allocations\[(\d+)\]/);
        if (match) {
            maxIndex = Math.max(maxIndex, parseInt(match[1], 10));
        }
    });

    return maxIndex;
})();
const enableAllocationToggle = document.getElementById('enable_allocation');
const addAllocationButton = document.getElementById('addAllocation');

if (enableAllocationToggle) {
    enableAllocationToggle.addEventListener('change', function() {
        toggleAllocationSection(this.checked);
    });
}

// Initialize allocation section if allocations exist
if (enableAllocationToggle && {{ $expense->allocations->count() }} > 0) {
    enableAllocationToggle.checked = true;
    toggleAllocationSection(enableAllocationToggle.checked);
}

if (addAllocationButton) {
    addAllocationButton.addEventListener('click', function() {
        addAllocationRow();
    });
}

const allocationsContainer = document.getElementById('allocations');
if (allocationsContainer) {
    allocationsContainer.addEventListener('click', function(event) {
        const removeButton = event.target.closest('.remove-allocation');
        if (!removeButton) {
            return;
        }

        const row = removeButton.closest('.allocation-row');
        if (row) {
            row.remove();
        }
    });
}

function addAllocationRow(warehouseId = '', amount = '') {
    allocationCount++;
    const allocationsDiv = document.getElementById('allocations');

    const row = document.createElement('div');
    row.className = 'row mb-2 allocation-row';
    row.innerHTML = `
        <div class="col-md-6">
            <select class="form-control warehouse-select" name="warehouse_allocations[${allocationCount}][warehouse_id]" required>
                <option value="">Select Warehouse</option>
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" ${warehouseId == {{ $warehouse->id }} ? 'selected' : ''}>
                        {{ $warehouse->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <input type="number" step="0.01" min="0.01" class="form-control amount-input"
                   name="warehouse_allocations[${allocationCount}][amount]"
                   placeholder="Amount" value="${amount}" required>
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-sm btn-outline-danger remove-allocation">
                <i class="mdi mdi-delete"></i>
            </button>
        </div>
    `;

    allocationsDiv.appendChild(row);
}

function toggleAllocationSection(enabled) {
    const section = document.getElementById('allocationSection');
    if (!section) {
        return;
    }

    section.style.display = enabled ? 'block' : 'none';

    section.querySelectorAll('select, input, textarea').forEach(function(input) {
        input.disabled = !enabled;
    });
}

if (enableAllocationToggle && {{ $expense->allocations->count() }} === 0) {
    toggleAllocationSection(false);
}

const supplierField = document.getElementById('supplier_id');
const purchaseOrderField = document.getElementById('purchase_order_id');
const grnField = document.getElementById('grn_id');

function syncSupplierFromReference(sourceField) {
    if (!supplierField || !sourceField) {
        return;
    }

    const selectedOption = sourceField.options[sourceField.selectedIndex];
    if (!selectedOption) {
        return;
    }

    const linkedSupplierId = selectedOption.getAttribute('data-supplier-id');
    if (linkedSupplierId && linkedSupplierId !== '0') {
        supplierField.value = linkedSupplierId;
    }
}

if (purchaseOrderField) {
    purchaseOrderField.addEventListener('change', function() {
        syncSupplierFromReference(purchaseOrderField);
    });
}

if (grnField) {
    grnField.addEventListener('change', function() {
        syncSupplierFromReference(grnField);
    });
}

if (supplierField && !supplierField.value) {
    if (purchaseOrderField && purchaseOrderField.value) {
        syncSupplierFromReference(purchaseOrderField);
    } else if (grnField && grnField.value) {
        syncSupplierFromReference(grnField);
    }
}
</script>
@endsection


