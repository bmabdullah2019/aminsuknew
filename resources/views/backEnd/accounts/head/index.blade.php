@extends('backEnd.layouts.master')
@section('title','Chart of Accounts')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h4 class="page-title mb-1">Chart of Accounts</h4>
                    <p class="text-muted mb-0">Tree view for creating and managing the full account hierarchy.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-soft-primary text-primary px-3 py-2">Total: {{ $summary['totalHeads'] }}</span>
                    <span class="badge bg-soft-success text-success px-3 py-2">Leaf: {{ $summary['leafHeads'] }}</span>
                    <span class="badge bg-soft-info text-info px-3 py-2">Root: {{ $summary['rootHeads'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 coa-layout">
        <div class="col-xl-4 col-lg-5">
            <div class="card coa-form-card" id="headFormCard">
                <div class="card-header border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1" id="formTitle">Add Account Head</h5>
                            <p class="text-muted mb-0 small" id="formSubtitle">Choose a parent from the tree or create a root account.</p>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddRoot">
                            <i class="mdi mdi-file-tree-outline me-1"></i> Root
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form id="headForm">
                        @csrf
                        <input type="hidden" name="HeadId" id="fHeadId">
                        <input type="hidden" name="ParentId" id="fParentId">
                        <input type="hidden" name="AccType" id="fAccType">
                        <input type="hidden" name="Label" id="fLabel">
                        <input type="hidden" name="HeadCode" id="fHeadCode">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Parent</label>
                            <select class="form-control" id="fParentSelector">
                                <option value="0">Root account</option>
                                @foreach($headOptions as $option)
                                    <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                            <div class="form-text" id="parentSelectorHelp">Search by account code or account head name to choose the parent.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">AC Head Name <span class="text-danger">*</span></label>
                            <input type="text" name="HeadName" id="fHeadName" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="Description" id="fDescription" class="form-control" rows="3" placeholder="Optional details for this account head"></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="mdi mdi-content-save-outline me-1"></i> Save
                            </button>
                            <button type="button" class="btn btn-light border" id="btnResetForm">Reset</button>
                        </div>
                    </form>

                    <div class="alert mt-3 d-none mb-0" id="formMessage"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">
            <div class="card coa-tree-card">
                <div class="card-header border-0 pb-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h5 class="mb-1">Account Tree</h5>
                            <p class="text-muted mb-0 small">Expanded hierarchy view similar to your reference image.</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <div class="search-box">
                                <input type="text" class="form-control" id="treeSearch" placeholder="Search account name or code">
                                <i class="fe-search search-icon"></i>
                            </div>
                            <button type="button" class="btn btn-light border btn-sm" id="btnExpandAll">Expand all</button>
                            <button type="button" class="btn btn-light border btn-sm" id="btnCollapseAll">Collapse all</button>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-3">
                    <div class="coa-tree-shell" id="treeShell">
                        @if($tree->isNotEmpty())
                            <ul class="account-tree" id="accountTree">
                                @foreach($tree as $head)
                                    @include('backEnd.accounts.head._tree_node', ['head' => $head])
                                @endforeach
                            </ul>
                        @else
                            <div class="text-center py-5 text-muted">
                                <i class="mdi mdi-file-tree-outline fs-1 d-block mb-2"></i>
                                No account heads found.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
<link href="{{ asset('public/backEnd/assets/libs/select2/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .coa-layout {
        align-items: flex-start;
    }

    .coa-form-card,
    .coa-tree-card {
        border: 1px solid #dbe7f3;
        box-shadow: 0 10px 30px rgba(38, 78, 118, 0.08);
    }

    .coa-form-card {
        position: sticky;
        top: 90px;
    }

    .coa-tree-shell {
        max-height: 72vh;
        overflow: auto;
        padding: 0.35rem 0.5rem 0.5rem 0.15rem;
        border: 1px solid #e3edf7;
        border-radius: 0.75rem;
        background: linear-gradient(180deg, #fcfdff 0%, #f7fbff 100%);
    }

    .account-tree,
    .account-tree ul {
        list-style: none;
        margin: 0;
        padding-left: 1.5rem;
    }

    .account-tree {
        padding-left: 0.2rem;
    }

    .tree-item {
        position: relative;
        padding: 0.15rem 0 0.15rem 0.2rem;
    }

    .tree-children {
        position: relative;
        margin-top: 0.1rem;
    }

    .tree-children::before {
        content: "";
        position: absolute;
        left: 0.45rem;
        top: -0.15rem;
        bottom: 0.8rem;
        border-left: 1px dotted #79b6e3;
    }

    .tree-children > .tree-item::before {
        content: "";
        position: absolute;
        left: -0.85rem;
        top: 1.05rem;
        width: 0.95rem;
        border-top: 1px dotted #79b6e3;
    }

    .tree-row {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        min-height: 2rem;
        padding: 0.2rem 0.5rem 0.2rem 0.15rem;
        border-radius: 0.5rem;
        transition: background-color 0.2s ease, box-shadow 0.2s ease;
    }

    .tree-row:hover,
    .tree-row.is-selected {
        background: #edf6ff;
    }

    .tree-row.is-selected {
        box-shadow: inset 0 0 0 1px #b7d8f4;
    }

    .tree-toggle,
    .tree-spacer {
        width: 1rem;
        height: 1rem;
        flex: 0 0 1rem;
    }

    .tree-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        border: 1px solid #7ab7e3;
        background: #fff;
        color: #2f7db7;
        font-size: 0.8rem;
        line-height: 1;
    }

    .tree-toggle:hover {
        background: #f2f9ff;
    }

    .tree-label {
        display: inline-flex;
        align-items: center;
        border: 0;
        background: transparent;
        padding: 0;
        color: #2d3e50;
        text-align: left;
        flex: 0 1 auto;
        min-width: 0;
    }

    .tree-text {
        display: inline-flex;
        align-items: baseline;
        gap: 0.45rem;
        min-width: 0;
        flex-wrap: wrap;
    }

    .tree-name {
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .tree-code {
        color: #7b8ea2;
        font-size: 0.75rem;
        white-space: nowrap;
    }

    .tree-actions {
        display: inline-flex;
        align-items: center;
        justify-content: flex-start;
        flex: 0 0 auto;
        margin-left: 0.15rem;
        gap: 0.35rem;
    }

    .tree-item.is-collapsed > .tree-children {
        display: none;
    }

    .tree-item.is-hidden {
        display: none;
    }

    .btn-xs {
        padding: 0.15rem 0.42rem;
        font-size: 0.75rem;
        line-height: 1.2;
    }

    .btn-soft-primary,
    .btn-soft-success,
    .btn-soft-danger {
        border: 1px solid transparent;
    }

    .btn-soft-primary {
        background: rgba(59, 130, 246, 0.12);
        color: #2563eb;
    }

    .btn-soft-success {
        background: rgba(16, 185, 129, 0.12);
        color: #059669;
    }

    .btn-soft-danger {
        background: rgba(239, 68, 68, 0.12);
        color: #dc2626;
    }

    .search-box {
        position: relative;
        min-width: 230px;
    }

    .search-box .form-control {
        padding-left: 2rem;
    }

    .search-box .search-icon {
        position: absolute;
        left: 0.7rem;
        top: 50%;
        transform: translateY(-50%);
        color: #8aa0b6;
    }

    .select2-container {
        width: 100% !important;
    }

    .select2-container .select2-selection--single {
        min-height: calc(1.5em + 0.9rem + 2px);
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        padding: 0.3rem 0.75rem;
        display: flex;
        align-items: center;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #495057;
        line-height: 1.5;
        padding-left: 0;
        padding-right: 1.5rem;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 100%;
        right: 0.5rem;
    }

    .select2-dropdown {
        border-color: #ced4da;
        box-shadow: 0 12px 30px rgba(38, 78, 118, 0.12);
    }

    .select2-search--dropdown .select2-search__field {
        border-color: #ced4da;
        border-radius: 0.375rem;
        padding: 0.45rem 0.6rem;
    }

    @media (max-width: 991.98px) {
        .coa-form-card {
            position: static;
        }

        .coa-tree-shell {
            max-height: none;
        }

        .tree-row {
            flex-wrap: wrap;
        }

        .tree-actions {
            width: 100%;
            justify-content: flex-start;
            margin-left: 1.45rem;
        }
    }
</style>
@endsection

@section('script')
<script src="{{ asset('public/backEnd/assets/libs/select2/js/select2.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name="_token"]')?.value;
    const tree = document.getElementById('accountTree');
    const treeShell = document.getElementById('treeShell');
    const form = document.getElementById('headForm');
    const message = document.getElementById('formMessage');
    const parentSelector = document.getElementById('fParentSelector');
    const submitButton = form.querySelector('button[type="submit"]');
    const initialUrl = new URL(window.location.href);
    const focusHeadId = Number(initialUrl.searchParams.get('focus_head') || 0);
    const focusParentId = Number(initialUrl.searchParams.get('focus_parent') || 0);
    const shouldShowSavedMessage = initialUrl.searchParams.get('saved') === '1';
    let selectedRow = null;
    let syncingParentSelector = false;

    const formFields = {
        headId: document.getElementById('fHeadId'),
        parentId: document.getElementById('fParentId'),
        accType: document.getElementById('fAccType'),
        label: document.getElementById('fLabel'),
        parentSelector: parentSelector,
        parentSelectorHelp: document.getElementById('parentSelectorHelp'),
        headCode: document.getElementById('fHeadCode'),
        headName: document.getElementById('fHeadName'),
        description: document.getElementById('fDescription'),
        title: document.getElementById('formTitle'),
        subtitle: document.getElementById('formSubtitle')
    };

    function showMessage(type, text) {
        message.className = `alert alert-${type} mt-3 mb-0`;
        message.textContent = text;
        message.classList.remove('d-none');
    }

    function clearMessage() {
        message.className = 'alert mt-3 d-none mb-0';
        message.textContent = '';
    }

    function setSubmittingState(submitting) {
        if (!submitButton) {
            return;
        }

        submitButton.disabled = submitting;
        submitButton.innerHTML = submitting
            ? '<i class="mdi mdi-loading mdi-spin me-1"></i> Saving...'
            : '<i class="mdi mdi-content-save-outline me-1"></i> Save';
    }

    function markSelected(row) {
        if (selectedRow) {
            selectedRow.classList.remove('is-selected');
        }

        selectedRow = row;

        if (selectedRow) {
            selectedRow.classList.add('is-selected');
        }
    }

    function setParentSelectorValue(parentId) {
        const normalizedParentId = String(parentId ?? 0);
        syncingParentSelector = true;
        formFields.parentSelector.value = normalizedParentId;

        if (window.jQuery && window.jQuery.fn.select2) {
            window.jQuery(formFields.parentSelector).trigger('change.select2');
        }

        window.setTimeout(function () {
            syncingParentSelector = false;
        }, 0);
    }

    function setParentSelectorDisabled(disabled) {
        formFields.parentSelector.disabled = disabled;

        if (window.jQuery && window.jQuery.fn.select2) {
            window.jQuery(formFields.parentSelector)
                .prop('disabled', disabled)
                .trigger('change.select2');
        }
    }

    function revealTreeRow(row) {
        if (!row) {
            return;
        }

        let item = row.closest('.tree-item');

        while (item) {
            item.classList.remove('is-hidden');

            const parent = item.parentElement ? item.parentElement.closest('.tree-item') : null;
            if (parent) {
                openBranch(parent, true);
            }

            item = parent;
        }
    }

    function focusTreeRow(headId) {
        if (!headId) {
            return;
        }

        const row = document.querySelector(`.tree-row[data-head-id="${headId}"]`);
        if (!row) {
            return;
        }

        revealTreeRow(row);
        markSelected(row);
        row.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    }

    function clearTransientQueryParams() {
        const nextUrl = new URL(window.location.href);
        let changed = false;

        ['refresh', 'focus_head', 'focus_parent', 'saved'].forEach(param => {
            if (nextUrl.searchParams.has(param)) {
                nextUrl.searchParams.delete(param);
                changed = true;
            }
        });

        if (changed) {
            window.history.replaceState({}, '', nextUrl.toString());
        }
    }

    function setFormMode(mode, payload) {
        clearMessage();

        if (mode === 'edit') {
            formFields.title.textContent = 'Edit Account Head';
            formFields.subtitle.textContent = 'Update the selected account head.';
            formFields.headId.value = payload.headId;
            formFields.parentId.value = payload.parentId;
            formFields.accType.value = payload.accType;
            formFields.label.value = payload.label;
            setParentSelectorValue(payload.parentId);
            setParentSelectorDisabled(true);
            formFields.parentSelectorHelp.textContent = 'Parent selection is locked while editing this account head.';
            formFields.headCode.value = payload.headCode;
            formFields.headName.value = payload.headName;
            formFields.description.value = payload.description || '';
            formFields.headName.focus();
            return;
        }

        formFields.title.textContent = 'Add Account Head';
        formFields.subtitle.textContent = payload.parentId === 0
            ? 'Create a new root account.'
            : 'Create a child account under the selected branch.';

        formFields.headId.value = '';
        formFields.parentId.value = payload.parentId;
        formFields.accType.value = payload.accType;
        formFields.label.value = payload.label;
        setParentSelectorValue(payload.parentId);
        setParentSelectorDisabled(false);
        formFields.parentSelectorHelp.textContent = 'Search by account code or account head name to choose the parent.';
        formFields.headCode.value = payload.headCode;
        formFields.headName.value = '';
        formFields.description.value = '';
        formFields.headName.focus();
    }

    function fetchJson(url, options = {}) {
        const headers = {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(options.headers || {})
        };

        return fetch(url, {
            ...options,
            headers
        }).then(async response => {
            const contentType = response.headers.get('content-type') || '';
            const isJson = contentType.includes('application/json');
            const body = isJson ? await response.json() : null;
            const text = isJson ? '' : (await response.text()).trim();

            if (!response.ok) {
                if (body?.message) {
                    throw new Error(body.message);
                }

                if (body?.errors) {
                    const firstError = Object.values(body.errors)[0];
                    throw new Error(Array.isArray(firstError) ? firstError[0] : 'Validation failed.');
                }

                if (text) {
                    throw new Error(text.slice(0, 200));
                }

                throw new Error('Request failed. Please try again.');
            }

            return body;
        });
    }

    function loadNewCodeForm(parentId) {
        formFields.parentId.value = parentId;

        return fetchJson(`{{ url('admin/accounts/getNewCode') }}/${parentId}`)
            .then(data => {
                if (!data) {
                    throw new Error('Server returned an invalid response. Please check your session.');
                }
                const row = parentId > 0
                    ? tree?.querySelector(`.tree-row[data-head-id="${parentId}"]`)
                    : null;

                if (row) {
                    revealTreeRow(row);
                    markSelected(row);
                } else if (parentId === 0) {
                    markSelected(null);
                }

                setFormMode('create', {
                    parentId: parentId,
                    accType: data.accType,
                    label: data.label,
                    parentName: data.breadcrumb || 'Root',
                    headCode: data.code
                });
            })
            .catch(error => showMessage('danger', error.message));
    }

    function openBranch(item, expanded) {
        item.classList.toggle('is-collapsed', !expanded);
        const toggle = item.querySelector(':scope > .tree-row .tree-toggle .toggle-icon');
        if (toggle) {
            toggle.textContent = expanded ? '-' : '+';
        }
    }

    function createTreeToggle() {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'tree-toggle is-open';
        button.setAttribute('aria-label', 'Collapse branch');

        const icon = document.createElement('span');
        icon.className = 'toggle-icon';
        icon.textContent = '-';
        button.appendChild(icon);

        return button;
    }

    function createTreeItem(head) {
        const item = document.createElement('li');
        item.className = 'tree-item';
        item.dataset.nodeId = String(head.headId);

        const row = document.createElement('div');
        row.className = 'tree-row';
        row.dataset.headId = String(head.headId);
        row.dataset.parentId = String(head.parentId);
        row.dataset.accType = String(head.accType);
        row.dataset.headCode = head.headCode;
        row.dataset.headName = head.headName;
        row.dataset.label = String(head.label);
        row.dataset.description = head.description || '';
        row.dataset.parentName = head.parentName || '';
        row.dataset.hasChild = head.hasChild ? '1' : '0';

        const spacer = document.createElement('span');
        spacer.className = 'tree-spacer';
        row.appendChild(spacer);

        const labelButton = document.createElement('button');
        labelButton.type = 'button';
        labelButton.className = 'tree-label';

        const textWrap = document.createElement('span');
        textWrap.className = 'tree-text';

        const nameSpan = document.createElement('span');
        nameSpan.className = 'tree-name';
        nameSpan.textContent = head.headName;

        const codeSpan = document.createElement('span');
        codeSpan.className = 'tree-code';
        codeSpan.textContent = head.headCode;

        textWrap.appendChild(nameSpan);
        textWrap.appendChild(codeSpan);
        labelButton.appendChild(textWrap);
        row.appendChild(labelButton);

        const actions = document.createElement('div');
        actions.className = 'tree-actions';

        const editButton = document.createElement('button');
        editButton.type = 'button';
        editButton.className = 'btn btn-xs btn-soft-primary btn-edit-head';
        editButton.title = 'Edit account';
        editButton.innerHTML = '<i class="mdi mdi-pencil"></i>';

        const addButton = document.createElement('button');
        addButton.type = 'button';
        addButton.className = 'btn btn-xs btn-soft-success btn-add-child';
        addButton.title = 'Add child';
        addButton.dataset.parentId = String(head.headId);
        addButton.innerHTML = '<i class="mdi mdi-plus"></i>';

        actions.appendChild(editButton);
        actions.appendChild(addButton);

        if (head.parentId > 0) {
            const deleteButton = document.createElement('button');
            deleteButton.type = 'button';
            deleteButton.className = 'btn btn-xs btn-soft-danger btn-delete-head';
            deleteButton.title = 'Delete account';
            deleteButton.dataset.headId = String(head.headId);
            deleteButton.innerHTML = '<i class="mdi mdi-delete"></i>';
            actions.appendChild(deleteButton);
        }

        row.appendChild(actions);
        item.appendChild(row);

        return item;
    }

    function ensureTreeRoot() {
        let treeRoot = document.getElementById('accountTree');

        if (!treeRoot) {
            treeShell.innerHTML = '';
            treeRoot = document.createElement('ul');
            treeRoot.className = 'account-tree';
            treeRoot.id = 'accountTree';
            treeShell.appendChild(treeRoot);
        }

        return treeRoot;
    }

    function ensureParentBranch(parentItem, parentRow) {
        let childrenList = parentItem.querySelector(':scope > .tree-children');
        if (!childrenList) {
            childrenList = document.createElement('ul');
            childrenList.className = 'tree-children';
            parentItem.appendChild(childrenList);
        }

        const spacer = parentRow.querySelector(':scope > .tree-spacer');
        if (spacer) {
            spacer.replaceWith(createTreeToggle());
        }

        parentRow.dataset.hasChild = '1';
        openBranch(parentItem, true);

        return childrenList;
    }

    function upsertTreeNode(head) {
        const existingRow = document.querySelector(`.tree-row[data-head-id="${head.headId}"]`);

        if (existingRow) {
            existingRow.dataset.parentId = String(head.parentId);
            existingRow.dataset.accType = String(head.accType);
            existingRow.dataset.headCode = head.headCode;
            existingRow.dataset.headName = head.headName;
            existingRow.dataset.label = String(head.label);
            existingRow.dataset.description = head.description || '';
            existingRow.dataset.parentName = head.parentName || '';
            existingRow.querySelector('.tree-name').textContent = head.headName;
            existingRow.querySelector('.tree-code').textContent = head.headCode;
            return existingRow;
        }

        const newItem = createTreeItem(head);
        const treeRoot = ensureTreeRoot();

        if (head.parentId > 0) {
            const parentRow = document.querySelector(`.tree-row[data-head-id="${head.parentId}"]`);
            if (parentRow) {
                const parentItem = parentRow.closest('.tree-item');
                const childrenList = ensureParentBranch(parentItem, parentRow);
                childrenList.appendChild(newItem);
                const insertedRow = newItem.querySelector('.tree-row');
                revealTreeRow(insertedRow);
                markSelected(insertedRow);
                return insertedRow;
            }
        }

        treeRoot.appendChild(newItem);
        const insertedRow = newItem.querySelector('.tree-row');
        markSelected(insertedRow);
        return insertedRow;
    }

    function setAllBranches(expanded) {
        tree?.querySelectorAll('.tree-item').forEach(item => {
            if (item.querySelector(':scope > .tree-children')) {
                openBranch(item, expanded);
            }
        });
    }

    function filterTree(term) {
        if (!tree) {
            return;
        }

        const query = term.trim().toLowerCase();
        const items = Array.from(tree.querySelectorAll('.tree-item'));

        if (!query) {
            items.forEach(item => item.classList.remove('is-hidden'));
            return;
        }

        items.reverse().forEach(item => {
            const row = item.querySelector(':scope > .tree-row');
            const text = `${row.dataset.headName} ${row.dataset.headCode}`.toLowerCase();
            const childMatches = Array.from(item.querySelectorAll(':scope > .tree-children > .tree-item'))
                .some(child => !child.classList.contains('is-hidden'));
            const isMatch = text.includes(query) || childMatches;

            item.classList.toggle('is-hidden', !isMatch);

            if (isMatch) {
                let parent = item.parentElement.closest('.tree-item');
                while (parent) {
                    parent.classList.remove('is-hidden');
                    openBranch(parent, true);
                    parent = parent.parentElement.closest('.tree-item');
                }
            }
        });
    }

    if (tree) {
        tree.addEventListener('click', function (event) {
            const toggleButton = event.target.closest('.tree-toggle');
            const addButton = event.target.closest('.btn-add-child');
            const editButton = event.target.closest('.btn-edit-head');
            const deleteButton = event.target.closest('.btn-delete-head');
            const labelButton = event.target.closest('.tree-label');

            if (toggleButton) {
                const item = toggleButton.closest('.tree-item');
                openBranch(item, item.classList.contains('is-collapsed'));
                return;
            }

            if (addButton) {
                loadNewCodeForm(Number(addButton.dataset.parentId));
                return;
            }

            if (editButton) {
                const row = editButton.closest('.tree-row');
                revealTreeRow(row);
                markSelected(row);
                setFormMode('edit', {
                    headId: row.dataset.headId,
                    parentId: row.dataset.parentId,
                    accType: row.dataset.accType,
                    label: row.dataset.label,
                    parentName: row.dataset.parentName || 'Root',
                    headCode: row.dataset.headCode,
                    headName: row.dataset.headName,
                    description: row.dataset.description || ''
                });
                return;
            }

            if (deleteButton) {
                const headId = deleteButton.dataset.headId;
                if (!window.confirm('Delete this account head?')) {
                    return;
                }

                fetchJson("{{ route('admin.accounts.destroy') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ HeadId: headId })
                })
                .then(response => {
                    if (!response) {
                        throw new Error('Server returned an invalid response. Please check your session.');
                    }
                    showMessage(response.hasError ? 'danger' : 'success', response.message);
                    if (!response.hasError) {
                        window.setTimeout(() => window.location.reload(), 650);
                    }
                })
                .catch(error => showMessage('danger', error.message));
                return;
            }

            if (labelButton) {
                const row = labelButton.closest('.tree-row');
                revealTreeRow(row);
                markSelected(row);
                loadNewCodeForm(Number(row.dataset.headId));
            }
        });
    }

    if (window.jQuery && window.jQuery.fn.select2) {
        window.jQuery(parentSelector).select2({
            width: '100%',
            placeholder: 'Select parent account',
            allowClear: false
        });
    }

    parentSelector.addEventListener('change', function (event) {
        if (syncingParentSelector || formFields.parentSelector.disabled) {
            return;
        }

        loadNewCodeForm(Number(event.target.value || 0));
    });

    if (window.jQuery && window.jQuery.fn.select2) {
        window.jQuery(parentSelector).on('change', function () {
            if (syncingParentSelector || formFields.parentSelector.disabled) {
                return;
            }

            loadNewCodeForm(Number(this.value || 0));
        });
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        clearMessage();
        setSubmittingState(true);

        const formData = new FormData(form);
        const payload = {};
        formData.forEach((value, key) => {
            payload[key] = value;
        });

        if (!formFields.parentSelector.disabled) {
            payload.ParentId = String(parentSelector.value || 0);
            formFields.parentId.value = payload.ParentId;
        }

        fetchJson("{{ route('admin.accounts.store') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(payload)
        })
        .then(response => {
            if (!response) {
                throw new Error('Server returned an invalid response. Please check your session.');
            }
            showMessage(response.hasError ? 'danger' : 'success', response.message);
            if (!response.hasError) {
                window.setTimeout(() => {
                    const nextUrl = new URL(window.location.href);
                    nextUrl.searchParams.set('refresh', String(Date.now()));
                    if (response.head?.headId) {
                        nextUrl.searchParams.set('focus_head', String(response.head.headId));
                    }
                    if (response.head?.parentId !== undefined) {
                        nextUrl.searchParams.set('focus_parent', String(response.head.parentId));
                    }
                    nextUrl.searchParams.set('saved', '1');
                    window.location.href = nextUrl.toString();
                }, 300);
            }
        })
        .catch(error => showMessage('danger', error.message))
        .finally(() => {
            setSubmittingState(false);
        });
    });

    document.getElementById('btnResetForm').addEventListener('click', function () {
        markSelected(null);
        form.reset();
        loadNewCodeForm(0);
    });

    document.getElementById('btnAddRoot').addEventListener('click', function () {
        markSelected(null);
        loadNewCodeForm(0);
    });

    document.getElementById('btnExpandAll').addEventListener('click', function () {
        setAllBranches(true);
    });

    document.getElementById('btnCollapseAll').addEventListener('click', function () {
        setAllBranches(false);
    });

    document.getElementById('treeSearch').addEventListener('input', function (event) {
        filterTree(event.target.value);
    });

    loadNewCodeForm(focusParentId || 0).then(() => {
        if (focusHeadId) {
            window.setTimeout(() => focusTreeRow(focusHeadId), 120);
        } else if (focusParentId) {
            window.setTimeout(() => focusTreeRow(focusParentId), 120);
        }

        if (shouldShowSavedMessage) {
            showMessage('success', 'Account head saved successfully.');
        }

        clearTransientQueryParams();
    });
});
</script>
@endsection
