@extends('backEnd.layouts.master')
@section('title','Create Transfer')
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
                    <a href="{{route('admin.transfer.index')}}" class="btn btn-danger rounded-pill"><i class="fe-arrow-left"></i> Back</a>
                </div>
                <h4 class="page-title">Create Warehouse Transfer</h4>
            </div>
        </div>
    </div>       
    <!-- end page title --> 
   <div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{route('admin.transfer.store')}}" id="transferForm">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label>From Warehouse <span class="text-danger">*</span></label>
                                <select name="from_warehouse_id" id="from_warehouse_id" class="form-control" required>
                                    <option value="">Select Source Warehouse</option>
                                    @foreach($warehouses as $warehouse)
                                        <option value="{{$warehouse->id}}" {{old('from_warehouse_id')==$warehouse->id?'selected':''}}>{{$warehouse->name}} ({{$warehouse->code}})</option>
                                    @endforeach
                                </select>
                                @error('from_warehouse_id')
                                    <span class="text-danger">{{$message}}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label>To Warehouse <span class="text-danger">*</span></label>
                                <select name="to_warehouse_id" id="to_warehouse_id" class="form-control" required>
                                    <option value="">Select Destination Warehouse</option>
                                    @foreach($warehouses as $warehouse)
                                        <option value="{{$warehouse->id}}" {{old('to_warehouse_id')==$warehouse->id?'selected':''}}>{{$warehouse->name}} ({{$warehouse->code}})</option>
                                    @endforeach
                                </select>
                                @error('to_warehouse_id')
                                    <span class="text-danger">{{$message}}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label>Transfer Date <span class="text-danger">*</span></label>
                                <input type="date" name="transfer_date" class="form-control" value="{{old('transfer_date', date('Y-m-d'))}}" required>
                                @error('transfer_date')
                                    <span class="text-danger">{{$message}}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label>Expected Arrival Date</label>
                                <input type="date" name="estimated_arrival" class="form-control" value="{{old('estimated_arrival')}}">
                                @error('estimated_arrival')
                                    <span class="text-danger">{{$message}}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label>Reason <span class="text-danger">*</span></label>
                                <input type="text" name="reason" class="form-control" value="{{old('reason')}}" placeholder="Reason for transfer" required>
                                @error('reason')
                                    <span class="text-danger">{{$message}}</span>
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
                            <div class="col-md-6">
                                <label class="form-label">Product <span class="text-danger">*</span></label>
                                <select id="builderProduct" class="form-control select-product" disabled required>
                                    <option value="">Select source warehouse first</option>
                                </select>
                                <small class="text-muted available-stock">Available: <span class="stock-qty">0.00</span></small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                <input type="number" id="builderQty" class="form-control transfer-qty" step="0.01" min="0.01" required>
                            </div>
                            <div class="col-md-3 d-flex align-items-end gap-2">
                                <button type="button" class="btn btn-info w-100" id="add-item"><i class="fe-plus"></i> <span id="addItemText">Add Item</span></button>
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
                                    <th class="text-end" style="width:160px;">Qty</th>
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
                            <button type="submit" class="btn btn-primary">Create Transfer</button>
                            <a href="{{route('admin.transfer.index')}}" class="btn btn-secondary">Cancel</a>
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
    const lines = [];
    let editingIndex = null;
    let fromWarehouseId = $('#from_warehouse_id').val() || null;
    let sourceProducts = [];
    const warehouseProductsUrl = '{{ route("admin.transfer.warehouse-products") }}';

    function initializeSelect2($select) {
        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }
        $select.select2({ width: '100%' });
    }

    function findSourceProduct(productId) {
        return sourceProducts.find(function (product) {
            return String(product.product_id) === String(productId);
        });
    }

    function buildProductOptions(selectedProductId = '') {
        let options = '<option value="">Select Product</option>';
        sourceProducts.forEach(function (product) {
            const selected = String(product.product_id) === String(selectedProductId) ? 'selected' : '';
            options += `<option value="${product.product_id}" ${selected}>${product.product_name} (${product.sku || '-'})</option>`;
        });
        return options;
    }

    function updateRowAvailability($row) {
        const $productSelect = $row.find('.select-product');
        const productId = $productSelect.val();
        const $stockQty = $row.find('.stock-qty');
        const $availableStock = $row.find('.available-stock');
        const $qtyInput = $row.find('.transfer-qty');

        const product = findSourceProduct(productId);
        const availableQty = Number(product?.available_quantity || 0);

        $stockQty.text(availableQty.toFixed(2));
        $qtyInput.attr('max', availableQty > 0 ? availableQty : 0);

        if (availableQty <= 0) {
            $availableStock.addClass('text-danger');
        } else {
            $availableStock.removeClass('text-danger');
        }

        const requestedQty = Number($qtyInput.val() || 0);
        if (requestedQty > availableQty && availableQty >= 0) {
            $qtyInput[0].setCustomValidity(`Only ${availableQty.toFixed(2)} available in source warehouse`);
            $qtyInput.addClass('is-invalid');
        } else {
            $qtyInput[0].setCustomValidity('');
            $qtyInput.removeClass('is-invalid');
        }
    }

    function showItemMessage(message) {
        $('#itemMessage').text(message || '').toggleClass('d-none', !message);
    }

    function updateBuilderAvailability() {
        const productId = $('#builderProduct').val();
        const product = findSourceProduct(productId);
        const availableQty = Number(product?.available_quantity || 0);
        $('.stock-qty').text(availableQty.toFixed(2));
        $('#builderQty').attr('max', availableQty > 0 ? availableQty : 0);
        $('.available-stock').toggleClass('text-danger', availableQty <= 0);
        const requestedQty = Number($('#builderQty').val() || 0);
        if (requestedQty > availableQty && availableQty >= 0) {
            $('#builderQty')[0].setCustomValidity(`Only ${availableQty.toFixed(2)} available in source warehouse`);
            $('#builderQty').addClass('is-invalid');
        } else {
            $('#builderQty')[0].setCustomValidity('');
            $('#builderQty').removeClass('is-invalid');
        }
    }

    function refreshProductDropdowns() {
        const hasWarehouse = !!fromWarehouseId;
        const hasProducts = sourceProducts.length > 0;

        const $select = $('#builderProduct');
        const selectedValue = $select.val() || '';
        if (!hasWarehouse) {
            $select.html('<option value="">Select source warehouse first</option>');
            $select.prop('disabled', true);
        } else if (!hasProducts) {
            $select.html('<option value="">No available products in source warehouse</option>');
            $select.prop('disabled', true);
        } else {
            $select.html(buildProductOptions(selectedValue));
            $select.prop('disabled', false);
            if (selectedValue) {
                $select.val(String(selectedValue)).trigger('change.select2');
            }
        }
        initializeSelect2($select);
        updateBuilderAvailability();
    }

    function loadWarehouseProducts(warehouseId) {
        sourceProducts = [];
        refreshProductDropdowns();

        if (!warehouseId) {
            return;
        }

        $.ajax({
            url: warehouseProductsUrl,
            method: 'GET',
            data: { warehouse_id: warehouseId },
            success: function (response) {
                sourceProducts = Array.isArray(response.products) ? response.products : [];
                refreshProductDropdowns();
            },
            error: function () {
                sourceProducts = [];
                refreshProductDropdowns();
                alert('Failed to load source warehouse products. Please try again.');
            }
        });
    }

    $('#from_warehouse_id').on('change', function () {
        fromWarehouseId = $(this).val() || null;
        loadWarehouseProducts(fromWarehouseId);
    });

    $(document).on('change', '#builderProduct', function () {
        updateBuilderAvailability();
    });

    $(document).on('input', '#builderQty', function () {
        updateBuilderAvailability();
    });
    
    function renderGrid() {
        const $grid = $('#items-grid');
        const $hidden = $('#items-container');
        $grid.empty();
        $hidden.empty();

        if (!lines.length) {
            $grid.html('<tr><td colspan="4" class="text-center text-muted py-3">No items added yet.</td></tr>');
            return;
        }

        lines.forEach(function (line, index) {
            $grid.append(`
                <tr data-index="${index}">
                    <td class="text-muted fw-semibold">${index + 1}</td>
                    <td>${line.product_label || '-'}</td>
                    <td class="text-end fw-semibold">${Number(line.quantity || 0).toFixed(2)}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary btn-edit-item">Edit</button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item">Remove</button>
                    </td>
                </tr>
            `);
            $hidden.append(`
                <input type="hidden" name="items[${index}][product_id]" value="${line.product_id}">
                <input type="hidden" name="items[${index}][quantity]" value="${line.quantity}">
            `);
        });
    }

    function clearBuilder() {
        editingIndex = null;
        $('#itemEditState').addClass('d-none');
        $('#addItemText').text('Add Item');
        $('#clear-item').addClass('d-none');
        $('#builderProduct').val('').trigger('change.select2');
        $('#builderQty').val('');
        showItemMessage('');
        updateBuilderAvailability();
    }

    $('#add-item').click(function() {
        const productId = $('#builderProduct').val();
        const productLabel = $('#builderProduct option:selected').text().trim();
        const qty = $('#builderQty').val();

        if (!productId) { showItemMessage('Please select a product.'); return; }
        if (!qty || Number(qty) <= 0) { showItemMessage('Please enter a valid quantity.'); return; }
        // use built-in validity check from stock availability
        if (!$('#builderQty')[0].checkValidity()) { showItemMessage($('#builderQty')[0].validationMessage || 'Invalid quantity.'); return; }

        const line = { product_id: String(productId), product_label: productLabel, quantity: qty };
        if (editingIndex === null) lines.push(line); else lines[editingIndex] = line;

        renderGrid();
        clearBuilder();
    });

    $('#clear-item').on('click', clearBuilder);

    $(document).on('click', '.btn-remove-item', function() {
        const index = Number($(this).closest('tr').data('index'));
        if (!Number.isInteger(index)) return;
        lines.splice(index, 1);
        if (editingIndex === index) clearBuilder();
        if (editingIndex !== null && editingIndex > index) editingIndex -= 1;
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
        $('#builderQty').val(line.quantity);
        showItemMessage('');
        updateBuilderAvailability();
    });

    refreshProductDropdowns();
    if (fromWarehouseId) {
        loadWarehouseProducts(fromWarehouseId);
    }

    renderGrid();
});
</script>
@endsection

