@extends('backEnd.layouts.master')
@section('title','Journal Vouchers')

@section('css')
<style>
    .voucher-modal .modal-dialog{max-width:860px}.voucher-modal .modal-content{border:0;border-radius:10px;overflow:visible}.voucher-modal .modal-header{border-bottom:2px solid #f15a24;padding:.9rem 1.2rem}.voucher-modal .modal-body{padding:1rem;overflow:visible}.voucher-modal .modal-footer{background:#edf3fb;border-top:1px solid #d7e2ef}.voucher-modal{background:rgba(15,23,42,.38)}.voucher-panel{border:1px solid #dfe6ef;padding:.8rem;margin-bottom:.8rem;overflow:visible}.voucher-search-wrap{position:relative}.voucher-search-results{position:absolute;top:calc(100% + 2px);left:0;right:0;z-index:1080;display:none;max-height:220px;overflow-y:auto;border:1px solid #dfe6ef;background:#fff;box-shadow:0 12px 24px rgba(0,0,0,.08)}.voucher-search-results button{width:100%;border:0;background:transparent;text-align:left;padding:.5rem .7rem}.voucher-search-results button:hover{background:#f5f8fc}.voucher-lines-table th,.voucher-lines-table td{padding:.45rem;vertical-align:middle}.voucher-lines-table .text-end{font-variant-numeric:tabular-nums}.voucher-empty{text-align:center;color:#7a8794;padding:1rem .5rem}.voucher-cheque span{display:inline-block;margin:0 .25rem .25rem 0;padding:.14rem .45rem;border-radius:999px;background:#edf4ff;color:#38597a;font-size:.76rem}.voucher-summary-card{border:1px solid #dfe6ef;padding:.65rem .8rem}.voucher-summary-card .small{color:#6d7d90}
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <button type="button" class="btn btn-sm btn-primary" id="btnOpenCreateVoucher">
                        <i class="mdi mdi-plus"></i> New Voucher
                    </button>
                </div>
                <h4 class="page-title">Journal Vouchers</h4>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Voucher No or remarks">
                </div>
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                </div>
                <div class="col-md-3 align-self-end">
                    <button class="btn btn-primary" type="submit">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Voucher No</th>
                            <th>Date</th>
                            <th class="text-end">Amount</th>
                            <th>Remarks</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vouchers as $v)
                        <tr>
                            <td><strong>{{ $v->TranNo }}</strong></td>
                            <td>{{ $v->TranDate ? $v->TranDate->format('d/m/Y') : '' }}</td>
                            <td class="text-end">{{ number_format($v->TranAmount, 2) }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($v->Remarks, 50) }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.accounts.voucher.show', $v->TranId) }}" class="btn btn-sm btn-outline-info" title="View"><i class="mdi mdi-eye"></i></a>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-edit-voucher" title="Edit" data-url="{{ route('admin.accounts.voucher.data', $v->TranId) }}"><i class="mdi mdi-pencil"></i></button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted">No vouchers found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($vouchers->hasPages())
                <div class="d-flex justify-content-center">{{ $vouchers->links() }}</div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade voucher-modal" id="voucherModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="voucherModalTitle">Add Voucher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="voucherForm">
                @csrf
                <input type="hidden" name="TranId" id="voucherTranId">
                <input type="hidden" name="TotalDebit" id="hiddenTotalDebit">
                <input type="hidden" name="TotalCredit" id="hiddenTotalCredit">
                <div class="modal-body">
                    <div class="voucher-panel">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Voucher No</label>
                                <input type="text" id="voucherNo" class="form-control" value="{{ $voucherNo }}" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Date</label>
                                <input type="date" name="TranDate" id="voucherDate" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Description</label>
                                <textarea name="Remarks" id="voucherRemarks" rows="2" class="form-control"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="voucher-panel">
                        <input type="hidden" id="lineHeadId">
                        <div class="row g-2">
                            <div class="col-md-5">
                                <label class="form-label">A/C Head</label>
                                <div class="voucher-search-wrap">
                                    <input type="text" id="lineHeadSearch" class="form-control form-control-sm" autocomplete="off" placeholder="Type to search accounts head">
                                    <div class="voucher-search-results" id="lineHeadResults"></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Bank Name</label>
                                <input type="text" id="lineBankName" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Branch Name</label>
                                <input type="text" id="lineBranchName" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Subsidiary</label>
                                <select id="lineSubId" class="form-select form-select-sm">
                                    <option value="">Select Subsidiary</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Cheque No</label>
                                <input type="text" id="lineChequeNo" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Cheque Date</label>
                                <input type="date" id="lineChequeDate" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Narration</label>
                                <input type="text" id="lineNarration" class="form-control form-control-sm" placeholder="Narration">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Debit</label>
                                <input type="number" id="lineDebit" class="form-control form-control-sm text-end" value="0" min="0" step="0.01">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Credit</label>
                                <input type="number" id="lineCredit" class="form-control form-control-sm text-end" value="0" min="0" step="0.01">
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="button" class="btn btn-primary btn-sm w-100" id="btnAddLine"><i class="mdi mdi-plus"></i></button>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <div class="small text-muted" id="lineEditorState">Add a row, then it will appear in the table below.</div>
                            <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 d-none" id="btnClearLine">Clear edit</button>
                        </div>
                        <div class="alert alert-warning mt-2 mb-0 d-none" id="lineMessage"></div>
                    </div>

                    <div class="voucher-panel mb-0">
                        <div class="row g-2 mb-2">
                            <div class="col-md-4"><div class="voucher-summary-card"><div class="small">Total Debit</div><div class="fw-bold" id="totalDebit">0.00</div></div></div>
                            <div class="col-md-4"><div class="voucher-summary-card"><div class="small">Total Credit</div><div class="fw-bold" id="totalCredit">0.00</div></div></div>
                            <div class="col-md-4"><div class="voucher-summary-card"><div class="small">Difference</div><div class="fw-bold" id="totalDifference">0.00</div></div></div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered voucher-lines-table mb-0">
                                <thead class="table-light">
                                    <tr><th style="width:50px;">SL</th><th>A/C Head</th><th>Subsidiary</th><th>Narration</th><th>Cheque Info</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th style="width:90px;">Action</th></tr>
                                </thead>
                                <tbody id="voucherLines"></tbody>
                            </table>
                        </div>
                        <div class="alert alert-danger mt-2 mb-0 d-none" id="balanceAlert">Total debit and total credit must be equal before saving.</div>
                        <div id="hiddenLines"></div>
                    </div>
                    <div id="saveMessage" class="mt-3 d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-info text-white" id="btnSaveVoucher">Save</button>
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
    const nextVoucherNo = @json($voucherNo);
    const autoModal = @json(request('modal'));
    const autoEditId = @json(request('edit'));
    const indexUrl = @json(route('admin.accounts.voucher.index'));
    const modalElement = document.getElementById('voucherModal');
    let modalOpen = false;

    const state = { lines: [], editingIndex: null, searchTimer: null };

    const el = {
        modalTitle: document.getElementById('voucherModalTitle'),
        form: document.getElementById('voucherForm'),
        tranId: document.getElementById('voucherTranId'),
        voucherNo: document.getElementById('voucherNo'),
        voucherDate: document.getElementById('voucherDate'),
        voucherRemarks: document.getElementById('voucherRemarks'),
        headId: document.getElementById('lineHeadId'),
        headSearch: document.getElementById('lineHeadSearch'),
        headResults: document.getElementById('lineHeadResults'),
        subId: document.getElementById('lineSubId'),
        narration: document.getElementById('lineNarration'),
        bankName: document.getElementById('lineBankName'),
        branchName: document.getElementById('lineBranchName'),
        chequeNo: document.getElementById('lineChequeNo'),
        chequeDate: document.getElementById('lineChequeDate'),
        debit: document.getElementById('lineDebit'),
        credit: document.getElementById('lineCredit'),
        addLine: document.getElementById('btnAddLine'),
        clearLine: document.getElementById('btnClearLine'),
        editorState: document.getElementById('lineEditorState'),
        lineMessage: document.getElementById('lineMessage'),
        linesBody: document.getElementById('voucherLines'),
        totalDebit: document.getElementById('totalDebit'),
        totalCredit: document.getElementById('totalCredit'),
        totalDifference: document.getElementById('totalDifference'),
        hiddenTotalDebit: document.getElementById('hiddenTotalDebit'),
        hiddenTotalCredit: document.getElementById('hiddenTotalCredit'),
        balanceAlert: document.getElementById('balanceAlert'),
        hiddenLines: document.getElementById('hiddenLines'),
        saveButton: document.getElementById('btnSaveVoucher'),
        saveMessage: document.getElementById('saveMessage')
    };

    function amount(value) { const parsed = parseFloat(value); return Number.isFinite(parsed) ? parsed : 0; }
    function fmt(value) { return amount(value).toFixed(2); }
    function esc(value) { return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;'); }
    function normalizeLine(line) {
        return {
            head_id: String(line.head_id ?? '').trim(),
            head_label: String(line.head_label ?? '').trim(),
            sub_id: String(line.sub_id ?? '').trim(),
            sub_label: String(line.sub_label ?? '').trim(),
            narration: String(line.narration ?? '').trim(),
            bank_name: String(line.bank_name ?? '').trim(),
            branch_name: String(line.branch_name ?? '').trim(),
            cheque_no: String(line.cheque_no ?? '').trim(),
            cheque_date: String(line.cheque_date ?? '').trim(),
            debit: amount(line.debit),
            credit: amount(line.credit)
        };
    }
    function showLineMessage(message) {
        el.lineMessage.textContent = message || '';
        el.lineMessage.classList.toggle('d-none', !message);
    }
    function showSaveMessage(message, type) {
        el.saveMessage.className = `alert alert-${type} mt-3`;
        el.saveMessage.textContent = message;
        el.saveMessage.classList.remove('d-none');
    }
    function clearSaveMessage() {
        el.saveMessage.className = 'mt-3 d-none';
        el.saveMessage.textContent = '';
    }
    function dispatchModalEvent(name) {
        modalElement.dispatchEvent(new Event(name));
    }
    function showModal() {
        if (modalOpen) return;
        modalOpen = true;
        modalElement.style.display = 'block';
        modalElement.removeAttribute('aria-hidden');
        modalElement.setAttribute('aria-modal', 'true');
        modalElement.classList.add('show');
        document.body.classList.add('modal-open');
        document.body.style.overflow = 'hidden';
    }
    function hideModal() {
        if (!modalOpen) return;
        modalOpen = false;
        modalElement.classList.remove('show');
        modalElement.style.display = 'none';
        modalElement.setAttribute('aria-hidden', 'true');
        modalElement.removeAttribute('aria-modal');
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        dispatchModalEvent('hidden.bs.modal');
    }
    function closeResults() {
        el.headResults.innerHTML = '';
        el.headResults.style.display = 'none';
    }
    function renderSubsidiaries(items, selectedId = '', selectedLabel = '') {
        const currentId = String(selectedId ?? '');
        let html = '<option value="">Select Subsidiary</option>';
        items.forEach(function (item) {
            const label = `${item.SubCode} - ${item.SubName}`;
            html += `<option value="${esc(item.SubId)}"${String(item.SubId) === currentId ? ' selected' : ''}>${esc(label)}</option>`;
        });
        if (currentId && !items.some(function (item) { return String(item.SubId) === currentId; })) {
            html += `<option value="${esc(currentId)}" selected>${esc(selectedLabel || 'Selected subsidiary')}</option>`;
        }
        el.subId.innerHTML = html;
    }
    function loadSubsidiaries(headId, selectedId = '', selectedLabel = '') {
        if (!headId) { renderSubsidiaries([]); return Promise.resolve(); }
        return fetch("{{ route('admin.accounts.getSubsidiaryList') }}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ HeadId: headId })
        }).then(function (response) {
            if (!response.ok) throw new Error();
            return response.json();
        }).then(function (items) {
            renderSubsidiaries(items, selectedId, selectedLabel);
        }).catch(function () {
            renderSubsidiaries([], selectedId, selectedLabel);
        });
    }
    function clearComposer() {
        state.editingIndex = null;
        el.headId.value = '';
        el.headSearch.value = '';
        el.headSearch.dataset.selectedLabel = '';
        renderSubsidiaries([]);
        el.narration.value = '';
        el.bankName.value = '';
        el.branchName.value = '';
        el.chequeNo.value = '';
        el.chequeDate.value = '';
        el.debit.value = '0';
        el.credit.value = '0';
        el.editorState.textContent = 'Add a row, then it will appear in the table below.';
        el.clearLine.classList.add('d-none');
        showLineMessage('');
        closeResults();
    }
    function resetFormState() {
        state.lines = [];
        state.editingIndex = null;
        el.tranId.value = '';
        el.voucherNo.value = nextVoucherNo;
        el.voucherDate.value = new Date().toISOString().slice(0, 10);
        el.voucherRemarks.value = '';
        clearComposer();
        renderLines();
        clearSaveMessage();
    }
    function openCreateModal() {
        resetFormState();
        el.modalTitle.textContent = 'Add Voucher';
        showModal();
    }
    function fillComposer(line, index) {
        state.editingIndex = index;
        el.headId.value = line.head_id;
        el.headSearch.value = line.head_label;
        el.headSearch.dataset.selectedLabel = line.head_label;
        el.narration.value = line.narration;
        el.bankName.value = line.bank_name;
        el.branchName.value = line.branch_name;
        el.chequeNo.value = line.cheque_no;
        el.chequeDate.value = line.cheque_date;
        el.debit.value = fmt(line.debit);
        el.credit.value = fmt(line.credit);
        el.editorState.textContent = `Editing row ${index + 1}`;
        el.clearLine.classList.remove('d-none');
        showLineMessage('');
        loadSubsidiaries(line.head_id, line.sub_id, line.sub_label);
    }
    function currentLine() {
        const option = el.subId.options[el.subId.selectedIndex];
        return normalizeLine({
            head_id: el.headId.value,
            head_label: el.headSearch.value,
            sub_id: el.subId.value,
            sub_label: el.subId.value ? (option ? option.text.trim() : '') : '',
            narration: el.narration.value,
            bank_name: el.bankName.value,
            branch_name: el.branchName.value,
            cheque_no: el.chequeNo.value,
            cheque_date: el.chequeDate.value,
            debit: el.debit.value,
            credit: el.credit.value
        });
    }
    function validateLine(line) {
        if (!line.head_id || !line.head_label) return 'Please select a valid account head.';
        if (line.debit > 0 && line.credit > 0) return 'A row can contain either debit or credit, not both.';
        if (line.debit <= 0 && line.credit <= 0) return 'Please enter a debit or credit amount greater than zero.';
        return '';
    }
    function chequeInfo(line) {
        const items = [];
        if (line.bank_name) items.push(`<span>Bank: ${esc(line.bank_name)}</span>`);
        if (line.branch_name) items.push(`<span>Branch: ${esc(line.branch_name)}</span>`);
        if (line.cheque_no) items.push(`<span>Cheque: ${esc(line.cheque_no)}</span>`);
        if (line.cheque_date) items.push(`<span>Date: ${esc(line.cheque_date)}</span>`);
        return items.length ? `<div class="voucher-cheque">${items.join('')}</div>` : '<span class="text-muted">-</span>';
    }
    function renderHiddenLines() {
        el.hiddenLines.innerHTML = state.lines.map(function (line) {
            return `
                <input type="hidden" name="HeadId[]" value="${esc(line.head_id)}">
                <input type="hidden" name="SubId[]" value="${esc(line.sub_id)}">
                <input type="hidden" name="Narration[]" value="${esc(line.narration)}">
                <input type="hidden" name="BankName[]" value="${esc(line.bank_name)}">
                <input type="hidden" name="BranchName[]" value="${esc(line.branch_name)}">
                <input type="hidden" name="ChequeNo[]" value="${esc(line.cheque_no)}">
                <input type="hidden" name="ChequeDate[]" value="${esc(line.cheque_date)}">
                <input type="hidden" name="Debit[]" value="${esc(fmt(line.debit))}">
                <input type="hidden" name="Credit[]" value="${esc(fmt(line.credit))}">
            `;
        }).join('');
    }
    function updateTotals() {
        const totalDebit = state.lines.reduce(function (sum, line) { return sum + amount(line.debit); }, 0);
        const totalCredit = state.lines.reduce(function (sum, line) { return sum + amount(line.credit); }, 0);
        const difference = Math.abs(totalDebit - totalCredit);
        const balanced = difference < 0.01 && state.lines.length >= 2;
        el.totalDebit.textContent = fmt(totalDebit);
        el.totalCredit.textContent = fmt(totalCredit);
        el.totalDifference.textContent = fmt(difference);
        el.hiddenTotalDebit.value = fmt(totalDebit);
        el.hiddenTotalCredit.value = fmt(totalCredit);
        el.balanceAlert.classList.toggle('d-none', balanced || state.lines.length === 0);
        el.saveButton.disabled = !balanced;
    }
    function renderLines() {
        clearSaveMessage();
        if (!state.lines.length) {
            el.linesBody.innerHTML = '<tr><td colspan="8" class="voucher-empty">No voucher rows added yet.</td></tr>';
        } else {
            el.linesBody.innerHTML = state.lines.map(function (line, index) {
                return `<tr><td>${index + 1}</td><td>${esc(line.head_label)}</td><td>${line.sub_label ? esc(line.sub_label) : '<span class="text-muted">-</span>'}</td><td>${line.narration ? esc(line.narration) : '<span class="text-muted">-</span>'}</td><td>${chequeInfo(line)}</td><td class="text-end">${fmt(line.debit)}</td><td class="text-end">${fmt(line.credit)}</td><td><button type="button" class="btn btn-sm btn-outline-primary me-1" data-action="edit" data-index="${index}"><i class="mdi mdi-pencil"></i></button><button type="button" class="btn btn-sm btn-outline-danger" data-action="remove" data-index="${index}"><i class="mdi mdi-delete"></i></button></td></tr>`;
            }).join('');
        }
        renderHiddenLines();
        updateTotals();
    }
    function searchHeads(keyword) {
        const term = keyword.trim();
        if (term.length < 2) { closeResults(); return; }
        clearTimeout(state.searchTimer);
        el.headResults.innerHTML = '<div class="px-2 py-2 text-muted small">Searching...</div>';
        el.headResults.style.display = 'block';
        state.searchTimer = setTimeout(function () {
            fetch(`{{ route('admin.accounts.getHeadList') }}?Keyword=${encodeURIComponent(term)}`, { headers: { 'Accept': 'application/json' } })
                .then(function (response) { if (!response.ok) throw new Error(); return response.json(); })
                .then(function (items) {
                    if (!items.length) {
                        el.headResults.innerHTML = '<div class="px-2 py-2 text-muted small">No account head found.</div>';
                        el.headResults.style.display = 'block';
                        return;
                    }
                    el.headResults.innerHTML = items.map(function (item) {
                        const label = `${item.HeadCode} - ${item.HeadName}`;
                        return `<button type="button" data-id="${esc(item.HeadId)}" data-label="${esc(label)}">${esc(label)}</button>`;
                    }).join('');
                    el.headResults.style.display = items.length ? 'block' : 'none';
                })
                .catch(closeResults);
        }, 250);
    }
    function openEditModal(url) {
        resetFormState();
        el.modalTitle.textContent = 'Edit Voucher';
        el.editorState.textContent = 'Loading voucher...';
        showModal();
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (response) { if (!response.ok) throw new Error(); return response.json(); })
            .then(function (payload) {
                state.lines = (payload.lines || []).map(normalizeLine);
                el.tranId.value = payload.voucher.tran_id || '';
                el.voucherNo.value = payload.voucher.tran_no || nextVoucherNo;
                el.voucherDate.value = payload.voucher.tran_date || new Date().toISOString().slice(0, 10);
                el.voucherRemarks.value = payload.voucher.remarks || '';
                clearComposer();
                renderLines();
            })
            .catch(function () {
                showSaveMessage('Unable to load voucher details for editing.', 'danger');
            });
    }
    document.getElementById('btnOpenCreateVoucher').addEventListener('click', openCreateModal);
    modalElement.querySelectorAll('[data-bs-dismiss="modal"]').forEach(function (button) {
        button.addEventListener('click', hideModal);
    });
    modalElement.addEventListener('click', function (event) {
        if (event.target === modalElement) hideModal();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modalOpen) hideModal();
    });
    document.querySelectorAll('.btn-edit-voucher').forEach(function (button) {
        button.addEventListener('click', function () {
            openEditModal(button.dataset.url);
        });
    });
    el.headSearch.addEventListener('input', function () {
        if (el.headSearch.value.trim() !== (el.headSearch.dataset.selectedLabel || '')) {
            el.headId.value = '';
            renderSubsidiaries([]);
        }
        searchHeads(el.headSearch.value);
    });
    el.headResults.addEventListener('click', function (event) {
        const button = event.target.closest('button[data-id]');
        if (!button) return;
        el.headId.value = button.dataset.id || '';
        el.headSearch.value = button.dataset.label || '';
        el.headSearch.dataset.selectedLabel = button.dataset.label || '';
        closeResults();
        loadSubsidiaries(el.headId.value);
    });
    el.debit.addEventListener('input', function () { if (amount(el.debit.value) > 0) el.credit.value = '0'; });
    el.credit.addEventListener('input', function () { if (amount(el.credit.value) > 0) el.debit.value = '0'; });
    el.addLine.addEventListener('click', function () {
        const line = currentLine();
        const error = validateLine(line);
        if (error) { showLineMessage(error); return; }
        if (state.editingIndex === null) state.lines.push(line); else state.lines[state.editingIndex] = line;
        renderLines();
        clearComposer();
        el.headSearch.focus();
    });
    el.clearLine.addEventListener('click', clearComposer);
    el.linesBody.addEventListener('click', function (event) {
        const button = event.target.closest('button[data-action]');
        if (!button) return;
        const index = Number(button.dataset.index);
        if (!Number.isInteger(index) || !state.lines[index]) return;
        if (button.dataset.action === 'edit') { fillComposer(state.lines[index], index); return; }
        state.lines.splice(index, 1);
        if (state.editingIndex === index) clearComposer();
        if (state.editingIndex !== null && state.editingIndex > index) state.editingIndex -= 1;
        renderLines();
    });
    document.addEventListener('click', function (event) {
        if (!event.target.closest('.voucher-search-wrap')) closeResults();
    });
    modalElement.addEventListener('hidden.bs.modal', function () {
        clearComposer();
        clearSaveMessage();
        const params = new URLSearchParams(window.location.search);
        params.delete('modal');
        params.delete('edit');
        const query = params.toString();
        if (window.history.replaceState) {
            window.history.replaceState({}, document.title, query ? `${indexUrl}?${query}` : indexUrl);
        }
    });
    el.form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (state.lines.length < 2) { showSaveMessage('Please add at least two voucher rows before saving.', 'danger'); return; }
        if (Math.abs(amount(el.hiddenTotalDebit.value) - amount(el.hiddenTotalCredit.value)) >= 0.01) {
            showSaveMessage('Voucher cannot be saved until debit and credit totals are equal.', 'danger');
            return;
        }
        el.saveButton.disabled = true;
        fetch("{{ route('admin.accounts.voucher.store') }}", {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: new FormData(el.form)
        }).then(async function (response) {
            const payload = await response.json().catch(function () { return {}; });
            if (!response.ok) throw payload;
            return payload;
        }).then(function (payload) {
            showSaveMessage(`${payload.message} (${payload.tranNo})`, 'success');
            setTimeout(function () { window.location.href = indexUrl; }, 900);
        }).catch(function (error) {
            let message = 'Unable to save the voucher. Please review the entered data.';
            if (error && error.message) message = error.message;
            if (error && error.errors) {
                const key = Object.keys(error.errors)[0];
                if (key && Array.isArray(error.errors[key]) && error.errors[key][0]) message = error.errors[key][0];
            }
            showSaveMessage(message, 'danger');
            updateTotals();
        });
    });
    renderLines();
    clearComposer();
    if (autoModal === 'create') {
        openCreateModal();
    } else if (autoEditId) {
        openEditModal(`{{ url('admin/accounts/voucher') }}/${autoEditId}/data`);
    }
});
</script>
@endsection
