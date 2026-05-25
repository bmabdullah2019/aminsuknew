@extends('backEnd.layouts.master')
@section('title', $subsidiary ? 'Edit Subsidiary' : 'New Subsidiary')

@php
    $selectedHeadIds = collect(old('HeadId', $assignedHeads ?? []))
        ->filter(fn ($id) => filled($id))
        ->map(fn ($id) => (int) $id)
        ->unique()
        ->values();

    $headLookup = $heads->mapWithKeys(function ($head) {
        return [
            $head->HeadId => [
                'id' => (int) $head->HeadId,
                'code' => (string) $head->HeadCode,
                'name' => (string) $head->HeadName,
                'label' => trim((string) $head->HeadCode . ' - ' . $head->HeadName),
            ],
        ];
    });

    $selectedHeads = $selectedHeadIds
        ->map(fn ($id) => $headLookup->get($id))
        ->filter()
        ->values();
@endphp

@section('css')
<link href="{{ asset('public/backEnd/assets/libs/select2/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .subsidiary-shell {
        min-height: calc(100vh - 180px);
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding: 1rem 0 2rem;
    }

    .subsidiary-modal-card {
        width: min(100%, 920px);
        border: 1px solid #d7e3ef;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 18px 45px rgba(37, 78, 117, 0.12);
    }

    .subsidiary-modal-header {
        background: #fff;
        border-bottom: 3px solid #ef5b3f;
        padding: 1rem 1.25rem;
    }

    .subsidiary-modal-title {
        font-size: 1.65rem;
        font-weight: 600;
        color: #1f3b57;
    }

    .subsidiary-modal-body {
        background: #fff;
        padding: 1rem 1.25rem 1.25rem;
    }

    .subsidiary-grid {
        display: grid;
        grid-template-columns: 160px 1fr;
        gap: 0.85rem 1rem;
        align-items: center;
    }

    .subsidiary-grid label {
        margin: 0;
        font-weight: 600;
        color: #30485f;
    }

    .subsidiary-grid .form-control,
    .subsidiary-grid .select2-container .select2-selection--single {
        border-radius: 0.25rem;
    }

    .subsidiary-status {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        flex-wrap: wrap;
    }

    .subsidiary-status .form-check {
        margin-bottom: 0;
    }

    .subsidiary-heads-box {
        border: 1px solid #d7e3ef;
        border-radius: 0.5rem;
        overflow: hidden;
        background: #fbfdff;
    }

    .subsidiary-heads-title {
        background: #f1f6fb;
        border-bottom: 1px solid #d7e3ef;
        padding: 0.7rem 0.9rem;
        font-weight: 700;
        color: #2f4962;
    }

    .subsidiary-heads-toolbar {
        display: grid;
        grid-template-columns: 42px minmax(0, 1fr) auto;
        gap: 0.5rem;
        align-items: center;
        padding: 0.75rem 0.9rem;
        border-bottom: 1px solid #e6edf5;
        background: #fff;
    }

    .subsidiary-heads-toolbar .toolbar-index {
        text-align: center;
        font-weight: 700;
        color: #637b92;
    }

    .subsidiary-heads-table {
        margin: 0;
    }

    .subsidiary-heads-table th,
    .subsidiary-heads-table td {
        vertical-align: middle;
    }

    .subsidiary-heads-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .subsidiary-empty {
        text-align: center;
        color: #7b8ea2;
        padding: 1rem !important;
    }

    .subsidiary-modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 0.65rem;
        padding: 0.9rem 1.25rem 1.1rem;
        background: #f3f7fb;
        border-top: 1px solid #d7e3ef;
    }

    .btn-subsidiary-save {
        min-width: 120px;
    }

    .select2-container {
        width: 100% !important;
    }

    .select2-container .select2-selection--single {
        min-height: calc(1.5em + 0.85rem + 2px);
        border: 1px solid #ced4da;
        padding: 0.28rem 0.7rem;
        display: flex;
        align-items: center;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 1.5;
        padding-left: 0;
        padding-right: 1.25rem;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 100%;
        right: 0.4rem;
    }

    @media (max-width: 767.98px) {
        .subsidiary-grid {
            grid-template-columns: 1fr;
        }

        .subsidiary-heads-toolbar {
            grid-template-columns: 1fr;
        }

        .subsidiary-modal-footer {
            justify-content: stretch;
            flex-direction: column-reverse;
        }

        .subsidiary-modal-footer .btn {
            width: 100%;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center">
                <h4 class="page-title mb-0">Subsidiary</h4>
                <a href="{{ route('admin.accounts.subsidiary.index') }}" class="btn btn-sm btn-secondary">
                    <i class="mdi mdi-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>

    <div class="subsidiary-shell">
        <div class="subsidiary-modal-card">
            <div class="subsidiary-modal-header d-flex justify-content-between align-items-center">
                <div class="subsidiary-modal-title">{{ $subsidiary ? 'Edit Subsidiary' : 'New Subsidiary' }}</div>
                <a href="{{ route('admin.accounts.subsidiary.index') }}" class="btn btn-link text-muted text-decoration-none p-0">
                    <i class="mdi mdi-close fs-3"></i>
                </a>
            </div>

            <form method="POST" action="{{ route('admin.accounts.subsidiary.store') }}" id="subsidiaryForm">
                @csrf
                @if($subsidiary)
                    <input type="hidden" name="SubId" value="{{ $subsidiary->SubId }}">
                @endif

                <div class="subsidiary-modal-body">
                    <div class="subsidiary-grid mb-4">
                        <label>Subsidiary Code</label>
                        <input type="text" class="form-control" value="{{ $subCode }}" readonly>

                        <label for="SubName">Subsidiary Name</label>
                        <div>
                            <input
                                type="text"
                                id="SubName"
                                name="SubName"
                                class="form-control @error('SubName') is-invalid @enderror"
                                value="{{ old('SubName', $subsidiary->SubName ?? '') }}"
                                required
                            >
                            @error('SubName')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <label for="Description">Description</label>
                        <div>
                            <input
                                type="text"
                                id="Description"
                                name="Description"
                                class="form-control @error('Description') is-invalid @enderror"
                                value="{{ old('Description', $subsidiary->Description ?? '') }}"
                            >
                            @error('Description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <label>Item Status</label>
                        <div class="subsidiary-status">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="Status" id="status_active" value="A" {{ old('Status', $subsidiary->Status ?? 'A') === 'A' ? 'checked' : '' }}>
                                <label class="form-check-label" for="status_active">Active</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="Status" id="status_inactive" value="I" {{ old('Status', $subsidiary->Status ?? 'A') === 'I' ? 'checked' : '' }}>
                                <label class="form-check-label" for="status_inactive">Inactive</label>
                            </div>
                            @error('Status')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="subsidiary-heads-box">
                        <div class="subsidiary-heads-title">Accounts Head</div>

                        <div class="subsidiary-heads-toolbar">
                            <div class="toolbar-index">#</div>
                            <select id="headSelector" class="form-control">
                                <option value="">Type to search accounts head</option>
                                @foreach($heads as $head)
                                    <option value="{{ $head->HeadId }}">{{ $head->HeadCode }} - {{ $head->HeadName }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-primary" id="btnAddHead">
                                <i class="mdi mdi-plus"></i> Add
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered subsidiary-heads-table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:70px;">#</th>
                                        <th>Selected Account Head</th>
                                        <th style="width:90px;" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="selectedHeadsTable">
                                    @forelse($selectedHeads as $index => $head)
                                        <tr data-head-id="{{ $head['id'] }}">
                                            <td class="text-center head-order">{{ $index + 1 }}</td>
                                            <td>
                                                {{ $head['label'] }}
                                                <input type="hidden" name="HeadId[]" value="{{ $head['id'] }}">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-head">
                                                    <i class="mdi mdi-delete"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="empty-row">
                                            <td colspan="3" class="subsidiary-empty">No account head selected yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @error('HeadId')<div class="text-danger small px-3 pb-3">{{ $message }}</div>@enderror
                        @error('HeadId.*')<div class="text-danger small px-3 pb-3">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="subsidiary-modal-footer">
                    <a href="{{ route('admin.accounts.subsidiary.index') }}" class="btn btn-danger">Cancel</a>
                    <button type="submit" class="btn btn-info text-white btn-subsidiary-save">
                        {{ $subsidiary ? 'Update' : 'Save' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ asset('public/backEnd/assets/libs/select2/js/select2.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const headSelector = document.getElementById('headSelector');
    const addButton = document.getElementById('btnAddHead');
    const tableBody = document.getElementById('selectedHeadsTable');
    const headLookup = @json($headLookup->values());
    const headsById = new Map(headLookup.map(head => [String(head.id), head]));

    function refreshRowNumbers() {
        tableBody.querySelectorAll('tr[data-head-id]').forEach((row, index) => {
            const orderCell = row.querySelector('.head-order');
            if (orderCell) {
                orderCell.textContent = String(index + 1);
            }
        });
    }

    function ensureEmptyState() {
        const rows = tableBody.querySelectorAll('tr[data-head-id]');
        const emptyRow = tableBody.querySelector('.empty-row');

        if (rows.length === 0 && !emptyRow) {
            const row = document.createElement('tr');
            row.className = 'empty-row';
            row.innerHTML = '<td colspan="3" class="subsidiary-empty">No account head selected yet.</td>';
            tableBody.appendChild(row);
        }

        if (rows.length > 0 && emptyRow) {
            emptyRow.remove();
        }
    }

    function addHeadRow(headId) {
        const normalizedId = String(headId || '');
        if (!normalizedId) {
            return;
        }

        if (tableBody.querySelector(`tr[data-head-id="${normalizedId}"]`)) {
            return;
        }

        const head = headsById.get(normalizedId);
        if (!head) {
            return;
        }

        ensureEmptyState();

        const row = document.createElement('tr');
        row.dataset.headId = normalizedId;
        row.innerHTML = `
            <td class="text-center head-order"></td>
            <td>
                ${head.label}
                <input type="hidden" name="HeadId[]" value="${head.id}">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-head">
                    <i class="mdi mdi-delete"></i>
                </button>
            </td>
        `;

        tableBody.appendChild(row);
        refreshRowNumbers();
        ensureEmptyState();
    }

    if (window.jQuery && window.jQuery.fn.select2) {
        window.jQuery(headSelector).select2({
            width: '100%',
            placeholder: 'Type to search accounts head',
            allowClear: true
        });
    }

    addButton.addEventListener('click', function () {
        addHeadRow(headSelector.value);

        if (window.jQuery && window.jQuery.fn.select2) {
            window.jQuery(headSelector).val('').trigger('change');
        } else {
            headSelector.value = '';
        }
    });

    tableBody.addEventListener('click', function (event) {
        const removeButton = event.target.closest('.btn-remove-head');
        if (!removeButton) {
            return;
        }

        removeButton.closest('tr')?.remove();
        refreshRowNumbers();
        ensureEmptyState();
    });

    refreshRowNumbers();
    ensureEmptyState();
});
</script>
@endsection
