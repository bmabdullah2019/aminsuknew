@extends('backEnd.layouts.master')

@section('title', 'Manage Product Variants')

@section('content')
@php
    $catalog = $attributes->map(function ($attribute) {
        return [
            'id' => (int) $attribute->id,
            'name' => (string) $attribute->name,
            'slug' => (string) $attribute->slug,
            'sort_order' => (int) $attribute->sort_order,
            'values' => $attribute->values->map(function ($value) {
                return [
                    'id' => (int) $value->id,
                    'value' => (string) $value->value,
                    'meta' => is_array($value->meta ?? null) ? $value->meta : null,
                ];
            })->values()->all(),
        ];
    })->values();

    $oldVariantRows = old('variants');
    $variantRows = is_array($oldVariantRows) ? $oldVariantRows : $variants->toArray();
    $normalizedRows = collect($variantRows)->map(function ($row) use ($catalog) {
        $row = (array) $row;
        $valueIds = [];
        if (!empty($row['attribute_value_ids']) && is_array($row['attribute_value_ids'])) {
            $valueIds = collect($row['attribute_value_ids'])->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->values()->all();
        } elseif (!empty($row['attribute_value_map']) && is_array($row['attribute_value_map'])) {
            $valueIds = collect($row['attribute_value_map'])->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->values()->all();
        }

        return [
            'id' => isset($row['id']) ? (int) $row['id'] : null,
            'sku_code' => (string) ($row['sku_code'] ?? ''),
            'price' => (float) ($row['price'] ?? 0),
            'cost_price' => (float) ($row['cost_price'] ?? 0),
            'status' => (string) ($row['status'] ?? 'active'),
            'image' => (string) ($row['image'] ?? ''),
            'image_url' => (string) ($row['image_url'] ?? ''),
            'attribute_value_ids' => $valueIds,
        ];
    })->values();

    $selectedIds = old('selected_attribute_ids', $selectedAttributeIds);
