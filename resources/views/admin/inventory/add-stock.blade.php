@extends('backEnd.layouts.master')


@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="mb-0">➕ Add Stock (Goods Receipt)</h1>
            <small class="text-muted">Receive stock from suppliers</small>
            <hr>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.inventory.store-add-stock') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><strong>Select Warehouse</strong></label>
                                    <select name="warehouse_id" class="form-control @error('warehouse_id') is-invalid @enderror"
                                        required>
                                        <option value="">-- Choose Warehouse --</option>
                                        @foreach ($warehouses as $warehouse)
                                            <option value="{{ $warehouse->id }}" {{ (string) old('warehouse_id') === (string) $warehouse->id ? 'selected' : '' }}>
                                                {{ $warehouse->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('warehouse_id')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><strong>Supplier (Optional)</strong></label>
                                    <select name="supplier_id" class="form-control @error('supplier_id') is-invalid @enderror">
                                        <option value="">-- No Supplier --</option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" {{ (string) old('supplier_id') === (string) $supplier->id ? 'selected' : '' }}>
                                                {{ $supplier->supplier_code }} - {{ $supplier->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('supplier_id')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                    <small class="form-text text-muted">Set this to track supplier dues automatically</small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><strong>GRN Number (Optional)</strong></label>
                                    <input type="text" name="grn_number" class="form-control"
                                        placeholder="e.g., GRN-2025-001" value="{{ old('grn_number') }}">
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">
                        
                        <div class="card builder-card mb-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0"><i class="fas fa-magic text-primary"></i> Stock Item Builder</h5>
                                <small class="text-muted">Fill details and click 'Add to List'</small>
                            </div>
                            <div class="card-body" id="item-builder">
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="form-group mb-0">
                                            <label class="fw-bold">Select Product <span class="text-danger">*</span></label>
                                            <select id="builder-product-id" class="form-control product-select" data-placeholder="Search by name, SKU, product code, or variant SKU…">
                                                <option value=""></option>
                                            </select>
                                            <small class="form-text text-muted">Type at least 2 characters to search.</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group mb-0">
                                            <label class="fw-bold">Quantity <span class="text-danger">*</span></label>
                                            <input type="number" id="builder-quantity" class="form-control" placeholder="0" step="0.01">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group mb-0">
                                            <label class="fw-bold">Unit Cost (Base)</label>
                                            <input type="number" id="builder-unit-cost" class="form-control" placeholder="0.00" step="0.01">
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3 variant-selection-area d-none" id="builder-variant-section">
                                    <div class="col-md-6">
                                        <label class="fw-bold small d-block mb-2">Variant Selection</label>
                                        <div class="variant-attributes-container border rounded p-3 bg-light">
                                            <!-- Variant dropdowns will be injected here -->
                                        </div>
                                        <input type="hidden" id="builder-variant-id" class="variant-id-input" value="">
                                        <small class="form-text text-muted variant-hint mt-2 d-block"></small>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card product-details-card h-100 bg-light">
                                            <div class="card-body py-2">
                                                <h6 class="card-title small mb-2">📋 Product/Variant Info</h6>
                                                <div class="product-info-content small mt-1 d-flex align-items-start">
                                                    <div id="variant-reference-image" class="me-3 d-none">
                                                        <div class="border rounded p-1 bg-white shadow-sm" style="width: 80px; height: 80px; overflow: hidden;">
                                                            <img src="" alt="Reference" class="w-100 h-100 object-fit-cover" id="builder-image-preview">
                                                        </div>
                                                    </div>
                                                    <div id="variant-details-text" class="flex-grow-1">
                                                        <small class="text-muted">Details will appear here</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-end mt-4 pt-3 border-top">
                                    <button type="button" class="btn btn-primary px-4 py-2" id="add-item-to-list">
                                        <i class="fas fa-plus-circle"></i> Add to Receipt List
                                    </button>
                                </div>
                            </div>
                        </div>

                        <h5 class="mb-3 d-flex align-items-center">
                            <i class="fas fa-list-ul me-2 text-success"></i> Receipt Items 
                            <span class="badge bg-secondary ms-2" id="items-count">0</span>
                        </h5>

                        <div id="items-summary-list">
                            <div class="empty-state">
                                <i class="fas fa-boxes fa-3x mb-3 text-light"></i>
                                <p class="mb-0">No items added to this receipt yet.</p>
                                <small>Use the builder above to start adding products.</small>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="form-group">
                            <label class="fw-bold">Notes</label>
                            <textarea name="notes" class="form-control" rows="3"
                                placeholder="Any additional notes...">{{ old('notes') }}</textarea>
                        </div>

                        <div class="form-group mt-3 pt-3">
                            <button type="submit" class="btn btn-success btn-lg px-5 shadow-sm" id="submit-btn" disabled>
                                <i class="fas fa-save me-1"></i> Confirm & Save Stock
                            </button>
                            <a href="{{ route('admin.inventory.index') }}" class="btn btn-light btn-lg">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-body">
                    <h5 class="card-title">📋 Quick Tips</h5>
                    <ul class="small">
                        <li>Select supplier to auto-track payable (who gets how much)</li>
                        <li>Select warehouse first to see current stock levels</li>
                        <li>You can add multiple items in one receipt</li>
                        <li>Unit Cost drives supplier due calculation and stock valuation</li>
                        <li>GRN Number helps track supplier receipts</li>
                        <li>Click "Add Another Item" to add more products</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    let addedItems = [];
    const productSearchUrl = @json(route('admin.inventory.api.search-products'));
    const productVariantsEndpoint = '{{ url("admin/inventory/api/product-variants") }}';
    const variantCache = {};
    let currentBuilderVariants = [];
    let currentAttributeGroups = [];

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function normalize(value) {
        return String(value ?? '').trim();
    }

    function getBuilderProductLabel() {
        const $sel = $('#builder-product-id');
        if ($sel.length && typeof $.fn.select2 !== 'undefined' && $sel.data('select2')) {
            const data = $sel.select2('data');
            if (data && data[0] && data[0].text) {
                return data[0].text;
            }
        }
        const native = document.getElementById('builder-product-id');
        if (native && native.options && native.selectedIndex >= 0) {
            return native.options[native.selectedIndex].text || '';
        }
        return '';
    }

    function getBuilderElements() {
        const builder = document.getElementById('item-builder');
        return {
            productSelect: builder.querySelector('.product-select'),
            quantityInput: document.getElementById('builder-quantity'),
            unitCostInput: document.getElementById('builder-unit-cost'),
            variantContainer: builder.querySelector('.variant-attributes-container'),
            variantIdInput: document.getElementById('builder-variant-id'),
            variantSection: document.getElementById('builder-variant-section'),
            hint: builder.querySelector('.variant-hint'),
            details: builder.querySelector('.product-info-content'),
            detailsText: document.getElementById('variant-details-text'),
            imageContainer: document.getElementById('variant-reference-image'),
            imagePreview: document.getElementById('builder-image-preview')
        };
    }

    function setBuilderVariantHint(text, isError = false) {
        const { hint } = getBuilderElements();
        if (!hint) return;
        hint.innerHTML = text;
        hint.className = 'form-text mt-2 d-block ' + (isError ? 'text-danger' : 'text-muted');
    }

    function updateProductDetails(html, imageUrl = null) {
        const { detailsText, imageContainer, imagePreview } = getBuilderElements();
        if (detailsText) detailsText.innerHTML = html;
        
        if (imageContainer && imagePreview) {
            if (imageUrl) {
                // Ensure asset-like path
                let finalUrl = imageUrl;
                if (!finalUrl.startsWith('http') && !finalUrl.startsWith('data:') && !finalUrl.startsWith('/')) {
                    finalUrl = '{{ asset("") }}' + finalUrl;
                }
                imagePreview.src = finalUrl;
                imageContainer.classList.remove('d-none');
            } else {
                imageContainer.classList.add('d-none');
                imagePreview.src = '';
            }
        }
    }

    async function fetchProductVariants(productId, warehouseId = null) {
        let url = `${productVariantsEndpoint}?product_id=${productId}`;
        if (warehouseId) url += `&warehouse_id=${warehouseId}`;
        
        const cacheKey = `${productId}_${warehouseId || 'all'}`;
        if (variantCache[cacheKey]) return variantCache[cacheKey];

        const response = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        });
        if (!response.ok) throw new Error('Failed to load variants');
        const payload = await response.json();
        variantCache[cacheKey] = payload;
        return payload;
    }

    function matchVariant() {
        const { variantIdInput, unitCostInput } = getBuilderElements();
        const selectors = document.querySelectorAll('.attribute-selector');
        const selections = {};
        let allSelected = true;

        selectors.forEach(sel => {
            const attrSlug = sel.dataset.slug;
            const valId = sel.value;
            if (!valId) allSelected = false;
            selections[attrSlug] = valId;
        });

        if (!allSelected) {
            variantIdInput.value = '';
            setBuilderVariantHint('<i class="fas fa-exclamation-circle me-1"></i> Please complete selection');
            updateProductDetails('<small class="text-muted">Selection in progress...</small>');
            return;
        }

        // Find variant where ALL attribute values match
        const matched = currentBuilderVariants.find(v => {
            return Object.entries(selections).every(([slug, valId]) => {
                const vAttr = v.attribute_values.find(av => normalize(av.attribute_slug) === normalize(slug));
                return vAttr && normalize(vAttr.value_id) === normalize(valId);
            });
        });

        showMatchedVariant(matched);
    }

    function showMatchedVariant(matched) {
        const { variantIdInput, unitCostInput } = getBuilderElements();
        if (matched) {
            variantIdInput.value = matched.id;
            if (matched.cost_price > 0) {
                unitCostInput.value = matched.cost_price.toFixed(2);
            }
            setBuilderVariantHint('<span class="text-success fw-bold"><i class="fas fa-check-circle"></i> Variant Identified</span>');
            updateProductDetails(`
                <div class="variant-info-grid">
                    <div class="row mb-1"><div class="col-5 text-muted">SKU:</div><div class="col-7 fw-bold">${matched.sku_code || 'N/A'}</div></div>
                    <div class="row mb-1"><div class="col-5 text-muted">Base Cost:</div><div class="col-7 fw-bold">${matched.cost_price.toFixed(2)}</div></div>
                    <div class="row mb-1"><div class="col-5 text-muted">Stock:</div><div class="col-7"><span class="badge bg-info">${matched.sellable_stock}</span></div></div>
                </div>
            `, matched.image || 'public/backEnd/img/no-image.png');
        } else {
            variantIdInput.value = '';
            setBuilderVariantHint('<i class="fas fa-times-circle me-1"></i> No matching variant found', true);
            updateProductDetails('<div class="text-danger small">Invalid Combination</div>');
        }
    }

    function handleVariantSelectionUpdate() {
        const { productSelect, variantSection, variantIdInput, variantContainer } = getBuilderElements();
        const productId = productSelect.value;
        const warehouseId = document.querySelector('select[name="warehouse_id"]').value;
        
        if (!productId) {
            variantSection.classList.add('d-none');
            updateProductDetails('<small class="text-muted">Select a product to view details</small>');
            return;
        }

        variantSection.classList.remove('d-none');
        variantIdInput.value = '';
        variantContainer.innerHTML = '<div class="d-flex align-items-center"><div class="spinner-border spinner-border-sm text-primary me-2"></div><small>Initializing attributes...</small></div>';
        setBuilderVariantHint('Fetching product configuration...');
        updateProductDetails('<small class="text-muted">Loading variants...</small>');

        fetchProductVariants(productId, warehouseId).then(payload => {
            currentBuilderVariants = payload.variants || [];
            currentAttributeGroups = payload.attribute_groups || [];
            
            if (currentAttributeGroups.length === 0) {
                if (currentBuilderVariants.length > 1) {
                    // Fallback to single dropdown for legacy/simple variable products
                    let html = `
                        <div class="mb-2">
                            <label class="small text-muted mb-1 d-block fw-bold">Select Variant</label>
                            <select class="form-control form-control-sm" id="builder-variant-selector">
                                <option value="">-- Choose Variant --</option>
                                ${currentBuilderVariants.map(v => `<option value="${v.id}">${escapeHtml(v.label)}</option>`).join('')}
                            </select>
                        </div>`;
                    variantContainer.innerHTML = html;
                    setBuilderVariantHint('<i class="fas fa-list me-1"></i> Multiple variants available. Please select one.');
                    
                    document.getElementById('builder-variant-selector').addEventListener('change', function() {
                        const matched = currentBuilderVariants.find(v => v.id == this.value);
                        showMatchedVariant(matched);
                    });
                } else {
                    // Simple product logic
                    variantContainer.innerHTML = '<div class="alert alert-info py-2 mb-0 small border-0"><i class="fas fa-info-circle me-1"></i>Simple product (no attributes)</div>';
                    setBuilderVariantHint('No variants available for this product');
                    updateProductDetails('<div class="small text-success"><i class="fas fa-check-circle"></i> Simple product. Ready to add.</div>');
                    if (currentBuilderVariants.length === 1) {
                        variantIdInput.value = currentBuilderVariants[0].id;
                        showMatchedVariant(currentBuilderVariants[0]);
                    }
                }
                return;
            }

            // Render Individual Attribute Selectors
            let html = '<div class="row g-2">';
            currentAttributeGroups.forEach(group => {
                html += `
                    <div class="col-md-6 mb-2">
                        <label class="small text-muted mb-1 d-block fw-bold">${escapeHtml(group.attribute_name)}</label>
                        <select class="form-control form-control-sm attribute-selector" data-slug="${group.attribute_slug}">
                            <option value="">-- Choose ${group.attribute_name} --</option>
                            ${group.values.map(v => `<option value="${v.value_id}">${escapeHtml(v.value)}</option>`).join('')}
                        </select>
                    </div>`;
            });
            html += '</div>';
            variantContainer.innerHTML = html;
            setBuilderVariantHint('<i class="fas fa-magic me-1"></i> Select attributes to identify variant');

            document.querySelectorAll('.attribute-selector').forEach(sel => {
                sel.addEventListener('change', matchVariant);
            });
        }).catch(err => {
            console.error('Fetch error:', err);
            setBuilderVariantHint('Error connecting to inventory server', true);
            variantContainer.innerHTML = '<div class="text-danger small">System Error: Unable to sync attributes</div>';
        });
    }

    function renderSummary() {
        const container = document.getElementById('items-summary-list');
        const countBadge = document.getElementById('items-count');
        const submitBtn = document.getElementById('submit-btn');
        
        container.innerHTML = '';
        countBadge.textContent = addedItems.length;
        submitBtn.disabled = addedItems.length === 0;

        if (addedItems.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-boxes fa-3x mb-3 text-light"></i>
                    <p class="mb-0">No items added to this receipt yet.</p>
                    <small>Use the builder above to start adding products.</small>
                </div>`;
            return;
        }

        addedItems.forEach((item, index) => {
            const total = (item.quantity * item.unitCost).toFixed(2);
            container.innerHTML += `
                <div class="receipt-item-tile shadow-sm animate__animated animate__fadeIn">
                    <div class="receipt-item-info">
                        <div class="d-flex justify-content-between">
                            <h6 class="mb-1 fw-bold text-primary">${escapeHtml(item.productName)}</h6>
                            <span class="fw-bold text-dark">${total}</span>
                        </div>
                        <div class="receipt-item-meta small text-muted">
                            <span><strong>Qty:</strong> ${item.quantity}</span>
                            <span class="ms-2"><strong>@ Cost:</strong> ${item.unitCost.toFixed(2)}</span>
                            ${item.variantLabel ? `
                                <div class="mt-1">
                                    <span class="badge bg-light text-dark border"><i class="fas fa-tag me-1 text-secondary"></i>${escapeHtml(item.variantLabel)}</span>
                                </div>
                            ` : ''}
                        </div>
                        <input type="hidden" name="items[${index}][product_id]" value="${item.productId}">
                        <input type="hidden" name="items[${index}][variant_id]" value="${item.variantId || ''}">
                        <input type="hidden" name="items[${index}][quantity]" value="${item.quantity}">
                        <input type="hidden" name="items[${index}][unit_cost]" value="${item.unitCost}">
                    </div>
                    <div class="receipt-item-actions">
                        <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="removeItem(${index})" title="Remove Item">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>`;
        });
    }

    function removeItem(index) {
        if (confirm('Are you sure you want to remove this item from the list?')) {
            addedItems.splice(index, 1);
            renderSummary();
        }
    }

    window.removeItem = removeItem;

    document.getElementById('add-item-to-list').addEventListener('click', function() {
        const { productSelect, quantityInput, unitCostInput, variantIdInput } = getBuilderElements();
        
        const productId = productSelect.value;
        const productNameRaw = getBuilderProductLabel();
        const quantity = parseFloat(quantityInput.value) || 0;
        const unitCost = parseFloat(unitCostInput.value) || 0;
        const variantId = variantIdInput.value;
        let variantLabel = '';

        if (!productId) { 
            alert('Selection Required: Please select a product to add.'); 
            productSelect.focus();
            return; 
        }
        if (quantity <= 0) { 
            alert('Invalid Quantity: Please enter a quantity greater than zero.'); 
            quantityInput.focus();
            return; 
        }
        
        if (currentAttributeGroups.length > 0 && !variantId) {
            alert('Variant Required: Please complete all attribute selections to identify the specific item.');
            return;
        }

        // Build a human-readable variant label from matched variant or selectors
        if (variantId) {
            const matched = currentBuilderVariants.find(v => v.id == variantId);
            variantLabel = matched ? matched.label : '';
        }

        addedItems.push({
            productId,
            productName: productNameRaw.split(' (SKU:')[0].trim(),
            variantId,
            variantLabel,
            quantity,
            unitCost
        });

        const builder = document.getElementById('item-builder');
        builder.style.backgroundColor = '#f0fff4';
        setTimeout(() => builder.style.backgroundColor = '#fdfdfe', 300);

        // Reset fields
        quantityInput.value = '';
        unitCostInput.value = '';
        $(productSelect).val(null).trigger('change');
        
        renderSummary();
    });

    // Vanilla searchable dropdown for #builder-product-id (no select2 dependency)
    (function () {
        const selectEl = document.getElementById('builder-product-id');
        if (!selectEl) return;

        // Skip if already enhanced
        if (selectEl.dataset.widgetInit === '1') return;

        const parent = selectEl.parentElement;
        if (!parent) return;

        // Build input + dropdown
        const wrapperId = selectEl.id || 'builder-product-id';
        selectEl.dataset.widgetInit = '1';

        const input = document.createElement('input');
        input.type = 'text';
        input.autocomplete = 'off';
        input.className = 'form-control product-search-input';
        input.placeholder = 'Search product by keyword...';
        input.setAttribute('data-product-search-for', wrapperId);

        const dropdown = document.createElement('div');
        dropdown.className = 'list-group position-absolute product-search-dropdown';
        dropdown.style.zIndex = '9999';
        dropdown.style.display = 'none';
        dropdown.style.maxHeight = '240px';
        dropdown.style.overflowY = 'auto';
        dropdown.setAttribute('data-product-dropdown-for', wrapperId);

        parent.style.position = parent.style.position || 'relative';

        parent.insertBefore(input, selectEl);
        parent.insertBefore(dropdown, selectEl);

        // Hide the original select — only the text search input is visible
        selectEl.style.display = 'none';

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '<')
                .replace(/>/g, '>')
                .replace(/"/g, '"')
                .replace(/'/g, '&#039;');
        }

        function hideDropdown() {
            dropdown.style.display = 'none';
            dropdown.innerHTML = '';
        }

        function getOptionTextByValue(value) {
            const opt = Array.from(selectEl.options || []).find(o => String(o.value) === String(value));
            return opt ? (opt.textContent || '') : '';
        }

        // Prefill input if select already has a value
        if (selectEl.value) {
            input.value = getOptionTextByValue(selectEl.value);
        }

        let abortCtrl = null;
        let debounceTimer = null;
        let lastQuery = '';

        async function search(q) {
            const query = String(q ?? '').trim();
            if (query.length < 2) {
                hideDropdown();
                return [];
            }

            if (abortCtrl) abortCtrl.abort();
            abortCtrl = new AbortController();

            const url = new URL(productSearchUrl, window.location.origin);
            // backend route used by select2: expects q and returns {results:[{id,text/sku}]}
            url.searchParams.set('q', query);
            url.searchParams.set('limit', '25');

            const res = await fetch(url.toString(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: abortCtrl.signal
            });

            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Search failed');

            return data.results || data.data || [];
        }

        function renderResults(items) {
            const safeItems = Array.isArray(items) ? items : [];
            if (safeItems.length === 0) {
                dropdown.innerHTML = '<div class="list-group-item text-muted small">No products found</div>';
                dropdown.style.display = 'block';
                return;
            }

            dropdown.innerHTML = safeItems.map(item => {
                const id = item.id ?? item.product_id ?? '';
                const name = item.name ?? item.text ?? item.label ?? '';
                const sku = item.sku ?? item.sku_code ?? '';
                const display = sku ? `${name} (SKU: ${sku})` : name;

                return `
                    <button type="button"
                        class="list-group-item list-group-item-action product-search-item"
                        data-value="${escapeHtml(id)}"
                        data-text="${escapeHtml(display)}">
                        ${escapeHtml(display)}
                    </button>
                `;
            }).join('');

            dropdown.style.display = 'block';
        }

        function setSelected(value, text) {
            // Clear existing options (except the empty placeholder)
            Array.from(selectEl.options).forEach(opt => {
                if (opt.value !== '') selectEl.removeChild(opt);
            });

            if (value) {
                // Create a new option so the select can actually hold this value
                const opt = document.createElement('option');
                opt.value = String(value);
                opt.textContent = String(text || value);
                opt.selected = true;
                selectEl.appendChild(opt);
            }

            selectEl.value = value ? String(value) : '';
            input.value = text ? String(text) : '';
            hideDropdown();
            selectEl.dispatchEvent(new Event('change', { bubbles: true }));
        }

        input.addEventListener('input', function () {
            lastQuery = input.value;

            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(async () => {
                try {
                    const results = await search(lastQuery);

                    // ignore stale keystrokes
                    if (lastQuery !== input.value) return;

                    const normalized = results.map(r => ({
                        id: r.id ?? r.product_id ?? '',
                        name: r.name ?? r.text ?? r.label ?? '',
                        sku: r.sku ?? r.sku_code ?? ''
                    }));

                    renderResults(normalized);
                } catch (e) {
                    hideDropdown();
                }
            }, 250);
        });

        document.addEventListener('click', function (e) {
            const within = dropdown.contains(e.target) || input.contains(e.target);
            if (!within) hideDropdown();
        });

        dropdown.addEventListener('click', function (e) {
            const btn = e.target.closest('button.product-search-item');
            if (!btn) return;

            setSelected(btn.getAttribute('data-value'), btn.getAttribute('data-text'));
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') hideDropdown();
        });

        // Keep existing dependent behavior
        selectEl.addEventListener('change', handleVariantSelectionUpdate);

        // Refresh variants/stock if warehouse changes
        const whSelect = document.querySelector('select[name="warehouse_id"]');
        if (whSelect) {
            whSelect.addEventListener('change', function () {
                if (selectEl.value) {
                    handleVariantSelectionUpdate();
                }
            });
        }
    })();

</script>
@endsection
