@extends('backEnd.layouts.master')
@section('title','Edit Stock Adjustment')
@section('css')
<link href="{{asset('public/backEnd')}}/assets/libs/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
<style>
    .item-row { border-bottom: 1px solid #eee; padding: 10px 0; }
    .remove-item { cursor: pointer; color: #dc3545; }
</style>
@endsection
@section('content')
<div class="container-fluid">

    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{route('admin.adjustment.show',$adjustment->id)}}" class="btn btn-danger rounded-pill"><i class="fe-arrow-left"></i> Back</a>
                </div>
                <h4 class="page-title">Edit Stock Adjustment</h4>
            </div>
        </div>
    </div>

   <div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{route('admin.adjustment.update',$adjustment->id)}}" id="adjustmentForm">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label>Warehouse <span class="text-danger">*</span></label>
                                <select name="warehouse_id" id="warehouse_id" class="form-control" required>
                                    <option value="">Select Warehouse</option>
                                    @foreach($warehouses as $warehouse)
                                        <option value="{{$warehouse->id}}" {{old('warehouse_id',$adjustment->warehouse_id)==$warehouse->id?'selected':''}}>{{$warehouse->name}} ({{$warehouse->code}})</option>
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
                                <input type="date" name="adjustment_date" class="form-control" value="{{old('adjustment_date', optional($adjustment->adjustment_date)->format('Y-m-d'))}}" required>
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
                                    <option value="increase" {{old('adjustment_type',$adjustment->adjustment_type)=='increase'?'selected':''}}>Increase</option>
                                    <option value="decrease" {{old('adjustment_type',$adjustment->adjustment_type)=='decrease'?'selected':''}}>Decrease</option>
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
                                    <option value="physical_count" {{ old('reason',$adjustment->reason)=='physical_count'? 'selected' : '' }}>Physical Count</option>
                                    <option value="damage" {{ old('reason',$adjustment->reason)=='damage'? 'selected' : '' }}>Damage</option>
                                    <option value="expiry" {{ old('reason',$adjustment->reason)=='expiry'? 'selected' : '' }}>Expiry</option>
                                    <option value="theft" {{ old('reason',$adjustment->reason)=='theft'? 'selected' : '' }}>Theft</option>
                                    <option value="found" {{ old('reason',$adjustment->reason)=='found'? 'selected' : '' }}>Found</option>
                                    <option value="correction" {{ old('reason',$adjustment->reason)=='correction'? 'selected' : '' }}>Correction</option>
                                    <option value="migration" {{ old('reason',$adjustment->reason)=='migration'? 'selected' : '' }}>Migration</option>
                                    <option value="other" {{ old('reason',$adjustment->reason)=='other'? 'selected' : '' }}>Other</option>
                                </select>
                                @error('reason')
                                    <span class="text-danger">{{$message}}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group mb-3">
                                <label>Reason Details <span class="text-danger">*</span></label>
                                <textarea name="reason_details" class="form-control" rows="3" placeholder="Provide detailed reason (min 20 characters)">{{ old('reason_details',$adjustment->reason_details) }}</textarea>
                                @error('reason_details')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <hr>
                    <h5>Items</h5>
                    @php
                        $items = old('items');
                        if (!$items) {
                            $items = $adjustment->items->map(function($i) {
                                return [
                                    'product_id' => $i->product_id,
                                    'product_variant_id' => $i->product_variant_id,
                                    'quantity' => abs((float) $i->adjusted_quantity - (float) $i->system_quantity),
                                    'unit_cost' => $i->unit_cost,
                                    'notes' => $i->notes,
                                ];
                            })->toArray();
                        }
                    @endphp

                    <div id="items-container">
                        @foreach($items as $index => $item)
                        <div class="item-row">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label>Product <span class="text-danger">*</span></label>
                                        <select name="items[{{$index}}][product_id]" class="form-control select-product" required>
                                            <option value="">Select Product</option>
                                            @foreach($products as $product)
                                                <option value="{{$product->id}}" {{(string)($item['product_id'] ?? '') === (string)$product->id ? 'selected' : ''}}>{{$product->name}}</option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted available-stock">Available: <span class="stock-qty">0</span></small>
                                        @error("items.$index.product_id")
                                            <span class="text-danger">{{$message}}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group mb-3">
                                        <label>Variant</label>
                                        <select name="items[{{$index}}][product_variant_id]" class="form-control select-variant" data-variant="{{$item['product_variant_id'] ?? ''}}">
                                            <option value="">Auto-select</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group mb-3">
                                        <label>Quantity <span class="text-danger">*</span></label>
                                        <input type="number" name="items[{{$index}}][quantity]" class="form-control" step="0.01" min="0.01" value="{{$item['quantity'] ?? ''}}" required>
                                        @error("items.$index.quantity")
                                            <span class="text-danger">{{$message}}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group mb-3">
                                        <label>Unit Cost</label>
                                        <input type="number" name="items[{{$index}}][unit_cost]" class="form-control" step="0.01" min="0" value="{{$item['unit_cost'] ?? ''}}">
                                        @error("items.$index.unit_cost")
                                            <span class="text-danger">{{$message}}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-2" style="display: flex; align-items: center;">
                                    <button type="button" class="btn btn-danger btn-sm remove-item" style="width: 100%;"><i class="fe-trash-2"></i></button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group mb-3">
                                        <label>Notes</label>
                                        <input type="text" name="items[{{$index}}][notes]" class="form-control" placeholder="Item notes" value="{{$item['notes'] ?? ''}}">
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
                                <textarea name="notes" class="form-control" rows="3">{{old('notes',$adjustment->notes)}}</textarea>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">Update Adjustment</button>
                            <a href="{{route('admin.adjustment.show',$adjustment->id)}}" class="btn btn-secondary">Cancel</a>
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
    let itemIndex = {{ count($items) }};
    let warehouseId = $('#warehouse_id').val() || null;

    $('.select-product').select2();
    $('.select-variant').select2();

    $('#warehouse_id').change(function() {
        warehouseId = $(this).val();
        updateStockAvailability();
    });

    function updateStockAvailability() {
        if (!warehouseId) return;

        $('.select-product').each(function() {
            const productId = $(this).val();
            if (productId) {
                checkStock(productId, $(this));
            }
        });
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
                const stockElement = selectElement.closest('.item-row').find('.stock-qty');
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
                const selectedValue = variantSelect.data('variant');
                if (response.variants && response.variants.length > 0) {
                    response.variants.forEach(function(variant) {
                        const isSelected = selectedValue == variant.id ? 'selected' : '';
                        html += '<option value="' + variant.id + '" ' + isSelected + '>' + variant.sku_code + ' ' + (variant.label ? '(' + variant.label + ')' : '') + '</option>';
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
        const productId = $(this).val();
        const variantSelect = $(this).closest('.item-row').find('.select-variant');
        
        if (productId && warehouseId) {
            checkStock(productId, $(this));
        }
        
        loadVariants(productId, variantSelect);
    });

    $('#add-item').click(function() {
        const newRow = `
            <div class="item-row">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label>Product <span class="text-danger">*</span></label>
                            <select name="items[${itemIndex}][product_id]" class="form-control select-product" required>
                                <option value="">Select Product</option>
                                @foreach($products as $product)
                                    <option value="{{$product->id}}">{{$product->name}}</option>
                                @endforeach
                            </select>
                            <small class="text-muted available-stock">Available: <span class="stock-qty">0</span></small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group mb-3">
                            <label>Variant</label>
                            <select name="items[${itemIndex}][product_variant_id]" class="form-control select-variant">
                                <option value="">Auto-select</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group mb-3">
                            <label>Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="items[${itemIndex}][quantity]" class="form-control" step="0.01" min="0.01" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group mb-3">
                            <label>Unit Cost</label>
                            <input type="number" name="items[${itemIndex}][unit_cost]" class="form-control" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="col-md-2" style="display: flex; align-items: center;">
                        <button type="button" class="btn btn-danger btn-sm remove-item" style="width: 100%;"><i class="fe-trash-2"></i></button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group mb-3">
                            <label>Notes</label>
                            <input type="text" name="items[${itemIndex}][notes]" class="form-control" placeholder="Item notes">
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#items-container').append(newRow);
        $('.select-product').select2();
        $('.select-variant').select2();
        itemIndex++;
        updateStockAvailability();
    });

    $(document).on('click', '.remove-item', function() {
        if ($('.item-row').length > 1) {
            $(this).closest('.item-row').remove();
        } else {
            alert('At least one item is required');
        }
    });

    updateStockAvailability();
    
    // Load variants for existing items on page load
    $('.item-row').each(function() {
        const productId = $(this).find('.select-product').val();
        const variantSelect = $(this).find('.select-variant');
        if (productId) {
            loadVariants(productId, variantSelect);
        }
    });
});
</script>
@endpush
