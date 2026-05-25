@extends('backEnd.layouts.master')
@section('title','Create Return')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{route('admin.returns.index')}}">Returns</a></li>
                        <li class="breadcrumb-item active">Create Return</li>
                    </ol>
                </div>
                <h4 class="page-title">Create Return</h4>
                <p class="text-muted">Process a new product return</p>
            </div>
        </div>
    </div>

    <form action="{{route('admin.returns.store')}}" method="POST" id="returnForm">
        @csrf

        <!-- Step 1: Order Selection -->
        <div class="row" id="orderSelectionStep">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Step 1: Select Order</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Search Order</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="orderSearch" placeholder="Enter Order Number, Invoice ID, or Customer Name" autocomplete="off">
                                        <button class="btn btn-outline-secondary" type="button" id="searchBtn">
                                            <i class="mdi mdi-magnify"></i> Search
                                        </button>
                                    </div>
                                    <div id="searchResults" class="mt-2" style="display: none;">
                                        <div class="list-group" id="orderList" style="max-height: 300px; overflow-y: auto;"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Return Source</label>
                                    <select class="form-select" name="return_source" id="return_source" required>
                                        <option value="customer" {{ old('return_source', 'customer') === 'customer' ? 'selected' : '' }}>Customer Return</option>
                                        <option value="warehouse" {{ old('return_source') === 'warehouse' ? 'selected' : '' }}>Warehouse Return</option>
                                        <option value="supplier" {{ old('return_source') === 'supplier' ? 'selected' : '' }}>Supplier Return</option>
                                        <option value="qc" {{ old('return_source') === 'qc' ? 'selected' : '' }}>Quality Control</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2: Order Details & Item Selection -->
        @if($order)
        <div class="row" id="orderDetailsStep">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Step 2: Order Details & Item Selection</h5>
                    </div>
                    <div class="card-body">
                        <!-- Order Information -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="border p-3 rounded">
                                    <h6 class="text-muted">Order #</h6>
                                    <h5 class="mb-0">{{ $order->invoice_id }}</h5>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border p-3 rounded">
                                    <h6 class="text-muted">Customer</h6>
                                    <h5 class="mb-0">{{ optional($order->customer)->name ?? 'N/A' }}</h5>
                                    <small class="text-muted">{{ optional($order->customer)->phone ?? 'N/A' }}</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border p-3 rounded">
                                    <h6 class="text-muted">Order Date</h6>
                                    <h5 class="mb-0">{{ $order->created_at->format('d M Y') }}</h5>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border p-3 rounded">
                                    <h6 class="text-muted">Order Total</h6>
                                    <h5 class="mb-0 text-success">BDT {{ number_format((float) $order->amount, 2) }}</h5>
                                </div>
                            </div>
                        </div>

                        <!-- Hidden Order ID -->
                        <input type="hidden" name="order_id" value="{{ $order->id }}">

                        <!-- Return Type -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Return Type</label>
                                <select class="form-select" name="return_type" id="return_type" required>
                                    <option value="partial" {{ old('return_type', 'partial') === 'partial' ? 'selected' : '' }}>Partial Return</option>
                                    <option value="full" {{ old('return_type') === 'full' ? 'selected' : '' }}>Full Return</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Primary Return Reason</label>
                                <select class="form-select" name="return_reason_id" id="return_reason_id" required>
                                    <option value="">Select Return Reason</option>
                                    @foreach($returnReasons as $reason)
                                    <option value="{{ $reason->id }}" data-category="{{ $reason->reason_category }}" data-auto-restock="{{ $reason->auto_restock }}" data-refund-eligible="{{ $reason->refund_eligible }}" {{ (string) old('return_reason_id') === (string) $reason->id ? 'selected' : '' }}>
                                        {{ $reason->reason_name }} ({{ ucfirst($reason->reason_category) }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Items Selection -->
                        <div class="table-responsive">
                            <table class="table table-bordered" id="returnItemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%"><input type="checkbox" id="selectAllItems"></th>
                                        <th>Product</th>
                                        <th>Ordered Qty</th>
                                        <th>Already Returned</th>
                                        <th>Available Qty</th>
                                        <th>Return Qty</th>
                                        <th>Unit Price</th>
                                        <th>Total Value</th>
                                        <th>Return Reason</th>
                                        <th>Condition</th>
                                        <th>Warehouse</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($orderDetails as $detail)
                                    <tr>
                                        <td>
                                            <input type="hidden" name="items[{{ $detail->id }}][order_detail_id]" value="{{ $detail->id }}">
                                            <input type="checkbox" class="item-checkbox" name="items[{{ $detail->id }}][selected]" value="1">
                                        </td>
                                        <td>
                                            <strong>{{ optional($detail->product)->name ?? optional($detail->product)->product_name ?? $detail->product_name ?? 'Unknown Product' }}</strong>
                                            <br><small class="text-muted">{{ optional($detail->product)->sku ?? optional($detail->product)->product_code ?? '-' }}</small>
                                        </td>
                                        <td>{{ $detail->qty }}</td>
                                        <td>{{ $detail->returned_quantity }}</td>
                                        <td><strong>{{ $detail->qty - $detail->returned_quantity }}</strong></td>
                                        <td>
                                            <input type="number" class="form-control return-qty"
                                                   name="items[{{ $detail->id }}][return_quantity]"
                                                   min="1" max="{{ $detail->qty - $detail->returned_quantity }}"
                                                   data-unit-price="{{ $detail->sale_price }}"
                                                   data-max-qty="{{ $detail->qty - $detail->returned_quantity }}"
                                                   disabled>
                                        </td>
                                        <td>BDT {{ number_format((float) $detail->sale_price, 2) }}</td>
                                        <td class="item-total">BDT 0.00</td>
                                        <td>
                                            <select class="form-select return-reason" name="items[{{ $detail->id }}][return_reason_id]" disabled>
                                                <option value="">Select Reason</option>
                                                @foreach($returnReasons as $reason)
                                                <option value="{{ $reason->id }}">{{ $reason->reason_name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-select return-condition" name="items[{{ $detail->id }}][return_condition]" disabled>
                                                <option value="new">New</option>
                                                <option value="opened">Opened</option>
                                                <option value="damaged">Damaged</option>
                                                <option value="defective">Defective</option>
                                                <option value="expired">Expired</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-select warehouse-select" name="items[{{ $detail->id }}][warehouse_id]" disabled>
                                                @foreach($warehouses as $warehouse)
                                                <option value="{{ $warehouse->id }}" {{ $detail->warehouse_id == $warehouse->id ? 'selected' : '' }}>
                                                    {{ $warehouse->name }}
                                                </option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="table-warning">
                                        <th colspan="7" class="text-end">Total Return Value:</th>
                                        <th colspan="4"><strong id="totalReturnValue">BDT 0.00</strong></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 3: Additional Information -->
        <div class="row" id="additionalInfoStep">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Step 3: Additional Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Refund Method</label>
                                    <select class="form-select" name="refund_method" id="refund_method">
                                        <option value="none" {{ old('refund_method', 'none') === 'none' ? 'selected' : '' }}>No Refund</option>
                                        <option value="cash" {{ old('refund_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                                        <option value="bank" {{ old('refund_method') === 'bank' ? 'selected' : '' }}>Bank Transfer</option>
                                        <option value="credit" {{ old('refund_method') === 'credit' ? 'selected' : '' }}>Credit to Account</option>
                                        <option value="voucher" {{ old('refund_method') === 'voucher' ? 'selected' : '' }}>Store Credit Voucher</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Restock Items</label>
                                    <select class="form-select" name="restock_flag" id="restock_flag">
                                        <option value="1" {{ (string) old('restock_flag', '1') === '1' ? 'selected' : '' }}>Yes - Add to inventory</option>
                                        <option value="0" {{ (string) old('restock_flag') === '0' ? 'selected' : '' }}>No - Mark as damaged/lost</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes/Comments</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="Additional notes about this return...">{{ old('notes') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="confirmReturn" required>
                                <label class="form-check-label" for="confirmReturn">
                                    I confirm that all return details are correct and the items meet the return policy requirements.
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                @if($order)
                                <a href="{{ route('admin.returns.create') }}" class="btn btn-secondary">
                                    <i class="mdi mdi-arrow-left"></i> Back to Order Selection
                                </a>
                                @endif
                            </div>
                            <div>
                                <button type="button" class="btn btn-outline-secondary me-2" onclick="resetForm()">
                                    <i class="mdi mdi-refresh"></i> Reset
                                </button>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="mdi mdi-content-save"></i> Create Return
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </form>
</div>

@push('css')
<style>
    .item-total { font-weight: bold; color: #28a745; }
    .return-qty:disabled, .return-reason:disabled, .return-condition:disabled, .warehouse-select:disabled {
        background-color: #f8f9fa;
    }
    #orderList .list-group-item:hover {
        background-color: #f8f9fa;
        cursor: pointer;
    }
    .table-responsive { max-height: 500px; overflow-y: auto; }
</style>
@endpush

@push('js')
<script>
$(document).ready(function() {
    // Order search functionality
    let searchTimeout;
    $('#orderSearch').on('input', function() {
        clearTimeout(searchTimeout);
        const query = $(this).val().trim();

        if (query.length >= 3) {
            searchTimeout = setTimeout(() => searchOrders(query), 500);
        } else {
            $('#searchResults').hide();
        }
    });

    $('#searchBtn').on('click', function() {
        const query = $('#orderSearch').val().trim();
        if (query.length >= 3) {
            searchOrders(query);
        }
    });

    // Select all items checkbox
    $('#selectAllItems').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.item-checkbox').prop('checked', isChecked).trigger('change');
    });

    // Individual item checkbox change
    $(document).on('change', '.item-checkbox', function() {
        const row = $(this).closest('tr');
        const isChecked = $(this).is(':checked');

        row.find('.return-qty, .return-reason, .return-condition, .warehouse-select').prop('disabled', !isChecked);

        if (isChecked) {
            row.find('.return-qty').focus();
        } else {
            row.find('.return-qty').val('');
            calculateRowTotal(row);
        }

        updateTotalValue();
    });

    // Return quantity change
    $(document).on('input', '.return-qty', function() {
        const row = $(this).closest('tr');
        const qty = parseFloat($(this).val()) || 0;
        const maxQty = parseFloat($(this).data('max-qty'));

        if (qty > maxQty) {
            $(this).val(maxQty);
            toastr.warning('Return quantity cannot exceed available quantity');
        }

        calculateRowTotal(row);
        updateTotalValue();
    });

    // Return reason change - auto-set restock flag
    $(document).on('change', '.return-reason', function() {
        const reasonId = $(this).val();
        if (reasonId) {
            const reason = $('#return_reason_id option[value="' + reasonId + '"]');
            const autoRestock = reason.data('auto-restock');

            if (autoRestock === 0) {
                $('#restock_flag').val('0');
            }
        }
    });

    // Primary return reason change
    $('#return_reason_id').on('change', function() {
        const reasonId = $(this).val();
        if (reasonId) {
            const reason = $(this).find('option:selected');
            const autoRestock = reason.data('auto-restock');
            const refundEligible = reason.data('refund-eligible');

            // Auto-set restock flag
            if (autoRestock === 0) {
                $('#restock_flag').val('0');
            }

            // Auto-set refund method
            if (!refundEligible) {
                $('#refund_method').val('none');
            }
        }
    });

    // Form submission
    $('#returnForm').on('submit', function(e) {
        const selectedItems = $('.item-checkbox:checked').length;
        if (selectedItems === 0) {
            e.preventDefault();
            toastr.error('Please select at least one item to return');
            return false;
        }

        const hasValidQty = $('.return-qty:not(:disabled)').filter(function() {
            return parseFloat($(this).val()) > 0;
        }).length > 0;

        if (!hasValidQty) {
            e.preventDefault();
            toastr.error('Please enter valid return quantities for selected items');
            return false;
        }

        $('#submitBtn').prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> Creating Return...');
    });

    // Return type change
    $('#return_type').on('change', function() {
        const returnType = $(this).val();
        if (returnType === 'full') {
            // Auto-select all available items
            $('.item-checkbox').prop('checked', true).trigger('change');
            // Set max quantity for all items
            $('.return-qty').each(function() {
                const maxQty = $(this).data('max-qty');
                $(this).val(maxQty);
                calculateRowTotal($(this).closest('tr'));
            });
            updateTotalValue();
        }
    });
});

function searchOrders(query) {
    $.ajax({
        url: '{{ route("admin.returns.search-orders") }}',
        method: 'GET',
        data: { search: query },
        success: function(response) {
            if (response.orders && response.orders.length > 0) {
                let html = '';
                response.orders.forEach(function(order) {
                    html += `
                        <a href="{{ route('admin.returns.create') }}?order_id=${order.id}" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">${order.invoice_id}</h6>
                                <small>${order.order_date_formatted}</small>
                            </div>
                            <p class="mb-1">${order.customer_name}</p>
                            <small class="text-muted">Total: BDT ${order.grand_total_formatted} | Items: ${order.total_items}</small>
                        </a>
                    `;
                });
                $('#orderList').html(html);
                $('#searchResults').show();
            } else {
                $('#orderList').html('<div class="list-group-item">No orders found</div>');
                $('#searchResults').show();
            }
        },
        error: function() {
            toastr.error('Error searching orders');
        }
    });
}

function calculateRowTotal(row) {
    const qty = parseFloat(row.find('.return-qty').val()) || 0;
    const unitPrice = parseFloat(row.find('.return-qty').data('unit-price'));
    const total = qty * unitPrice;
    row.find('.item-total').text('BDT ' + total.toFixed(2));
}

function updateTotalValue() {
    let total = 0;
    $('.item-checkbox:checked').each(function() {
        const row = $(this).closest('tr');
        const rowTotal = parseFloat(row.find('.item-total').text().replace('BDT', '').replace(/,/g, '')) || 0;
        total += rowTotal;
    });
    $('#totalReturnValue').text('BDT ' + total.toFixed(2));
}

function resetForm() {
    if (confirm('Are you sure you want to reset the form? All data will be lost.')) {
        location.reload();
    }
}
</script>
@endpush
@endsection
