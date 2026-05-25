@extends('backEnd.layouts.master')

@section('content')
<!-- Inventory View -->
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center inventory-page-header">
                <h1 class="mb-0">📦 Inventory Management</h1>
                <div class="inventory-header-actions">
                    <a href="{{ route('admin.inventory.add-stock') }}" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add Stock
                    </a>
                    <a href="{{ route('admin.inventory.adjust-stock') }}" class="btn btn-warning">
                        <i class="fas fa-sync"></i> Adjust Stock
                    </a>
                    <a href="{{ route('admin.inventory.transfer-stock') }}" class="btn btn-info">
                        <i class="fas fa-exchange-alt"></i> Transfer Stock
                    </a>
                    <a href="{{ route('admin.inventory.history') }}" class="btn btn-secondary">
                        <i class="fas fa-history"></i> History
                    </a>
                </div>
            </div>
            <hr>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="inventory-filter-wrap">
                <form method="GET" action="{{ route('admin.inventory.index') }}" class="inventory-filter-form">
                    <input type="text" name="search" class="form-control" placeholder="Search by name or SKU"
                        value="{{ request('search') }}">

                    <select name="warehouse_id" class="form-control">
                        <option value="">All Warehouses</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected((string) request('warehouse_id') === (string) $warehouse->id)>
                                {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="stock_status" class="form-control">
                        <option value="">All Stock Status</option>
                        <option value="in_stock" @selected(request('stock_status') === 'in_stock')>In Stock</option>
                        <option value="low_stock" @selected(request('stock_status') === 'low_stock')>Low Stock</option>
                        <option value="out_of_stock" @selected(request('stock_status') === 'out_of_stock')>Out of Stock</option>
                    </select>

                    <div class="inventory-filter-buttons">
                        <button type="submit" class="btn btn-primary rounded-pill">Search</button>
                        <a href="{{ route('admin.inventory.index') }}" class="btn btn-secondary rounded-pill">Reset</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Inventory Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive inventory-table-wrap report-sticky-container">
                        @php
                            $totPhysical = 0;
                            $totAvailable = 0;
                            $totReserved = 0;
                        @endphp
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th class="sticky-col-left-1">Image</th>
                                    <th class="sticky-col-left-2">Product</th>
                                    <th>SKU</th>
                                    <th class="inventory-col-warehouse">Warehouse</th>
                                    <th class="inventory-col-physical">Physical Stock</th>
                                    <th>Available Stock</th>
                                    <th class="inventory-col-reserved">Reserved</th>
                                    <th class="inventory-col-reorder">Reorder Point</th>
                                    <th>Status</th>
                                    <th class="inventory-actions-header sticky-col-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stocks as $stock)
                                    @php
                                        $product = $stock->product;
                                        $imgPath = $product && $product->thumbnail
                                            ? 'storage/'.$product->thumbnail
                                            : (optional($product?->image)->image ?? 'public/backEnd/img/no-image.png');
                                        if (!\Illuminate\Support\Str::startsWith($imgPath, ['http', 'storage', 'public'])) {
                                            $imgPath = 'storage/'.$imgPath;
                                        }

                                        $totPhysical += (float) ($stock->physical_quantity ?? 0);
                                        $totAvailable += (float) ($stock->available_quantity ?? 0);
                                        $totReserved += (float) ($stock->reserved_quantity ?? 0);
                                    @endphp
                                    <tr>
                                        <td class="sticky-col-left-1">
                                            <div class="inventory-img-container">
                                                <img src="{{ asset($imgPath) }}"
                                                     alt="{{ $product?->name }}"
                                                     class="inventory-product-img shadow-sm"
                                                     onerror="this.src='{{ asset('public/backEnd/img/no-image.png') }}'">
                                            </div>
                                        </td>
                                        <td class="sticky-col-left-2">
                                            <strong>{{ $product?->name }}</strong>
                                        </td>
                                        <td>
                                            <small class="badge bg-primary text-white">{{ $product?->sku }}</small>
                                        </td>
                                        <td class="inventory-col-warehouse">{{ optional($stock->warehouse)->name }}</td>
                                        <td class="inventory-col-physical">
                                            <span class="badge bg-info text-dark">{{ $stock->physical_quantity }}</span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $stock->available_quantity > 0 ? 'bg-success text-white' : 'bg-danger text-white' }}">
                                                {{ $stock->available_quantity }}
                                            </span>
                                        </td>
                                        <td class="inventory-col-reserved">
                                            <span class="badge bg-warning text-dark">{{ $stock->reserved_quantity }}</span>
                                        </td>
                                        <td class="inventory-col-reorder">
                                            <small>{{ $stock->reorder_point }}</small>
                                        </td>
                                        <td>
                                            @if ($stock->available_quantity <= 0)
                                                <span class="badge bg-danger text-white">Out of Stock</span>
                                            @elseif ($stock->available_quantity <= $stock->reorder_point)
                                                <span class="badge bg-warning text-dark">Low Stock</span>
                                            @else
                                                <span class="badge bg-success text-white">In Stock</span>
                                            @endif
                                        </td>
                                        <td class="inventory-actions-cell sticky-col-right">
                                            <button class="btn btn-sm btn-primary"
                                                onclick="quickAdjust({{ $stock->product_id }}, {{ $stock->warehouse_id }})">
                                                <i class="fas fa-edit"></i> Quick Edit
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center text-muted">
                                            No products found matching your search.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end fw-bold sticky-col-footer">
                                        Totals
                                    </td>
                                    <td class="fw-bold sticky-col-footer">
                                        {{ number_format($totPhysical, 2) }}
                                    </td>
                                    <td class="fw-bold sticky-col-footer">
                                        {{ number_format($totAvailable, 2) }}
                                    </td>
                                    <td class="fw-bold sticky-col-footer">
                                        {{ number_format($totReserved, 2) }}
                                    </td>
                                    <td class="inventory-col-reorder sticky-col-footer"></td>
                                    <td class="sticky-col-footer"></td>
                                    <td class="inventory-actions-header sticky-col-right sticky-col-footer"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Pagination (disabled - one page mode) -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Adjust Modal -->
