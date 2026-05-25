@extends('backEnd.layouts.master')
@section('title', $voucher ? 'Edit Voucher' : 'New Voucher')

@section('css')
<style>
    .voucher-box { border: 1px solid #dfe3eb; border-radius: 14px; background: #fff; }
    .voucher-box + .voucher-box { margin-top: 1rem; }
    .voucher-box .box-title { font-size: 1rem; font-weight: 700; color: #24486b; }
    .voucher-head-wrap { position: relative; }
    .voucher-head-results { position: absolute; inset: calc(100% + 4px) 0 auto 0; z-index: 20; display: none; max-height: 260px; overflow-y: auto; border: 1px solid #dfe3eb; border-radius: 10px; background: #fff; box-shadow: 0 12px 28px rgba(0,0,0,.08); }
    .voucher-head-results button { width: 100%; border: 0; background: transparent; text-align: left; padding: .65rem .8rem; }
    .voucher-head-results button:hover { background: #f4f8fc; }
    .voucher-empty { padding: 1.5rem .75rem; color: #7a8794; text-align: center; }
    .voucher-cheque span { display: inline-block; margin: 0 .3rem .3rem 0; padding: .18rem .5rem; border-radius: 999px; background: #eef4fb; color: #34587a; font-size: .78rem; }
</style>
@endsection

@section('content')
@php
    $initialLines = collect($details)->map(function ($detail) {
        return [
            'head_id' => (int) $detail->TranHead,
            'head_label' => trim(($detail->HeadCode ? $detail->HeadCode . ' - ' : '') . $detail->HeadName),
            'sub_id' => $detail->SubId ? (int) $detail->SubId : '',
            'sub_label' => trim(($detail->SubCode ? $detail->SubCode . ' - ' : '') . ($detail->SubName ?? '')),
            'narration' => $detail->Narration ?? '',
            'bank_name' => $detail->BankName ?? '',
            'branch_name' => $detail->BranchName ?? '',
            'cheque_no' => $detail->ChequeNo ?? '',
            'cheque_date' => !empty($detail->ChequeDate) ? \Carbon\Carbon::parse($detail->ChequeDate)->format('Y-m-d') : '',
            'debit' => (float) $detail->Debit,
            'credit' => (float) $detail->Credit,
        ];
    })->values();
@endphp

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.accounts.voucher.index') }}" class="btn btn-sm btn-secondary"><i class="mdi mdi-arrow-left"></i> Back</a>
                </div>
                <h4 class="page-title">{{ $voucher ? 'Edit Voucher: ' . $voucher->TranNo : 'Add Voucher' }}</h4>
            </div>
        </div>
    </div>

    <form id="voucherForm">
        @csrf
        @if($voucher)
            <input type="hidden" name="TranId" value="{{ $voucher->TranId }}">
        @endif

        <div class="voucher-box p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <div class="box-title">{{ $voucher ? 'Update Voucher' : 'Create Voucher' }}</div>
                    <div class="text-muted small">Add rows dynamically like the reference layout.</div>
                </div>
                <div class="text-md-end">
                    <div class="small text-muted">Voucher No</div>
                    <div class="fw-bold">{{ $voucherNo }}</div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                    <input type="date" name="TranDate" class="form-control" value="{{ $voucher ? $voucher->TranDate->format('Y-m-d') : date('Y-m-d') }}" required>
                </div>
                <div class="col-md-9">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="Remarks" rows="2" class="form-control" placeholder="Write the overall voucher description">{{ $voucher->Remarks ?? '' }}</textarea>
                </div>
            </div>
        </div>

        <div class="voucher-box p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div class="box-title mb-0">Line Entry Builder</div>
                <span class="badge bg-warning text-dark d-none" id="editState">Editing row</span>
            </div>

            <input type="hidden" id="lineHeadId">

            <div class="row g-3">
                <div class="col-lg-5">
                    <label class="form-label fw-semibold">A/C Head <span class="text-danger">*</span></label>
                    <div class="voucher-head-wrap">
                        <input type="text" id="lineHeadSearch" class="form-control" autocomplete="off" placeholder="Type to search accounts head">
                        <div class="voucher-head-results" id="lineHeadResults"></div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <label class="form-label fw-semibold">Subsidiary</label>
                    <select id="lineSubId" class="form-select">
                        <option value="">Select Subsidiary</option>
                    </select>
                </div>
                <div class="col-lg-4">
                    <label class="form-label fw-semibold">Narration</label>
                    <input type="text" id="lineNarration" class="form-control" placeholder="Narration">
                </div>

                <div class="col-lg-3">
                    <label class="form-label fw-semibold">Bank Name</label>
                    <input type="text" id="lineBankName" class="form-control">
                </div>
                <div class="col-lg-3">
                    <label class="form-label fw-semibold">Branch Name</label>
                    <input type="text" id="lineBranchName" class="form-control">
                </div>
                <div class="col-lg-2">
                    <label class="form-label fw-semibold">Cheque No</label>
                    <input type="text" id="lineChequeNo" class="form-control">
                </div>
                <div class="col-lg-2">
                    <label class="form-label fw-semibold">Cheque Date</label>
                    <input type="date" id="lineChequeDate" class="form-control">
                </div>
                <div class="col-lg-1 col-md-6">
                    <label class="form-label fw-semibold">Debit</label>
                    <input type="number" id="lineDebit" class="form-control text-end" value="0" min="0" step="0.01">
                </div>
                <div class="col-lg-1 col-md-6">
                    <label class="form-label fw-semibold">Credit</label>
                    <input type="number" id="lineCredit" class="form-control text-end" value="0" min="0" step="0.01">
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-3">
                <button type="button" class="btn btn-primary" id="btnAddLine"><i class="mdi mdi-plus-circle-outline"></i> <span id="addButtonText">Add Row</span></button>
                <button type="button" class="btn btn-outline-secondary" id="btnClearLine"><i class="mdi mdi-refresh"></i> Clear</button>
            </div>

            <div class="alert alert-warning mt-3 mb-0 d-none" id="lineMessage"></div>
        </div>

        <div class="voucher-box p-4">
            <div class="row g-3 mb-3">
                <div class="col-md-4"><div class="border rounded p-3"><div class="small text-muted">Total Debit</div><div class="fs-5 fw-bold" id="totalDebit">0.00</div></div></div>
                <div class="col-md-4"><div class="border rounded p-3"><div class="small text-muted">Total Credit</div><div class="fs-5 fw-bold" id="totalCredit">0.00</div></div></div>
                <div class="col-md-4"><div class="border rounded p-3"><div class="small text-muted">Difference</div><div class="fs-5 fw-bold" id="totalDifference">0.00</div></div></div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:60px;">SL</th>
                            <th>A/C Head</th>
                            <th>Subsidiary</th>
                            <th>Narration</th>
                            <th>Cheque Info</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Credit</th>
                            <th style="width:120px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="voucherLines"></tbody>
                </table>
            </div>

            <div class="alert alert-danger mt-3 mb-0 d-none" id="balanceAlert">Total debit and total credit must be equal before saving.</div>
        </div>

        <div id="hiddenLines"></div>
        <input type="hidden" name="TotalDebit" id="hiddenTotalDebit">
        <input type="hidden" name="TotalCredit" id="hiddenTotalCredit">

        <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
            <a href="{{ route('admin.accounts.voucher.index') }}" class="btn btn-light">Cancel</a>
            <button type="submit" class="btn btn-success px-4" id="btnSaveVoucher"><i class="mdi mdi-content-save"></i> Save Voucher</button>
        </div>
    </form>

    <div id="saveMessage" class="mt-3 d-none"></div>
</div>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value;
    const lines = @json($initialLines).map(normalizeLine);
    let editingIndex = null;
    let searchTimer = null;

    const el = {
        form: document.getElementById('voucherForm'),
        saveMessage: document.getElementById('saveMessage'),
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
        lineMessage: document.getElementById('lineMessage'),
        addButtonText: document.getElementById('addButtonText'),
        editState: document.getElementById('editState'),
        linesBody: document.getElementById('voucherLines'),
        hiddenLines: document.getElementById('hiddenLines'),
        totalDebit: document.getElementById('totalDebit'),
        totalCredit: document.getElementById('totalCredit'),
        totalDifference: document.getElementById('totalDifference'),
        hiddenTotalDebit: document.getElementById('hiddenTotalDebit'),
        hiddenTotalCredit: document.getElementById('hiddenTotalCredit'),
        balanceAlert: document.getElementById('balanceAlert'),
        saveButton: document.getElementById('btnSaveVoucher')
    };

    function amount(value) { const n = parseFloat(value); return Number.isFinite(n) ? n : 0; }
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
        editingIndex = null;
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
        el.addButtonText.textContent = 'Add Row';
        el.editState.classList.add('d-none');
        showLineMessage('');
        closeResults();
    }
    function fillComposer(line, index) {
        editingIndex = index;
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
        el.addButtonText.textContent = 'Update Row';
        el.editState.textContent = `Editing row ${index + 1}`;
        el.editState.classList.remove('d-none');
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
    function renderHidden() {
        el.hiddenLines.innerHTML = lines.map(function (line) {
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
        const debit = lines.reduce(function (sum, line) { return sum + amount(line.debit); }, 0);
        const credit = lines.reduce(function (sum, line) { return sum + amount(line.credit); }, 0);
        const diff = Math.abs(debit - credit);
        const balanced = diff < 0.01 && lines.length >= 2;
        el.totalDebit.textContent = fmt(debit);
        el.totalCredit.textContent = fmt(credit);
        el.totalDifference.textContent = fmt(diff);
        el.hiddenTotalDebit.value = fmt(debit);
        el.hiddenTotalCredit.value = fmt(credit);
        el.balanceAlert.classList.toggle('d-none', balanced || lines.length === 0);
        el.saveButton.disabled = !balanced;
    }
    function renderLines() {
        el.saveMessage.classList.add('d-none');
        el.saveMessage.textContent = '';
        if (!lines.length) {
            el.linesBody.innerHTML = '<tr><td colspan="8" class="voucher-empty">No voucher rows added yet.</td></tr>';
        } else {
            el.linesBody.innerHTML = lines.map(function (line, index) {
                return `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${esc(line.head_label)}</td>
                        <td>${line.sub_label ? esc(line.sub_label) : '<span class="text-muted">-</span>'}</td>
                        <td>${line.narration ? esc(line.narration) : '<span class="text-muted">-</span>'}</td>
                        <td>${chequeInfo(line)}</td>
                        <td class="text-end">${fmt(line.debit)}</td>
                        <td class="text-end">${fmt(line.credit)}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary me-1" data-action="edit" data-index="${index}"><i class="mdi mdi-pencil"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-action="remove" data-index="${index}"><i class="mdi mdi-delete"></i></button>
                        </td>
                    </tr>
                `;
            }).join('');
        }
        renderHidden();
        updateTotals();
    }

    el.headSearch.addEventListener('input', function () {
        if (el.headSearch.value.trim() !== (el.headSearch.dataset.selectedLabel || '')) {
            el.headId.value = '';
            renderSubsidiaries([]);
        }
        const keyword = el.headSearch.value.trim();
        if (keyword.length < 2) { closeResults(); return; }
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            fetch(`{{ route('admin.accounts.getHeadList') }}?Keyword=${encodeURIComponent(keyword)}`, { headers: { 'Accept': 'application/json' } })
                .then(function (response) { if (!response.ok) throw new Error(); return response.json(); })
                .then(function (items) {
                    el.headResults.innerHTML = items.map(function (item) {
                        const label = `${item.HeadCode} - ${item.HeadName}`;
                        return `<button type="button" data-id="${esc(item.HeadId)}" data-label="${esc(label)}">${esc(label)}</button>`;
                    }).join('');
                    el.headResults.style.display = items.length ? 'block' : 'none';
                })
                .catch(closeResults);
        }, 250);
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
    document.getElementById('btnAddLine').addEventListener('click', function () {
        const line = currentLine();
        const error = validateLine(line);
        if (error) { showLineMessage(error); return; }
        if (editingIndex === null) lines.push(line); else lines[editingIndex] = line;
        renderLines();
        clearComposer();
        el.headSearch.focus();
    });
    document.getElementById('btnClearLine').addEventListener('click', clearComposer);
    el.linesBody.addEventListener('click', function (event) {
        const button = event.target.closest('button[data-action]');
        if (!button) return;
        const index = Number(button.dataset.index);
        if (!Number.isInteger(index) || !lines[index]) return;
        if (button.dataset.action === 'edit') { fillComposer(lines[index], index); return; }
        lines.splice(index, 1);
        if (editingIndex === index) clearComposer();
        if (editingIndex !== null && editingIndex > index) editingIndex -= 1;
        renderLines();
    });
    document.addEventListener('click', function (event) {
        if (!event.target.closest('.voucher-head-wrap')) closeResults();
    });
    el.form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (lines.length < 2) {
            el.saveMessage.className = 'alert alert-danger mt-3';
            el.saveMessage.textContent = 'Please add at least two voucher rows before saving.';
            el.saveMessage.classList.remove('d-none');
            return;
        }
        if (Math.abs(amount(el.hiddenTotalDebit.value) - amount(el.hiddenTotalCredit.value)) >= 0.01) {
            el.saveMessage.className = 'alert alert-danger mt-3';
            el.saveMessage.textContent = 'Voucher cannot be saved until debit and credit totals are equal.';
            el.saveMessage.classList.remove('d-none');
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
            el.saveMessage.className = 'alert alert-success mt-3';
            el.saveMessage.textContent = `${payload.message} (${payload.tranNo})`;
            el.saveMessage.classList.remove('d-none');
            setTimeout(function () { window.location.href = "{{ route('admin.accounts.voucher.index') }}"; }, 1000);
        }).catch(function (error) {
            let message = 'Unable to save the voucher. Please review the entered data.';
            if (error && error.message) message = error.message;
            if (error && error.errors) {
                const key = Object.keys(error.errors)[0];
                if (key && Array.isArray(error.errors[key]) && error.errors[key][0]) message = error.errors[key][0];
            }
            el.saveMessage.className = 'alert alert-danger mt-3';
            el.saveMessage.textContent = message;
            el.saveMessage.classList.remove('d-none');
            updateTotals();
        });
    });

    renderLines();
    clearComposer();
});
</script>
@endsection
