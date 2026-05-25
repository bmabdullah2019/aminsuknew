@extends('backEnd.layouts.master')
@section('title','Edit Stock Loss')
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
                    <a href="{{route('admin.loss.show',$loss->id)}}" class="btn btn-danger rounded-pill"><i class="fe-arrow-left"></i> Back</a>
                </div>
                <h4 class="page-title">Edit Stock Loss</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

   <div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{route('admin.loss.update',$loss->id)}}" id="lossForm">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label>Warehouse <span class="text-danger">*</span></label>
                                <select name="warehouse_id" id="warehouse_id" class="form-control" required>
                                    <option value="">Select Warehouse</option>
                                    @foreach($warehouses as $warehouse)
                                        <option value="{{$warehouse->id}}" {{old('warehouse_id',$loss->warehouse_id)==$warehouse->id?'selected':''}}>{{$warehouse->name}} ({{$warehouse->code}})</option>
                                    @endforeach
                                </select>
                                @error('warehouse_id')
                                    <span class="text-danger">{{$message}}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group mb-3">
                                <label>Loss Date <span class="text-danger">*</span></label>
                                <input type="date" name="loss_date" class="form-control" value="{{old('loss_date', optional($loss->loss_date)->format('Y-m-d'))}}" required>
                                @error('loss_date')
                                    <span class="text-danger">{{$message}}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group mb-3">
                                <label>Loss Type <span class="text-danger">*</span></label>
                                <select name="loss_type" class="form-control" required>
                                    <option value="">Select Type</option>
                                    <option value="damage" {{old('loss_type',$loss->loss_type)=='damage'?'selected':''}}>Damage</option>
                                    <option value="expiry" {{old('loss_type',$loss->loss_type)=='expiry'?'selected':''}}>Expiry</option>
                                    <option value="theft" {{old('loss_type',$loss->loss_type)=='theft'?'selected':''}}>Theft</option>
                                    <option value="missing" {{old('loss_type',$loss->loss_type)=='missing'?'selected':''}}>Missing</option>
                                    <option value="quality_issue" {{old('loss_type',$loss->loss_type)=='quality_issue'?'selected':''}}>Quality Issue</option>
                                    <option value="other" {{old('loss_type',$loss->loss_type)=='other'?'selected':''}}>Other</option>
                                </select>
                                @error('loss_type')
                                    <span class="text-danger">{{$message}}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <hr>
                    <h5>Items</h5>
                    @php
                        $items = old('items');
                        if (!$items) {
                            $items = $loss->items->map(function($i) {
                                return [
                                    'product_id' => $i->product_id,
                                    'quantity' => $i->quantity,
                                    'notes' => $i->notes,
                                ];
                            })->toArray();
                        }
                    @endphp

                    <div class="border rounded-3 p-3 mb-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <div class="fw-bold">Item Builder</div>
                            <span class="badge bg-warning text-dark d-none" id="itemEditState">Editing row</span>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-5">
                                <label class="form-label">Product <span class="text-danger">*</span></label>
                                <select id="builderProduct" class="form-control select-product" required>
                                    <option value="">Select Product</option>
                                    @foreach($products as $product)
                                        <option value="{{$product->id}}">{{$product->name}}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted available-stock">Available: <span class="stock-qty">0</span></small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                <input type="number" id="builderQty" class="form-control loss-qty" step="0.01" min="0.01" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Notes</label>
                                <input type="text" id="builderNotes" class="form-control" placeholder="Loss reason/details">
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
                                    <th class="text-end" style="width:160px;">Qty</th>
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
                                <textarea name="notes" class="form-control" rows="3">{{old('notes',$loss->notes)}}</textarea>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">Update Loss</button>
                            <a href="{{route('admin.loss.show',$loss->id)}}" class="btn btn-secondary">Cancel</a>
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
    const initialItems = @json($items);
    const lines = (Array.isArray(initialItems) ? initialItems : []).map(function (item) {
        const id = String(item.product_id || '');
        const label = $(`#builderProduct option[value="${id}"]`).text().trim();
        return {
            product_id: id,
            product_label: label || '',
            quantity: item.quantity || '',
            notes: item.notes || ''
        };
    });
    let editingIndex = null;
    let warehouseId = $('#warehouse_id').val() || null;

    $('#builderProduct').select2({ width: '100%' });

    $('#warehouse_id').change(function() {
        warehouseId = $(this).val();
        updateStockAvailability();
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

                $('#builderQty').attr('max', stockQty);

                if (stockQty <= 0) {
                    stockElement.parent().addClass('text-danger');
                } else {
                    stockElement.parent().removeClass('text-danger');
                }
            }
        });
    }

    $(document).on('change', '#builderProduct', function() {
        const productId = $('#builderProduct').val();
        if (productId && warehouseId) {
            checkStock(productId, $('#builderProduct'));
        }
    });

    function renderGrid() {
        const $grid = $('#items-grid');
        const $hidden = $('#items-container');
        $grid.empty();
        $hidden.empty();

        if (!lines.length) {
            $grid.html('<tr><td colspan="5" class="text-center text-muted py-3">No items added yet.</td></tr>');
            return;
        }

        lines.forEach(function (line, index) {
            $grid.append(`
                <tr data-index="${index}">
                    <td class="text-muted fw-semibold">${index + 1}</td>
                    <td>${line.product_label || '-'}</td>
                    <td class="text-end fw-semibold">${Number(line.quantity || 0).toFixed(2)}</td>
                    <td>${line.notes ? $('<div>').text(line.notes).html() : '-'}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary btn-edit-item">Edit</button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item">Remove</button>
                    </td>
                </tr>
            `);

            $hidden.append(`
                <input type="hidden" name="items[${index}][product_id]" value="${line.product_id}">
                <input type="hidden" name="items[${index}][quantity]" value="${line.quantity}">
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
        $('#builderQty').val('');
        $('#builderNotes').val('');
        showItemMessage('');
    }

    $('#add-item').click(function() {
        const productId = $('#builderProduct').val();
        const productLabel = $('#builderProduct option:selected').text().trim();
        const qty = $('#builderQty').val();
        const notes = $('#builderNotes').val();

        if (!productId) { showItemMessage('Please select a product.'); return; }
        if (!qty || Number(qty) <= 0) { showItemMessage('Please enter a valid quantity.'); return; }

        const line = { product_id: String(productId), product_label: productLabel, quantity: qty, notes: notes || '' };
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
        $('#builderNotes').val(line.notes);
        showItemMessage('');
    });

    renderGrid();
    // initial stock display (if warehouse + first line exists)
    if (warehouseId) {
        const productId = $('#builderProduct').val();
        if (productId) checkStock(productId, $('#builderProduct'));
    }
});
</script>
@endpush