<div class="modal fade" id="quickAdjustModal" tabindex="-1" role="dialog" aria-labelledby="quickAdjustModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="quickAdjustModalLabel">📝 Quick Adjust Stock</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close" onclick="closeQuickAdjustModal()"></button>
            </div>
            <div class="modal-body">
                <form id="quickAdjustForm" method="POST">
                    @csrf
                    <input type="hidden" name="product_id" id="modalProductId">
                    <input type="hidden" name="warehouse_id" id="modalWarehouseId">

                    <div class="form-group">
                        <label for="productName" class="font-weight-bold">Product</label>
                        <input type="text" class="form-control" id="productName" readonly>
                    </div>

                    <div class="form-group">
                        <label for="warehouseName" class="font-weight-bold">Warehouse</label>
                        <input type="text" class="form-control" id="warehouseName" readonly>
                    </div>

                    <div class="form-group" id="variantGroup" style="display:none;">
                        <label class="font-weight-bold">Variant Attributes</label>
                        <div class="row">
                            <div class="col-md-4">
                                <label for="variantColor" class="small">Color</label>
                                <select class="form-control" id="variantColor">
                                    <option value="">-- No Color --</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="variantSize" class="small">Size</label>
                                <select class="form-control" id="variantSize">
                                    <option value="">-- No Size --</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="variantAge" class="small">Age</label>
                                <select class="form-control" id="variantAge">
                                    <option value="">-- No Age --</option>
                                </select>
                            </div>
                        </div>
                        <input type="hidden" name="variant_id" id="variantId">
                        <small class="form-text text-muted" id="variantHint">Select color/size/age combination if applicable</small>
                    </div>

                    <div class="form-group">
                        <label for="currentPhysical" class="font-weight-bold">Current Physical Quantity</label>
                        <input type="number" class="form-control" id="currentPhysical" readonly>
                    </div>

                    <div class="form-group">
                        <label for="adjustmentQuantity" class="font-weight-bold">Adjustment Quantity</label>
                        <input type="number" class="form-control" id="adjustmentQuantity" name="adjustment_quantity" 
                               placeholder="Enter quantity (positive or negative)" required>
                        <small class="form-text text-muted">
                            Use positive numbers to add stock, negative numbers to remove stock
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="reason" class="font-weight-bold">Reason for Adjustment</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" 
                                  placeholder="e.g., Stock count discrepancy, damaged items, etc." required></textarea>
                    </div>

                    <div class="alert alert-info" role="alert">
                        <strong>New Quantity will be:</strong> <span id="newQuantityPreview">0</span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal" onclick="closeQuickAdjustModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitQuickAdjust()">
                    <i class="fas fa-check"></i> Apply Adjustment
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Sticky header + footer refinements */
    .inventory-table-wrap table {
        border-collapse: separate;
        border-spacing: 0;
    }

    .report-sticky-container {
        max-height: 75vh;
        border-radius: 0; /* Align with wc-admin-shell square buttons */
    }

    /* Horizontal Sticky Columns */
    .sticky-col-left-1 {
        position: sticky !important;
        left: 0;
        z-index: 10;
        background: #fff !important;
        border-right: 1px solid #dee2e6 !important;
    }

    .sticky-col-left-2 {
        position: sticky !important;
        left: 56px; /* Image column (40px) + padding (~16px) */
        z-index: 10;
        background: #fff !important;
        border-right: 2px solid #dee2e6 !important;
    }

    .sticky-col-right {
        position: sticky !important;
        right: 0;
        z-index: 10;
        background: #fff !important;
        border-left: 2px solid #dee2e6 !important;
        box-shadow: -5px 0 5px -5px rgba(0,0,0,0.15);
    }

    /* Force dark header with white text for all header cells */
    .inventory-table-wrap .table thead.table-dark th {
        background: #212529 !important;
        color: #ffffff !important;
        border-bottom-color: #444 !important;
    }

    /* Ensure sticky headers of sticky columns stay on top of everything */
    thead th.sticky-col-left-1,
    thead th.sticky-col-left-2,
    thead th.sticky-col-right {
        z-index: 30 !important;
        background: #212529 !important;
        color: #ffffff !important;
    }

    /* Sticky Footer Refinement - Specificity to override global light backgrounds */
    .report-sticky-container tfoot td.sticky-col-footer {
        position: sticky !important;
        bottom: 0 !important;
        z-index: 25 !important;
        background-color: #212529 !important;
        color: #ffffff !important;
        border-top: 2px solid #444 !important;
    }

    /* Ensure horizontal sticky columns in footer also have dark background */
    .report-sticky-container tfoot td.sticky-col-footer.sticky-col-left-1,
    .report-sticky-container tfoot td.sticky-col-footer.sticky-col-left-2,
    .report-sticky-container tfoot td.sticky-col-footer.sticky-col-right {
        z-index: 35 !important;
        background-color: #212529 !important;
        color: #ffffff !important;
    }

    /* Opaque background for striped rows in sticky columns */
    .table-striped tbody tr:nth-of-type(odd) .sticky-col-left-1,
    .table-striped tbody tr:nth-of-type(odd) .sticky-col-left-2,
    .table-striped tbody tr:nth-of-type(odd) .sticky-col-right {
        background: #f8f9fa !important;
    }

    .inventory-img-container {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        background: #fff;
        border-radius: 4px;
        border: 1px solid #dee2e6;
    }

    .inventory-product-img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .inventory-table-wrap .table tbody td,
    .inventory-table-wrap .table tbody td small {
        color: #212529 !important;
    }

    .inventory-table-wrap .badge {
        font-weight: 600;
    }

    .table-striped tbody tr:nth-of-type(odd) .inventory-actions-cell {
        background: rgba(0, 0, 0, 0.02);
    }

    .inventory-page-header {
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .inventory-header-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .inventory-filter-wrap {
        overflow-x: auto;
        padding-bottom: 4px;
        margin-bottom: 1rem;
    }

    .inventory-filter-form {
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: nowrap !important;
        align-items: center;
        gap: .5rem;
        width: 100%;
    }

    .inventory-filter-form .form-control {
        width: auto;
        min-width: 180px;
        flex: 1 1 auto;
    }

    .inventory-filter-form input[name="search"] {
        flex: 2 1 auto;
        min-width: 260px;
    }

    .inventory-filter-buttons {
        display: flex;
        align-items: center;
        gap: .5rem;
        flex: 0 0 auto;
    }

    @media (max-width: 991.98px) {
        .inventory-filter-form {
            flex-wrap: nowrap;
            overflow-x: auto;
            padding-bottom: 8px;
        }

        .inventory-filter-form .form-control {
            min-width: 140px;
            flex: 0 0 auto;
        }

        .inventory-filter-form input[name="search"] {
            min-width: 180px;
        }

        .inventory-filter-buttons {
            width: auto;
            flex-wrap: nowrap;
            display: flex;
            gap: .5rem;
        }

        .inventory-filter-buttons .btn {
            flex: 0 0 auto;
            width: auto;
            margin-left: 0 !important;
        }

        .inventory-col-warehouse,
        .inventory-col-physical,
        .inventory-col-reserved,
        .inventory-col-reorder {
            display: none;
        }
    }

    @media (max-width: 575.98px) {
        .inventory-header-actions {
            width: 100%;
        }

        .inventory-header-actions .btn,
        .inventory-filter-buttons .btn,
        .inventory-actions-cell .btn {
            width: 100%;
        }

        .inventory-actions-header,
        .inventory-actions-cell {
            min-width: 112px;
        }
    }
</style>

<script>
    const stockEndpoint = '{{ route('admin.inventory.get-product-stock') }}';
    const variantsEndpoint = '{{ route('admin.inventory.api.product-variants') }}';
    const quickAdjustEndpoint = '{{ route('admin.inventory.quick-adjust') }}';
    const quickAdjustModal = document.getElementById('quickAdjustModal');
    const quickAdjustForm = document.getElementById('quickAdjustForm');
    const adjustmentInput = document.getElementById('adjustmentQuantity');
    const currentPhysicalInput = document.getElementById('currentPhysical');
    const reasonInput = document.getElementById('reason');
    const variantGroup = document.getElementById('variantGroup');
    const variantColorSelect = document.getElementById('variantColor');
    const variantSizeSelect = document.getElementById('variantSize');
    const variantAgeSelect = document.getElementById('variantAge');
    const variantIdInput = document.getElementById('variantId');
    const variantHint = document.getElementById('variantHint');
    const previewAlert = quickAdjustForm.querySelector('.alert');
    let modalVariants = [];
    let modalHasColor = false;
    let modalHasSize = false;
    let modalHasAge = false;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function toVariantValue(value) {
        return String(value ?? '').trim();
    }

    function setSelectOptions(selectEl, placeholder, values, selectedValue = '', autoSelectSingle = false) {
        const options = [`<option value="">${escapeHtml(placeholder)}</option>`];
        values.forEach((value) => {
            options.push(`<option value="${escapeHtml(value)}">${escapeHtml(value)}</option>`);
        });

        selectEl.innerHTML = options.join('');
        const selected = toVariantValue(selectedValue);
        if (selected && values.includes(selected)) {
            selectEl.value = selected;
        } else if (autoSelectSingle && values.length === 1) {
            selectEl.value = values[0];
        } else {
            selectEl.value = '';
        }
    }

    function renderPreview(currentValue, adjustmentValue) {
        const current = Number(currentValue || 0);
        const adjustment = Number(adjustmentValue || 0);
        const next = current + adjustment;
        const formattedNext = next.toFixed(2);

        if (next < 0) {
            previewAlert.classList.remove('alert-info');
            previewAlert.classList.add('alert-danger');
            previewAlert.innerHTML = `<strong>Warning:</strong> New Quantity will be <strong>${formattedNext}</strong> (negative stock)`;
            return;
        }

        previewAlert.classList.remove('alert-danger');
        previewAlert.classList.add('alert-info');
        previewAlert.innerHTML = `<strong>New Quantity will be:</strong> <span id="newQuantityPreview">${formattedNext}</span>`;
    }

    function setVariantHint(text, isError = false) {
        variantHint.classList.toggle('text-muted', !isError);
        variantHint.classList.toggle('text-danger', isError);
        variantHint.textContent = text;
    }

    function resolveWarehouseName(warehouseId) {
        const warehouseFilter = document.querySelector('select[name="warehouse_id"]');
        if (!warehouseFilter) {
            return '';
        }

        const option = Array.from(warehouseFilter.options || []).find((opt) => String(opt.value) === String(warehouseId));
        return option ? option.textContent.trim() : '';
    }

    function resetVariantSelector() {
        variantGroup.style.display = 'none';
        modalVariants = [];
        modalHasColor = false;
        modalHasSize = false;
        modalHasAge = false;
        setSelectOptions(variantColorSelect, '-- No Color --', []);
        setSelectOptions(variantSizeSelect, '-- No Size --', []);
        setSelectOptions(variantAgeSelect, '-- No Age --', []);
        variantColorSelect.disabled = true;
        variantSizeSelect.disabled = true;
        variantAgeSelect.disabled = true;
        variantIdInput.value = '';
        setVariantHint('Select color/size/age combination if applicable');
    }

    function setLoadingVariantState() {
        variantGroup.style.display = 'block';
        setSelectOptions(variantColorSelect, 'Loading colors...', []);
        setSelectOptions(variantSizeSelect, 'Loading sizes...', []);
        setSelectOptions(variantAgeSelect, 'Loading ages...', []);
        variantColorSelect.disabled = true;
        variantSizeSelect.disabled = true;
        variantAgeSelect.disabled = true;
        variantIdInput.value = '';
        setVariantHint('Loading variants...');
    }

    function uniqueSorted(values) {
        return Array.from(new Set(values.filter((value) => value !== ''))).sort((a, b) => a.localeCompare(b));
    }

    function buildVariantOptions(variants, key, selectedValues) {
        const filtered = variants.filter((variant) => {
            return Object.keys(selectedValues).every((selectedKey) => {
                if (selectedKey === key) {
                    return true;
                }

                const selectedValue = toVariantValue(selectedValues[selectedKey]);
                if (selectedValue === '') {
                    return true;
                }

                return toVariantValue(variant[selectedKey]) === selectedValue;
            });
        });

        return uniqueSorted(filtered.map((variant) => toVariantValue(variant[key])));
    }

    function variantMatchesSelection(variant, selectedValues, hasColor, hasSize, hasAge) {
        if (hasColor && selectedValues.color !== '' && toVariantValue(variant.color) !== selectedValues.color) {
            return false;
        }

        if (hasSize && selectedValues.size !== '' && toVariantValue(variant.size) !== selectedValues.size) {
            return false;
        }

        if (hasAge && selectedValues.age !== '' && toVariantValue(variant.age) !== selectedValues.age) {
            return false;
        }

        return true;
    }

    function syncModalVariantSelection(changedField = null) {
        if (modalVariants.length === 0) {
            variantIdInput.value = '';
            return;
        }

        const selectedValues = {
            color: modalHasColor ? toVariantValue(variantColorSelect.value) : '',
            size: modalHasSize ? toVariantValue(variantSizeSelect.value) : '',
            age: modalHasAge ? toVariantValue(variantAgeSelect.value) : ''
        };

        const selectors = [
            { key: 'color', enabled: modalHasColor, element: variantColorSelect, selectLabel: '-- Select Color --', noneLabel: '-- No Color --' },
            { key: 'size', enabled: modalHasSize, element: variantSizeSelect, selectLabel: '-- Select Size --', noneLabel: '-- No Size --' },
            { key: 'age', enabled: modalHasAge, element: variantAgeSelect, selectLabel: '-- Select Age --', noneLabel: '-- No Age --' },
        ];

        selectors.forEach((selector) => {
            if (!selector.enabled) {
                setSelectOptions(selector.element, selector.noneLabel, []);
                selector.element.disabled = true;
                selectedValues[selector.key] = '';
                return;
            }

            const filterValues = { ...selectedValues };
            if (changedField && selector.key === changedField) {
                Object.keys(filterValues).forEach((key) => {
                    if (key !== changedField) {
                        filterValues[key] = '';
                    }
                });
            }

            const options = buildVariantOptions(modalVariants, selector.key, filterValues);
            setSelectOptions(selector.element, selector.selectLabel, options, selectedValues[selector.key], true);
            selector.element.disabled = false;
            selectedValues[selector.key] = toVariantValue(selector.element.value);
        });

        const matches = modalVariants.filter((variant) =>
            variantMatchesSelection(variant, selectedValues, modalHasColor, modalHasSize, modalHasAge)
        );

        const fullySpecified = (!modalHasColor || selectedValues.color !== '')
            && (!modalHasSize || selectedValues.size !== '')
            && (!modalHasAge || selectedValues.age !== '');
        const hasAnySelected = selectedValues.color !== '' || selectedValues.size !== '' || selectedValues.age !== '';
        const hasAnyAttribute = modalHasColor || modalHasSize || modalHasAge;

        let selectedVariant = null;
        if (matches.length === 1 && (fullySpecified || hasAnySelected)) {
            selectedVariant = matches[0];
        } else if (!hasAnyAttribute && modalVariants.length === 1) {
            selectedVariant = modalVariants[0];
        }

        if (selectedVariant) {
            const variantColor = toVariantValue(selectedVariant.color);
            const variantSize = toVariantValue(selectedVariant.size);
            const variantAge = toVariantValue(selectedVariant.age);

            if (modalHasColor && variantColor && selectedValues.color === '') {
                variantColorSelect.value = variantColor;
                selectedValues.color = variantColor;
            }

            if (modalHasSize && variantSize && selectedValues.size === '') {
                variantSizeSelect.value = variantSize;
                selectedValues.size = variantSize;
            }

            if (modalHasAge && variantAge && selectedValues.age === '') {
                variantAgeSelect.value = variantAge;
                selectedValues.age = variantAge;
            }

            variantIdInput.value = String(selectedVariant.id);
            const selectedLabel = selectedVariant.label || `Variant #${selectedVariant.id}`;
            const skuSuffix = selectedVariant.sku_code ? ` [${selectedVariant.sku_code}]` : '';
            setVariantHint(`Matched: ${selectedLabel}${skuSuffix}`);
            return;
        }

        variantIdInput.value = '';

        if (hasAnyAttribute) {
            setVariantHint('Select valid color/size/age combination');
        } else {
            setVariantHint('Variant data available');
        }
    }

    function initializeModalVariantSelectors(variants, selectedVariantId = '') {
        modalVariants = variants.map((variant) => ({
            id: String(variant.id),
            color: toVariantValue(variant.color),
            size: toVariantValue(variant.size),
            age: toVariantValue(variant.age),
            label: String(variant.label || ''),
            sku_code: String(variant.sku_code || '')
        }));

        const colorOptions = uniqueSorted(modalVariants.map((variant) => variant.color));
        const sizeOptions = uniqueSorted(modalVariants.map((variant) => variant.size));
        const ageOptions = uniqueSorted(modalVariants.map((variant) => variant.age));
        modalHasColor = colorOptions.length > 0;
        modalHasSize = sizeOptions.length > 0;
        modalHasAge = ageOptions.length > 0;

        variantGroup.style.display = 'block';

        if (modalHasColor) {
            setSelectOptions(variantColorSelect, '-- Select Color --', colorOptions);
            variantColorSelect.disabled = false;
        } else {
            setSelectOptions(variantColorSelect, '-- No Color --', []);
            variantColorSelect.disabled = true;
        }

        if (modalHasSize) {
            setSelectOptions(variantSizeSelect, '-- Select Size --', sizeOptions);
            variantSizeSelect.disabled = false;
        } else {
            setSelectOptions(variantSizeSelect, '-- No Size --', []);
            variantSizeSelect.disabled = true;
        }

        if (modalHasAge) {
            setSelectOptions(variantAgeSelect, '-- Select Age --', ageOptions);
            variantAgeSelect.disabled = false;
        } else {
            setSelectOptions(variantAgeSelect, '-- No Age --', []);
            variantAgeSelect.disabled = true;
        }

        variantIdInput.value = '';
        setVariantHint('Select color/size/age combination if applicable');

        const selected = modalVariants.find((variant) => variant.id === String(selectedVariantId));
        if (selected) {
            if (modalHasColor && selected.color) {
                variantColorSelect.value = selected.color;
            }
            if (modalHasSize && selected.size) {
                variantSizeSelect.value = selected.size;
            }
            if (modalHasAge && selected.age) {
                variantAgeSelect.value = selected.age;
            }
        } else {
            if (modalHasColor && colorOptions.length === 1) {
                variantColorSelect.value = colorOptions[0];
            }
            if (modalHasSize && sizeOptions.length === 1) {
                variantSizeSelect.value = sizeOptions[0];
            }
            if (modalHasAge && ageOptions.length === 1) {
                variantAgeSelect.value = ageOptions[0];
            }
        }

        syncModalVariantSelection();
    }

    async function fetchVariantData(productId) {
        const url = new URL(variantsEndpoint, window.location.origin);
        url.searchParams.set('product_id', String(productId));

        const response = await fetch(url.toString(), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Failed to load variants');
        }

        return data;
    }

    async function fetchStockData(productId, warehouseId, variantId = '') {
        const url = new URL(stockEndpoint, window.location.origin);
        url.searchParams.set('product_id', String(productId));
        url.searchParams.set('warehouse_id', String(warehouseId));
        if (String(variantId || '').trim() !== '') {
            url.searchParams.set('variant_id', String(variantId));
        }

        const response = await fetch(url.toString(), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Failed to load stock information');
        }

        return data;
    }

    async function populateStockPreview(productId, warehouseId, variantId = '') {
        const data = await fetchStockData(productId, warehouseId, variantId);
        const variantLabel = String(data?.data?.variant_label || '').trim();
        const baseName = String(data.product_name || '');
        document.getElementById('productName').value = variantLabel !== '' ? `${baseName} (${variantLabel})` : baseName;
        document.getElementById('warehouseName').value = String(data.warehouse_name || resolveWarehouseName(warehouseId));

        const currentPhysical = Number(data?.data?.physical_quantity || 0);
        currentPhysicalInput.value = currentPhysical.toFixed(2);
        renderPreview(currentPhysical, adjustmentInput.value || 0);
    }

    async function handleVariantChange() {
        const productId = String(document.getElementById('modalProductId').value || '').trim();
        const warehouseId = String(document.getElementById('modalWarehouseId').value || '').trim();
        if (productId === '' || warehouseId === '') {
            return;
        }

        const variantId = String(variantIdInput.value || '').trim();
        if (variantGroup.style.display !== 'none' && variantId === '') {
            setVariantHint('Select valid color/size/age combination');
            currentPhysicalInput.value = '0.00';
            renderPreview(0, adjustmentInput.value || 0);
            return;
        }

        await populateStockPreview(productId, warehouseId, variantId);
    }

    function closeQuickAdjustModal() {
        if (typeof $ !== 'undefined' && typeof $('#quickAdjustModal').modal === 'function') {
            $('#quickAdjustModal').modal('hide');
        } else {
            quickAdjustModal.classList.remove('show');
            quickAdjustModal.style.display = 'none';
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('padding-right');
            document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());
        }

        quickAdjustForm.reset();
        resetVariantSelector();
        renderPreview(0, 0);
    }

    async function quickAdjust(productId, warehouseId) {
        try {
            document.getElementById('modalProductId').value = String(productId);
            document.getElementById('modalWarehouseId').value = String(warehouseId);

            adjustmentInput.value = '';
            reasonInput.value = '';
            currentPhysicalInput.value = '0.00';
            document.getElementById('productName').value = '';
            document.getElementById('warehouseName').value = resolveWarehouseName(warehouseId);
            resetVariantSelector();

            const variantPayload = await fetchVariantData(productId);
            const variants = Array.isArray(variantPayload.variants) ? variantPayload.variants : [];
            document.getElementById('productName').value = String(variantPayload.product_name || '');
            if (variants.length > 0) {
                initializeModalVariantSelectors(variants);
            }

            const selectedVariantId = String(variantIdInput.value || '').trim();
            if (variants.length > 0 && selectedVariantId === '') {
                setVariantHint('Select valid color/size/age combination');
                renderPreview(0, 0);
            } else {
                await populateStockPreview(productId, warehouseId, selectedVariantId);
                renderPreview(currentPhysicalInput.value, 0);
            }

            if (typeof $ !== 'undefined' && typeof $('#quickAdjustModal').modal === 'function') {
                $('#quickAdjustModal').modal('show');
            } else {
                quickAdjustModal.classList.add('show');
                quickAdjustModal.style.display = 'block';
                document.body.classList.add('modal-open');
            }
        } catch (error) {
            console.error(error);
            alert(error.message || 'Failed to load stock information');
        }
    }

    // Update preview as user types
    adjustmentInput.addEventListener('input', function () {
        renderPreview(currentPhysicalInput.value, this.value);
    });

    variantColorSelect.addEventListener('change', function () {
        syncModalVariantSelection('color');
        handleVariantChange().catch((error) => {
            console.error(error);
            alert(error.message || 'Failed to load variant stock');
        });
    });

    variantSizeSelect.addEventListener('change', function () {
        syncModalVariantSelection('size');
        handleVariantChange().catch((error) => {
            console.error(error);
            alert(error.message || 'Failed to load variant stock');
        });
    });

    variantAgeSelect.addEventListener('change', function () {
        syncModalVariantSelection('age');
        handleVariantChange().catch((error) => {
            console.error(error);
            alert(error.message || 'Failed to load variant stock');
        });
    });

    function submitQuickAdjust() {
        const adjustment = Number(adjustmentInput.value);
        const current = Number(currentPhysicalInput.value || 0);
        const reason = reasonInput.value.trim();
        const variantId = String(variantIdInput.value || '').trim();
        const selectedVariant = modalVariants.find((variant) => variant.id === variantId);
        const selectedVariantText = selectedVariant
            ? `${selectedVariant.label || ('Variant #' + selectedVariant.id)}${selectedVariant.sku_code ? ' [' + selectedVariant.sku_code + ']' : ''}`
            : '';

        if (!Number.isFinite(adjustment) || adjustment === 0) {
            alert('Please enter a valid non-zero adjustment quantity');
            return;
        }

        if (variantGroup.style.display !== 'none' && variantId === '') {
            alert('Please select a valid color/size/age combination for this variable product');
            if (!variantColorSelect.disabled) {
                variantColorSelect.focus();
            } else if (!variantSizeSelect.disabled) {
                variantSizeSelect.focus();
            } else if (!variantAgeSelect.disabled) {
                variantAgeSelect.focus();
            }
            return;
        }

        if (reason.length < 3) {
            alert('Please provide a reason (at least 3 characters)');
            return;
        }

        // Show confirmation
        const newQty = current + adjustment;
        const variantLine = selectedVariantText !== '' ? `\nVariant: ${selectedVariantText}` : '';

        if (!confirm(`Are you sure you want to adjust stock from ${current.toFixed(2)} to ${newQty.toFixed(2)}?${variantLine}\n\nReason: ${reason}`)) {
            return;
        }

        // Submit via AJAX
        fetch(quickAdjustEndpoint, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                product_id: document.getElementById('modalProductId').value,
                warehouse_id: document.getElementById('modalWarehouseId').value,
                variant_id: variantId === '' ? null : Number(variantId),
                adjustment_quantity: adjustment,
                reason: reason
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                const firstValidationError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
                throw new Error(firstValidationError || data.message || 'Failed to adjust stock');
            }

            alert('Stock adjusted successfully');
            closeQuickAdjustModal();
            location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert(error.message || 'Failed to submit adjustment');
        });
    }
</script>
@endsection
