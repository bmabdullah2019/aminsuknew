@extends('backEnd.layouts.master')
@section('title','Edit GRN')
@section('css')
<link href="{{asset('public/backEnd')}}/assets/libs/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
<style>
    .item-row { 
        border-bottom: 1px solid #eee; 
        padding: 10px 0; 
    }
    .remove-item { 
        cursor: pointer; 
        color: #dc3545; 
    }
    .table-note { 
        font-size: 12px; 
        color: #6c757d; 
    }
    .auto-populated {
        background-color: #f0f7ff;
        border-color: #0d6efd !important;
    }
    .auto-populated-badge {
        font-size: 10px;
        padding: 2px 6px;
        margin-left: 5px;
        background-color: #0d6efd;
        color: white;
        border-radius: 3px;
        display: inline-block;
    }
    .variant-info-panel {
        background-color: #f8f9fa;
        border-left: 3px solid #0d6efd;
        padding: 8px 12px;
        margin: 10px 0;
        border-radius: 4px;
        font-size: 13px;
    }
    .dynamic-attribute {
        padding: 5px 8px;
        background-color: #e7f1ff;
        border-radius: 3px;
        margin: 2px 0;
        font-size: 12px;
    }
</style>
@endsection
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{route('admin.grn.show',$grn->id)}}" class="btn btn-danger rounded-pill"><i class="fe-arrow-left"></i> Back</a>
                </div>
                <h4 class="page-title">Edit GRN</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{route('admin.grn.update',$grn->id)}}" id="grnForm">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>GRN Number</label>
                                    <input type="text" class="form-control" value="{{$grn->grn_number}}" disabled>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Warehouse <span class="text-danger">*</span></label>
                                    <select name="warehouse_id" class="form-control" required>
                                        <option value="">Select Warehouse</option>
                                        @foreach($warehouses as $warehouse)
                                            <option value="{{$warehouse->id}}" {{old('warehouse_id',$grn->warehouse_id)==$warehouse->id?'selected':''}}>{{$warehouse->name}} ({{$warehouse->code}})</option>
                                        @endforeach
                                    </select>
                                    @error('warehouse_id')
                                        <span class="text-danger">{{$message}}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Supplier</label>
                                    <select name="supplier_id" class="form-control">
                                        <option value="">Select Supplier (Optional)</option>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{$supplier->id}}" {{old('supplier_id',$grn->supplier_id)==$supplier->id?'selected':''}}>
                                                {{$supplier->name}} ({{$supplier->supplier_code ?? 'N/A'}})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('supplier_id')
                                        <span class="text-danger">{{$message}}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-3">
                                    <label>GRN Date <span class="text-danger">*</span></label>
                                    <input type="date" name="grn_date" class="form-control" value="{{old('grn_date',$grn->grn_date->format('Y-m-d'))}}" required>
                                    @error('grn_date')
                                        <span class="text-danger">{{$message}}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-3">
                                    <label>Invoice Date</label>
                                    <input type="date" name="invoice_date" class="form-control" value="{{old('invoice_date',$grn->invoice_date?$grn->invoice_date->format('Y-m-d'):'')}}">
                                    @error('invoice_date')
                                        <span class="text-danger">{{$message}}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-3">
                                    <label>Shipping Cost</label>
                                    <input type="number" name="shipping_cost" class="form-control" value="{{old('shipping_cost',$grn->shipping_cost)}}" step="0.01" min="0">
                                    @error('shipping_cost')
                                        <span class="text-danger">{{$message}}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-3">
                                    <label>Other Charges</label>
                                    <input type="number" name="other_charges" class="form-control" value="{{old('other_charges',$grn->other_charges)}}" step="0.01" min="0">
                                    @error('other_charges')
                                        <span class="text-danger">{{$message}}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Invoice Number</label>
                                    <input type="text" name="invoice_number" class="form-control" value="{{old('invoice_number',$grn->invoice_number)}}">
                                    @error('invoice_number')
                                        <span class="text-danger">{{$message}}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h5>Items</h5>
                        <p class="table-note mb-2">Variant SKU must match received stock updates for accurate inventory and expiry tracking.</p>
                        @php
                            $oldItems = old('items');
                            $formItems = [];

                            if (is_array($oldItems) && !empty($oldItems)) {
                                $formItems = $oldItems;
                            } else {
                                $formItems = $grn->items->map(function ($item) {
                                    return [
                                        'product_id' => $item->product_id,
                                        'product_variant_id' => $item->product_variant_id,
                                        'sku' => $item->sku,
                                        'color' => (string) ($item->productVariant->color ?? ''),
                                        'size' => (string) ($item->productVariant->size ?? ''),
                                        'age' => (string) ($item->productVariant->age ?? ''),
                                        'ordered_quantity' => $item->ordered_quantity,
                                        'quantity' => $item->quantity,
                                        'unit_cost' => $item->unit_cost,
                                        'tax_rate' => $item->tax_rate,
                                        'batch_number' => $item->batch_number,
                                        'expiry_date' => $item->expiry_date ? $item->expiry_date->format('Y-m-d') : '',
                                    ];
                                })->values()->all();
                            }

                            if (empty($formItems)) {
                                $formItems = [[
                                    'product_id' => '',
                                    'product_variant_id' => '',
                                    'sku' => '',
                                    'color' => '',
                                    'size' => '',
                                    'age' => '',
                                    'ordered_quantity' => '',
                                    'quantity' => '',
                                    'unit_cost' => '',
                                    'tax_rate' => 0,
                                    'batch_number' => '',
                                    'expiry_date' => '',
                                ]];
                            }
                        @endphp

                        <div id="items-container">
                            @foreach($formItems as $index => $item)
                                <div class="item-row">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group mb-3">
                                                <label>Product <span class="text-danger">*</span></label>
                                                <select name="items[{{$index}}][product_id]" class="form-control select-product" required>
                                                    <option value="">Select Product</option>
                                                    @foreach($products as $product)
                                                        <option value="{{$product->id}}" data-price="{{$product->new_price}}" {{ (string)($item['product_id'] ?? '') === (string)$product->id ? 'selected' : '' }}>{{$product->name}}</option>
                                                    @endforeach
                                                </select>
                                                @error("items.$index.product_id")
                                                    <span class="text-danger d-block">{{$message}}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group mb-3">
                                                <label>Variant SKU</label>
                                                <select
                                                    name="items[{{$index}}][product_variant_id]"
                                                    class="form-control select-variant"
                                                    data-selected="{{ $item['product_variant_id'] ?? '' }}"
                                                    data-selected-sku="{{ $item['sku'] ?? '' }}"
                                                >
                                                    <option value="">Select Variant SKU</option>
                                                </select>
                                                @error("items.$index.product_variant_id")
                                                    <span class="text-danger d-block">{{$message}}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group mb-3">
                                                <label>Ordered Qty</label>
                                                <input type="number" name="items[{{$index}}][ordered_quantity]" class="form-control" step="0.01" min="0.01" value="{{ $item['ordered_quantity'] ?? '' }}" placeholder="Optional">
                                                @error("items.$index.ordered_quantity")
                                                    <span class="text-danger d-block">{{$message}}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group mb-3">
                                                <label>Received Qty <span class="text-danger">*</span></label>
                                                <input type="number" name="items[{{$index}}][quantity]" class="form-control" step="0.01" min="0.01" value="{{ $item['quantity'] ?? '' }}" required>
                                                @error("items.$index.quantity")
                                                    <span class="text-danger d-block">{{$message}}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-2" style="display: flex; align-items: center;">
                                            <button type="button" class="btn btn-danger btn-sm remove-item" style="width: 100%;"><i class="fe-trash-2"></i></button>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-2">
                                            <div class="form-group mb-3">
                                                <label>SKU</label>
                                                <input type="text" name="items[{{$index}}][sku]" class="form-control item-sku" value="{{ $item['sku'] ?? '' }}" placeholder="Auto / Manual">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group mb-3">
                                                <label>Color</label>
                                                <input type="text" name="items[{{$index}}][color]" class="form-control item-color" value="{{ $item['color'] ?? '' }}" placeholder="e.g. Red">
                                                @error("items.$index.color")
                                                    <span class="text-danger d-block">{{$message}}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group mb-3">
                                                <label>Size</label>
                                                <input type="text" name="items[{{$index}}][size]" class="form-control item-size" value="{{ $item['size'] ?? '' }}" placeholder="e.g. M">
                                                @error("items.$index.size")
                                                    <span class="text-danger d-block">{{$message}}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group mb-3">
                                                <label>Age</label>
                                                <input type="text" name="items[{{$index}}][age]" class="form-control item-age" value="{{ $item['age'] ?? '' }}" placeholder="e.g. 3-5Y">
                                                @error("items.$index.age")
                                                    <span class="text-danger d-block">{{$message}}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row dynamic-attributes-row" id="dynamic-attrs-{{$index}}">
                                        <!-- Dynamic attribute dropdowns will be inserted here -->
                                    </div>
                                    <div class="row">
                                        <div class="col-md-2">
                                            <div class="form-group mb-3">
                                                <label>Unit Cost <span class="text-danger">*</span></label>
                                                <input type="number" name="items[{{$index}}][unit_cost]" class="form-control unit-cost" step="0.01" min="0" value="{{ $item['unit_cost'] ?? '' }}" required>
                                                @error("items.$index.unit_cost")
                                                    <span class="text-danger d-block">{{$message}}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group mb-3">
                                                <label>Tax Rate (%)</label>
                                                <input type="number" name="items[{{$index}}][tax_rate]" class="form-control" step="0.01" min="0" max="100" value="{{ $item['tax_rate'] ?? 0 }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group mb-3">
                                                <label>Batch Number</label>
                                                <input type="text" name="items[{{$index}}][batch_number]" class="form-control" value="{{ $item['batch_number'] ?? '' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group mb-3">
                                                <label>Expiry Date</label>
                                                <input type="date" name="items[{{$index}}][expiry_date]" class="form-control" value="{{ $item['expiry_date'] ?? '' }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-info" id="add-item"><i class="fe-plus"></i> Add Item</button>

                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label>Notes</label>
                                    <textarea name="notes" class="form-control" rows="3">{{old('notes',$grn->notes)}}</textarea>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">Update GRN</button>
                                <a href="{{route('admin.grn.show',$grn->id)}}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{asset('public/backEnd')}}/assets/libs/select2/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    const variantApiUrl = @json($variantApiUrl);
    let itemIndex = $('#items-container .item-row').length;
    let attributeGroupsCache = {}; // Store attribute_groups from API responses

    function initSelect2(scope) {
        scope.find('.select-product').select2({ width: '100%' });
        scope.find('.select-variant').select2({ width: '100%' });
    }

    function optionLabel(variant) {
        const attrs = [variant.color, variant.size, variant.age].filter(Boolean).join(' / ');
        const dynamicAttrs = Array.isArray(variant.attribute_values) ? variant.attribute_values.map(function (val) { if (typeof val === 'string') return val; if (val && typeof val === 'object') return val.value || val.name || ''; return ''; }).filter(Boolean).join(' / ') : '';
        const suffix = attrs || dynamicAttrs || variant.label || 'Default Variant';
        return `${variant.sku_code} - ${suffix}`;
    }

    function buildVariantOptions($row, variants, selectedVariantId, selectedSku) {
        const $variantSelect = $row.find('.select-variant');
        $variantSelect.empty().append('<option value="">Select Variant SKU</option>');

        variants.forEach(function(variant) {
            const isSelected = (selectedVariantId && String(selectedVariantId) === String(variant.id))
                || (!selectedVariantId && selectedSku && String(selectedSku) === String(variant.sku_code));
            const option = new Option(optionLabel(variant), variant.id, isSelected, isSelected);
            $(option)
                .attr('data-sku', variant.sku_code || '')
                .attr('data-cost', variant.cost_price || 0)
                .attr('data-color', variant.color || '')
                .attr('data-size', variant.size || '')
                .attr('data-age', variant.age || '')
                .attr('data-attributes', JSON.stringify(variant.attributes || {}))
                .attr('data-attribute-values', JSON.stringify(variant.attribute_values || []))
                .attr('data-product-id', variant.product_id || '');
            $variantSelect.append(option);
        });

        $variantSelect.trigger('change.select2');
    }

    function syncSkuAndCost($row) {
        const $selected = $row.find('.select-variant option:selected');
        const sku = $selected.data('sku') || '';
        const cost = $selected.data('cost');
        const color = $selected.data('color') || '';
        const size = $selected.data('size') || '';
        const age = $selected.data('age') || '';
        const attributes = $selected.data('attributes') || {};
        const attributeValues = $selected.data('attribute-values') || [];
        const rowIndex = $row.index() || 0;

        // Populate basic fields with visual feedback
        if (sku) {
            $row.find('.item-sku').val(sku).addClass('auto-populated');
        }
        if (color) {
            $row.find('.item-color').val(color).addClass('auto-populated');
        }
        if (size) {
            $row.find('.item-size').val(size).addClass('auto-populated');
        }
        if (age) {
            $row.find('.item-age').val(age).addClass('auto-populated');
        }

        // Handle unit cost
        const $unitCost = $row.find('.unit-cost');
        if ((!$unitCost.val() || Number($unitCost.val()) <= 0) && cost !== undefined && cost !== null && cost !== '') {
            $unitCost.val(Number(cost).toFixed(2)).addClass('auto-populated');
        }

        // Handle dynamic attributes
        updateDynamicAttributes($row, attributes, attributeValues);
        
        // Render dynamic attribute dropdowns
        const attributeGroups = $row.find('.select-variant').data('attribute-groups') || [];
        if (attributeGroups.length > 0) {
            renderDynamicAttributeDropdowns($row, attributeGroups, attributeValues, rowIndex);
        }
    }

    function updateDynamicAttributes($row, attributes, attributeValues) {
        // Remove existing dynamic attribute panel
        $row.find('.variant-info-panel').remove();

        if (!attributes || Object.keys(attributes).length === 0) {
            return;
        }

        // Create info panel for dynamic attributes
        let infoHtml = '<div class="variant-info-panel"><strong>Variant Details:</strong>';
        
        for (const [key, value] of Object.entries(attributes)) {
            if (key !== 'color' && key !== 'size' && key !== 'age' && value) {
                infoHtml += `<div class="dynamic-attribute"><strong>${capitalizeFirstLetter(key)}:</strong> ${value}</div>`;
            }
        }
        
        infoHtml += '</div>';
        $row.find('.select-variant').closest('.form-group').after(infoHtml);
    }

    function capitalizeFirstLetter(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function renderDynamicAttributeDropdowns($row, attributeGroups, attributeValues, rowIndex) {
        const $dynamicRow = $row.find(`#dynamic-attrs-${rowIndex}`);
        $dynamicRow.empty();

        if (!attributeGroups || attributeGroups.length === 0) {
            return;
        }

        // Build a map of attribute values for quick lookup
        const valueMap = {};
        if (Array.isArray(attributeValues)) {
            attributeValues.forEach(val => {
                const attrKey = (val.attribute_slug || val.attribute_name || '').toLowerCase();
                if (attrKey && val.value) {
                    valueMap[attrKey] = val.value;
                }
            });
        }

        attributeGroups.forEach(group => {
            const attrSlug = (group.attribute_slug || group.attribute_name || '').toLowerCase();
            const values = group.values || [];
            
            if (values.length > 0) {
                const colClass = values.length > 5 ? 'col-md-3' : 'col-md-2';
                const selectedValue = valueMap[attrSlug] || '';
                
                let html = `
                    <div class="${colClass}">
                        <div class="form-group mb-3">
                            <label>${group.attribute_name || capitalizeFirstLetter(attrSlug)}</label>
                            <select name="items[${rowIndex}][attribute_${attrSlug}]" class="form-control dynamic-attribute-select" data-attr-slug="${attrSlug}">
                                <option value="">-- Select --</option>
                `;
                
                values.forEach(val => {
                    const selected = selectedValue === val.value ? 'selected' : '';
                    html += `<option value="${val.value}" ${selected}>${val.value}</option>`;
                });
                
                html += `
                            </select>
                        </div>
                    </div>
                `;
                
                $dynamicRow.append(html);
            }
        });

        // Re-initialize select2 for new dropdowns
        $row.find('.dynamic-attribute-select').select2({ width: '100%' });
    }

    function loadVariantsForRow($row) {
        const productId = $row.find('.select-product').val();
        const selectedVariantId = $row.find('.select-variant').attr('data-selected') || '';
        const selectedSku = $row.find('.select-variant').attr('data-selected-sku') || '';
        const rowIndex = $row.index() || 0;

        if (!productId) {
            $row.find('.select-variant').empty().append('<option value="">Select Variant SKU</option>').trigger('change.select2');
            $row.find('.item-sku').val('');
            $row.find('#dynamic-attrs-' + rowIndex).empty();
            return;
        }

        $.get(variantApiUrl, { product_id: productId })
            .done(function(response) {
                const variants = response && response.variants ? response.variants : [];
                const attributeGroups = response && response.attribute_groups ? response.attribute_groups : [];
                
                buildVariantOptions($row, variants, selectedVariantId, selectedSku);
                
                // Store attribute_groups in row for later use
                $row.find('.select-variant').data('attribute-groups', attributeGroups);
                
                syncSkuAndCost($row);
                $row.find('.select-variant').removeAttr('data-selected').removeAttr('data-selected-sku');
            })
            .fail(function() {
                $row.find('.select-variant').empty().append('<option value="">No variants found</option>').trigger('change.select2');
                $row.find('.item-sku').val('');
                $row.find('#dynamic-attrs-' + rowIndex).empty();
            });
    }

    initSelect2($(document));
    $('#items-container .item-row').each(function() {
        loadVariantsForRow($(this));
    });

    function getRowSelectionKey($row) {
        const productId = String($row.find('.select-product').val() || '');
        const variantId = String($row.find('.select-variant').val() || '');
        if (!productId) {
            return '';
        }
        return productId + '::' + variantId;
    }

    function hasDuplicateSelection(selectionKey, exceptRow) {
        if (!selectionKey) {
            return false;
        }

        let duplicate = false;
        $('#items-container .item-row').each(function() {
            if (exceptRow && this === exceptRow[0]) {
                return;
            }

            if (getRowSelectionKey($(this)) === selectionKey) {
                duplicate = true;
                return false;
            }
        });

        return duplicate;
    }

    $('#add-item').click(function() {
        const $lastRow = $('#items-container .item-row').last();
        const selectionKey = getRowSelectionKey($lastRow);

        if (!selectionKey) {
            alert('Please select a product in the current row before adding a new row.');
            $lastRow.find('.select-product').focus();
            return;
        }

        if (hasDuplicateSelection(selectionKey, $lastRow)) {
            alert('This product/variant is already added.');
            return;
        }

        const newRow = `
            <div class="item-row">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group mb-3">
                            <label>Product <span class="text-danger">*</span></label>
                            <select name="items[${itemIndex}][product_id]" class="form-control select-product" required>
                                <option value="">Select Product</option>
                                @foreach($products as $product)
                                    <option value="{{$product->id}}" data-price="{{$product->new_price}}">{{$product->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-3">
                            <label>Variant SKU</label>
                            <select name="items[${itemIndex}][product_variant_id]" class="form-control select-variant">
                                <option value="">Select Variant SKU</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group mb-3">
                            <label>Ordered Qty</label>
                            <input type="number" name="items[${itemIndex}][ordered_quantity]" class="form-control" step="0.01" min="0.01" placeholder="Optional">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group mb-3">
                            <label>Received Qty <span class="text-danger">*</span></label>
                            <input type="number" name="items[${itemIndex}][quantity]" class="form-control" step="0.01" min="0.01" required>
                        </div>
                    </div>
                    <div class="col-md-2" style="display: flex; align-items: center;">
                        <button type="button" class="btn btn-danger btn-sm remove-item" style="width: 100%;"><i class="fe-trash-2"></i></button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group mb-3">
                            <label>SKU</label>
                            <input type="text" name="items[${itemIndex}][sku]" class="form-control item-sku" placeholder="Auto / Manual">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group mb-3">
                            <label>Color</label>
                            <input type="text" name="items[${itemIndex}][color]" class="form-control item-color" placeholder="e.g. Red">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group mb-3">
                            <label>Size</label>
                            <input type="text" name="items[${itemIndex}][size]" class="form-control item-size" placeholder="e.g. M">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group mb-3">
                            <label>Age</label>
                            <input type="text" name="items[${itemIndex}][age]" class="form-control item-age" placeholder="e.g. 3-5Y">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group mb-3">
                            <label>Unit Cost <span class="text-danger">*</span></label>
                            <input type="number" name="items[${itemIndex}][unit_cost]" class="form-control unit-cost" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group mb-3">
                            <label>Tax Rate (%)</label>
                            <input type="number" name="items[${itemIndex}][tax_rate]" class="form-control" step="0.01" min="0" max="100" value="0">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group mb-3">
                            <label>Batch Number</label>
                            <input type="text" name="items[${itemIndex}][batch_number]" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-3">
                            <label>Expiry Date</label>
                            <input type="date" name="items[${itemIndex}][expiry_date]" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
        `;

        const $newRow = $(newRow);
        $('#items-container').append($newRow);
        initSelect2($newRow);
        itemIndex++;
    });

    $(document).on('click', '.remove-item', function() {
        if ($('.item-row').length > 1) {
            $(this).closest('.item-row').remove();
        } else {
            alert('At least one item is required');
        }
    });

    $(document).on('change', '.select-product', function() {
        const $row = $(this).closest('.item-row');
        const rowIndex = $row.index() || 0;
        $row.find('.select-variant').attr('data-selected', '').attr('data-selected-sku', '');
        
        // Clear all fields and remove auto-populated styling
        $row.find('.item-sku, .item-color, .item-size, .item-age').val('').removeClass('auto-populated');
        $row.find('.unit-cost').removeClass('auto-populated');
        
        // Remove dynamic attribute panel and dropdowns
        $row.find('.variant-info-panel').remove();
        $row.find('#dynamic-attrs-' + rowIndex).empty();
        
        loadVariantsForRow($row);
    });

    $(document).on('change', '.select-variant', function() {
        const $row = $(this).closest('.item-row');
        const selectionKey = getRowSelectionKey($row);
        if (hasDuplicateSelection(selectionKey, $row)) {
            alert('This product/variant is already added.');
            $(this).val('').trigger('change.select2');
            syncSkuAndCost($row);
            return;
        }

        syncSkuAndCost($row);
    });

    const focusIndex = new URLSearchParams(window.location.search).get('focus');
    if (focusIndex !== null) {
        const $focusRow = $('#items-container .item-row').eq(Number(focusIndex));
        if ($focusRow.length) {
            $('html, body').animate({ scrollTop: $focusRow.offset().top - 140 }, 400);
            $focusRow.addClass('border border-primary');
            setTimeout(function() {
                $focusRow.removeClass('border border-primary');
            }, 2500);
        }
    }
});
</script>
@endsection
