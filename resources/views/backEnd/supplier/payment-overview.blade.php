@extends('backEnd.layouts.master')
@section('title', 'Bill Payment')

@section('css')
<style>
    .procure-modal .modal-dialog{max-width:640px}.procure-modal .modal-content{border:0;border-radius:10px;overflow:visible}.procure-modal .modal-header{border-bottom:2px solid #f15a24;padding:.9rem 1.2rem}.procure-modal .modal-footer{background:#edf3fb;border-top:1px solid #d7e2ef}.procure-modal{background:rgba(15,23,42,.38)}.procure-box{border:1px solid #dfe6ef;background:#fff}.procure-box .card-body{padding:1rem}
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.accounts.payment-head-mappings.index') }}" class="btn btn-outline-primary me-2">
                        <i class="mdi mdi-tune-variant"></i> Payment Head Settings
                    </a>
                    <button type="button" class="btn btn-danger" id="btnOpenPaymentModal"><i class="mdi mdi-plus"></i> Record Payment</button>
                </div>
                <h4 class="page-title">Bill Payment</h4>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-3"><div class="procure-box"><div class="card-body py-2"><small>Total Rows</small><h5 class="mb-0">{{ number_format($summary['total_rows'] ?? 0) }}</h5></div></div></div>
        <div class="col-md-3"><div class="procure-box"><div class="card-body py-2"><small>Gross Amount</small><h5 class="mb-0">BDT {{ number_format($summary['gross_amount'] ?? 0, 2) }}</h5></div></div></div>
        <div class="col-md-3"><div class="procure-box"><div class="card-body py-2"><small>Completed</small><h5 class="mb-0">BDT {{ number_format($summary['completed_amount'] ?? 0, 2) }}</h5></div></div></div>
        <div class="col-md-3"><div class="procure-box"><div class="card-body py-2"><small>Pending</small><h5 class="mb-0">BDT {{ number_format($summary['pending_amount'] ?? 0, 2) }}</h5></div></div></div>
    </div>

    <div class="card procure-box">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end mb-3">
                <div class="col-md-3">
                    <label class="form-label">Supplier</label>
                    <select name="supplier_id" class="form-select">
                        <option value="">All</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ (string) request('supplier_id') === (string) $supplier->id ? 'selected' : '' }}>{{ $supplier->supplier_code }} - {{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Branch</label>
                    <select name="branch_id" class="form-select">
                        <option value="">All</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ (string) request('branch_id') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->code }} - {{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Method</label>
                    <select name="payment_method" class="form-select">
                        <option value="">All</option>
                        <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="bank_transfer" {{ request('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                        <option value="cheque" {{ request('payment_method') === 'cheque' ? 'selected' : '' }}>Cheque</option>
                        <option value="card" {{ request('payment_method') === 'card' ? 'selected' : '' }}>Card</option>
                        <option value="online" {{ request('payment_method') === 'online' ? 'selected' : '' }}>Online</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Accounts Head</label>
                    <select name="account_head_id" class="form-select">
                        <option value="">All</option>
                        @foreach(($accountHeads ?? collect()) as $head)
                            <option value="{{ $head->HeadId }}" {{ (string) request('account_head_id') === (string) $head->HeadId ? 'selected' : '' }}>
                                {{ $head->HeadCode }} - {{ $head->HeadName }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control">
                </div>
                <div class="col-md-1 text-md-end">
                    <button class="btn btn-primary" type="submit">Go</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Payment Date</th>
                            <th>Payment No</th>
                            <th>Supplier Name</th>
                            <th>Payment Through</th>
                            <th>Cheque No</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td>{{ optional($payment->payment_date)->format('d/m/Y') }}</td>
                                <td>{{ $payment->payment_number }}</td>
                                <td>{{ $payment->supplier->name ?? 'N/A' }}</td>
                                <td>{{ $payment->accountHead?->HeadCode ? ($payment->accountHead->HeadCode . ' - ' . $payment->accountHead->HeadName) : ($payment->branch->code ?? $payment->payment_method_label) }}</td>
                                <td>{{ $payment->reference_number ?: '-' }}</td>
                                <td class="text-end">{{ number_format((float) $payment->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">No payments found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($payments->hasPages())
                <div class="mt-3 d-flex justify-content-center">{{ $payments->links() }}</div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade procure-modal" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Bill Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="paymentForm">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Supplier</label>
                            <select id="paymentSupplierId" class="form-select">
                                <option value="">Select Supplier</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->supplier_code }} - {{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Date</label>
                            <input type="date" id="paymentDate" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Through</label>
                            <select id="paymentBranchId" class="form-select">
                                <option value="">Select</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->code }} - {{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Accounts Head</label>
                            <select id="paymentAccountHeadId" class="form-select">
                                <option value="">Select Accounts Head</option>
                                @foreach(($accountHeads ?? collect()) as $head)
                                    <option value="{{ $head->HeadId }}">{{ $head->HeadCode }} - {{ $head->HeadName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label d-block">Payment Type</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="payment_method_ui" id="payCash" value="cash" checked>
                                <label class="form-check-label" for="payCash">Cash</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="payment_method_ui" id="payCheque" value="cheque">
                                <label class="form-check-label" for="payCheque">Cheque</label>
                            </div>
                        </div>
                        <div class="col-md-6 payment-cheque-only d-none">
                            <label class="form-label">Bank Name</label>
                            <input type="text" id="paymentBankName" class="form-control">
                        </div>
                        <div class="col-md-6 payment-cheque-only d-none">
                            <label class="form-label">Cheque No</label>
                            <input type="text" id="paymentReference" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Payment Amount</label>
                            <input type="number" id="paymentAmount" class="form-control" min="0.01" step="0.01" placeholder="Payment Amount">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Remarks</label>
                            <textarea id="paymentNotes" rows="2" class="form-control" placeholder="Remarks"></textarea>
                        </div>
                    </div>
                    <div id="paymentMessage" class="mt-3 d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-info text-white" id="btnSavePayment">Save</button>
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const paymentHeadMapsByBranch = @json($paymentHeadMapsByBranch ?? ['global' => []]);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value;
    const modalElement = document.getElementById('paymentModal');
    const chequeFields = document.querySelectorAll('.payment-cheque-only');
    let modalOpen = false;

    const el = {
        form: document.getElementById('paymentForm'),
        supplierId: document.getElementById('paymentSupplierId'),
        date: document.getElementById('paymentDate'),
        branchId: document.getElementById('paymentBranchId'),
        accountHeadId: document.getElementById('paymentAccountHeadId'),
        bankName: document.getElementById('paymentBankName'),
        reference: document.getElementById('paymentReference'),
        amount: document.getElementById('paymentAmount'),
        notes: document.getElementById('paymentNotes'),
        save: document.getElementById('btnSavePayment'),
        message: document.getElementById('paymentMessage')
    };

    function currentMethod() {
        return document.querySelector('input[name="payment_method_ui"]:checked')?.value || 'cash';
    }
    function showModal() {
        modalOpen = true;
        modalElement.style.display = 'block';
        modalElement.classList.add('show');
        document.body.classList.add('modal-open');
        document.body.style.overflow = 'hidden';
    }
    function hideModal() {
        modalOpen = false;
        modalElement.classList.remove('show');
        modalElement.style.display = 'none';
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
    }
    function toggleChequeFields() {
        const visible = currentMethod() === 'cheque';
        chequeFields.forEach(function (field) {
            field.classList.toggle('d-none', !visible);
        });
        const method = currentMethod();
        
        const currentBranch = el.branchId.value || 'global';
        const activeMap = paymentHeadMapsByBranch[currentBranch] || paymentHeadMapsByBranch['global'] || {};
        const mappingDetail = activeMap[method];
        
        if (el.accountHeadId && method && mappingDetail) {
            el.accountHeadId.value = String(mappingDetail.head_id);
            if (mappingDetail.is_locked) {
                el.accountHeadId.classList.add('bg-light');
                el.accountHeadId.style.pointerEvents = 'none';
                el.accountHeadId.tabIndex = -1;
            } else {
                el.accountHeadId.classList.remove('bg-light');
                el.accountHeadId.style.pointerEvents = 'auto';
                el.accountHeadId.tabIndex = 0;
            }
        } else if (el.accountHeadId) {
            el.accountHeadId.classList.remove('bg-light');
            el.accountHeadId.style.pointerEvents = 'auto';
            el.accountHeadId.tabIndex = 0;
        }
    }
    function resetForm() {
        el.supplierId.value = '';
        el.date.value = new Date().toISOString().slice(0, 10);
        el.branchId.value = '';
        el.accountHeadId.value = '';
        el.bankName.value = '';
        el.reference.value = '';
        el.amount.value = '';
        el.notes.value = '';
        document.getElementById('payCash').checked = true;
        toggleChequeFields();
        el.message.className = 'mt-3 d-none';
        el.message.textContent = '';
    }
    function setMessage(message, type) {
        if (!message) {
            el.message.className = 'mt-3 d-none';
            el.message.textContent = '';
            return;
        }
        el.message.className = `alert alert-${type} mt-3`;
        el.message.textContent = message;
        el.message.classList.remove('d-none');
    }

    document.getElementById('btnOpenPaymentModal').addEventListener('click', function () {
        resetForm();
        showModal();
    });
    document.querySelectorAll('input[name="payment_method_ui"]').forEach(function (input) {
        input.addEventListener('change', toggleChequeFields);
    });
    if (el.branchId) {
        el.branchId.addEventListener('change', toggleChequeFields);
    }
    modalElement.querySelectorAll('[data-bs-dismiss="modal"]').forEach(function (button) {
        button.addEventListener('click', hideModal);
    });
    modalElement.addEventListener('click', function (event) {
        if (event.target === modalElement) hideModal();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modalOpen) hideModal();
    });

    el.form.addEventListener('submit', function (event) {
        event.preventDefault();
        setMessage('', 'success');
        el.save.disabled = true;

        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('supplier_id', el.supplierId.value);
        formData.append('payment_date', el.date.value);
        formData.append('branch_id', el.branchId.value);
        
        let headId = el.accountHeadId.value;
        const method = currentMethod();
        const currentBranch = el.branchId.value || 'global';
        const activeMap = paymentHeadMapsByBranch[currentBranch] || paymentHeadMapsByBranch['global'] || {};
        const mappingDetail = activeMap[method];
        if (mappingDetail && mappingDetail.is_locked) {
            headId = mappingDetail.head_id;
        }
        formData.append('account_head_id', headId);
        formData.append('payment_method', currentMethod());
        formData.append('bank_name', el.bankName.value);
        formData.append('reference_number', el.reference.value);
        formData.append('amount', el.amount.value);
        formData.append('notes', el.notes.value);
        formData.append('status', 'completed');

        fetch("{{ route('admin.supplier.payments.record') }}", {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: formData
        }).then(async function (response) {
            const payload = await response.json().catch(function () { return {}; });
            if (!response.ok) throw payload;
            return payload;
        }).then(function (payload) {
            setMessage(payload.message || 'Payment saved successfully.', 'success');
            setTimeout(function () { window.location.reload(); }, 700);
        }).catch(function (error) {
            let message = 'Unable to save payment.';
            if (error && error.message) message = error.message;
            if (error && error.errors) {
                const key = Object.keys(error.errors)[0];
                if (key && Array.isArray(error.errors[key]) && error.errors[key][0]) message = error.errors[key][0];
            }
            setMessage(message, 'danger');
        }).finally(function () {
            el.save.disabled = false;
        });
    });
});
</script>
@endsection
