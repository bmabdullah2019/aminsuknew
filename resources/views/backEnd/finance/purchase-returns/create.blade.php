@extends('backEnd.layouts.master')
@section('title','Create Purchase Return')
@section('css')
<link href="{{asset('public/backEnd')}}/assets/libs/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
@endsection

@section('content')
<div class="container-fluid">
    
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{route('admin.finance.purchase-returns.index')}}" class="btn btn-secondary rounded-pill"><i class="fe-arrow-left"></i> Back</a>
                </div>
                <h4 class="page-title">Create Purchase Return</h4>
            </div>
        </div>
    </div>       
    <!-- end page title --> 

   <div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card">
            <div class="card-body">
                <form action="{{route('admin.finance.purchase-returns.store')}}" method="POST" id="returnForm">
                    @csrf

                    <!-- Basic Information -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="branch_id" class="form-label">Branch <span class="text-danger">*</span></label>
                                <select class="form-control select2 @error('branch_id') is-invalid @enderror" id="branch_id" name="branch_id" required>
                                    <option value="">-- Select Branch --</option>
                                    @foreach($branches as $branch)
                                    <option value="{{$branch->id}}" {{old('branch_id') == $branch->id ? 'selected' : ''}}>{{$branch->name}}</option>
                                    @endforeach
                                </select>
                                @error('branch_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="supplier_id" class="form-label">Supplier <span class="text-danger">*</span></label>
                                <select class="form-control select2 @error('supplier_id') is-invalid @enderror" id="supplier_id" name="supplier_id" required>
                                    <option value="">-- Select Supplier --</option>
                                    @foreach($suppliers as $supplier)
                                    <option value="{{$supplier->id}}" {{old('supplier_id') == $supplier->id ? 'selected' : ''}}>{{$supplier->name}}</option>
                                    @endforeach
                                </select>
                                @error('supplier_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="return_date" class="form-label">Return Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('return_date') is-invalid @enderror" id="return_date" name="return_date" value="{{old('return_date', now()->format('Y-m-d'))}}" required>
                                @error('return_date')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="return_reason" class="form-label">Return Reason <span class="text-danger">*</span></label>
                                <select class="form-control @error('return_reason') is-invalid @enderror" id="return_reason" name="return_reason" required>
                                    <option value="">-- Select Reason --</option>
                                    <option value="damaged" {{old('return_reason') == 'damaged' ? 'selected' : ''}}>Damaged</option>
                                    <option value="quality_issue" {{old('return_reason') == 'quality_issue' ? 'selected' : ''}}>Quality Issue</option>
                                    <option value="wrong_item" {{old('return_reason') == 'wrong_item' ? 'selected' : ''}}>Wrong Item</option>
                                    <option value="quantity_mismatch" {{old('return_reason') == 'quantity_mismatch' ? 'selected' : ''}}>Quantity Mismatch</option>
                                    <option value="expired" {{old('return_reason') == 'expired' ? 'selected' : ''}}>Expired</option>
                                    <option value="other" {{old('return_reason') == 'other' ? 'selected' : ''}}>Other</option>
                                </select>
                                @error('return_reason')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="purchase_order_id" class="form-label">Purchase Order</label>
                                <select class="form-control select2 @error('purchase_order_id') is-invalid @enderror" id="purchase_order_id" name="purchase_order_id">
                                    <option value="">-- Optional --</option>
                                    @foreach($purchaseOrders as $po)
                                    <option value="{{$po->id}}" {{old('purchase_order_id') == $po->id ? 'selected' : ''}}>{{$po->po_number}}</option>
                                    @endforeach
                                </select>
                                @error('purchase_order_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" placeholder="Enter return details...">{{old('description')}}</textarea>
                                @error('description')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Items Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="mb-3">Return Items <span class="text-danger">*</span></h5>

                            <div class="border rounded-3 p-3 mb-3">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                    <div class="fw-bold">Item Builder</div>
                                    <span class="badge bg-warning text-dark d-none" id="itemEditState">Editing row</span>
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label">Product <span class="text-danger">*</span></label>
                                        <select id="builderProduct" class="form-control select2">
                                            <option value="">-- Select Product --</option>
                                            @foreach($products as $product)
                                                <option value="{{$product->id}}">{{$product->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Variant</label>
                                        <select id="builderVariant" class="form-control">
                                            <option value="">-- None --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" min="0.01" id="builderQty" class="form-control" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Unit Price <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" min="0" id="builderPrice" class="form-control" required>
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end">
                                        <button type="button" class="btn btn-success w-100" id="addItemBtn"><i class="fe-plus"></i></button>
                                    </div>
                                    <div class="col-12 d-flex justify-content-end">
                                        <button type="button" class="btn btn-link text-decoration-none p-0 d-none" id="clear-item">Clear edit</button>
                                    </div>
                                    <div class="col-12">
                                        <div class="alert alert-warning d-none mb-0" id="itemMessage"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="table-responsive mb-3">
                                <table class="table table-bordered table-sm" id="itemsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 30%;">Product</th>
                                            <th style="width: 20%;">Variant</th>
                                            <th style="width: 15%;">Quantity</th>
                                            <th style="width: 15%;">Unit Price</th>
                                            <th style="width: 15%;">Line Total</th>
                                            <th style="width: 5%; text-align: center;">
                                                <span class="text-muted small">Action</span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody id="itemsBody">
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <td colspan="4" style="text-align: right;"><strong>Total Return Amount:</strong></td>
                                            <td><strong id="totalAmount">৳0.00</strong></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div id="hiddenItems"></div>

                            @error('items')
                                <div class="alert alert-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Submit Section -->
                    <div class="row">
                        <div class="col-12">
                            <div class="float-end">
                                <button type="submit" class="btn btn-success btn-lg"><i class="fe-check"></i> Create Return</button>
                                <a href="{{route('admin.finance.purchase-returns.index')}}" class="btn btn-secondary btn-lg"><i class="fe-x"></i> Cancel</a>
                            </div>
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
    // Initialize Select2
    $('.select2').select2({ width: '100%' });

    const initialItems = @json(old('items', []));
    const lines = (Array.isArray(initialItems) ? initialItems : []).map(function (item) {
        const pid = String(item.product_id || '');
        const plabel = $(`#builderProduct option[value="${pid}"]`).text().trim();
        return {
            product_id: pid,
            product_label: plabel || '',
            product_variant_id: String(item.product_variant_id || ''),
            quantity: String(item.quantity || ''),
            unit_price: String(item.unit_price || '')
        };
    });
    let editingIndex = null;

    function showItemMessage(message) {
        $('#itemMessage').text(message || '').toggleClass('d-none', !message);
    }

    function render() {
        const $body = $('#itemsBody');
        const $hidden = $('#hiddenItems');
        $body.empty();
        $hidden.empty();

        if (!lines.length) {
            $body.html('<tr><td colspan="6" class="text-center text-muted py-3">No items added yet.</td></tr>');
        }

        let total = 0;
        lines.forEach(function (line, index) {
            const qty = parseFloat(line.quantity || 0) || 0;
            const price = parseFloat(line.unit_price || 0) || 0;
            const lineTotal = qty * price;
            total += lineTotal;

            $body.append(`
                <tr class="item-row" data-index="${index}">
                    <td>${line.product_label || '-'}</td>
                    <td>${line.product_variant_id ? line.product_variant_id : '<span class="text-muted">--</span>'}</td>
                    <td class="text-end fw-semibold">${qty.toFixed(2)}</td>
                    <td class="text-end">${price.toFixed(2)}</td>
                    <td class="text-end">${lineTotal.toFixed(2)}</td>
                    <td style="text-align:center;">
                        <button type="button" class="btn btn-sm btn-outline-primary btn-edit-item"><i class="fe-edit"></i></button>
                        <button type="button" class="btn btn-sm btn-danger btn-remove-item"><i class="fe-trash-2"></i></button>
                    </td>
                </tr>
            `);

            $hidden.append(`
                <input type="hidden" name="items[${index}][product_id]" value="${line.product_id}">
                <input type="hidden" name="items[${index}][product_variant_id]" value="${line.product_variant_id}">
                <input type="hidden" name="items[${index}][quantity]" value="${line.quantity}">
                <input type="hidden" name="items[${index}][unit_price]" value="${line.unit_price}">
            `);
        });

        $('#totalAmount').text('৳' + total.toFixed(2));
    }

    function clearBuilder() {
        editingIndex = null;
        $('#itemEditState').addClass('d-none');
        $('#clear-item').addClass('d-none');
        $('#builderProduct').val('').trigger('change.select2');
        $('#builderVariant').val('');
        $('#builderQty').val('');
        $('#builderPrice').val('');
        showItemMessage('');
    }

    $('#addItemBtn').on('click', function () {
        const productId = $('#builderProduct').val();
        const productLabel = $('#builderProduct option:selected').text().trim();
        const variantId = $('#builderVariant').val();
        const qty = $('#builderQty').val();
        const price = $('#builderPrice').val();

        if (!productId) { showItemMessage('Please select a product.'); return; }
        if (!qty || Number(qty) <= 0) { showItemMessage('Please enter a valid quantity.'); return; }
        if (price === '' || Number(price) < 0) { showItemMessage('Please enter a valid unit price.'); return; }

        const line = {
            product_id: String(productId),
            product_label: productLabel,
            product_variant_id: String(variantId || ''),
            quantity: String(qty),
            unit_price: String(price)
        };

        if (editingIndex === null) lines.push(line); else lines[editingIndex] = line;
        render();
        clearBuilder();
    });

    $('#clear-item').on('click', clearBuilder);

    $(document).on('click', '.btn-remove-item', function () {
        const index = Number($(this).closest('tr').data('index'));
        if (!Number.isInteger(index)) return;
        lines.splice(index, 1);
        if (editingIndex === index) clearBuilder();
        if (editingIndex !== null && editingIndex > index) editingIndex -= 1;
        render();
    });

    $(document).on('click', '.btn-edit-item', function () {
        const index = Number($(this).closest('tr').data('index'));
        if (!Number.isInteger(index) || !lines[index]) return;
        const line = lines[index];
        editingIndex = index;
        $('#itemEditState').text(`Editing row ${index + 1}`).removeClass('d-none');
        $('#clear-item').removeClass('d-none');
        $('#builderProduct').val(String(line.product_id)).trigger('change.select2');
        $('#builderVariant').val(String(line.product_variant_id || ''));
        $('#builderQty').val(line.quantity);
        $('#builderPrice').val(line.unit_price);
        showItemMessage('');
    });

    render();
});
</script>
@endpush
