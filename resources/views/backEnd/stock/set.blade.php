@extends('backEnd.layouts.master')
@section('title','Bulk Stock Management')
@section('content')
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{route('admin.stock.inventory')}}" class="btn btn-info rounded-pill"><i class="fe-grid"></i> Advanced Inventory</a>
                    <a href="{{route('admin.stock.balance')}}" class="btn btn-outline-primary rounded-pill"><i class="fe-list"></i> Stock Balance</a>
                    <a href="{{route('admin.stock.movements')}}" class="btn btn-info rounded-pill"><i class="fe-activity"></i> Movements</a>
                </div>
                <h4 class="page-title">Bulk Stock Management - {{$warehouse->name}}</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <!-- Warehouse Selector -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-3">Selected Warehouse: <strong>{{$warehouse->name}}</strong></h6>
                            <p class="text-muted small mb-0">
                                Code: {{$warehouse->code}} |
                                Location: {{$warehouse->address or 'No address specified'}}
                            </p>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="btn-group">
                                @foreach($warehouses as $wh)
                                    <a href="{{ route('admin.stock.set', ['warehouse_id' => $wh->id, 'search' => ($searchTerm ?? '') !== '' ? $searchTerm : null]) }}"
                                       class="btn btn-sm {{ $wh->id == $selectedWarehouseId ? 'btn-primary' : 'btn-outline-primary' }}">
                                        {{$wh->name}}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-2">
                    <form method="GET" action="{{ route('admin.stock.set') }}" class="row g-2 align-items-end">
                        <input type="hidden" name="warehouse_id" value="{{ $selectedWarehouseId }}">
                        <div class="col-md-8">
                            <label class="form-label mb-1">Search Product Name</label>
                            <input
                                type="text"
                                name="search"
                                value="{{ $searchTerm ?? '' }}"
                                class="form-control"
                                maxlength="120"
                                placeholder="Type product name..."
                            >
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fe-search"></i> Search
                            </button>
                            <a href="{{ route('admin.stock.set', ['warehouse_id' => $selectedWarehouseId]) }}" class="btn btn-outline-secondary">
                                <i class="fe-x"></i> Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Update Form -->
    <form id="bulkStockForm" method="POST" action="{{route('admin.stock.bulk-adjust')}}">
        @csrf
        <input type="hidden" name="warehouse_id" value="{{$selectedWarehouseId}}">

        <!-- Controls -->
        <div class="row mb-3">
            <div class="col-md-8">
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary btn-sm" id="selectAllBtn">
                        <i class="fe-check-square"></i> Select All
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="clearSelectionBtn">
                        <i class="fe-x"></i> Clear Selection
                    </button>
                    <span class="align-self-center text-muted small" id="selectionCount">
                        0 products selected
                    </span>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <button type="submit" class="btn btn-success" id="updateStockBtn" disabled>
                    <i class="fe-save"></i> Update Selected Stock
                </button>
            </div>
        </div>

        <!-- Products Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="productsTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width: 3%;">
                                            <input type="checkbox" id="masterCheckbox">
                                        </th>
                                        <th style="width: 8%;">Image</th>
                                        <th style="width: 25%;">Product Details</th>
                                        <th style="width: 12%;">Category</th>
                                        <th style="width: 10%;">Current Stock</th>
                                        <th style="width: 12%;">New Quantity</th>
                                        <th style="width: 18%;">Variant</th>
                                        <th style="width: 10%;">Status</th>
                                        <th style="width: 10%;">Available</th>
                                        <th style="width: 10%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($products as $product)
                                    <tr data-product-id="{{ $product->id }}">
                                        <td>
                                            <input type="checkbox" class="product-checkbox"
                                                   name="selected_products[]"
                                                   value="{{ $product->id }}">
                                        </td>
                                        <td>
                                            @if($product->images->first())
                                                <img src="{{ asset($product->images->first()->image) }}"
                                                     alt="{{ $product->name }}" class="rounded" style="width: 40px; height: 40px; object-fit: cover;">
                                            @else
                                                <div class="bg-light rounded d-flex align-items-center justify-content-center"
                                                     style="width: 40px; height: 40px;">
                                                    <i class="fe-image text-muted"></i>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="product-info">
                                                <div>
                                                    <strong>{{ $product->name }}</strong><br>
                                                    <small class="text-muted">
                                                        SKU: {{ $product->sku ?: 'N/A' }}<br>
                                                        Code: {{ $product->product_code ?: 'N/A' }}
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $product->category->name ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-info">
                                                {{ number_format($product->current_stock, 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm quantity-input"
                                                   name="stock_updates[{{ $product->id }}][quantity]"
                                                   value="{{ number_format((float) $product->current_stock, 2, '.', '') }}"
                                                   step="0.01" min="0" style="width: 100px;">
                                            <input type="hidden" name="stock_updates[{{ $product->id }}][product_id]"
                                                   value="{{ $product->id }}">
                                        </td>
                                        <td>
                                            <div class="row-variant-selector"
                                                 data-row-variants="[]"
                                                 data-row-groups="[]"
                                                 data-row-selected-values="{}"
                                                 data-row-has-variant="0"
                                                 data-row-variant-loaded="0">
                                                <div class="row g-1 row-variant-groups">
                                                    <small class="text-muted">Will load on select</small>
                                                </div>
                                                <input type="hidden" class="row-variant-id-input" value="">
                                                <small class="form-text text-muted row-variant-hint d-block mt-1">Will load on select</small>
                                            </div>
                                        </td>
                                        <td>
                                            @if($product->current_stock <= 0)
                                                <span class="badge bg-danger">Out of Stock</span>
                                            @elseif($product->current_stock <= $product->reorder_point)
                                                <span class="badge bg-warning text-dark">Low Stock</span>
                                            @else
                                                <span class="badge bg-success">In Stock</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                {{ number_format($product->available_stock, 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary quick-adjust-btn"
                                                        data-product-id="{{ $product->id }}"
                                                        data-product-name="{{ $product->name }}"
                                                        data-current-stock="{{ $product->current_stock }}"
                                                        title="Quick Adjust">
                                                    <i class="fe-edit"></i>
                                                </button>
                                                <a href="{{ route('admin.stock.edit', ['warehouseId' => $selectedWarehouseId, 'productId' => $product->id]) }}"
                                                   class="btn btn-outline-warning" title="Edit Stock Details">
                                                    <i class="fe-settings"></i>
                                                </a>
                                                <a href="{{ route('admin.stock.show', ['warehouseId' => $selectedWarehouseId, 'productId' => $product->id]) }}"
                                                   class="btn btn-outline-info" title="View Details">
                                                    <i class="fe-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-4">
                                            <i class="fe-package font-24 text-muted"></i>
                                            <div class="mt-2">No products found</div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-3">
                            {{ $products->withQueryString()->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reason Form -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-3">Stock Update Reason</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Reason Category <span class="text-danger">*</span></label>
                                    <select name="reason_category" class="form-control" required>
                                        <option value="">Select Reason</option>
                                        <option value="audit">Physical Count/Audit</option>
                                        <option value="correction">Data Correction</option>
                                        <option value="received">Stock Received</option>
                                        <option value="returned">Customer Returns</option>
                                        <option value="damaged">Damaged/Lost Items</option>
                                        <option value="adjustment">General Adjustment</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Notes/Details <span class="text-danger">*</span></label>
                                    <textarea name="reason" class="form-control" rows="2" required
                                              placeholder="Describe the reason for this stock update..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Quick Adjust Modal -->
<div class="modal fade" id="quickAdjustModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quick Stock Adjust</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="quickAdjustForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group mb-3">
                                <label>Product</label>
                                <input type="text" class="form-control" id="qaProductName" readonly>
                                <input type="hidden" name="product_id" id="qaProductId">
                            </div>
                            <div class="form-group mb-3">
                                <label>Current Stock</label>
                                <input type="text" class="form-control" id="qaCurrentStock" readonly>
                            </div>
                            <div class="border rounded p-2 mb-3" id="qaVariantGroup" style="display: none;">
                                <div class="row g-2" id="qaVariantAttributes">
                                    <small class="text-muted">Variant attributes will load here</small>
                                </div>
                                <input type="hidden" name="variant_id" id="qaVariantId">
                                <small class="form-text text-muted d-block mt-2" id="qaVariantHint">Select variant attributes if applicable</small>
                            </div>
                            <div class="form-group mb-3">
                                <label>Adjustment Type</label>
                                <select name="adjustment_type" id="qaType" class="form-control" required>
                                    <option value="set">Set to</option>
                                    <option value="add">Add</option>
                                    <option value="subtract">Subtract</option>
                                </select>
                            </div>
                            <div class="form-group mb-3">
                                <label>Quantity</label>
                                <input type="number" name="physical_quantity" id="qaQuantity"
                                       class="form-control" step="0.01" min="0" required>
                            </div>
                            <div class="form-group mb-3">
                                <label>Reason Category</label>
                                <select name="reason_category" id="qaReasonCategory" class="form-control" required>
                                    <option value="audit">Physical Count/Audit</option>
                                    <option value="correction">Data Correction</option>
                                    <option value="received">Stock Received</option>
                                    <option value="returned">Customer Returns</option>
                                    <option value="damaged">Damaged/Lost Items</option>
                                    <option value="adjustment">General Adjustment</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Reason/Notes</label>
                                <textarea name="reason" id="qaReason" class="form-control" rows="2" required></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('css')
<style>
.table th, .table td {
    vertical-align: middle;
    padding: 0.5rem;
}

.product-info {
    display: flex;
    align-items: center;
}

.product-image {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
    margin-right: 10px;
}

.quantity-input {
    font-size: 0.9rem;
}

.row-variant-selector .form-control-sm,
#qaVariantAttributes .form-control-sm {
    height: calc(1.5em + 0.5rem + 2px);
    padding: 0.1rem 0.25rem;
    font-size: 0.72rem;
}

.row-variant-groups .variant-attribute-col {
    min-width: 110px;
}

.row-variant-hint {
    font-size: 0.7rem;
    line-height: 1.2;
}

.badge {
    font-size: 0.75rem;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
}

#selectionCount {
    font-weight: 500;
}

@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.8em;
    }

    .btn-group {
        flex-direction: column;
    }

    .btn-group .btn {
        margin-bottom: 0.25rem;
    }
}
</style>
@endpush

@push('js')
<script>
$(document).ready(function() {
    let selectedProducts = [];
    const quickAdjustModalEl = document.getElementById('quickAdjustModal');
    const selectedWarehouseId = Number('{{ $selectedWarehouseId }}');
    const quickAdjustRoute = '{{ route("admin.stock.quick-adjust") }}';
    const bulkAdjustRoute = '{{ route("admin.stock.bulk-adjust") }}';
    const productVariantsRoute = '{{ route("admin.stock.api.product-variants") }}';
    const productStockRoute = '{{ route("admin.stock.get-product-stock") }}';
    const variantCache = {};
    let modalVariants = [];
    let modalGroups = [];
    let modalSelectedValues = {};

    const $qaProductId = $('#qaProductId');
    const $qaProductName = $('#qaProductName');
    const $qaCurrentStock = $('#qaCurrentStock');
    const $qaQuantity = $('#qaQuantity');
    const $qaVariantGroup = $('#qaVariantGroup');
    const $qaVariantAttributes = $('#qaVariantAttributes');
    const $qaVariantId = $('#qaVariantId');
    const $qaVariantHint = $('#qaVariantHint');

    function showQuickAdjustModal() {
        if (window.bootstrap && quickAdjustModalEl) {
            window.bootstrap.Modal.getOrCreateInstance(quickAdjustModalEl).show();
            return;
        }

        if (typeof $('#quickAdjustModal').modal === 'function') {
            $('#quickAdjustModal').modal('show');
        }
    }

    function hideQuickAdjustModal() {
        if (window.bootstrap && quickAdjustModalEl) {
            window.bootstrap.Modal.getOrCreateInstance(quickAdjustModalEl).hide();
            return;
        }

        if (typeof $('#quickAdjustModal').modal === 'function') {
            $('#quickAdjustModal').modal('hide');
        }
    }

    function normalize(value) {
        return String(value ?? '').trim();
    }

    function parseDecimalInput(value) {
        const normalizedValue = normalize(value).replace(/,/g, '');
        if (normalizedValue === '') {
            return NaN;
        }

        const parsed = Number(normalizedValue);
        return Number.isFinite(parsed) ? parsed : NaN;
    }

    function parseJson(value, fallback) {
        try {
            const parsed = JSON.parse(String(value ?? ''));
            if (Array.isArray(fallback)) {
                return Array.isArray(parsed) ? parsed : fallback;
            }

            if (fallback && typeof fallback === 'object') {
                return parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : fallback;
            }

            return parsed ?? fallback;
        } catch (_) {
            return fallback;
        }
    }

    function escapeHtml(value) {
        return $('<div>').text(normalize(value)).html();
    }

    function setVariantHint(text, isError = false) {
        $qaVariantHint.toggleClass('text-muted', !isError);
        $qaVariantHint.toggleClass('text-danger', isError);
        $qaVariantHint.text(text);
    }

    function normalizeVariantPayload(payload) {
        const rawVariants = Array.isArray(payload?.variants) ? payload.variants : [];
        const rawGroups = Array.isArray(payload?.attribute_groups) ? payload.attribute_groups : [];

        const variants = rawVariants.map(function(variant) {
            const valueMap = {};
            const rows = Array.isArray(variant.attribute_values) ? variant.attribute_values : [];
            rows.forEach(function(row) {
                const attributeId = normalize(row.attribute_id);
                const valueId = normalize(row.value_id || row.value);
                if (attributeId !== '' && valueId !== '') {
                    valueMap['a:' + attributeId] = valueId;
                }
            });

            const attributes = (variant.attributes && typeof variant.attributes === 'object') ? variant.attributes : {};
            Object.keys(attributes).forEach(function(slug) {
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
                valueMap: valueMap,
            };
        }).filter(function(variant) {
            return variant.id !== '';
        });

        let groups = rawGroups.map(function(group) {
            const attributeId = normalize(group.attribute_id);
            const slug = normalize(group.attribute_slug).toLowerCase();
            const key = attributeId !== '' ? ('a:' + attributeId) : ('slug:' + slug);
            const values = (Array.isArray(group.values) ? group.values : [])
                .map(function(value) {
                    const valueId = normalize(value.value_id || value.value);
                    const valueText = normalize(value.value);
                    return {
                        value_id: valueId,
                        value: valueText,
                    };
                })
                .filter(function(value) {
                    return value.value_id !== '' && value.value !== '';
                });

            return {
                key: key,
                name: normalize(group.attribute_name) || 'Attribute',
                values: values,
            };
        }).filter(function(group) {
            return group.values.length > 0;
        });

        if (groups.length === 0) {
            const derived = {};
            variants.forEach(function(variant) {
                Object.keys(variant.valueMap).forEach(function(groupKey) {
                    if (!groupKey.startsWith('slug:')) {
                        return;
                    }

                    if (!derived[groupKey]) {
                        const slug = groupKey.replace(/^slug:/, '');
                        derived[groupKey] = {
                            key: groupKey,
                            name: slug
                                .split('-')
                                .map(function(part) {
                                    return part.charAt(0).toUpperCase() + part.slice(1);
                                })
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

            groups = Object.values(derived).map(function(group) {
                return {
                    key: group.key,
                    name: group.name,
                    values: Object.values(group.values),
                };
            });
        }

        return { variants, groups };
    }

    function buildAttributeGroupsHtml(groups, selectClassName) {
        return groups.map(function(group) {
            const safeKey = escapeHtml(group.key);
            const safeName = escapeHtml(group.name);
            return `
                <div class="col-md-4 col-sm-6 variant-attribute-col">
                    <label class="small mb-1 d-block">${safeName}</label>
                    <select class="form-control form-control-sm ${selectClassName}" data-group-key="${safeKey}">
                        <option value="">Select ${safeName}</option>
                    </select>
                </div>
            `;
        }).join('');
    }

    function variantMatchesSelection(variant, groups, selectedValues, ignoreGroupKey = '') {
        return groups.every(function(group) {
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
        const allowedValueIds = new Set();

        variants.forEach(function(variant) {
            if (!variantMatchesSelection(variant, groups, selectedValues, group.key)) {
                return;
            }

            const valueId = normalize(variant.valueMap[group.key]);
            if (valueId !== '') {
                allowedValueIds.add(valueId);
            }
        });

        return (group.values || []).filter(function(value) {
            return allowedValueIds.has(normalize(value.value_id));
        });
    }

    function resetVariantSelectors() {
        modalVariants = [];
        modalGroups = [];
        modalSelectedValues = {};
        $qaVariantAttributes.empty();
        $qaVariantId.val('');
        setVariantHint('Select variant attributes if applicable');
        $qaVariantGroup.hide();
    }

    function setSimpleVariantState(message = 'Simple product (no variant)') {
        resetVariantSelectors();
        setVariantHint(message);
    }

    function setLoadingVariantState() {
        $qaVariantGroup.show();
        $qaVariantAttributes.html('<small class="text-muted">Loading variants...</small>');
        $qaVariantId.val('');
        setVariantHint('Loading variants...');
    }

    function syncVariantSelection(changedField = null) {
        if (modalVariants.length === 0) {
            $qaVariantId.val('');
            return;
        }

        if (modalGroups.length === 0) {
            const autoVariant = modalVariants[0] || null;
            if (autoVariant) {
                $qaVariantId.val(autoVariant.id);
                const selectedLabel = autoVariant.label || `Variant #${autoVariant.id}`;
                const skuSuffix = autoVariant.sku_code ? ` [${autoVariant.sku_code}]` : '';
                setVariantHint(`Matched: ${selectedLabel}${skuSuffix}`);
            } else {
                $qaVariantId.val('');
                setVariantHint('Variant data available');
            }
            return;
        }

        modalGroups.forEach(function(group) {
            if (!Object.prototype.hasOwnProperty.call(modalSelectedValues, group.key)) {
                modalSelectedValues[group.key] = '';
            }

            const $select = $qaVariantAttributes.find('.qa-variant-attribute-select').filter(function() {
                return normalize($(this).attr('data-group-key')) === group.key;
            }).first();

            if ($select.length === 0) {
                return;
            }

            const options = optionsForGroup(modalVariants, modalGroups, modalSelectedValues, group);
            const current = normalize(modalSelectedValues[group.key]);
            const optionHtml = ['<option value="">Select ' + escapeHtml(group.name) + '</option>']
                .concat(options.map(function(option) {
                    const valueId = normalize(option.value_id);
                    const selected = current === valueId ? ' selected' : '';
                    return '<option value="' + escapeHtml(valueId) + '"' + selected + '>' + escapeHtml(option.value) + '</option>';
                }));
            $select.html(optionHtml.join(''));

            if (current !== '' && !options.some(function(option) {
                return normalize(option.value_id) === current;
            })) {
                modalSelectedValues[group.key] = '';
                $select.val('');
            } else if (modalSelectedValues[group.key] === '' && options.length === 1 && changedField !== group.key) {
                modalSelectedValues[group.key] = normalize(options[0].value_id);
                $select.val(modalSelectedValues[group.key]);
            } else {
                $select.val(modalSelectedValues[group.key] || '');
            }
        });

        const matches = modalVariants.filter(function(variant) {
            return variantMatchesSelection(variant, modalGroups, modalSelectedValues);
        });
        const fullySpecified = modalGroups.every(function(group) {
            return normalize(modalSelectedValues[group.key]) !== '';
        });
        const hasAnySelected = modalGroups.some(function(group) {
            return normalize(modalSelectedValues[group.key]) !== '';
        });

        let selectedVariant = null;
        if (matches.length === 1 && (fullySpecified || hasAnySelected)) {
            selectedVariant = matches[0];
        }

        if (selectedVariant) {
            $qaVariantId.val(selectedVariant.id);
            const selectedLabel = selectedVariant.label || `Variant #${selectedVariant.id}`;
            const skuSuffix = selectedVariant.sku_code ? ` [${selectedVariant.sku_code}]` : '';
            setVariantHint(`Matched: ${selectedLabel}${skuSuffix}`);
            return;
        }

        $qaVariantId.val('');
        setVariantHint('Select valid attribute combination');
    }

    function initializeVariantSelectors(payload) {
        const normalized = normalizeVariantPayload(payload);
        modalVariants = normalized.variants;
        modalGroups = normalized.groups;
        modalSelectedValues = {};

        if (modalVariants.length === 0) {
            setSimpleVariantState('Simple product (no variant)');
            return false;
        }

        $qaVariantId.val('');
        $qaVariantGroup.show();

        if (modalGroups.length === 0) {
            const selectedVariant = modalVariants[0];
            $qaVariantAttributes.html('<small class="text-muted">No attributes available. Variant selected automatically.</small>');
            if (selectedVariant) {
                $qaVariantId.val(selectedVariant.id);
                const selectedLabel = selectedVariant.label || `Variant #${selectedVariant.id}`;
                const skuSuffix = selectedVariant.sku_code ? ` [${selectedVariant.sku_code}]` : '';
                setVariantHint(`Matched: ${selectedLabel}${skuSuffix}`);
            } else {
                setVariantHint('Variant selected automatically');
            }
            return true;
        }

        modalGroups.forEach(function(group) {
            modalSelectedValues[group.key] = '';
        });

        $qaVariantAttributes.html(buildAttributeGroupsHtml(modalGroups, 'qa-variant-attribute-select'));
        setVariantHint('Select variant attributes if applicable');
        syncVariantSelection();
        return true;
    }

    function fetchProductVariants(productId) {
        const key = normalize(productId);
        if (key === '') {
            return Promise.resolve({ variants: [], attribute_groups: [] });
        }

        if (variantCache[key]) {
            return Promise.resolve(variantCache[key]);
        }

        return $.ajax({
            url: productVariantsRoute,
            method: 'GET',
            dataType: 'json',
            data: { product_id: key }
        }).then(function(payload) {
            variantCache[key] = payload;
            return payload;
        });
    }

    function loadCurrentStock(productId, variantId = '') {
        const payload = {
            product_id: productId,
            warehouse_id: selectedWarehouseId
        };

        if (normalize(variantId) !== '') {
            payload.variant_id = variantId;
        }

        return $.ajax({
            url: productStockRoute,
            method: 'GET',
            dataType: 'json',
            data: payload
        }).done(function(response) {
            const physical = Number(response?.data?.physical_quantity ?? 0);
            $qaCurrentStock.val(physical.toFixed(2));
            if ($('#qaType').val() === 'set') {
                $qaQuantity.val(physical.toFixed(2));
            }
        }).fail(function() {
            $qaCurrentStock.val('0.00');
        });
    }

    function loadVariantSelectors(productId) {
        if (!productId) {
            setSimpleVariantState('Select product first');
            return Promise.resolve();
        }

        setLoadingVariantState();
        return fetchProductVariants(productId)
            .then(function(payload) {
                const hasVariants = initializeVariantSelectors(payload);
                if (!hasVariants) {
                    return loadCurrentStock(productId, '');
                }

                const selectedVariantId = normalize($qaVariantId.val());
                if (selectedVariantId !== '') {
                    return loadCurrentStock(productId, selectedVariantId);
                }

                $qaCurrentStock.val('0.00');
                return Promise.resolve();
            })
            .catch(function() {
                setSimpleVariantState('Unable to load variants');
                setVariantHint('Unable to load variants', true);
                return loadCurrentStock(productId, '');
            });
    }

    function resetQuickAdjustModal() {
        $('#quickAdjustForm')[0].reset();
        resetVariantSelectors();
        $qaCurrentStock.val('0.00');
    }

    function getRowVariantElements($row) {
        return {
            $selector: $row.find('.row-variant-selector'),
            $groups: $row.find('.row-variant-groups'),
            $variantId: $row.find('.row-variant-id-input'),
            $hint: $row.find('.row-variant-hint')
        };
    }

    function setRowVariantHint($row, text, isError = false) {
        const { $hint } = getRowVariantElements($row);
        $hint.toggleClass('text-muted', !isError);
        $hint.toggleClass('text-danger', isError);
        $hint.text(text);
    }

    function setRowSimpleVariantState($row, message = 'Simple product (no variant)') {
        const { $selector, $groups, $variantId } = getRowVariantElements($row);

        $groups.html('<small class="text-muted">Simple product</small>');
        $variantId.val('');

        $selector.attr('data-row-variants', '[]');
        $selector.attr('data-row-groups', '[]');
        $selector.attr('data-row-selected-values', '{}');
        $selector.attr('data-row-has-variant', '0');
        $selector.attr('data-row-variant-loaded', '1');
        setRowVariantHint($row, message);
    }

    function setRowLoadingVariantState($row) {
        const { $selector, $groups, $variantId } = getRowVariantElements($row);

        $groups.html('<small class="text-muted">Loading variants...</small>');
        $variantId.val('');
        $selector.attr('data-row-variant-loaded', '0');
        setRowVariantHint($row, 'Loading variants...');
    }

    function getRowVariantState($row) {
        const { $selector } = getRowVariantElements($row);

        return {
            variants: parseJson($selector.attr('data-row-variants'), []),
            groups: parseJson($selector.attr('data-row-groups'), []),
            selectedValues: parseJson($selector.attr('data-row-selected-values'), {})
        };
    }

    function setRowVariantState($row, state) {
        const { $selector } = getRowVariantElements($row);
        $selector.attr('data-row-variants', JSON.stringify(state.variants || []));
        $selector.attr('data-row-groups', JSON.stringify(state.groups || []));
        $selector.attr('data-row-selected-values', JSON.stringify(state.selectedValues || {}));
    }

    function syncRowVariantSelection($row, changedField = null) {
        const { $groups, $variantId } = getRowVariantElements($row);
        const state = getRowVariantState($row);
        const variants = state.variants || [];
        const groups = state.groups || [];
        const selectedValues = state.selectedValues || {};

        if (variants.length === 0) {
            $variantId.val('');
            return;
        }

        if (groups.length === 0) {
            const selectedVariant = variants[0] || null;
            if (selectedVariant) {
                $variantId.val(selectedVariant.id);
                const selectedLabel = selectedVariant.label || `Variant #${selectedVariant.id}`;
                const skuSuffix = selectedVariant.sku_code ? ` [${selectedVariant.sku_code}]` : '';
                setRowVariantHint($row, `Matched: ${selectedLabel}${skuSuffix}`);
            } else {
                $variantId.val('');
                setRowVariantHint($row, 'Variant data available');
            }
            return;
        }

        groups.forEach(function(group) {
            if (!Object.prototype.hasOwnProperty.call(selectedValues, group.key)) {
                selectedValues[group.key] = '';
            }

            const $select = $groups.find('.row-variant-attribute-select').filter(function() {
                return normalize($(this).attr('data-group-key')) === group.key;
            }).first();

            if ($select.length === 0) {
                return;
            }

            const options = optionsForGroup(variants, groups, selectedValues, group);
            const current = normalize(selectedValues[group.key]);
            const optionHtml = ['<option value="">Select ' + escapeHtml(group.name) + '</option>']
                .concat(options.map(function(option) {
                    const valueId = normalize(option.value_id);
                    const selected = current === valueId ? ' selected' : '';
                    return '<option value="' + escapeHtml(valueId) + '"' + selected + '>' + escapeHtml(option.value) + '</option>';
                }));
            $select.html(optionHtml.join(''));

            if (current !== '' && !options.some(function(option) {
                return normalize(option.value_id) === current;
            })) {
                selectedValues[group.key] = '';
                $select.val('');
            } else if (selectedValues[group.key] === '' && options.length === 1 && changedField !== group.key) {
                selectedValues[group.key] = normalize(options[0].value_id);
                $select.val(selectedValues[group.key]);
            } else {
                $select.val(selectedValues[group.key] || '');
            }
        });

        const matches = variants.filter(function(variant) {
            return variantMatchesSelection(variant, groups, selectedValues);
        });
        const fullySpecified = groups.every(function(group) {
            return normalize(selectedValues[group.key]) !== '';
        });
        const hasAnySelected = groups.some(function(group) {
            return normalize(selectedValues[group.key]) !== '';
        });

        let selectedVariant = null;
        if (matches.length === 1 && (fullySpecified || hasAnySelected)) {
            selectedVariant = matches[0];
        }

        if (selectedVariant) {
            $variantId.val(selectedVariant.id);
            const selectedLabel = selectedVariant.label || `Variant #${selectedVariant.id}`;
            const skuSuffix = selectedVariant.sku_code ? ` [${selectedVariant.sku_code}]` : '';
            setRowVariantHint($row, `Matched: ${selectedLabel}${skuSuffix}`);
            state.selectedValues = selectedValues;
            setRowVariantState($row, state);
            return;
        }

        $variantId.val('');
        setRowVariantHint($row, 'Select valid attribute combination');
        state.selectedValues = selectedValues;
        setRowVariantState($row, state);
    }

    function initializeRowVariantSelectors($row, payload) {
        const { $selector, $groups, $variantId } = getRowVariantElements($row);
        const normalized = normalizeVariantPayload(payload);
        const variants = normalized.variants;
        const groups = normalized.groups;

        if (variants.length === 0) {
            setRowSimpleVariantState($row, 'Simple product (no variant)');
            return;
        }

        const selectedValues = {};
        groups.forEach(function(group) {
            selectedValues[group.key] = '';
        });

        setRowVariantState($row, {
            variants: variants,
            groups: groups,
            selectedValues: selectedValues,
        });

        $selector.attr('data-row-has-variant', '1');
        $selector.attr('data-row-variant-loaded', '1');

        $variantId.val('');

        if (groups.length === 0) {
            const selectedVariant = variants[0];
            $groups.html('<small class="text-muted">No attributes available. Variant selected automatically.</small>');
            if (selectedVariant) {
                $variantId.val(selectedVariant.id);
                const selectedLabel = selectedVariant.label || `Variant #${selectedVariant.id}`;
                const skuSuffix = selectedVariant.sku_code ? ` [${selectedVariant.sku_code}]` : '';
                setRowVariantHint($row, `Matched: ${selectedLabel}${skuSuffix}`);
            } else {
                setRowVariantHint($row, 'Variant selected automatically');
            }
            return;
        }

        $groups.html(buildAttributeGroupsHtml(groups, 'row-variant-attribute-select'));
        setRowVariantHint($row, 'Select variant attributes if applicable');
        syncRowVariantSelection($row);
    }

    function loadRowVariantSelectors($row) {
        if (!$row || $row.length === 0) {
            return Promise.resolve();
        }

        const { $selector } = getRowVariantElements($row);
        if ($selector.attr('data-row-variant-loaded') === '1') {
            return Promise.resolve();
        }

        const productId = String($row.data('product-id') || '').trim();
        if (productId === '') {
            setRowSimpleVariantState($row, 'Invalid product');
            return Promise.resolve();
        }

        setRowLoadingVariantState($row);
        return fetchProductVariants(productId)
            .then(function(payload) {
                initializeRowVariantSelectors($row, payload);
            })
            .catch(function() {
                setRowSimpleVariantState($row, 'Unable to load variants');
                setRowVariantHint($row, 'Unable to load variants', true);
            });
    }

    function ensureVariantsForSelectedRows() {
        const loadPromises = [];
        $('.product-checkbox:checked').each(function() {
            const $row = $(this).closest('tr');
            if ($row.length > 0) {
                loadPromises.push(loadRowVariantSelectors($row));
            }
        });

        return Promise.all(loadPromises);
    }

    $(document).on('change', '.row-variant-attribute-select', function() {
        const $row = $(this).closest('tr');
        const groupKey = normalize($(this).attr('data-group-key'));
        if ($row.length > 0) {
            const state = getRowVariantState($row);
            state.selectedValues[groupKey] = normalize($(this).val());
            setRowVariantState($row, state);
            syncRowVariantSelection($row, groupKey);
        }
    });

    // Master checkbox functionality
    $('#masterCheckbox').change(function() {
        const isChecked = $(this).prop('checked');
        $('.product-checkbox').prop('checked', isChecked);
        updateSelection();
    });

    // Individual checkboxes
    $(document).on('change', '.product-checkbox', function() {
        if ($(this).is(':checked')) {
            const $row = $(this).closest('tr');
            if ($row.length > 0) {
                loadRowVariantSelectors($row);
            }
        }
        updateSelection();
    });

    // Select All button
    $('#selectAllBtn').click(function() {
        $('.product-checkbox').prop('checked', true);
        $('#masterCheckbox').prop('checked', true);
        ensureVariantsForSelectedRows();
        updateSelection();
    });

    // Clear Selection button
    $('#clearSelectionBtn').click(function() {
        $('.product-checkbox').prop('checked', false);
        $('#masterCheckbox').prop('checked', false);
        updateSelection();
    });

    // Update selection count and button state
    function updateSelection() {
        selectedProducts = [];
        $('.product-checkbox:checked').each(function() {
            selectedProducts.push($(this).val());
        });

        const count = selectedProducts.length;
        $('#selectionCount').text(count + ' products selected');
        $('#updateStockBtn').prop('disabled', count === 0);

        // Update master checkbox state
        const totalCheckboxes = $('.product-checkbox').length;
        const checkedCheckboxes = $('.product-checkbox:checked').length;
        $('#masterCheckbox').prop('checked', totalCheckboxes > 0 && checkedCheckboxes === totalCheckboxes);
        $('#masterCheckbox').prop('indeterminate', checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);

        ensureVariantsForSelectedRows();
    }

    // Quick adjust button
    $(document).on('click', '.quick-adjust-btn', function() {
        const productId = $(this).data('product-id');
        const productName = $(this).data('product-name');
        const currentStock = $(this).data('current-stock');

        resetVariantSelectors();
        $qaProductId.val(productId);
        $qaProductName.val(productName);
        const normalizedCurrent = parseDecimalInput(currentStock);
        const safeCurrent = Number.isFinite(normalizedCurrent) ? normalizedCurrent : 0;
        $qaCurrentStock.val(safeCurrent.toFixed(2));
        $qaQuantity.val(safeCurrent.toFixed(2));

        loadVariantSelectors(productId);
        showQuickAdjustModal();
    });

    $(document).on('change', '.qa-variant-attribute-select', function() {
        const groupKey = normalize($(this).attr('data-group-key'));
        modalSelectedValues[groupKey] = normalize($(this).val());
        syncVariantSelection(groupKey);

        const productId = normalize($qaProductId.val());
        const variantId = normalize($qaVariantId.val());

        if (productId === '') {
            return;
        }

        if ($qaVariantGroup.is(':visible') && modalVariants.length > 0 && modalGroups.length > 0 && variantId === '') {
            $qaCurrentStock.val('0.00');
            return;
        }

        loadCurrentStock(productId, variantId);
    });

    // Quick adjust form submit
    $('#quickAdjustForm').submit(function(e) {
        e.preventDefault();

        const variantId = normalize($qaVariantId.val());
        if ($qaVariantGroup.is(':visible') && modalVariants.length > 0 && modalGroups.length > 0 && variantId === '') {
            toastr.error('Please select a valid variant attribute combination');
            return;
        }

        const formData = $(this).serialize() + '&warehouse_id={{ $selectedWarehouseId }}';

        $.ajax({
            url: quickAdjustRoute,
            method: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    hideQuickAdjustModal();
                    resetQuickAdjustModal();
                    location.reload(); // Refresh to show updated stock
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                toastr.error(response?.message || 'Failed to update stock');
            }
        });
    });

    $('#quickAdjustModal').on('hidden.bs.modal', function() {
        resetQuickAdjustModal();
    });

    // Bulk form submit
    $('#bulkStockForm').submit(async function(e) {
        e.preventDefault();

        if (selectedProducts.length === 0) {
            toastr.warning('Please select at least one product');
            return;
        }

        // Show loading state
        const submitBtn = $('#updateStockBtn');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="fe-loader"></i> Updating...');

        try {
            await ensureVariantsForSelectedRows();

            const reasonCategory = String($('select[name="reason_category"]').val() || '').trim();
            const reason = String($('textarea[name="reason"]').val() || '').trim();

            if (reasonCategory === '') {
                toastr.error('Please select a reason category');
                return;
            }

            if (reason.length < 3) {
                toastr.error('Please provide detailed notes (at least 3 characters)');
                return;
            }

            const items = [];
            let validationError = '';

            selectedProducts.forEach(function(productIdRaw) {
                if (validationError !== '') {
                    return;
                }

                const productId = Number(productIdRaw);
                const $row = $(`tr[data-product-id="${productId}"]`);
                if ($row.length === 0) {
                    validationError = `Selected product ${productId} is missing from the table.`;
                    return;
                }

                const quantity = parseDecimalInput($row.find('.quantity-input').val());
                if (!Number.isFinite(quantity) || quantity < 0) {
                    validationError = `Invalid quantity for product ID ${productId}.`;
                    return;
                }

                const { $selector, $variantId } = getRowVariantElements($row);
                const hasVariant = $selector.attr('data-row-has-variant') === '1';
                const variantIdRaw = normalize($variantId.val());

                if (hasVariant && variantIdRaw === '') {
                    validationError = 'Please select a valid variant attribute combination for all variable products.';
                    return;
                }

                items.push({
                    product_id: productId,
                    variant_id: variantIdRaw === '' ? null : Number(variantIdRaw),
                    quantity: quantity
                });
            });

            if (validationError !== '') {
                toastr.error(validationError);
                return;
            }

            const payload = {
                warehouse_id: selectedWarehouseId,
                adjustment_type: 'set',
                reason_category: reasonCategory,
                reason: reason,
                items: items
            };

            const response = await $.ajax({
                url: bulkAdjustRoute,
                method: 'POST',
                data: JSON.stringify(payload),
                contentType: 'application/json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            if (response.success) {
                toastr.success(response.message);
                location.reload();
            } else {
                toastr.error(response.message || 'Failed to update stock');
            }
        } catch (xhr) {
            const response = xhr?.responseJSON;
            toastr.error(response?.message || 'Bulk update failed');
        } finally {
            submitBtn.prop('disabled', false).html(originalText);
        }
    });

    // Initialize selection count
    updateSelection();

    // Auto-select products that have quantity changes
    $('.quantity-input').change(function() {
        const row = $(this).closest('tr');
        const checkbox = row.find('.product-checkbox');
        const originalValue = parseDecimalInput($(this).data('original') || $(this).val());
        const newValue = parseDecimalInput($(this).val());

        if (!Number.isFinite(newValue) || !Number.isFinite(originalValue) || newValue !== originalValue) {
            checkbox.prop('checked', true);
            loadRowVariantSelectors(row);
        }

        updateSelection();
    });

    // Store original values
    $('.quantity-input').each(function() {
        $(this).data('original', $(this).val());
    });
});
</script>
@endpush
