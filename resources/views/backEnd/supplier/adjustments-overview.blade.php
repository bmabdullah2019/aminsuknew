@extends('backEnd.layouts.master')
@section('title', 'Supplier Adjustment')

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
                    <button type="button" class="btn btn-danger" id="btnOpenAdjustmentModal"><i class="mdi mdi-plus"></i> New Adjustment</button>
                </div>
                <h4 class="page-title">Supplier Adjustment</h4>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4"><div class="procure-box"><div class="card-body py-2"><small>Total Rows</small><h5 class="mb-0">{{ number_format($summary['total_rows'] ?? 0) }}</h5></div></div></div>
        <div class="col-md-4"><div class="procure-box"><div class="card-body py-2"><small>Total Debit</small><h5 class="mb-0">BDT {{ number_format($summary['total_debit'] ?? 0, 2) }}</h5></div></div></div>
        <div class="col-md-4"><div class="procure-box"><div class="card-body py-2"><small>Total Credit</small><h5 class="mb-0">BDT {{ number_format($summary['total_credit'] ?? 0, 2) }}</h5></div></div></div>
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
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control">
                </div>
                <div class="col-md-3 text-md-end">
                    <button class="btn btn-primary" type="submit">Filter</button>
                    <a href="{{ route('admin.supplier.adjustments.index') }}" class="btn btn-secondary">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Adjustment No</th>
                            <th>Supplier</th>
                            <th>Reason</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($adjustments as $adjustment)
                            <tr>
                                <td>{{ optional($adjustment->transaction_date)->format('d/m/Y') }}</td>
                                <td>{{ $adjustment->reference_number ?: '-' }}</td>
                                <td>{{ $adjustment->supplier->name ?? 'N/A' }}</td>
                                <td>{{ $adjustment->description ?: '-' }}</td>
                                <td class="text-end">{{ number_format((float) max($adjustment->debit, $adjustment->credit), 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">No supplier adjustments found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($adjustments->hasPages())
                <div class="mt-3 d-flex justify-content-center">{{ $adjustments->links() }}</div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade procure-modal" id="adjustmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Supplier Adjustment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="adjustmentForm">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Supplier</label>
                            <select id="adjustmentSupplierId" class="form-select">
                                <option value="">Select Supplier</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->supplier_code }} - {{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date</label>
                            <input type="date" id="adjustmentDate" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Amount</label>
                            <input type="number" id="adjustmentAmount" class="form-control" min="0.01" step="0.01" placeholder="Adjustment Amount">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Reason</label>
                            <input type="text" id="adjustmentReason" class="form-control" placeholder="Adjustment Reason">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Remarks</label>
                            <textarea id="adjustmentNotes" rows="2" class="form-control" placeholder="Remarks"></textarea>
                        </div>
                    </div>
                    <div id="adjustmentMessage" class="mt-3 d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-info text-white" id="btnSaveAdjustment">Save</button>
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
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value;
    const modalElement = document.getElementById('adjustmentModal');
    let modalOpen = false;

    const el = {
        form: document.getElementById('adjustmentForm'),
        supplierId: document.getElementById('adjustmentSupplierId'),
        date: document.getElementById('adjustmentDate'),
        amount: document.getElementById('adjustmentAmount'),
        reason: document.getElementById('adjustmentReason'),
        notes: document.getElementById('adjustmentNotes'),
        save: document.getElementById('btnSaveAdjustment'),
        message: document.getElementById('adjustmentMessage')
    };

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
    function resetForm() {
        el.supplierId.value = '';
        el.date.value = new Date().toISOString().slice(0, 10);
        el.amount.value = '';
        el.reason.value = '';
        el.notes.value = '';
        setMessage('', 'success');
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

    document.getElementById('btnOpenAdjustmentModal').addEventListener('click', function () {
        resetForm();
        showModal();
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

        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('supplier_id', el.supplierId.value);
        formData.append('adjustment_date', el.date.value);
        formData.append('amount', el.amount.value);
        formData.append('reason', el.reason.value);
        formData.append('notes', el.notes.value);
        formData.append('direction', 'credit');

        fetch("{{ route('admin.supplier.adjustments.store') }}", {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: formData
        }).then(async function (response) {
            const payload = await response.json().catch(function () { return {}; });
            if (!response.ok) throw payload;
            return payload;
        }).then(function (payload) {
            setMessage(payload.message || 'Adjustment saved successfully.', 'success');
            setTimeout(function () { window.location.reload(); }, 700);
        }).catch(function (error) {
            let message = 'Unable to save adjustment.';
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
