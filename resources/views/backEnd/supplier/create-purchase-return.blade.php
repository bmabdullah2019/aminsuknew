@extends('backEnd.layouts.master')
@section('title','Create Purchase Return - ' . $supplier->name)
@section('content')
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.supplier.purchase-returns', $supplier->id) }}" class="btn btn-sm btn-secondary">
                        <i class="mdi mdi-arrow-left"></i> Back to Returns
                    </a>
                </div>
                <h4 class="page-title">Create Purchase Return - {{ $supplier->name }}</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Return Details</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.supplier.purchase-returns.store', $supplier->id) }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="return_date" class="form-label">Return Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('return_date') is-invalid @enderror"
                                           id="return_date" name="return_date" value="{{ old('return_date', date('Y-m-d')) }}" required>
                                    @error('return_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="total_amount" class="form-label">Total Amount (BDT) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0.01" class="form-control @error('total_amount') is-invalid @enderror"
                                           id="total_amount" name="total_amount" value="{{ old('total_amount') }}" placeholder="0.00" readonly required>
                                    @error('total_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="original_purchase_id" class="form-label">Original Purchase (Optional)</label>
                            <select class="form-control @error('original_purchase_id') is-invalid @enderror" id="original_purchase_id" name="original_purchase_id">
                                <option value="">Select Purchase Order</option>
                                @foreach($purchaseOrders as $purchaseOrder)
                                <option value="{{ $purchaseOrder->id }}" {{ (string) old('original_purchase_id') === (string) $purchaseOrder->id ? 'selected' : '' }}>
                                    {{ $purchaseOrder->po_number }} | {{ ucfirst($purchaseOrder->status) }} | BDT {{ number_format((float) $purchaseOrder->total_cost, 2) }}
                                </option>
                                @endforeach
                            </select>
                            @error('original_purchase_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <label class="form-label mb-0">Return Items <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="add-return-item">
                                    <i class="mdi mdi-plus"></i> Add Item
                                </button>
                            </div>
                            @error('items')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                            <div class="table-responsive mt-2">
                                <table class="table table-sm table-bordered align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="min-width: 290px;">Purchase Item</th>
                                            <th style="min-width: 180px;">Warehouse</th>
                                            <th style="min-width: 120px;">Qty</th>
                                            <th style="min-width: 120px;">Unit Cost</th>
                                            <th style="min-width: 130px;">Line Total</th>
                                            <th style="min-width: 200px;">Notes</th>
                                            <th style="width: 60px;">#</th>
                                        </tr>
                                    </thead>
                                    <tbody id="return-items-body">
                                        @php
                                            $oldItems = old('items');
                                            if (!is_array($oldItems) || count($oldItems) === 0) {
                                                $oldItems = [[]];
                                            }
                                        @endphp
                                        @foreach($oldItems as $index => $oldItem)
                                            <tr data-row-index="{{ $index }}">
                                                <td>
                                                    <select name="items[{{ $index }}][purchase_item_id]"
                                                            class="form-control purchase-item-select @error('items.' . $index . '.purchase_item_id') is-invalid @enderror" required>
                                                        <option value="">Select received item</option>
                                                        @foreach($returnableItems as $returnableItem)
                                                            @php
                                                                $po = $returnableItem->purchaseOrder;
                                                                $variant = $returnableItem->productVariant;
                                                                $productName = $variant?->product?->name ?? 'Product';
                                                                $variantLabel = $variant?->name ?? ('Variant #' . $returnableItem->product_variant_id);
                                                                $receivedQty = number_format((float) $returnableItem->quantity_received, 2, '.', '');
                                                                $selectedItemId = (string) ($oldItem['purchase_item_id'] ?? '');
                                                            @endphp
                                                            <option value="{{ $returnableItem->id }}"
                                                                    data-po-id="{{ $po?->id }}"
                                                                    data-warehouse-id="{{ $po?->warehouse_id }}"
                                                                    data-max-qty="{{ $receivedQty }}"
                                                                    data-unit-cost="{{ number_format((float) $returnableItem->unit_cost, 2, '.', '') }}"
                                                                    {{ $selectedItemId === (string) $returnableItem->id ? 'selected' : '' }}>
                                                                {{ $po?->po_number }} | {{ $productName }} ({{ $variantLabel }}) | Recv: {{ number_format((float) $returnableItem->quantity_received, 2) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @error('items.' . $index . '.purchase_item_id')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </td>
                                                <td>
                                                    <select name="items[{{ $index }}][warehouse_id]"
                                                            class="form-control warehouse-select @error('items.' . $index . '.warehouse_id') is-invalid @enderror" required>
                                                        <option value="">Select warehouse</option>
                                                        @foreach($warehouses as $warehouse)
                                                            <option value="{{ $warehouse->id }}"
                                                                {{ (string) ($oldItem['warehouse_id'] ?? '') === (string) $warehouse->id ? 'selected' : '' }}>
                                                                {{ $warehouse->code }} - {{ $warehouse->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @error('items.' . $index . '.warehouse_id')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </td>
                                                <td>
                                                    <input type="number"
                                                           step="0.01"
                                                           min="0.01"
                                                           name="items[{{ $index }}][quantity]"
                                                           value="{{ $oldItem['quantity'] ?? '' }}"
                                                           class="form-control quantity-input @error('items.' . $index . '.quantity') is-invalid @enderror"
                                                           placeholder="0.00" required>
                                                    @error('items.' . $index . '.quantity')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </td>
                                                <td>
                                                    <input type="number"
                                                           step="0.01"
                                                           min="0"
                                                           name="items[{{ $index }}][unit_cost]"
                                                           value="{{ $oldItem['unit_cost'] ?? '' }}"
                                                           class="form-control unit-cost-input @error('items.' . $index . '.unit_cost') is-invalid @enderror"
                                                           placeholder="0.00" required>
                                                    @error('items.' . $index . '.unit_cost')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </td>
                                                <td class="text-end fw-semibold line-total">0.00</td>
                                                <td>
                                                    <input type="text"
                                                           name="items[{{ $index }}][notes]"
                                                           value="{{ $oldItem['notes'] ?? '' }}"
                                                           class="form-control"
                                                           placeholder="Optional">
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-outline-danger remove-item" title="Remove">
                                                        <i class="mdi mdi-close"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if($returnableItems->isEmpty())
                                <small class="text-warning d-block mt-2">No received purchase items found for this supplier.</small>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label for="return_reason" class="form-label">Reason for Return <span class="text-danger">*</span></label>
                            <select class="form-control @error('return_reason') is-invalid @enderror" id="return_reason" name="return_reason" required>
                                <option value="">Select Reason</option>
                                <option value="damaged" {{ old('return_reason') == 'damaged' ? 'selected' : '' }}>Damaged Goods</option>
                                <option value="wrong_item" {{ old('return_reason') == 'wrong_item' ? 'selected' : '' }}>Wrong Item Received</option>
                                <option value="quality_issue" {{ old('return_reason') == 'quality_issue' ? 'selected' : '' }}>Quality Issue</option>
                                <option value="over_supply" {{ old('return_reason') == 'over_supply' ? 'selected' : '' }}>Over Supplied</option>
                                <option value="other" {{ old('return_reason') == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('return_reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Additional Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes"
                                      rows="3" placeholder="Optional additional notes about this return">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('admin.supplier.purchase-returns', $supplier->id) }}" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-content-save"></i> Create Return
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Supplier Info -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Supplier Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>Name:</strong> {{ $supplier->name }}
                    </div>
                    <div class="mb-2">
                        <strong>Phone:</strong> {{ $supplier->phone }}
                    </div>
                    <div class="mb-2">
                        <strong>Email:</strong> {{ $supplier->email ?: 'N/A' }}
                    </div>
                    <div class="mb-2">
                        <strong>Current Balance:</strong>
                        <span class="badge bg-{{ $supplier->current_balance >= 0 ? 'danger' : 'success' }}">
                            BDT {{ number_format(abs($supplier->current_balance), 2) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Recent Returns -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Recent Returns</h6>
                </div>
                <div class="card-body">
                    @forelse($recentReturns as $return)
                        <div class="mb-2">
                            <small class="text-muted">{{ $return->return_date->format('d M Y') }}</small><br>
                            <strong>{{ $return->return_number }}</strong><br>
                            <span class="badge bg-danger">BDT {{ number_format($return->total_amount, 2) }}</span>
                        </div>
                        <hr>
                    @empty
                        <small class="text-muted">No recent returns</small>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const body = document.getElementById('return-items-body');
    const addButton = document.getElementById('add-return-item');
    const purchaseSelect = document.getElementById('original_purchase_id');
    const totalInput = document.getElementById('total_amount');

    const baseOptions = body.querySelector('.purchase-item-select')?.innerHTML ?? '<option value="">Select received item</option>';
    const baseWarehouses = body.querySelector('.warehouse-select')?.innerHTML ?? '<option value="">Select warehouse</option>';

    function rowTemplate(index) {
        return `
            <tr data-row-index="${index}">
                <td>
                    <select name="items[${index}][purchase_item_id]" class="form-control purchase-item-select" required>
                        ${baseOptions}
                    </select>
                </td>
                <td>
                    <select name="items[${index}][warehouse_id]" class="form-control warehouse-select" required>
                        ${baseWarehouses}
                    </select>
                </td>
                <td>
                    <input type="number" step="0.01" min="0.01" name="items[${index}][quantity]" class="form-control quantity-input" placeholder="0.00" required>
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="items[${index}][unit_cost]" class="form-control unit-cost-input" placeholder="0.00" required>
                </td>
                <td class="text-end fw-semibold line-total">0.00</td>
                <td>
                    <input type="text" name="items[${index}][notes]" class="form-control" placeholder="Optional">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-item" title="Remove">
                        <i class="mdi mdi-close"></i>
                    </button>
                </td>
            </tr>
        `;
    }

    function parseNumber(value) {
        const n = parseFloat(value);
        return Number.isFinite(n) ? n : 0;
    }

    function recalculateRow(row) {
        const qty = parseNumber(row.querySelector('.quantity-input')?.value);
        const unit = parseNumber(row.querySelector('.unit-cost-input')?.value);
        const line = Math.max(0, qty) * Math.max(0, unit);
        row.querySelector('.line-total').textContent = line.toFixed(2);
        return line;
    }

    function recalculateGrandTotal() {
        let total = 0;
        body.querySelectorAll('tr').forEach((row) => {
            total += recalculateRow(row);
        });
        totalInput.value = total.toFixed(2);
    }

    function applyPurchaseFilter(row) {
        const selectedPurchase = purchaseSelect.value;
        const itemSelect = row.querySelector('.purchase-item-select');
        if (!itemSelect) {
            return;
        }

        Array.from(itemSelect.options).forEach((option) => {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            if (!selectedPurchase) {
                option.hidden = false;
                return;
            }

            option.hidden = option.dataset.poId !== selectedPurchase;
        });

        const selectedOption = itemSelect.options[itemSelect.selectedIndex];
        if (selectedOption && selectedOption.hidden) {
            itemSelect.value = '';
            itemSelect.dispatchEvent(new Event('change'));
        }
    }

    function bindRow(row) {
        const itemSelect = row.querySelector('.purchase-item-select');
        const warehouseSelect = row.querySelector('.warehouse-select');
        const qtyInput = row.querySelector('.quantity-input');
        const unitCostInput = row.querySelector('.unit-cost-input');
        const removeButton = row.querySelector('.remove-item');

        const syncByItem = () => {
            const option = itemSelect.options[itemSelect.selectedIndex];
            const selectedItemId = String(itemSelect.value || '');
            if (selectedItemId) {
                const duplicate = Array.from(body.querySelectorAll('tr')).some((otherRow) => {
                    if (otherRow === row) {
                        return false;
                    }
                    return String(otherRow.querySelector('.purchase-item-select')?.value || '') === selectedItemId;
                });
                if (duplicate) {
                    alert('This purchase item is already added.');
                    itemSelect.value = '';
                    recalculateGrandTotal();
                    return;
                }
            }

            if (!option || !option.value) {
                recalculateGrandTotal();
                return;
            }

            const maxQty = parseNumber(option.dataset.maxQty);
            const unitCost = parseNumber(option.dataset.unitCost);
            const warehouseId = option.dataset.warehouseId;

            if (maxQty > 0) {
                qtyInput.max = maxQty.toFixed(2);
                if (!qtyInput.value || parseNumber(qtyInput.value) <= 0) {
                    qtyInput.value = maxQty.toFixed(2);
                }
            }

            if (!unitCostInput.value || parseNumber(unitCostInput.value) <= 0) {
                unitCostInput.value = unitCost.toFixed(2);
            }

            if (!warehouseSelect.value && warehouseId) {
                warehouseSelect.value = warehouseId;
            }

            recalculateGrandTotal();
        };

        itemSelect.addEventListener('change', syncByItem);
        qtyInput.addEventListener('input', () => {
            const max = parseNumber(qtyInput.max);
            const current = parseNumber(qtyInput.value);
            if (max > 0 && current > max) {
                qtyInput.value = max.toFixed(2);
            }
            recalculateGrandTotal();
        });
        unitCostInput.addEventListener('input', recalculateGrandTotal);
        warehouseSelect.addEventListener('change', recalculateGrandTotal);

        if (removeButton) {
            removeButton.addEventListener('click', () => {
                if (body.querySelectorAll('tr').length <= 1) {
                    return;
                }
                row.remove();
                recalculateGrandTotal();
            });
        }

        applyPurchaseFilter(row);
        syncByItem();
    }

    addButton.addEventListener('click', () => {
        const lastRow = body.querySelector('tr:last-child');
        const selectedItem = String(lastRow?.querySelector('.purchase-item-select')?.value || '');
        if (!selectedItem) {
            alert('Please select a purchase item in the current row before adding a new row.');
            lastRow?.querySelector('.purchase-item-select')?.focus();
            return;
        }

        const index = body.querySelectorAll('tr').length;
        body.insertAdjacentHTML('beforeend', rowTemplate(index));
        const row = body.querySelectorAll('tr')[index];
        const newItemSelect = row.querySelector('.purchase-item-select');
        const newWarehouseSelect = row.querySelector('.warehouse-select');
        const newQtyInput = row.querySelector('.quantity-input');
        const newUnitInput = row.querySelector('.unit-cost-input');
        if (newItemSelect) newItemSelect.value = '';
        if (newWarehouseSelect) newWarehouseSelect.value = '';
        if (newQtyInput) newQtyInput.value = '';
        if (newUnitInput) newUnitInput.value = '';
        bindRow(row);
        recalculateGrandTotal();
    });

    purchaseSelect.addEventListener('change', () => {
        body.querySelectorAll('tr').forEach((row) => applyPurchaseFilter(row));
    });

    body.querySelectorAll('tr').forEach((row) => bindRow(row));
    recalculateGrandTotal();
})();
</script>
@endsection


