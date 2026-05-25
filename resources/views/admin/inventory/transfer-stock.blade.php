@extends('backEnd.layouts.master')

@section('content')
<div class="container-fluid">
  <div class="row mb-4">
    <div class="col-md-12">
      <h1 class="mb-0">Transfer Stock</h1>
      <small class="text-muted">Move stock between warehouses</small>
      <hr>
    </div>
  </div>
  <div class="row">
    <div class="col-md-8">
      <div class="card"><div class="card-body">
        <form action="{{ route('admin.inventory.store-transfer-stock') }}" method="POST">
          @csrf
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label><strong>From Warehouse</strong></label>
                <select name="from_warehouse_id" class="form-control @error('from_warehouse_id') is-invalid @enderror" required onchange="loadSourceWarehouseStock(this.value)">
                  <option value="">-- Choose Source Warehouse --</option>
                  @foreach ($warehouses as $warehouse)
                  <option value="{{ $warehouse->id }}" {{ old('from_warehouse_id') == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                  @endforeach
                </select>
                @error('from_warehouse_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label><strong>To Warehouse</strong></label>
                <select name="to_warehouse_id" class="form-control @error('to_warehouse_id') is-invalid @enderror" required>
                  <option value="">-- Choose Destination Warehouse --</option>
                  @foreach ($warehouses as $warehouse)
                  <option value="{{ $warehouse->id }}" {{ old('to_warehouse_id') == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                  @endforeach
                </select>
                @error('to_warehouse_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
              </div>
            </div>
          </div>
          <hr class="my-4">
          <h5>Items to Transfer</h5>
          <div id="items-container">
            <div class="item-row mb-3 p-3 border rounded">
              <div class="row">
                <div class="col-md-3">
                  <label>Product</label>
                  <select name="items[0][product_id]" class="form-control product-select @error('items.0.product_id') is-invalid @enderror" data-selected="{{ old('items.0.product_id') }}" required>
                    <option value="">-- Select Product --</option>
                  </select>
                  @error('items.0.product_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-4">
                  <label>Variant Attributes</label>
                  <div class="variant-attributes-container border rounded p-2 @error('items.0.variant_id') border-danger @enderror"></div>
                  <input type="hidden" name="items[0][variant_id]" class="variant-id-input" data-selected="{{ old('items.0.variant_id') }}" value="{{ old('items.0.variant_id') }}">
                  <small class="form-text text-muted variant-hint">Select variant attributes if applicable</small>
                  @error('items.0.variant_id')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-4">
                  <label>Quantity</label>
                  <input type="number" name="items[0][quantity]" class="form-control quantity-input @error('items.0.quantity') is-invalid @enderror" placeholder="0" step="0.01" min="0.01" required value="{{ old('items.0.quantity') }}">
                  <small class="form-text text-muted available-text">Available: -</small>
                  @error('items.0.quantity')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-1 d-flex align-items-end"><button type="button" class="btn btn-sm btn-danger w-100" onclick="removeItem(this)"><i class="fas fa-trash"></i></button></div>
              </div>
            </div>
          </div>
          <button type="button" class="btn btn-sm btn-secondary mb-3" onclick="addItem()"><i class="fas fa-plus"></i> Add Another Item</button>
          <hr class="my-4">
          <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="3" placeholder="Reason for transfer...">{{ old('notes') }}</textarea></div>
          <div class="form-group mt-3">
            <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-arrow-right"></i> Transfer Stock</button>
            <a href="{{ route('admin.inventory.index') }}" class="btn btn-secondary btn-lg"><i class="fas fa-times"></i> Cancel</a>
          </div>
        </form>
      </div></div>
    </div>
    <div class="col-md-4"><div class="card bg-light"><div class="card-body">
      <h5 class="card-title">How to Transfer</h5>
      <ol class="small"><li>Select source warehouse</li><li>Select destination warehouse</li><li>Select product and variant attributes</li><li>Enter quantity</li><li>Transfer</li></ol>
    </div></div></div>
  </div>
</div>

<script>
let itemCount = document.querySelectorAll('#items-container .item-row').length;
let sourceProducts = [];

const variantCache = {};
const stockCache = {};
const warehouseProductsUrl = '{{ route('admin.inventory.api.warehouse-products') }}';
const productVariantsUrl = '{{ route('admin.inventory.api.product-variants') }}';
const productStockUrl = '{{ route('admin.inventory.get-product-stock') }}';

function normalize(value) {
    return String(value ?? '').trim();
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatNumber(value) {
    const number = Number(value || 0);
    return number.toFixed(2).replace(/\.00$/, '');
}

function getRowElements(row) {
    return {
        productSelect: row.querySelector('.product-select'),
        variantContainer: row.querySelector('.variant-attributes-container'),
        variantIdInput: row.querySelector('.variant-id-input'),
        quantityInput: row.querySelector('.quantity-input'),
        availableText: row.querySelector('.available-text')
    };
}

function setVariantHint(row, text, isError = false) {
    const hint = row.querySelector('.variant-hint');
    if (!hint) {
        return;
    }

    hint.classList.toggle('text-muted', !isError);
    hint.classList.toggle('text-danger', isError);
    hint.textContent = text;
}

function parseJson(value, fallback) {
    try {
        const parsed = JSON.parse(value || '');
        return parsed ?? fallback;
    } catch (_) {
        return fallback;
    }
}

function getRowState(row) {
    return {
        variants: parseJson(row.dataset.variantRows, []),
        groups: parseJson(row.dataset.variantGroups, []),
        selectedValues: parseJson(row.dataset.selectedValues, {})
    };
}

function setRowState(row, state) {
    row.dataset.variantRows = JSON.stringify(state.variants || []);
    row.dataset.variantGroups = JSON.stringify(state.groups || []);
    row.dataset.selectedValues = JSON.stringify(state.selectedValues || {});
}

function setSimpleProductState(row, message = 'Simple product (no variant)') {
    const { variantContainer, variantIdInput } = getRowElements(row);
    if (!variantContainer || !variantIdInput) {
        return;
    }

    variantContainer.innerHTML = '<small class="text-muted">No variant attributes</small>';
    variantIdInput.value = '';
    row.dataset.variantRows = '[]';
    row.dataset.variantGroups = '[]';
    row.dataset.selectedValues = '{}';
    row.dataset.hasVariants = '0';
    setVariantHint(row, message);
}

function setLoadingVariantState(row) {
    const { variantContainer, variantIdInput } = getRowElements(row);
    if (!variantContainer || !variantIdInput) {
        return;
    }

    variantContainer.innerHTML = '<small class="text-muted">Loading variant attributes...</small>';
    variantIdInput.value = '';
    setVariantHint(row, 'Loading variants...');
}

function normalizeVariantPayload(payload) {
    const rawVariants = Array.isArray(payload?.variants) ? payload.variants : [];
    const rawGroups = Array.isArray(payload?.attribute_groups) ? payload.attribute_groups : [];

    const variants = rawVariants.map((variant) => {
        const valueMap = {};
        const rows = Array.isArray(variant.attribute_values) ? variant.attribute_values : [];
        rows.forEach((row) => {
            const attributeId = normalize(row.attribute_id);
            const valueId = normalize(row.value_id);
            if (attributeId !== '' && valueId !== '') {
                valueMap['a:' + attributeId] = valueId;
            }
        });

        const attributes = (variant.attributes && typeof variant.attributes === 'object') ? variant.attributes : {};
        Object.keys(attributes).forEach((slug) => {
            const slugKey = 'slug:' + normalize(slug).toLowerCase();
            const valueText = normalize(attributes[slug]);
            if (valueText !== '') {
                valueMap[slugKey] = valueText;
            }
        });

        const legacyColor = normalize(variant.color);
        const legacySize = normalize(variant.size);
        const legacyAge = normalize(variant.age);
        if (legacyColor !== '' && !valueMap['slug:color']) {
            valueMap['slug:color'] = legacyColor;
        }
        if (legacySize !== '' && !valueMap['slug:size']) {
            valueMap['slug:size'] = legacySize;
        }
        if (legacyAge !== '' && !valueMap['slug:age']) {
            valueMap['slug:age'] = legacyAge;
        }

        return {
            id: normalize(variant.id),
            label: normalize(variant.label || ''),
            sku_code: normalize(variant.sku_code || ''),
            valueMap,
        };
    });

    let groups = rawGroups.map((group) => {
        const attributeId = normalize(group.attribute_id);
        const slug = normalize(group.attribute_slug).toLowerCase();
        const key = attributeId !== '' ? ('a:' + attributeId) : ('slug:' + slug);
        const values = (Array.isArray(group.values) ? group.values : [])
            .map((value) => {
                const valueId = normalize(value.value_id || value.value);
                const valueText = normalize(value.value);
                return {
                    value_id: valueId,
                    value: valueText,
                };
            })
            .filter((value) => value.value_id !== '' && value.value !== '');

        return {
            key,
            name: normalize(group.attribute_name) || 'Attribute',
            values,
        };
    }).filter((group) => group.values.length > 0);

    if (groups.length === 0) {
        const derived = {};
        variants.forEach((variant) => {
            Object.keys(variant.valueMap).forEach((groupKey) => {
                if (!groupKey.startsWith('slug:')) {
                    return;
                }

                if (!derived[groupKey]) {
                    const slug = groupKey.replace(/^slug:/, '');
                    derived[groupKey] = {
                        key: groupKey,
                        name: slug
                            .split('-')
                            .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
                            .join(' '),
                        values: {},
                    };
                }

                const valueText = normalize(variant.valueMap[groupKey]);
                if (valueText !== '') {
                    derived[groupKey].values[valueText] = {
                        value_id: valueText,
                        value: valueText,
                    };
                }
            });
        });

        groups = Object.values(derived).map((group) => ({
            key: group.key,
            name: group.name,
            values: Object.values(group.values),
        }));
    }

    return { variants, groups };
}

function buildGroupHtml(groups) {
    return groups.map((group) => {
        const safeKey = escapeHtml(group.key);
        const safeName = escapeHtml(group.name);
        return `
            <div class="mb-2">
                <label class="small mb-1 d-block">${safeName}</label>
                <select class="form-control form-control-sm variant-attribute-select" data-group-key="${safeKey}">
                    <option value="">Select ${safeName}</option>
                </select>
            </div>
        `;
    }).join('');
}

function variantMatchesSelection(variant, groups, selectedValues, ignoreGroupKey = '') {
    return groups.every((group) => {
        if (group.key === ignoreGroupKey) {
            return true;
        }

        const selectedValue = normalize(selectedValues[group.key]);
        if (selectedValue === '') {
            return true;
        }

        return normalize(variant.valueMap[group.key]) === selectedValue;
    });
}

function optionsForGroup(variants, groups, selectedValues, group) {
    const allowed = new Set();

    variants.forEach((variant) => {
        if (!variantMatchesSelection(variant, groups, selectedValues, group.key)) {
            return;
        }

        const valueId = normalize(variant.valueMap[group.key]);
        if (valueId !== '') {
            allowed.add(valueId);
        }
    });

    return (group.values || []).filter((value) => allowed.has(normalize(value.value_id)));
}

function validateQuantity(row) {
    const { quantityInput, variantIdInput, availableText } = getRowElements(row);
    if (!quantityInput || !availableText) {
        return;
    }

    const availableQty = Number(row.dataset.availableQty || 0);
    const requestedQty = Number(quantityInput.value || 0);
    const variantRequired = row.dataset.hasVariants === '1';
    const hasSelectedVariant = normalize(variantIdInput ? variantIdInput.value : '') !== '';

    quantityInput.setCustomValidity('');
    quantityInput.classList.remove('is-invalid');
    availableText.classList.remove('text-danger');

    if (requestedQty <= 0) {
        return;
    }

    if (variantRequired && !hasSelectedVariant) {
        quantityInput.classList.add('is-invalid');
        quantityInput.setCustomValidity('Select a valid variant combination first');
        availableText.classList.add('text-danger');
        return;
    }

    if (requestedQty > availableQty) {
        quantityInput.classList.add('is-invalid');
        quantityInput.setCustomValidity('Only ' + formatNumber(availableQty) + ' available in source warehouse');
        availableText.classList.add('text-danger');
    }
}

async function fetchVariantAvailable(productId, warehouseId, variantId) {
    const cacheKey = `${warehouseId}:${productId}:${variantId}`;
    if (Object.prototype.hasOwnProperty.call(stockCache, cacheKey)) {
        return Number(stockCache[cacheKey] || 0);
    }

    const url = new URL(productStockUrl, window.location.origin);
    url.searchParams.set('product_id', String(productId));
    url.searchParams.set('warehouse_id', String(warehouseId));
    url.searchParams.set('variant_id', String(variantId));

    const response = await fetch(url.toString(), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    });

    if (!response.ok) {
        throw new Error('Failed to load stock');
    }

    const payload = await response.json();
    const available = Number(payload?.data?.available_quantity || 0);
    stockCache[cacheKey] = available;
    return available;
}

async function updateAvailableForRow(row) {
    const { productSelect, variantIdInput, quantityInput, availableText } = getRowElements(row);
    const warehouseId = normalize(document.querySelector('select[name="from_warehouse_id"]')?.value);
    const productId = normalize(productSelect ? productSelect.value : '');
    const variantId = normalize(variantIdInput ? variantIdInput.value : '');
    const variantRequired = row.dataset.hasVariants === '1';

    if (!quantityInput || !availableText) {
        return;
    }

    quantityInput.setCustomValidity('');
    quantityInput.classList.remove('is-invalid');
    availableText.classList.remove('text-danger');

    if (warehouseId === '' || productId === '') {
        row.dataset.availableQty = '0';
        availableText.textContent = 'Available: -';
        quantityInput.removeAttribute('max');
        validateQuantity(row);
        return;
    }

    try {
        let availableQty = 0;

        if (variantRequired) {
            if (variantId === '') {
                row.dataset.availableQty = '0';
                availableText.textContent = 'Available: select variant';
                quantityInput.removeAttribute('max');
                validateQuantity(row);
                return;
            }

            availableQty = await fetchVariantAvailable(productId, warehouseId, variantId);
        } else {
            const sourceItem = sourceProducts.find((item) => String(item.product_id) === productId);
            availableQty = Number(sourceItem?.available_quantity || 0);
        }

        row.dataset.availableQty = String(availableQty);
        availableText.textContent = 'Available: ' + formatNumber(availableQty);
        quantityInput.setAttribute('max', String(availableQty));
        validateQuantity(row);
    } catch (_) {
        row.dataset.availableQty = '0';
        availableText.textContent = 'Available: error';
        availableText.classList.add('text-danger');
        quantityInput.removeAttribute('max');
    }
}

function updateVariantSelection(row, changedGroupKey = '') {
    const { variantContainer, variantIdInput } = getRowElements(row);
    if (!variantContainer || !variantIdInput) {
        return;
    }

    const state = getRowState(row);
    const variants = state.variants || [];
    const groups = state.groups || [];
    const selectedValues = state.selectedValues || {};

    if (variants.length === 0 || groups.length === 0) {
        variantIdInput.value = '';
        setVariantHint(row, variants.length === 0 ? 'Simple product (no variant)' : 'Select variant attributes if applicable');
        updateAvailableForRow(row);
        return;
    }

    groups.forEach((group) => {
        if (!Object.prototype.hasOwnProperty.call(selectedValues, group.key)) {
            selectedValues[group.key] = '';
        }

        const selectEl = variantContainer.querySelector(`select[data-group-key="${group.key}"]`);
        if (!selectEl) {
            return;
        }

        const options = optionsForGroup(variants, groups, selectedValues, group);
        const current = normalize(selectedValues[group.key]);

        const optionHtml = ['<option value="">Select ' + escapeHtml(group.name) + '</option>']
            .concat(options.map((option) => {
                const valueId = normalize(option.value_id);
                const selected = current === valueId ? ' selected' : '';
                return '<option value="' + escapeHtml(valueId) + '"' + selected + '>' + escapeHtml(option.value) + '</option>';
            }));

        selectEl.innerHTML = optionHtml.join('');

        if (current !== '' && !options.some((option) => normalize(option.value_id) === current)) {
            selectedValues[group.key] = '';
            selectEl.value = '';
        } else if (selectedValues[group.key] === '' && options.length === 1 && changedGroupKey !== group.key) {
            selectedValues[group.key] = normalize(options[0].value_id);
            selectEl.value = selectedValues[group.key];
        } else {
            selectEl.value = selectedValues[group.key] || '';
        }
    });

    const matches = variants.filter((variant) => variantMatchesSelection(variant, groups, selectedValues));
    const fullySpecified = groups.every((group) => normalize(selectedValues[group.key]) !== '');
    const hasAnySelected = groups.some((group) => normalize(selectedValues[group.key]) !== '');

    let selectedVariant = null;
    if (matches.length === 1 && (fullySpecified || hasAnySelected)) {
        selectedVariant = matches[0];
    }

    if (selectedVariant) {
        variantIdInput.value = selectedVariant.id;
        const variantLabel = selectedVariant.label || `Variant #${selectedVariant.id}`;
        const skuSuffix = selectedVariant.sku_code ? ` [${selectedVariant.sku_code}]` : '';
        setVariantHint(row, `Matched: ${variantLabel}${skuSuffix}`);
    } else {
        variantIdInput.value = '';
        setVariantHint(row, 'Select valid attribute combination');
    }

    state.selectedValues = selectedValues;
    setRowState(row, state);
    updateAvailableForRow(row);
}

function initializeVariantSelectors(row, payload, selectedVariantId = '') {
    const { variantContainer } = getRowElements(row);
    if (!variantContainer) {
        return;
    }

    const normalized = normalizeVariantPayload(payload);
    const variants = normalized.variants;
    const groups = normalized.groups;

    if (variants.length === 0) {
        setSimpleProductState(row, 'Simple product (no variant)');
        updateAvailableForRow(row);
        return;
    }

    if (groups.length === 0) {
        const { variantIdInput, variantContainer } = getRowElements(row);
        const selectedVariant = variants.find((variant) => variant.id === normalize(selectedVariantId)) || variants[0];
        if (variantIdInput && selectedVariant) {
            variantIdInput.value = selectedVariant.id;
        }
        if (variantContainer) {
            variantContainer.innerHTML = '<small class="text-muted">No attributes available. Variant selected automatically.</small>';
        }
        row.dataset.hasVariants = '1';
        setRowState(row, { variants, groups: [], selectedValues: {} });
        setVariantHint(row, selectedVariant ? ('Matched: ' + (selectedVariant.label || ('Variant #' + selectedVariant.id))) : 'Variant selected automatically');
        updateAvailableForRow(row);
        return;
    }

    const selectedValues = {};
    groups.forEach((group) => {
        selectedValues[group.key] = '';
    });

    const selectedVariant = variants.find((variant) => variant.id === normalize(selectedVariantId)) || variants[0];
    if (selectedVariant) {
        groups.forEach((group) => {
            selectedValues[group.key] = normalize(selectedVariant.valueMap[group.key]);
        });
    }

    variantContainer.innerHTML = buildGroupHtml(groups);
    variantContainer.querySelectorAll('.variant-attribute-select').forEach((selectEl) => {
        selectEl.addEventListener('change', function () {
            const groupKey = normalize(this.getAttribute('data-group-key'));
            const state = getRowState(row);
            state.selectedValues[groupKey] = normalize(this.value);
            setRowState(row, state);
            updateVariantSelection(row, groupKey);
        });
    });

    setRowState(row, {
        variants,
        groups,
        selectedValues,
    });

    row.dataset.hasVariants = '1';
    updateVariantSelection(row);
}

async function fetchProductVariants(productId) {
    const cacheKey = String(productId);
    if (variantCache[cacheKey]) {
        return variantCache[cacheKey];
    }

    const url = new URL(productVariantsUrl, window.location.origin);
    url.searchParams.set('product_id', cacheKey);

    const response = await fetch(url.toString(), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    });

    if (!response.ok) {
        throw new Error('Failed to load variants');
    }

    const payload = await response.json();
    variantCache[cacheKey] = payload;
    return payload;
}

function productOptions(selectedProductId = '') {
    if (!sourceProducts.length) {
        return '<option value="">-- Select Product --</option>';
    }

    return '<option value="">-- Select Product --</option>' + sourceProducts.map((item) => {
        const productId = String(item.product_id);
        const selected = productId === String(selectedProductId) ? ' selected' : '';
        const sku = normalize(item.sku || '-');
        return `<option value="${escapeHtml(productId)}"${selected}>${escapeHtml(item.product_name)} (${escapeHtml(sku)})</option>`;
    }).join('');
}

async function handleProductChange(productSelect, selectedVariantId = '') {
    const row = productSelect.closest('.item-row');
    if (!row) {
        return;
    }

    const productId = normalize(productSelect.value);
    if (productId === '') {
        setSimpleProductState(row, 'Select product first');
        await updateAvailableForRow(row);
        return;
    }

    try {
        setLoadingVariantState(row);
        const payload = await fetchProductVariants(productId);
        initializeVariantSelectors(row, payload, selectedVariantId);
    } catch (_) {
        setSimpleProductState(row, 'Unable to load variants');
        setVariantHint(row, 'Unable to load variants', true);
        await updateAvailableForRow(row);
    }
}

function bindRowEvents(row) {
    const { quantityInput } = getRowElements(row);
    if (quantityInput) {
        quantityInput.addEventListener('input', function () {
            validateQuantity(row);
        });
    }
}

async function refreshRowsForWarehouse() {
    const rows = Array.from(document.querySelectorAll('#items-container .item-row'));

    for (const row of rows) {
        const { productSelect, variantIdInput } = getRowElements(row);
        if (!productSelect) {
            continue;
        }

        const selectedProductId = normalize(productSelect.value || productSelect.dataset.selected);
        const selectedVariantId = normalize(variantIdInput ? (variantIdInput.value || variantIdInput.dataset.selected) : '');

        productSelect.innerHTML = productOptions(selectedProductId);
        productSelect.dataset.selected = '';
        if (variantIdInput) {
            variantIdInput.dataset.selected = '';
        }

        if (selectedProductId !== '') {
            await handleProductChange(productSelect, selectedVariantId);
        } else {
            setSimpleProductState(row, 'Select product first');
            await updateAvailableForRow(row);
        }
    }
}

function addItem() {
    const container = document.getElementById('items-container');
    const html = `
        <div class="item-row mb-3 p-3 border rounded">
            <div class="row">
                <div class="col-md-3">
                    <label>Product</label>
                    <select name="items[${itemCount}][product_id]" class="form-control product-select" required>
                        ${productOptions('')}
                    </select>
                </div>

                <div class="col-md-4">
                    <label>Variant Attributes</label>
                    <div class="variant-attributes-container border rounded p-2"></div>
                    <input type="hidden" name="items[${itemCount}][variant_id]" class="variant-id-input" value="">
                    <small class="form-text text-muted variant-hint">Select variant attributes if applicable</small>
                </div>

                <div class="col-md-4">
                    <label>Quantity</label>
                    <input type="number" name="items[${itemCount}][quantity]" class="form-control quantity-input" placeholder="0" step="0.01" min="0.01" required>
                    <small class="form-text text-muted available-text">Available: -</small>
                </div>

                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-sm btn-danger w-100" onclick="removeItem(this)"><i class="fas fa-trash"></i></button>
                </div>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', html);
    const row = container.lastElementChild;
    if (row) {
        bindRowEvents(row);
        setSimpleProductState(row, 'Select product first');
        updateAvailableForRow(row);
    }

    itemCount++;
}

function removeItem(button) {
    const rows = document.querySelectorAll('#items-container .item-row');
    if (rows.length <= 1) {
        return;
    }

    button.closest('.item-row').remove();
}

function clearStockCache() {
    Object.keys(stockCache).forEach((key) => delete stockCache[key]);
}

function loadSourceWarehouseStock(warehouseId) {
    sourceProducts = [];
    clearStockCache();

    if (!warehouseId) {
        refreshRowsForWarehouse();
        return;
    }

    fetch(`${warehouseProductsUrl}?warehouse_id=${warehouseId}`, {
        headers: {
            'Accept': 'application/json'
        }
    })
        .then((response) => response.json())
        .then((payload) => {
            if (!payload.success) {
                throw new Error(payload.message || 'Failed to load products');
            }

            sourceProducts = Array.isArray(payload.products) ? payload.products : [];
            return refreshRowsForWarehouse();
        })
        .catch((error) => {
            console.error(error);
            alert('Failed to load source warehouse stock. Please try again.');
        });
}

document.addEventListener('change', function (event) {
    const target = event.target;
    const row = target.closest('.item-row');

    if (target.classList.contains('product-select')) {
        handleProductChange(target);
        return;
    }

    if (target.classList.contains('variant-attribute-select') && row) {
        const groupKey = normalize(target.getAttribute('data-group-key'));
        const state = getRowState(row);
        state.selectedValues[groupKey] = normalize(target.value);
        setRowState(row, state);
        updateVariantSelection(row, groupKey);
    }
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('#items-container .item-row').forEach((row) => {
        bindRowEvents(row);
        setSimpleProductState(row, 'Select product first');
    });

    const warehouseSelect = document.querySelector('select[name="from_warehouse_id"]');
    loadSourceWarehouseStock(warehouseSelect ? warehouseSelect.value : '');
});
</script>
@endsection
