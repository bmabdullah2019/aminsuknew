@extends('backEnd.layouts.master')
@section('title','GRN Details')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{route('admin.grn.index')}}" class="btn btn-danger rounded-pill"><i class="fe-arrow-left"></i> Back</a>
                    @if($grn->status == 'draft')
                        <a href="{{route('admin.grn.edit',$grn->id)}}" class="btn btn-primary rounded-pill"><i class="fe-edit"></i> Edit</a>
                        <form method="post" action="{{route('admin.grn.approve',$grn->id)}}" class="d-inline">
                            @csrf
                            <button type="button" class="btn btn-success rounded-pill change-confirm"><i class="fe-check"></i> Approve</button>
                        </form>
                    @endif
                    <a href="{{route('admin.grn.print',$grn->id)}}" target="_blank" class="btn btn-info rounded-pill"><i class="fe-printer"></i> Print</a>
                </div>
                <h4 class="page-title">GRN Details</h4>
            </div>
        </div>
    </div>

    @php
        $expiryAlerts = $expiryAlerts ?? [];
        $lowStockAlerts = $lowStockAlerts ?? [];
        $discrepancyAlerts = $discrepancyAlerts ?? [];
        $itemMeta = $itemMeta ?? [];
    @endphp

    @if(!empty($expiryAlerts) || !empty($lowStockAlerts) || !empty($discrepancyAlerts))
        <div class="row">
            @if(!empty($expiryAlerts))
                <div class="col-md-4">
                    <div class="alert alert-warning">
                        <strong>Expiry Alerts ({{ count($expiryAlerts) }})</strong>
                        <ul class="mb-0 mt-2 ps-3">
                            @foreach(array_slice($expiryAlerts, 0, 3) as $msg)
                                <li>{{ $msg }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
            @if(!empty($lowStockAlerts))
                <div class="col-md-4">
                    <div class="alert alert-info">
                        <strong>Low Stock Alerts ({{ count($lowStockAlerts) }})</strong>
                        <ul class="mb-0 mt-2 ps-3">
                            @foreach(array_slice($lowStockAlerts, 0, 3) as $msg)
                                <li>{{ $msg }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
            @if(!empty($discrepancyAlerts))
                <div class="col-md-4">
                    <div class="alert alert-danger">
                        <strong>Qty Discrepancies ({{ count($discrepancyAlerts) }})</strong>
                        <ul class="mb-0 mt-2 ps-3">
                            @foreach(array_slice($discrepancyAlerts, 0, 3) as $msg)
                                <li>{{ $msg }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">GRN Information</h5>
                    <table class="table table-bordered">
                        <tr>
                            <th width="30%">GRN Number</th>
                            <td><strong>{{$grn->grn_number}}</strong></td>
                        </tr>
                        <tr>
                            <th>Date</th>
                            <td>{{$grn->grn_date->format('d M Y')}}</td>
                        </tr>
                        <tr>
                            <th>Warehouse</th>
                            <td>{{$grn->warehouse->name ?? 'N/A'}} ({{$grn->warehouse->code ?? 'N/A'}})</td>
                        </tr>
                        <tr>
                            <th>Supplier</th>
                            <td>{{$grn->supplier->name ?? 'N/A'}}</td>
                        </tr>
                        <tr>
                            <th>Invoice Number</th>
                            <td>{{$grn->invoice_number ?: 'N/A'}}</td>
                        </tr>
                        <tr>
                            <th>Invoice Date</th>
                            <td>{{$grn->invoice_date ? $grn->invoice_date->format('d M Y') : 'N/A'}}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                @if($grn->status == 'draft')
                                    <span class="badge bg-soft-warning text-warning">Draft</span>
                                @elseif($grn->status == 'approved')
                                    <span class="badge bg-soft-success text-success">Approved</span>
                                @else
                                    <span class="badge bg-soft-secondary text-secondary">{{ucfirst($grn->status)}}</span>
                                @endif
                            </td>
                        </tr>
                        @if($grn->receiver)
                        <tr>
                            <th>Received By</th>
                            <td>{{$grn->receiver->name}}</td>
                        </tr>
                        @endif
                        @if($grn->approved_at)
                        <tr>
                            <th>Approved At</th>
                            <td>{{$grn->approved_at->format('d M Y H:i')}}</td>
                        </tr>
                        @endif
                    </table>

                    <div class="d-flex flex-wrap justify-content-between align-items-center mt-4 mb-2 gap-2">
                        <h5 class="card-title mb-0">Items</h5>
                        <div class="d-flex gap-2">
                            <input type="text" id="grnItemSearch" class="form-control form-control-sm" placeholder="Search product / SKU" style="min-width: 220px;">
                            <select id="grnItemStateFilter" class="form-control form-control-sm">
                                <option value="all">All States</option>
                                <option value="low_stock">Low Stock</option>
                                <option value="expiring">Expiring</option>
                                <option value="discrepancy">Discrepancy</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="grn-items-table">
                            <thead>
                                <tr>
                                    <th class="sortable" data-key="product">Product</th>
                                    <th class="sortable" data-key="variant">Variant</th>
                                    <th class="sortable" data-key="sku">SKU</th>
                                    <th class="sortable" data-key="ordered">Ordered</th>
                                    <th class="sortable" data-key="received">Received</th>
                                    <th class="sortable" data-key="delta">Delta</th>
                                    <th class="sortable" data-key="unitCost">Unit Cost</th>
                                    <th>Batch</th>
                                    <th class="sortable" data-key="itemExpiry">Item Expiry</th>
                                    <th class="sortable" data-key="stockQty">Stock Qty</th>
                                    <th class="sortable" data-key="stockExpiry">Stock Expiry</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="grn-items-tbody">
                                @foreach($grn->items as $index => $item)
                                    @php
                                        $meta = $itemMeta[$item->id] ?? [];
                                        $variantLabel = $meta['variant_label'] ?? 'N/A';
                                        $stock = $meta['stock'] ?? null;
                                        $orderedQty = (float) ($meta['ordered_quantity'] ?? $item->quantity);
                                        $receivedQty = (float) ($meta['received_quantity'] ?? $item->quantity);
                                        $deltaQty = (float) ($meta['delta_quantity'] ?? 0);
                                        $isDiscrepancy = (bool) ($meta['is_discrepancy'] ?? false);
                                        $isLowStock = (bool) ($meta['is_low_stock'] ?? false);
                                        $isItemExpiring = (bool) ($meta['is_item_expiring'] ?? false);
                                        $isStockExpiring = (bool) ($meta['is_stock_expiring'] ?? false);

                                        $itemExpiryRaw = $item->expiry_date ? $item->expiry_date->format('Y-m-d') : '';
                                        $stockExpiryRaw = $stock && $stock->expiry_date ? \Carbon\Carbon::parse($stock->expiry_date)->format('Y-m-d') : '';
                                        $stockQty = $stock ? (float) $stock->available_quantity : null;
                                    @endphp
                                    <tr
                                        data-product="{{ strtolower($item->product->name ?? $item->description ?? '') }}"
                                        data-variant="{{ strtolower($variantLabel) }}"
                                        data-sku="{{ strtolower($item->sku ?? '') }}"
                                        data-ordered="{{ $orderedQty }}"
                                        data-received="{{ $receivedQty }}"
                                        data-delta="{{ $deltaQty }}"
                                        data-unit-cost="{{ (float) $item->unit_cost }}"
                                        data-item-expiry="{{ $itemExpiryRaw }}"
                                        data-stock-qty="{{ $stockQty ?? -999999 }}"
                                        data-stock-expiry="{{ $stockExpiryRaw }}"
                                        data-low-stock="{{ $isLowStock ? 1 : 0 }}"
                                        data-expiring="{{ ($isItemExpiring || $isStockExpiring) ? 1 : 0 }}"
                                        data-discrepancy="{{ $isDiscrepancy ? 1 : 0 }}"
                                    >
                                        <td>{{$item->product->name ?? $item->description}}</td>
                                        <td>{{$variantLabel}}</td>
                                        <td>{{$item->sku ?: 'N/A'}}</td>
                                        <td>{{ number_format($orderedQty, 2) }}</td>
                                        <td>{{ number_format($receivedQty, 2) }}</td>
                                        <td class="{{ $isDiscrepancy ? 'text-danger fw-bold' : 'text-success' }}">
                                            {{ number_format($deltaQty, 2) }}
                                        </td>
                                        <td>&#2547;{{number_format((float) $item->unit_cost, 2)}}</td>
                                        <td>{{$item->batch_number ?: 'N/A'}}</td>
                                        <td>{{$item->expiry_date ? $item->expiry_date->format('d M Y') : 'N/A'}}</td>
                                        <td>{{ $stock ? number_format((float) $stock->available_quantity, 2) : 'N/A' }}</td>
                                        <td>{{ $stock && $stock->expiry_date ? \Carbon\Carbon::parse($stock->expiry_date)->format('d M Y') : 'N/A' }}</td>
                                        <td>
                                            @if($isDiscrepancy)
                                                <span class="badge bg-danger">Discrepancy</span>
                                            @endif
                                            @if($isLowStock)
                                                <span class="badge bg-warning text-dark">Low Stock</span>
                                            @endif
                                            @if($isItemExpiring || $isStockExpiring)
                                                <span class="badge bg-info text-dark">Expiring</span>
                                            @endif
                                            @if(!$isDiscrepancy && !$isLowStock && !$isItemExpiring && !$isStockExpiring)
                                                <span class="badge bg-success">OK</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($grn->status === 'draft')
                                                <a href="{{ route('admin.grn.edit', $grn->id) }}?focus={{ $index }}" class="btn btn-sm btn-outline-primary">Quick Edit</a>
                                            @else
                                                <span class="text-muted">Locked</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="6">Subtotal</th>
                                    <th>&#2547;{{number_format((float) ($grn->subtotal ?? 0), 2)}}</th>
                                    <th colspan="6"></th>
                                </tr>
                                <tr>
                                    <th colspan="6">Tax Amount</th>
                                    <th>&#2547;{{number_format((float) ($grn->tax_amount ?? 0), 2)}}</th>
                                    <th colspan="6"></th>
                                </tr>
                                <tr>
                                    <th colspan="6">Shipping Cost</th>
                                    <th>&#2547;{{number_format((float) ($grn->shipping_cost ?? 0), 2)}}</th>
                                    <th colspan="6"></th>
                                </tr>
                                <tr>
                                    <th colspan="6">Other Charges</th>
                                    <th>&#2547;{{number_format((float) ($grn->other_charges ?? 0), 2)}}</th>
                                    <th colspan="6"></th>
                                </tr>
                                <tr>
                                    <th colspan="6">Total Amount</th>
                                    <th>&#2547;{{number_format((float) ($grn->total_amount ?? 0), 2)}}</th>
                                    <th colspan="6"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Summary</h5>
                    <div class="mb-3">
                        <strong>Total Items:</strong><br>
                        <span class="h4 text-primary">{{$grn->items->count()}}</span>
                    </div>
                    <div class="mb-3">
                        <strong>Total Quantity:</strong><br>
                        <span class="h4 text-info">{{number_format($grn->items->sum('quantity'), 2)}}</span>
                    </div>
                    <div class="mb-3">
                        <strong>Total Amount:</strong><br>
                        <span class="h4 text-success">&#2547;{{number_format((float) ($grn->total_amount ?? 0), 2)}}</span>
                    </div>
                    <div class="mb-3">
                        <strong>Expiry Alerts:</strong><br>
                        <span class="h5 text-warning">{{ count($expiryAlerts) }}</span>
                    </div>
                    <div class="mb-3">
                        <strong>Low Stock Alerts:</strong><br>
                        <span class="h5 text-info">{{ count($lowStockAlerts) }}</span>
                    </div>
                    <div class="mb-3">
                        <strong>Discrepancies:</strong><br>
                        <span class="h5 text-danger">{{ count($discrepancyAlerts) }}</span>
                    </div>
                    @if($grn->notes)
                        <div class="mb-3">
                            <strong>Notes:</strong><br>
                            <small>{{$grn->notes}}</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tbody = document.getElementById('grn-items-tbody');
    if (!tbody) {
        return;
    }

    const searchInput = document.getElementById('grnItemSearch');
    const stateFilter = document.getElementById('grnItemStateFilter');
    const headers = document.querySelectorAll('#grn-items-table thead th.sortable');

    let currentSortKey = '';
    let currentSortDirection = 1;

    function getSortValue(row, key) {
        if (key === 'product') return row.dataset.product || '';
        if (key === 'variant') return row.dataset.variant || '';
        if (key === 'sku') return row.dataset.sku || '';
        if (key === 'ordered') return parseFloat(row.dataset.ordered || '0');
        if (key === 'received') return parseFloat(row.dataset.received || '0');
        if (key === 'delta') return parseFloat(row.dataset.delta || '0');
        if (key === 'unitCost') return parseFloat(row.dataset.unitCost || '0');
        if (key === 'itemExpiry') return row.dataset.itemExpiry || '9999-12-31';
        if (key === 'stockQty') return parseFloat(row.dataset.stockQty || '-999999');
        if (key === 'stockExpiry') return row.dataset.stockExpiry || '9999-12-31';
        return '';
    }

    function sortRows(sortKey) {
        const rows = Array.from(tbody.querySelectorAll('tr'));
        if (!rows.length) return;

        if (currentSortKey === sortKey) {
            currentSortDirection = currentSortDirection * -1;
        } else {
            currentSortKey = sortKey;
            currentSortDirection = 1;
        }

        rows.sort(function (a, b) {
            const aVal = getSortValue(a, sortKey);
            const bVal = getSortValue(b, sortKey);

            if (typeof aVal === 'number' && typeof bVal === 'number') {
                return (aVal - bVal) * currentSortDirection;
            }

            return String(aVal).localeCompare(String(bVal)) * currentSortDirection;
        });

        rows.forEach(function (row) {
            tbody.appendChild(row);
        });
    }

    function applyFilters() {
        const term = (searchInput.value || '').toLowerCase().trim();
        const state = stateFilter.value;

        Array.from(tbody.querySelectorAll('tr')).forEach(function (row) {
            const haystack = `${row.dataset.product} ${row.dataset.variant} ${row.dataset.sku}`.toLowerCase();
            const matchesSearch = term === '' || haystack.includes(term);

            let matchesState = true;
            if (state === 'low_stock') matchesState = row.dataset.lowStock === '1';
            if (state === 'expiring') matchesState = row.dataset.expiring === '1';
            if (state === 'discrepancy') matchesState = row.dataset.discrepancy === '1';

            row.style.display = (matchesSearch && matchesState) ? '' : 'none';
        });
    }

    headers.forEach(function (header) {
        header.style.cursor = 'pointer';
        header.title = 'Click to sort';
        header.addEventListener('click', function () {
            sortRows(header.dataset.key);
        });
    });

    searchInput.addEventListener('input', applyFilters);
    stateFilter.addEventListener('change', applyFilters);
});
</script>
@endsection

