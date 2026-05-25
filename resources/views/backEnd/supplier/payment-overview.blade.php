@extends('backEnd.layouts.master')
@section('title', 'Bill Payment')

@section('css')
<style>
    .procure-modal .modal-dialog{max-width:480px}.procure-modal .modal-content{border:0;border-radius:6px;overflow:visible}.procure-modal .modal-header{border-bottom:3px solid #f15a24;padding:.85rem 1.25rem}.procure-modal .modal-footer{background:#fff;border-top:1px solid #e9ecef;padding:.75rem 1.25rem}.procure-modal{background:rgba(15,23,42,.38)}.procure-box{border:1px solid #dfe6ef;background:#fff}.procure-box .card-body{padding:1rem}.pay-modal-row{display:flex;align-items:center;margin-bottom:.85rem}.pay-modal-row label{width:140px;min-width:140px;font-size:.875rem;color:#444;margin:0}.pay-modal-row .pay-ctrl{flex:1}.pay-through-select{border-color:#f15a24!important;outline:none}.pay-through-select:focus{border-color:#f15a24!important;box-shadow:0 0 0 .15rem rgba(241,90,36,.2)}.btn-save-pay{background:#5b9bd5;color:#fff;border:0;min-width:80px}.btn-save-pay:hover,.btn-save-pay:focus{background:#4a8ac4;color:#fff}
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
                <div class="modal-body" style="padding:1.1rem 1.4rem">

                    <div class="pay-modal-row">
                        <label>Supplier</label>
                        <div class="pay-ctrl">
                            <select id="paymentSupplierId" class="form-select form-select-sm">
                                <option value="">Select Supplier</option>
                                @foreach($suppliers as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="pay-modal-row">
                        <label>Payment Date</label>
                        <div class="pay-ctrl">
                            <input type="date" id="paymentDate" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                        </div>
                    </div>

                    <div class="pay-modal-row">
                        <label>Payment Type</label>
                        <div class="pay-ctrl d-flex gap-3">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="radio" name="payment_method_ui" id="payCash" value="cash" checked>
                                <label class="form-check-label" for="payCash">Cash</label>
                            </div>
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="radio" name="payment_method_ui" id="payCheque" value="cheque">
                                <label class="form-check-label" for="payCheque">Cheque</label>
                            </div>
                        </div>
                    </div>

                    <div class="pay-modal-row">
                        <label>Payment Through</label>
                        <div class="pay-ctrl">
                            <select id="paymentThrough" class="form-select form-select-sm pay-through-select">
                                <option value="">Select</option>
                                <option value="cash|0">Cash</option>
                                @foreach($branches as $b)
                                    <option value="cash|{{ $b->id }}">Cash at {{ $b->name }}</option>
                                @endforeach
                                <option value="iou|0">IOU Slip</option>
                                @if(($accountHeads ?? collect())->count())
                                <optgroup label="Bank">
                                    @foreach($accountHeads as $head)
                                        <option value="bank|{{ $head->HeadId }}">{{ $head->HeadName }}</option>
                                    @endforeach
                                </optgroup>
                                @endif
                            </select>
                        </div>
                    </div>

                    <div class="pay-modal-row">
                        <label>Payment Amount</label>
                        <div class="pay-ctrl">
                            <input type="number" id="paymentAmount" class="form-control form-control-sm" min="0.01" step="0.01" placeholder="Payment Amount">
                        </div>
                    </div>

                    <div class="pay-modal-row payment-cheque-only d-none">
                        <label>Cheque Date</label>
                        <div class="pay-ctrl">
                            <input type="date" id="paymentChequeDate" class="form-control form-control-sm">
                        </div>
                    </div>

                    <div class="pay-modal-row payment-cheque-only d-none">
                        <label>Cheque No</label>
                        <div class="pay-ctrl">
                            <input type="text" id="paymentReference" class="form-control form-control-sm" placeholder="Cheque Number">
                        </div>
                    </div>

                    <div class="pay-modal-row mb-0">
                        <label>Remarks</label>
                        <div class="pay-ctrl">
                            <input type="text" id="paymentNotes" class="form-control form-control-sm" placeholder="Remarks">
                        </div>
                    </div>

                    <div id="paymentMessage" class="mt-3 d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-sm btn-save-pay" id="btnSavePayment">Save</button>
                    <button type="button" class="btn btn-sm btn-danger" data-bs-dismiss="modal">Cancel</button>
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
        through: document.getElementById('paymentThrough'),
        chequeDate: document.getElementById('paymentChequeDate'),
        reference: document.getElementById('paymentReference'),
        amount: document.getElementById('paymentAmount'),
        notes: document.getElementById('paymentNotes'),
        save: document.getElementById('btnSavePayment'),
        message: document.getElementById('paymentMessage')
    };

    function currentMethod() {
        return document.querySelector('input[name="payment_method_ui"]:checked')?.value || 'cash';
    }
    function parseThroughValue(val) {
        if (!val) return { type: '', id: '' };
        const parts = val.split('|');
        return { type: parts[0] || '', id: parts[1] || '' };
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
    }
    function resetForm() {
        el.supplierId.value = '';
        el.date.value = new Date().toISOString().slice(0, 10);
        el.through.value = '';
        if (el.chequeDate) el.chequeDate.value = '';
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
        el.message.className = 'alert alert-' + type + ' mt-3';
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

        const method = currentMethod();
        const through = parseThroughValue(el.through.value);

        let paymentMethod = method;
        let branchId = '';
        let accountHeadId = '';

        if (through.type === 'cash') {
            paymentMethod = 'cash';
            branchId = through.id;
            const mapKey = through.id || 'global';
            const activeMap = paymentHeadMapsByBranch[mapKey] || paymentHeadMapsByBranch['global'] || {};
            const mapping = activeMap['cash'];
            if (mapping) accountHeadId = mapping.head_id;
        } else if (through.type === 'iou') {
            paymentMethod = 'other';
        } else if (through.type === 'bank') {
            paymentMethod = method === 'cheque' ? 'cheque' : 'bank_transfer';
            accountHeadId = through.id;
        }

        let notes = el.notes.value;
        if (method === 'cheque' && el.chequeDate && el.chequeDate.value) {
            notes = notes ? ('Cheque Date: ' + el.chequeDate.value + ' | ' + notes) : ('Cheque Date: ' + el.chequeDate.value);
        }

        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('supplier_id', el.supplierId.value);
        formData.append('payment_date', el.date.value);
        formData.append('branch_id', branchId);
        formData.append('account_head_id', accountHeadId);
        formData.append('payment_method', paymentMethod);
        if (method === 'cheque') {
            formData.append('cheque_number', el.reference.value);
            formData.append('reference_number', el.reference.value);
        }
        formData.append('amount', el.amount.value);
        formData.append('notes', notes);
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