@endphp

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right d-flex gap-2">
                    <a href="{{ route('admin.catalog-attributes.index') }}" class="btn btn-outline-secondary rounded-pill">Global Attributes</a>
                    <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-secondary rounded-pill">Back Product</a>
                </div>
                <h4 class="page-title">Manage Variants: {{ $product->name }}</h4>
            </div>
        </div>
    </div>

    <!-- Success Messages -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('message'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-info-circle"></i> {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-2">
                    @php
                        $variantProductImage = optional($product->image)->image;
                        if (!$variantProductImage && !empty($product->thumbnail)) {
                            $thumb = ltrim((string) $product->thumbnail, '/');
                            $variantProductImage = \Illuminate\Support\Str::startsWith($thumb, ['public/', 'storage/'])
                                ? $thumb
                                : 'storage/' . $thumb;
                        }
                        $variantProductImage = $variantProductImage ?: 'public/frontEnd/images/no-image.jpg';
                    @endphp
                    <img
                        src="{{ asset($variantProductImage) }}"
                        class="img-fluid rounded border"
                        alt="{{ $product->name }}"
                    >
                </div>
                <div class="col-md-10">
                    <p class="mb-1"><strong>Base SKU:</strong> {{ $product->sku ?? 'N/A' }}</p>
                    <p class="mb-1"><strong>Base Price:</strong> {{ number_format((float) ($product->new_price ?? 0), 2) }}</p>
                    <p class="mb-0 text-muted">Define unlimited reusable-attribute combinations (Shopify-style variants).</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Messages Alert -->
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h4 class="alert-heading"><i class="fas fa-exclamation-circle"></i> Validation Errors</h4>
            <hr>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.products.variants.update', $product->id) }}" enctype="multipart/form-data" id="variantForm">
        @csrf

        <!-- Quick Guide Card -->
        <div class="alert alert-info alert-dismissible fade show mb-3" role="alert">
            <div class="d-flex gap-2">
                <div class="flex-shrink-0">
                    <i class="fas fa-lightbulb fa-lg"></i>
                </div>
                <div>
                    <h6 class="alert-heading mb-1">✨ Variant Management Tips</h6>
                    <ol class="small mb-0 ms-3">
                        <li><strong>Step 1:</strong> Check which attributes apply (e.g., Color, Size, Age)</li>
                        <li><strong>Step 2:</strong> Click "Add Variant Row" for each combination you want to create</li>
                        <li><strong>Step 3:</strong> Enter SKU, Price, and select attribute values</li>
                        <li><strong>Step 4:</strong> Click "Save Variants" to finish</li>
                    </ol>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-tag text-primary"></i> Step 1: Select Attributes For This Product
                    </h5>
                    <span class="badge bg-primary" id="attributeCountBadge">0 selected</span>
                </div>
            </div>
            <div class="card-body">
                @forelse ($attributes as $attribute)
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label class="form-check d-flex align-items-center h-100">
                                <input
                                    class="form-check-input variant-attribute-toggle me-2"
                                    type="checkbox"
                                    name="selected_attribute_ids[]"
                                    value="{{ $attribute->id }}"
                                    @checked(in_array((int) $attribute->id, array_map('intval', (array) $selectedIds), true))
                                >
                                <span class="form-check-label">
                                    <strong>{{ $attribute->name }}</strong>
                                </span>
                            </label>
                        </div>
                        <div class="col-md-8">
                            <small class="text-muted d-flex flex-wrap gap-1">
                                @foreach ($attribute->values as $value)
                                    <span class="badge bg-light text-dark">{{ $value->value }}</span>
                                @endforeach
                            </small>
                        </div>
                    </div>
                @empty
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>No attributes found!</strong> 
                        <a href="{{ route('admin.catalog-attributes.index') }}" class="btn btn-sm btn-primary ms-2">Create Attributes First</a>
                    </div>
                @endforelse
                @error('selected_attribute_ids')
                    <div class="alert alert-danger mt-3 mb-0"><i class="fas fa-times-circle"></i> {{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-copy text-success"></i> Step 2: Create Variant Combinations
                    </h5>
                    <span class="badge bg-success" id="variantCountBadge">0 variants</span>
                </div>
            </div>
            <div class="card-body">
                <!-- No variants message -->
                <div id="noVariantsMessage" class="text-center py-4 text-muted" style="display: none;">
                    <i class="fas fa-box fa-3x mb-3 opacity-50"></i>
                    <p><strong>No variants yet.</strong> Click the button below to get started!</p>
                </div>

                <!-- Variants container -->
                <div id="variantRows"></div>

                <!-- Action Buttons -->
                <div class="d-flex gap-2 mt-4">
                    <button type="button" class="btn btn-success" id="addVariantRowBtn">
                        <i class="fas fa-plus-circle"></i> Add Variant
                    </button>
                    <button type="button" class="btn btn-outline-info d-none" id="debugInfoBtn" title="Show form structure">
                        <i class="fas fa-bug"></i> Debug
                    </button>
                </div>
            </div>
        </div>

        <!-- Hidden debug field -->
        <input type="hidden" id="debugVariantJsonData" name="debug_variant_data" value="">

        <!-- Submit Section -->
        <div class="card mt-3 border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1"><i class="fas fa-check-circle text-success"></i> Ready to Save?</h5>
                        <small class="text-muted">All variants will be updated with the selected attributes and prices.</small>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-save"></i> Save All Variants
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('css')
<style>
    /* Variant Card Styling */
    .variant-row {
        transition: all 0.3s ease;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .variant-row:hover {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        transform: translateY(-2px);
    }

    .variant-row .card-header {
        border-bottom: 2px solid #f0f0f0;
    }

    /* Attribute value badges in step 1 */
    .badge.bg-light {
        font-size: 0.75rem;
        padding: 0.35rem 0.6rem;
    }

    /* Info alert styling */
    .alert-info {
        background-color: #e3f2fd;
        border-color: #64b5f6;
    }

    /* Step headers */
    .card-header {
        background: linear-gradient(135deg, #f5f7fa 0%, #f0f4f8 100%);
    }

    /* Status badge colors */
    .badge-active {
        background-color: #10b981;
    }

    .badge-inactive {
        background-color: #ef4444;
    }

    /* Currency input alignment */
    .input-group .form-control.text-end {
        text-align: right !important;
    }

    /* Attribute label styling */
    .variant-row .form-label {
        font-size: 0.875rem;
        margin-bottom: 0.5rem;
        color: #374151;
    }

    /* Select styling */
    .variant-row .form-select {
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
    }

    .variant-row .form-select:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    /* Quick guide custom styling */
    .alert-info ol {
        line-height: 1.8;
    }

    /* No variants message */
    #noVariantsMessage {
        padding: 3rem 1rem;
    }

    /* Button group spacing */
    .btn-group {
        gap: 0.5rem;
    }

    /* Variant count badge */
    #attributeCountBadge,
    #variantCountBadge {
        font-size: 0.875rem;
        font-weight: 600;
        padding: 0.4rem 0.8rem;
    }

    /* Better form spacing */
    .variant-row .col-md-3,
    .variant-row .col-md-2,
    .variant-row .col-lg-3 {
        margin-bottom: 1rem;
    }

    /* Disabled state */
    .variant-row.disabled {
        opacity: 0.6;
        pointer-events: none;
    }
</style>
@endsection

@section('script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const attributeCatalog = @json($catalog);
        const initialRows = @json($normalizedRows);
        const variantRowsContainer = document.getElementById('variantRows');
        const addVariantRowBtn = document.getElementById('addVariantRowBtn');
        const noVariantsMessage = document.getElementById('noVariantsMessage');
        const attributeCountBadge = document.getElementById('attributeCountBadge');
        const variantCountBadge = document.getElementById('variantCountBadge');
        const attributeToggleSelector = '.variant-attribute-toggle';

        // Validation check
        if (!variantRowsContainer) {
            console.error('variantRows container not found');
            return;
        }

        if (!addVariantRowBtn) {
            console.error('addVariantRowBtn button not found');
            return;
        }

        const valueIndex = {};
        attributeCatalog.forEach((attribute) => {
            (attribute.values || []).forEach((value) => {
                valueIndex[String(value.id)] = {
                    attributeId: Number(attribute.id),
                    valueText: String(value.value || ''),
                };
            });
        });

        let nextIndex = 0;

        // Update badges and visibility
        function updateUI() {
            const selectedAttrs = selectedAttributeIds().length;
            const variantRows = variantRowsContainer.querySelectorAll('.variant-row').length;

            if (attributeCountBadge) {
                attributeCountBadge.textContent = `${selectedAttrs} selected`;
            }

            if (variantCountBadge) {
                variantCountBadge.textContent = `${variantRows} variant${variantRows !== 1 ? 's' : ''}`;
            }

            if (noVariantsMessage) {
                noVariantsMessage.style.display = variantRows === 0 ? 'block' : 'none';
            }
        }

        function selectedAttributeIds() {
            return Array.from(document.querySelectorAll(attributeToggleSelector + ':checked'))
                .map((node) => Number(node.value))
                .filter((value) => value > 0);
        }

        function selectedAttributes() {
            const selectedIds = selectedAttributeIds();
            return attributeCatalog.filter((attribute) => selectedIds.includes(Number(attribute.id)));
        }

        function toNumber(value) {
            const parsed = Number(value);
            return Number.isFinite(parsed) ? parsed : 0;
        }

        function buildAttributeValueMap(attributeValueIds) {
            const map = {};
            (attributeValueIds || []).forEach((valueId) => {
                const key = String(valueId);
                if (valueIndex[key]) {
                    map[valueIndex[key].attributeId] = Number(valueId);
                }
            });

            return map;
        }

        function makeOption(value, selectedValueId) {
            const id = Number(value.id);
            const selected = id === Number(selectedValueId) ? 'selected' : '';
            return `<option value="${id}" ${selected}>${String(value.value || '')}</option>`;
        }

        function renderRow(rowData) {
            const index = nextIndex++;
            const selectedAttrs = selectedAttributes();
            const valueMap = buildAttributeValueMap(rowData.attribute_value_ids || []);

            // Build attribute selection badges/summary
            const attributeSummary = selectedAttrs.length > 0 
                ? selectedAttrs.map(attr => `<span class="badge bg-info">${attr.name}</span>`).join(' ')
                : '<span class="text-muted small">Select attributes in Step 1 first</span>';

            // Build attribute input fields
            const attributeFieldHtml = selectedAttrs.map((attribute) => {
                const selectedValueId = valueMap[Number(attribute.id)] || '';
                const options = [
                    '<option value="">-- Select ' + attribute.name + ' --</option>',
                    ...(attribute.values || []).map((value) => makeOption(value, selectedValueId)),
                ].join('');

                return `
                    <div class="col-md-6 col-lg-3 mb-3">
                        <label class="form-label fw-bold text-dark">${attribute.name} <span class="text-danger">*</span></label>
                        <select class="form-control form-select" 
                            name="variants[${index}][attribute_value_map][${attribute.id}]"
                            required>
                            ${options}
                        </select>
                    </div>
                `;
            }).join('');

            const imagePreview = rowData.image_url
                ? `<div class="mb-1 variant-image-server"><small class="text-muted d-block">Current image</small><img src="${rowData.image_url}" alt="Variant image" class="img-thumbnail" style="max-height: 64px;"></div>`
                : '';

            const idField = rowData.id ? `<input type="hidden" name="variants[${index}][id]" value="${Number(rowData.id)}">` : '';
            const statusActiveSelected = String(rowData.status || 'active') === 'active' ? 'selected' : '';
            const statusInactiveSelected = String(rowData.status || 'active') === 'inactive' ? 'selected' : '';

            // Build a nice variant label
            const variantLabel = selectedAttrs.length > 0 
                ? selectedAttrs.map(attr => `<strong>${attr.name}:</strong> <span class="text-secondary">${valueMap[attr.id] ? '✓ Selected' : '⚠ Pending'}</span>`).join(' | ')
                : 'New Variant';

            const rowHtml = `
                <div class="card mb-3 variant-row border-left-4" style="border-left: 4px solid #007bff;">
                    ${idField}
                    
                    <!-- Variant Header -->
                    <div class="card-header bg-light">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <small class="text-muted d-block mb-1">SKU: <span class="font-monospace text-dark" style="font-weight: 500;">${String(rowData.sku_code || '—')}</span></small>
                                <div>${variantLabel}</div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-variant-row" title="Remove this variant">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        </div>
                    </div>

                    <!-- Variant Body -->
                    <div class="card-body">
                        <!-- Pricing & Status Row -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">SKU Code <span class="text-danger">*</span></label>
                                <input type="text" 
                                    name="variants[${index}][sku_code]" 
                                    class="form-control" 
                                    placeholder="e.g., PROD-RED-L"
                                    value="${String(rowData.sku_code || '')}" 
                                    required>
                                <small class="text-muted d-block mt-1">Unique identifier for this variant</small>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Sale Price <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">৳</span>
                                    <input type="number" step="0.01" min="0" 
                                        name="variants[${index}][price]" 
                                        class="form-control text-end" 
                                        value="${toNumber(rowData.price).toFixed(2)}" 
                                        required>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Cost Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">৳</span>
                                    <input type="number" step="0.01" min="0" 
                                        name="variants[${index}][cost_price]" 
                                        class="form-control text-end" 
                                        value="${toNumber(rowData.cost_price).toFixed(2)}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Status <span class="text-danger">*</span></label>
                                <select name="variants[${index}][status]" class="form-control form-select" required>
                                    <option value="active" ${statusActiveSelected}>✓ Active</option>
                                    <option value="inactive" ${statusInactiveSelected}>✗ Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Variant Images</label>
                                <input type="file" name="variants[${index}][images][]" class="form-control variant-row-image-file" accept="image/*" multiple>
                                <div class="variant-image-preview-host mt-2">${imagePreview}</div>
                            </div>
                        </div>

                        <!-- Attributes Row -->
                        ${selectedAttrs.length > 0 ? `
                            <div class="border-top pt-3">
                                <h6 class="mb-3"><i class="fas fa-sliders-h"></i> Attribute Values</h6>
                                <div class="row">
                                    ${attributeFieldHtml}
                                </div>
                            </div>
                        ` : `
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-info-circle"></i> Select attributes in <strong>Step 1</strong> to enable variant selection here.
                            </div>
                        `}
                    </div>
                </div>
            `;

            variantRowsContainer.insertAdjacentHTML('beforeend', rowHtml);
            updateUI();
        }

        function readRowsFromDom() {
            const rows = [];

            variantRowsContainer.querySelectorAll('.variant-row').forEach((row) => {
                const idInput = row.querySelector('input[name$="[id]"]');
                const skuInput = row.querySelector('input[name$="[sku_code]"]');
                const priceInput = row.querySelector('input[name$="[price]"]');
                const costInput = row.querySelector('input[name$="[cost_price]"]');
                const statusInput = row.querySelector('select[name$="[status]"]');
                const attributeInputs = row.querySelectorAll('select[name*="[attribute_value_map]"]');

                const attributeValueIds = [];
                attributeInputs.forEach((selectEl) => {
                    const valueId = Number(selectEl.value || 0);
                    if (valueId > 0) {
                        attributeValueIds.push(valueId);
                    }
                });

                rows.push({
                    id: idInput ? Number(idInput.value || 0) : null,
                    sku_code: skuInput ? String(skuInput.value || '') : '',
                    price: priceInput ? Number(priceInput.value || 0) : 0,
                    cost_price: costInput ? Number(costInput.value || 0) : 0,
                    status: statusInput ? String(statusInput.value || 'active') : 'active',
                    attribute_value_ids: attributeValueIds,
                });
            });

            return rows;
        }

        function rebuildRowsForSelectedAttributes() {
            const currentRows = readRowsFromDom();
            variantRowsContainer.innerHTML = '';
            nextIndex = 0;

            if (currentRows.length === 0) {
                renderRow({
                    id: null,
                    sku_code: '',
                    price: 0,
                    cost_price: 0,
                    status: 'active',
                    image: '',
                    image_url: '',
                    attribute_value_ids: [],
                });
                return;
            }

            currentRows.forEach((row) => renderRow(row));
            updateUI();
        }

        function ensureAtLeastOneRow() {
            if (variantRowsContainer.querySelectorAll('.variant-row').length === 0) {
                renderRow({
                    id: null,
                    sku_code: '',
                    price: 0,
                    cost_price: 0,
                    status: 'active',
                    image: '',
                    image_url: '',
                    attribute_value_ids: [],
                });
            }
        }

        // Add click listener using proper method
        addVariantRowBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('Add Variant Row clicked');
            
            renderRow({
                id: null,
                sku_code: '',
                price: 0,
                cost_price: 0,
                status: 'active',
                image: '',
                image_url: '',
                attribute_value_ids: [],
            });
        });

        // Listen for attribute toggle changes
        document.addEventListener('change', function (event) {
            if (event.target.matches(attributeToggleSelector)) {
                console.log('Attribute toggled:', event.target.value);
                rebuildRowsForSelectedAttributes();
            }
        });

        // Listen for remove button clicks using event delegation
        document.addEventListener('click', function (event) {
            const removeBtn = event.target.closest('.remove-variant-row');
            if (!removeBtn) {
                return;
            }
            
            event.preventDefault();
            event.stopPropagation();
            
            console.log('Remove variant row clicked');
            removeBtn.closest('.variant-row').remove();
            ensureAtLeastOneRow();
            updateUI();
        });

        // Initialize with existing rows or create empty row
        if (Array.isArray(initialRows) && initialRows.length > 0) {
            console.log('Initializing with', initialRows.length, 'rows');
            initialRows.forEach((row) => renderRow(row));
        } else {
            console.log('Initializing with empty row');
            ensureAtLeastOneRow();
        }
        
        // Initial UI update
        updateUI();

        variantRowsContainer.addEventListener('change', function (e) {
            const input = e.target;
            if (!input || !input.matches || !input.matches('.variant-row-image-file')) {
                return;
            }
            const col = input.closest('.col-md-3');
            if (!col) return;
            const host = col.querySelector('.variant-image-preview-host');
            if (!host) return;
            const oldLive = host.querySelector('.variant-image-live');
            if (oldLive) {
                oldLive.remove();
            }
            const file = input.files && input.files[0];
            if (!file) {
                return;
            }
            const reader = new FileReader();
            reader.onload = function (ev) {
                const wrap = document.createElement('div');
                wrap.className = 'variant-image-live mt-2';
                wrap.innerHTML = '<small class="text-muted d-block">New upload preview</small><img src="' + ev.target.result + '" class="img-thumbnail" style="max-height: 64px;" alt="">';
                host.appendChild(wrap);
            };
            reader.readAsDataURL(file);
        });

        // Form validation before submit
        const variantForm = document.getElementById('variantForm');
        const debugJsonField = document.getElementById('debugVariantJsonData');
        
        if (variantForm) {
            variantForm.addEventListener('submit', function (e) {
                const selectedAttrs = selectedAttributeIds();
                const variantRows = variantRowsContainer.querySelectorAll('.variant-row');
                
                console.log('🔍 Form submit handler - Checking validation');
                console.log('Selected attributes:', selectedAttrs.length, selectedAttrs);
                console.log('Variant rows in DOM:', variantRows.length);
                
                if (selectedAttrs.length === 0) {
                    e.preventDefault();
                    alert('⚠️ Please select at least one attribute in Step 1');
                    console.log('❌ Validation failed: No attributes selected');
                    return false;
                }
                
                if (variantRows.length === 0) {
                    e.preventDefault();
                    alert('⚠️ Please add at least one variant row');
                    console.log('❌ Validation failed: No variant rows');
                    return false;
                }
                
                // Collect all form data to see what's being sent
                const formData = new FormData(variantForm);
                const variantDataArray = [];
                
                console.log('📋 Analyzing DOM structure...');
                variantRows.forEach((row, rowIdx) => {
                    console.log(`\n📦 Row ${rowIdx}:`);
                    
                    const skuInput = row.querySelector('input[name$="[sku_code]"]');
                    const priceInput = row.querySelector('input[name$="[price]"]');
                    const costInput = row.querySelector('input[name$="[cost_price]"]');
                    const statusInput = row.querySelector('select[name$="[status]"]');
                    const attributeSelects = row.querySelectorAll('select[name*="[attribute_value_map]"]');
                    
                    const rowData = {
                        index: rowIdx,
                        sku: skuInput ? skuInput.value : 'MISSING',
                        price: priceInput ? priceInput.value : 'MISSING',
                        cost_price: costInput ? costInput.value : 'MISSING',
                        status: statusInput ? statusInput.value : 'MISSING',
                        attributes: []
                    };
                    
                    attributeSelects.forEach((select, attrIdx) => {
                        const name = select.getAttribute('name');
                        const value = select.value;
                        console.log(`  - Attribute ${attrIdx}: name="${name}", value="${value}"`);
                        rowData.attributes.push({ name, value });
                    });
                    
                    console.log('  Data:', rowData);
                    variantDataArray.push(rowData);
                    
                    // Validate
                    if (!skuInput || !skuInput.value.trim()) {
                        console.warn(`  ❌ SKU is empty`);
                        return;
                    }
                    
                    if (!priceInput || !priceInput.value) {
                        console.warn(`  ❌ Price is empty`);
                        return;
                    }
                    
                    // Check attribute selections
                    attributeSelects.forEach((select, attrIdx) => {
                        if (!select.value) {
                            console.warn(`  ❌ Attribute ${attrIdx} not selected`);
                        }
                    });
                });
                
                // Store debug data
                if (debugJsonField) {
                    debugJsonField.value = JSON.stringify({
                        selected_attributes: selectedAttrs,
                        variant_rows: variantDataArray,
                        total_rows: variantRows.length
                    });
                }
                
                console.log('✅ Form validation passed - submitting with', variantRows.length, 'rows');
                console.log('📤 Debug data stored for server inspection');
                return true;
            });
        }
        
        console.log('Variant manager initialized successfully');
    });

    // Debug Info Handler
    document.getElementById('debugInfoBtn')?.addEventListener('click', function (e) {
        e.preventDefault();
        const container = document.getElementById('variantRows');
        const rows = container.querySelectorAll('.variant-row');
        const selectedAttrs = Array.from(document.querySelectorAll('.variant-attribute-toggle:checked'))
            .map(n => n.value);
        
        let debugInfo = `
=== FORM DEBUG INFO ===

Selected Attributes: ${selectedAttrs.join(', ') || 'NONE'}
Total Variant Rows: ${rows.length}

`;
        
        rows.forEach((row, idx) => {
            const sku = row.querySelector('input[name$="[sku_code]"]')?.value || 'EMPTY';
            const price = row.querySelector('input[name$="[price]"]')?.value || 'EMPTY';
            const attrs = row.querySelectorAll('select[name*="[attribute_value_map]"]');
            const attrValues = Array.from(attrs).map(s => `${s.name}=${s.value}`).join(', ');
            
            debugInfo += `Row ${idx + 1}:
  SKU: ${sku}
  Price: ${price}
  Attributes: ${attrValues || 'NONE'}
  
`;
        });
        
        debugInfo += `
Right-click → Inspect to see Form Data
Press F12 Console to see detailed logs
`;

        alert(debugInfo);
    });

    // Toastr Notifications
    document.addEventListener('DOMContentLoaded', function () {
        // Display Toastr messages if Toastr library is available
        if (typeof toastr !== 'undefined') {
            const successAlert = document.querySelector('.alert-success');
            const infoAlert = document.querySelector('.alert-info');
            
            if (successAlert) {
                const successMsg = successAlert.textContent.trim();
                if (successMsg.includes('✓') || successMsg.length > 0) {
                    toastr.success(successMsg, 'Success', {
                        positionClass: 'toast-top-right',
                        timeOut: 5000
                    });
                }
            }
            
            if (infoAlert) {
                const infoMsg = infoAlert.textContent.trim();
                if (infoMsg.includes('ℹ') || infoMsg.length > 0) {
                    toastr.info(infoMsg, 'Info', {
                        positionClass: 'toast-top-right',
                        timeOut: 5000
                    });
                }
            }
        }
        
        console.log('Notification listeners initialized');
    });
</script>
@endsection
