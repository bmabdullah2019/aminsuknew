@extends('backEnd.layouts.master')
@section('title','Create Stock Adjustment')
@section('css')
<link href="{{asset('public/backEnd')}}/assets/libs/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
<style>
    .item-row { border-bottom: 1px solid #eee; padding: 10px 0; }
    .remove-item { cursor: pointer; color: #dc3545; }
</style>
@endsection
@section('content')
<div class="container-fluid">
    
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                    <div class="page-title-right">
                    <a href="{{route('admin.adjustment.index')}}" class="btn btn-danger rounded-pill"><i class="fe-arrow-left"></i> Back</a>
                </div>
                <h4 class="page-title">Create Stock Adjustment</h4>
            </div>
        </div>
    </div>       
    <!-- end page title --> 
   <div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{route('admin.adjustment.store')}}" id="adjustmentForm">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label>Warehouse <span class="text-danger">*</span></label>
                                <select name="warehouse_id" id="warehouse_id" class="form-control" required>
                                    <option value="">Select Warehouse</option>
                                    @foreach($warehouses as $warehouse)
                                        <option value="{{$warehouse->id}}" {{old('warehouse_id')==$warehouse->id?'selected':''}}>{{$warehouse->name}} ({{$warehouse->code}})</option>
                                    @endforeach
                                </select>
                                @error('warehouse_id')
                                    <span class="text-danger">{{$message}}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group mb-3">
                                <label>Adjustment Date <span class="text-danger">*</span></label>
                                <input type="date" name="adjustment_date" class="form-control" value="{{old('adjustment_date', date('Y-m-d'))}}" required>
                                @error('adjustment_date')
                                    <span class="text-danger">{{$message}}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group mb-3">
                                <label>Adjustment Type <span class="text-danger">*</span></label>
                                <select name="adjustment_type" class="form-control" required>
                                    <option value="">Select Type</option>
                                    <option value="increase" {{old('adjustment_type')=='increase'?'selected':''}}>Increase</option>
                                    <option value="decrease" {{old('adjustment_type')=='decrease'?'selected':''}}>Decrease</option>
                                </select>
                                @error('adjustment_type')
                                    <span class="text-danger">{{$message}}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group mb-3">
                                <label>Reason <span class="text-danger">*</span></label>
                                <select name="reason" class="form-control" required>
                                    <option value="">Select reason</option>
                                    <option value="physical_count" {{ old('reason')=='physical_count'? 'selected' : '' }}>Physical Count</option>
                                    <option value="damage" {{ old('reason')=='damage'? 'selected' : '' }}>Damage</option>
                                    <option value="expiry" {{ old('reason')=='expiry'? 'selected' : '' }}>Expiry</option>
                                    <option value="theft" {{ old('reason')=='theft'? 'selected' : '' }}>Theft</option>
                                    <option value="found" {{ old('reason')=='found'? 'selected' : '' }}>Found</option>
                                    <option value="correction" {{ old('reason')=='correction'? 'selected' : '' }}>Correction</option>
                                    <option value="migration" {{ old('reason')=='migration'? 'selected' : '' }}>Migration</option>
                                    <option value="other" {{ old('reason')=='other'? 'selected' : '' }}>Other</option>
                                </select>
                                @error('reason')
                                    <span class="text-danger">{{$message}}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group mb-3">
                                <label>Reason Details <span class="text-danger">*</span></label>
                                <textarea name="reason_details" class="form-control" rows="3" placeholder="Provide detailed reason (min 20 characters)">{{ old('reason_details') }}</textarea>
                                @error('reason_details')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <hr>
                    <h5>Items</h5>
                    <div class="border rounded-3 p-3 mb-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <div class="fw-bold">Item Builder</div>
                            <span class="badge bg-warning text-dark d-none" id="itemEditState">Editing row</span>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label">Product <span class="text-danger">*</span></label>
                                <select id="builderProduct" class="form-control select-product" required>
                                    <option value="">Select Product</option>
                                    @foreach($products as $product)
                                        <option value="{{$product->id}}">{{$product->name}}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted available-stock">Available: <span class="stock-qty">0</span></small>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Variant</label>
                                <select id="builderVariant" class="form-control select-variant">
                                    <option value="">Auto-select</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                <input type="number" id="builderQty" class="form-control" step="0.01" min="0.01" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Unit Cost</label>
                                <input type="number" id="builderUnitCost" class="form-control" step="0.01" min="0">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Notes</label>
                                <input type="text" id="builderNotes" class="form-control" placeholder="Item notes">
                            </div>
                            <div class="col-12 d-flex flex-wrap gap-2 mt-1">
                                <button type="button" class="btn btn-info" id="add-item"><i class="fe-plus"></i> <span id="addItemText">Add Item</span></button>
                                <button type="button" class="btn btn-outline-secondary d-none" id="clear-item">Clear</button>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-warning d-none mb-0" id="itemMessage"></div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:60px;">#</th>
                                    <th>Product</th>
                                    <th>Variant</th>
                                    <th class="text-end" style="width:140px;">Qty</th>
                                    <th class="text-end" style="width:160px;">Unit Cost</th>
                                    <th>Notes</th>
                                    <th style="width:120px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="items-grid"></tbody>
                        </table>
                    </div>
                    <div id="items-container"></div>

                    <hr>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group mb-3">
                                <label>Notes</label>
                                <textarea name="notes" class="form-control" rows="3">{{old('notes')}}</textarea>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">Create Adjustment</button>
                            <a href="{{route('admin.adjustment.index')}}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
   </div>
</div>
@endsection
@push('script')
<script src="{{asset('public/backEnd')}}/assets/libs/select2/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    const lines = [];
    let editingIndex = null;
    let warehouseId = null;
    
    $('#builderProduct').select2({ width: '100%' });
    $('#builderVariant').select2({ width: '100%' });
    
    $('#warehouse_id').change(function() {
        warehouseId = $(this).val();
        const productId = $('#builderProduct').val();
        if (productId) {
            checkStock(productId, $('#builderProduct'));
        }
    });
    
    function showItemMessage(message) {
        $('#itemMessage').text(message || '').toggleClass('d-none', !message);
    }
    
    function checkStock(productId, selectElement) {
        if (!warehouseId || !productId) return;
        
        $.ajax({
            url: '{{route("admin.stock.balance")}}',
            method: 'GET',
            data: {
                warehouse_id: warehouseId,
                product_id: productId
            },
            success: function(response) {
                const stockQty = response.available_quantity || 0;
                const stockElement = $('.stock-qty');
                stockElement.text(stockQty);
            }
        });
    }

    function loadVariants(productId, variantSelect) {
        if (!productId) {
            variantSelect.html('<option value="">Auto-select</option>').trigger('change');
            return;
        }

        $.ajax({
            url: '{{ route("admin.stock.api.product-variants") }}',
            method: 'GET',
            data: { product_id: productId },
            success: function(response) {
                let html = '<option value="">Auto-select</option>';
                if (response.variants && response.variants.length > 0) {
                    response.variants.forEach(function(variant) {
                        html += '<option value="' + variant.id + '">' + variant.sku_code + ' ' + (variant.label ? '(' + variant.label + ')' : '') + '</option>';
                    });
                }
                variantSelect.html(html).trigger('change');
            },
            error: function() {
                variantSelect.html('<option value="">Auto-select</option>').trigger('change');
            }
        });
    }
    
    $(document).on('change', '.select-product', function() {
        const productId = $('#builderProduct').val();
        const variantSelect = $('#builderVariant');
        
        if (productId && warehouseId) {
            checkStock(productId, $('#builderProduct'));
        }
        
        loadVariants(productId, variantSelect);
    });
    
    function renderGrid() {
        const $grid = $('#items-grid');
        const $hidden = $('#items-container');
        $grid.empty();
        $hidden.empty();

        if (!lines.length) {
            $grid.html('<tr><td colspan="7" class="text-center text-muted py-3">No items added yet.</td></tr>');
            return;
        }

        lines.forEach(function (line, index) {
            $grid.append(`
                <tr data-index="${index}">
                    <td class="text-muted fw-semibold">${index + 1}</td>
                    <td>${line.product_label || '-'}</td>
                    <td>${line.variant_label || '-'}</td>
                    <td class="text-end fw-semibold">${Number(line.quantity || 0).toFixed(2)}</td>
                    <td class="text-end">${line.unit_cost !== '' ? Number(line.unit_cost || 0).toFixed(2) : '-'}</td>
                    <td>${line.notes ? $('<div>').text(line.notes).html() : '-'}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary btn-edit-item">Edit</button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item">Remove</button>
                    </td>
                </tr>
            `);

            $hidden.append(`
                <input type="hidden" name="items[${index}][product_id]" value="${line.product_id}">
                <input type="hidden" name="items[${index}][product_variant_id]" value="${line.product_variant_id || ''}">
                <input type="hidden" name="items[${index}][quantity]" value="${line.quantity}">
                <input type="hidden" name="items[${index}][unit_cost]" value="${line.unit_cost}">
                <input type="hidden" name="items[${index}][notes]" value="${$('<div>').text(line.notes || '').html()}">
            `);
        });
    }

    function clearBuilder() {
        editingIndex = null;
        $('#itemEditState').addClass('d-none');
        $('#addItemText').text('Add Item');
        $('#clear-item').addClass('d-none');
        $('#builderProduct').val('').trigger('change.select2');
        $('#builderVariant').html('<option value="">Auto-select</option>').val('').trigger('change.select2');
        $('#builderQty').val('');
        $('#builderUnitCost').val('');
        $('#builderNotes').val('');
        showItemMessage('');
    }

    $('#add-item').click(function() {
        const productId = $('#builderProduct').val();
        const productLabel = $('#builderProduct option:selected').text().trim();
        const variantId = $('#builderVariant').val();
        const variantLabel = $('#builderVariant option:selected').text().trim();
        const qty = $('#builderQty').val();
        const unitCost = $('#builderUnitCost').val();
        const notes = $('#builderNotes').val();

        if (!productId) {
            showItemMessage('Please select a product.');
            return;
        }
        if (!qty || Number(qty) <= 0) {
            showItemMessage('Please enter a valid quantity.');
            return;
        }

        const line = {
            product_id: productId,
            product_label: productLabel,
            product_variant_id: variantId || '',
            variant_label: (variantId ? variantLabel : ''),
            quantity: qty,
            unit_cost: unitCost || '',
            notes: notes || ''
        };

        if (editingIndex === null) {
            lines.push(line);
        } else {
            lines[editingIndex] = line;
        }

        renderGrid();
        clearBuilder();
    });
    
    $('#clear-item').on('click', clearBuilder);

    $(document).on('click', '.btn-remove-item', function() {
        const index = Number($(this).closest('tr').data('index'));
        if (!Number.isInteger(index)) return;
        lines.splice(index, 1);
        if (editingIndex === index) {
            clearBuilder();
        }
        if (editingIndex !== null && editingIndex > index) {
            editingIndex -= 1;
        }
        renderGrid();
    });

    $(document).on('click', '.btn-edit-item', function() {
        const index = Number($(this).closest('tr').data('index'));
        if (!Number.isInteger(index) || !lines[index]) return;
        const line = lines[index];
        editingIndex = index;
        $('#itemEditState').text(`Editing row ${index + 1}`).removeClass('d-none');
        $('#addItemText').text('Update Item');
        $('#clear-item').removeClass('d-none');

        $('#builderProduct').val(String(line.product_id)).trigger('change.select2');
        loadVariants(line.product_id, $('#builderVariant'));
        // wait a moment for variants to load then set selected
        setTimeout(function () {
            $('#builderVariant').val(String(line.product_variant_id || '')).trigger('change.select2');
        }, 250);

        $('#builderQty').val(line.quantity);
        $('#builderUnitCost').val(line.unit_cost);
        $('#builderNotes').val(line.notes);
        showItemMessage('');
    });

    renderGrid();
});
</script>
@endpush

