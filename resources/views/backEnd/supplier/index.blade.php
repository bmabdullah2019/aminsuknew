@extends('backEnd.layouts.master')
@section('title','Supplier')

@section('css')
<style>
    .procure-modal .modal-dialog{max-width:800px}.procure-modal .modal-content{border:0;border-radius:10px;overflow:visible}.procure-modal .modal-header{border-bottom:2px solid #f15a24;padding:.9rem 1.2rem}.procure-modal .modal-footer{background:#edf3fb;border-top:1px solid #d7e2ef}.procure-modal{background:rgba(15,23,42,.38)}.procure-modal .modal-body{padding:1rem}.procure-grid-card{border:1px solid #dfe6ef;background:#fff}.procure-grid-card .card-body{padding:1rem}.procure-summary{border:1px solid #dfe6ef;padding:.65rem .8rem;height:100%}.procure-summary small{color:#6d7d90}.procure-table td,.procure-table th{vertical-align:middle}.procure-table .text-end{font-variant-numeric:tabular-nums}
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <button type="button" class="btn btn-danger" id="btnOpenSupplierModal"><i class="mdi mdi-plus"></i> New Supplier</button>
                </div>
                <h4 class="page-title">Supplier</h4>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-3"><div class="procure-summary"><small>Total Suppliers</small><div class="fw-bold fs-5">{{ number_format($suppliers->total()) }}</div></div></div>
        <div class="col-md-3"><div class="procure-summary"><small>Current Page</small><div class="fw-bold fs-5">{{ number_format($suppliers->count()) }}</div></div></div>
        <div class="col-md-3"><div class="procure-summary"><small>With Dues</small><div class="fw-bold fs-5">{{ number_format($suppliers->getCollection()->filter(fn($s) => $s->total_dues > 0)->count()) }}</div></div></div>
        <div class="col-md-3"><div class="procure-summary"><small>Over Limit</small><div class="fw-bold fs-5">{{ number_format($suppliers->getCollection()->filter(fn($s) => $s->is_over_credit_limit)->count()) }}</div></div></div>
    </div>

    <div class="card procure-grid-card">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.supplier.index') }}" class="row g-2 align-items-end mb-3">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Supplier code or name">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="with_dues" value="1" id="with_dues" {{ request('with_dues') ? 'checked' : '' }}>
                        <label class="form-check-label" for="with_dues">With dues</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="over_credit_limit" value="1" id="over_credit_limit" {{ request('over_credit_limit') ? 'checked' : '' }}>
                        <label class="form-check-label" for="over_credit_limit">Over limit</label>
                    </div>
                </div>
                <div class="col-md-3 text-md-end">
                    <button class="btn btn-primary" type="submit">Filter</button>
                    <a href="{{ route('admin.supplier.index') }}" class="btn btn-secondary">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover procure-table">
                    <thead class="table-light">
                        <tr>
                            <th>Supplier Code</th>
                            <th>Supplier Name</th>
                            <th>Mobile</th>
                            <th>Current Balance</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suppliers as $supplier)
                        <tr>
                            <td>{{ $supplier->supplier_code }}</td>
                            <td>{{ $supplier->name }}</td>
                            <td>{{ $supplier->mobile ?: ($supplier->phone ?: '-') }}</td>
                            <td>{{ number_format((float) $supplier->current_balance, 2) }}</td>
                            <td>{{ ucfirst($supplier->status) }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.supplier.show', $supplier->id) }}" class="btn btn-sm btn-outline-info"><i class="mdi mdi-eye"></i></a>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-edit-supplier" data-url="{{ route('admin.supplier.data', $supplier->id) }}"><i class="mdi mdi-pencil"></i></button>
                                <a href="{{ route('admin.supplier.payments', $supplier->id) }}" class="btn btn-sm btn-outline-success"><i class="mdi mdi-cash"></i></a>
                                <a href="{{ route('admin.supplier.ledger', $supplier->id) }}" class="btn btn-sm btn-outline-secondary"><i class="mdi mdi-book-open-page-variant"></i></a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted">No suppliers found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($suppliers->hasPages())
                <div class="mt-3 d-flex justify-content-center">{{ $suppliers->links() }}</div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade procure-modal" id="supplierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="supplierModalTitle">Supplier Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="supplierForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="supplierId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Supplier Code</label>
                            <input type="text" id="supplierCode" class="form-control" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select id="supplierStatus" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text" id="supplierName" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mobile</label>
                            <input type="text" id="supplierMobile" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" id="supplierPhone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Person</label>
                            <input type="text" id="supplierContactPerson" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" id="supplierEmail" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Opening Date</label>
                            <input type="date" id="supplierOpeningDate" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Opening Balance</label>
                            <input type="number" id="supplierOpeningBalance" class="form-control" min="0" step="0.01">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Address</label>
                            <textarea id="supplierAddress" rows="2" class="form-control"></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Remarks</label>
                            <textarea id="supplierNotes" rows="2" class="form-control"></textarea>
                        </div>
                    </div>
                    <div id="supplierFormMessage" class="mt-3 d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-info text-white" id="btnSaveSupplier">Save</button>
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
    const nextSupplierCode = @json($nextSupplierCode);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value;
    const modalElement = document.getElementById('supplierModal');
    let modalOpen = false;

    const el = {
        title: document.getElementById('supplierModalTitle'),
        form: document.getElementById('supplierForm'),
        id: document.getElementById('supplierId'),
        code: document.getElementById('supplierCode'),
        status: document.getElementById('supplierStatus'),
        name: document.getElementById('supplierName'),
        mobile: document.getElementById('supplierMobile'),
        phone: document.getElementById('supplierPhone'),
        contactPerson: document.getElementById('supplierContactPerson'),
        email: document.getElementById('supplierEmail'),
        openingDate: document.getElementById('supplierOpeningDate'),
        openingBalance: document.getElementById('supplierOpeningBalance'),
        address: document.getElementById('supplierAddress'),
        notes: document.getElementById('supplierNotes'),
        message: document.getElementById('supplierFormMessage'),
        save: document.getElementById('btnSaveSupplier')
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
    function resetForm() {
        el.id.value = '';
        el.code.value = nextSupplierCode;
        el.status.value = 'active';
        el.name.value = '';
        el.mobile.value = '';
        el.phone.value = '';
        el.contactPerson.value = '';
        el.email.value = '';
        el.openingDate.value = '';
        el.openingBalance.value = '';
        el.address.value = '';
        el.notes.value = '';
        setMessage('', 'success');
    }
    function fillForm(supplier) {
        el.id.value = supplier.id || '';
        el.code.value = supplier.supplier_code || '';
        el.status.value = supplier.status || 'active';
        el.name.value = supplier.name || '';
        el.mobile.value = supplier.mobile || '';
        el.phone.value = supplier.phone || '';
        el.contactPerson.value = supplier.contact_person || '';
        el.email.value = supplier.email || '';
        el.openingDate.value = supplier.opening_date || '';
        el.openingBalance.value = supplier.opening_balance || '';
        el.address.value = supplier.address || '';
        el.notes.value = supplier.notes || '';
        setMessage('', 'success');
    }
    document.getElementById('btnOpenSupplierModal').addEventListener('click', function () {
        resetForm();
        el.title.textContent = 'Supplier Information';
        showModal();
    });
    document.querySelectorAll('.btn-edit-supplier').forEach(function (button) {
        button.addEventListener('click', function () {
            fetch(button.dataset.url, { headers: { 'Accept': 'application/json' } })
                .then(function (response) { if (!response.ok) throw new Error(); return response.json(); })
                .then(function (payload) {
                    fillForm(payload.supplier || {});
                    el.title.textContent = 'Edit Supplier';
                    showModal();
                })
                .catch(function () {
                    setMessage('Unable to load supplier information.', 'danger');
                });
        });
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
        el.save.disabled = true;
        setMessage('', 'success');

        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('supplier_code', el.code.value);
        formData.append('status', el.status.value);
        formData.append('name', el.name.value);
        formData.append('mobile', el.mobile.value);
        formData.append('phone', el.phone.value);
        formData.append('contact_person', el.contactPerson.value);
        formData.append('email', el.email.value);
        formData.append('opening_date', el.openingDate.value);
        formData.append('opening_balance', el.openingBalance.value || '0');
        formData.append('address', el.address.value);
        formData.append('notes', el.notes.value);

        const supplierId = el.id.value;
        const url = supplierId
            ? `{{ url('admin/supplier') }}/${supplierId}/update`
            : `{{ route('admin.supplier.store') }}`;

        fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: formData
        }).then(async function (response) {
            const payload = await response.json().catch(function () { return {}; });
            if (!response.ok) throw payload;
            return payload;
        }).then(function (payload) {
            setMessage(payload.message || 'Saved successfully.', 'success');
            setTimeout(function () { window.location.reload(); }, 700);
        }).catch(function (error) {
            let message = 'Unable to save supplier.';
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
