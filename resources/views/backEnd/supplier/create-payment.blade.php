@extends('backEnd.layouts.master')
@section('title','Create Payment - ' . $supplier->name)
@section('content')
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.accounts.payment-head-mappings.index') }}" class="btn btn-sm btn-outline-primary me-2">
                        <i class="mdi mdi-tune-variant"></i> Payment Head Settings
                    </a>
                    <a href="{{ route('admin.supplier.payments', $supplier->id) }}" class="btn btn-sm btn-secondary">
                        <i class="mdi mdi-arrow-left"></i> Back to Payments
                    </a>
                </div>
                <h4 class="page-title">Create Payment - {{ $supplier->name }}</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Payment Details</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.supplier.payments.store', $supplier->id) }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="account_head_id" class="form-label">Accounts Head <span class="text-danger">*</span></label>
                                    <select class="form-control @error('account_head_id') is-invalid @enderror" id="account_head_id" name="account_head_id" required>
                                        <option value="">Select Accounts Head</option>
                                        @foreach(($accountHeads ?? collect()) as $head)
                                            <option value="{{ $head->HeadId }}" {{ (string) old('account_head_id') === (string) $head->HeadId ? 'selected' : '' }}>
                                                {{ $head->HeadCode }} - {{ $head->HeadName }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('account_head_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="branch_id" class="form-label">Branch <span class="text-danger">*</span></label>
                                    <select class="form-control @error('branch_id') is-invalid @enderror" id="branch_id" name="branch_id" required>
                                        <option value="">Select Branch</option>
                                        @foreach(($branches ?? collect()) as $branch)
                                            @php
                                                $branchDue = (float) (($branchDueMap ?? collect())->get((string) $branch->id, 0) ?? 0);
                                                $selectedBranchValue = (int) old('branch_id', $selectedBranchId ?? 0);
                                            @endphp
                                            <option
                                                value="{{ $branch->id }}"
                                                data-due="{{ number_format($branchDue, 2, '.', '') }}"
                                                {{ $selectedBranchValue === (int) $branch->id ? 'selected' : '' }}
                                            >
                                                {{ $branch->code }} - {{ $branch->name }}{{ (isset($branch->status) && !$branch->status) ? ' (Inactive)' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('branch_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small id="branch_due_hint" class="form-text text-muted d-block"></small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="payment_date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('payment_date') is-invalid @enderror"
                                           id="payment_date" name="payment_date" value="{{ old('payment_date', date('Y-m-d')) }}" required>
                                    @error('payment_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="amount" class="form-label">Amount (BDT) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0.01" class="form-control @error('amount') is-invalid @enderror"
                                           id="amount" name="amount"
                                           value="{{ old('amount', ($prefillAmount ?? 0) > 0 ? number_format((float) $prefillAmount, 2, '.', '') : '') }}"
                                           placeholder="0.00" required>
                                    @error('amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                                    <select class="form-control @error('payment_method') is-invalid @enderror"
                                            id="payment_method" name="payment_method" required>
                                        <option value="">Select Payment Method</option>
                                        <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                                        <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                        <option value="cheque" {{ old('payment_method') == 'cheque' ? 'selected' : '' }}>Cheque</option>
                                        <option value="card" {{ old('payment_method') == 'card' ? 'selected' : '' }}>Card</option>
                                        <option value="online" {{ old('payment_method') == 'online' ? 'selected' : '' }}>Online Payment</option>
                                    </select>
                                    @error('payment_method')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3" id="bank_name_field" style="display: none;">
                                    <label for="bank_name" class="form-label">Bank Name</label>
                                    <input type="text" class="form-control @error('bank_name') is-invalid @enderror"
                                           id="bank_name" name="bank_name" value="{{ old('bank_name') }}" placeholder="Enter bank name">
                                    @error('bank_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3" id="bank_account_number_field" style="display: none;">
                                    <label for="bank_account_number" class="form-label">Bank Account Number</label>
                                    <input type="text" class="form-control @error('bank_account_number') is-invalid @enderror"
                                           id="bank_account_number" name="bank_account_number" value="{{ old('bank_account_number') }}" placeholder="Enter bank account number">
                                    @error('bank_account_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3" id="reference_number_field" style="display: none;">
                                    <label for="reference_number" class="form-label" id="reference_number_label">Reference Number</label>
                                    <input type="text" class="form-control @error('reference_number') is-invalid @enderror"
                                           id="reference_number" name="reference_number" value="{{ old('reference_number') }}" placeholder="Enter cheque number">
                                    @error('reference_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes"
                                      rows="3" placeholder="Optional notes about this payment">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('admin.supplier.payments', $supplier->id) }}" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary" id="save_payment_btn">
                                <i class="mdi mdi-content-save"></i> Save Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Supplier Info -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Supplier Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>Name:</strong> {{ $supplier->name }}
                    </div>
                    <div class="mb-2">
                        <strong>Phone:</strong> {{ $supplier->phone }}
                    </div>
                    <div class="mb-2">
                        <strong>Email:</strong> {{ $supplier->email ?: 'N/A' }}
                    </div>
                    <div class="mb-2">
                        <strong>Outstanding Due:</strong>
                        <span class="badge bg-{{ $supplier->current_balance >= 0 ? 'danger' : 'success' }}">
                            BDT {{ number_format(max(0, (float) $supplier->current_balance), 2) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const paymentHeadMapsByBranch = @json($paymentHeadMapsByBranch ?? ['global' => []]);
    const paymentMethod = document.getElementById('payment_method');
    const accountHeadSelect = document.getElementById('account_head_id');
    const bankField = document.getElementById('bank_name_field');
    const bankAccountField = document.getElementById('bank_account_number_field');
    const referenceField = document.getElementById('reference_number_field');
    const referenceLabel = document.getElementById('reference_number_label');
    const branchSelect = document.getElementById('branch_id');
    const amountInput = document.getElementById('amount');
    const branchDueHint = document.getElementById('branch_due_hint');
    const savePaymentBtn = document.getElementById('save_payment_btn');

    const formatCurrency = (value) => {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value);
    };

    const updatePaymentMethodFields = () => {
        const method = paymentMethod.value;
        const currentBranch = branchSelect && branchSelect.value ? branchSelect.value : 'global';
        const activeMap = paymentHeadMapsByBranch[currentBranch] || paymentHeadMapsByBranch['global'] || {};
        const mappingDetail = activeMap[method];

        if (accountHeadSelect && method && mappingDetail) {
            accountHeadSelect.value = String(mappingDetail.head_id);
            if (mappingDetail.is_locked) {
                accountHeadSelect.classList.add('bg-light');
                accountHeadSelect.style.pointerEvents = 'none';
                accountHeadSelect.tabIndex = -1;
                
                // Add a hidden input to preserve the value since disabled select won't submit
                let hiddenInput = document.getElementById('hidden_locked_account_head_id');
                if (!hiddenInput) {
                    hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.id = 'hidden_locked_account_head_id';
                    hiddenInput.name = 'account_head_id';
                    accountHeadSelect.parentNode.appendChild(hiddenInput);
                }
                hiddenInput.value = mappingDetail.head_id;
                accountHeadSelect.name = ''; // Remove name from original so it doesn't submit
            } else {
                accountHeadSelect.classList.remove('bg-light');
                accountHeadSelect.style.pointerEvents = 'auto';
                accountHeadSelect.tabIndex = 0;
                accountHeadSelect.name = 'account_head_id'; // Restore name
                const hiddenInput = document.getElementById('hidden_locked_account_head_id');
                if (hiddenInput) {
                    hiddenInput.remove();
                }
            }
        } else if (accountHeadSelect) {
            accountHeadSelect.classList.remove('bg-light');
            accountHeadSelect.style.pointerEvents = 'auto';
            accountHeadSelect.tabIndex = 0;
            accountHeadSelect.name = 'account_head_id';
            const hiddenInput = document.getElementById('hidden_locked_account_head_id');
            if (hiddenInput) {
                hiddenInput.remove();
            }
        }
        
        if (method === 'bank_transfer') {
            bankField.style.display = 'block';
            bankAccountField.style.display = 'block';
            referenceField.style.display = 'block';
            referenceLabel.textContent = 'Reference Number';
        } else if (method === 'cheque') {
            bankField.style.display = 'block';
            bankAccountField.style.display = 'block';
            referenceField.style.display = 'block';
            referenceLabel.textContent = 'Cheque Number';
        } else if (method === 'online' || method === 'card') {
            bankField.style.display = 'none';
            bankAccountField.style.display = 'none';
            referenceField.style.display = 'block';
            referenceLabel.textContent = 'Transaction Reference';
        } else {
            bankField.style.display = 'none';
            bankAccountField.style.display = 'none';
            referenceField.style.display = 'none';
        }
    };

    const selectedBranchDue = () => {
        if (!branchSelect) {
            return 0;
        }

        const option = branchSelect.options[branchSelect.selectedIndex];
        const dueValue = option ? parseFloat(option.getAttribute('data-due') || '0') : 0;
        return Number.isFinite(dueValue) ? Math.max(0, dueValue) : 0;
    };

    const validateAmountAgainstBranchDue = () => {
        const due = selectedBranchDue();
        const amount = parseFloat(amountInput.value || '0');

        if (due <= 0.01) {
            amountInput.setCustomValidity('No outstanding due is available in the selected branch.');
            return;
        }

        if (Number.isFinite(amount) && amount > (due + 0.01)) {
            amountInput.setCustomValidity(`Maximum payable amount for this branch is BDT ${formatCurrency(due)}.`);
        } else {
            amountInput.setCustomValidity('');
        }
    };

    const updateBranchDueHint = () => {
        const due = selectedBranchDue();

        if (due > 0) {
            branchDueHint.textContent = `Outstanding due in selected branch: BDT ${formatCurrency(due)}.`;
            branchDueHint.classList.remove('text-danger');
            branchDueHint.classList.add('text-muted');
            savePaymentBtn.disabled = false;

            const currentAmount = parseFloat(amountInput.value || '0');
            if (!Number.isFinite(currentAmount) || currentAmount <= 0 || currentAmount > (due + 0.01)) {
                amountInput.value = due.toFixed(2);
            }

            amountInput.setAttribute('max', due.toFixed(2));
        } else {
            branchDueHint.textContent = 'No outstanding due in selected branch.';
            branchDueHint.classList.remove('text-muted');
            branchDueHint.classList.add('text-danger');
            amountInput.removeAttribute('max');
            amountInput.value = '';
            savePaymentBtn.disabled = true;
        }

        validateAmountAgainstBranchDue();
    };

    paymentMethod.addEventListener('change', updatePaymentMethodFields);
    amountInput.addEventListener('input', validateAmountAgainstBranchDue);
    if (branchSelect) {
        branchSelect.addEventListener('change', () => {
            updateBranchDueHint();
            updatePaymentMethodFields();
        });
    }

    updatePaymentMethodFields();
    updateBranchDueHint();
})();
</script>
@endsection


