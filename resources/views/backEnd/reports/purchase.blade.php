@extends('backEnd.layouts.master')
@section('title', 'Purchase Report')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title">Purchase Report</h4>
                <div class="no-print d-flex gap-2">
                    <button onclick="window.print()" class="btn btn-sm btn-outline-primary"><i class="fe-printer me-1"></i>Print / PDF</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-2">
                    <label class="form-label">Keyword</label>
                    <input type="text" name="keyword" class="form-control" value="{{ $filters['keyword'] ?? '' }}" placeholder="GRN/Product/SKU">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Supplier</label>
                    <select name="supplier_id" class="form-control">
                        <option value="">All</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected((string) ($filters['supplier_id'] ?? '') === (string) $supplier->id)>
                                {{ $supplier->supplier_code }} - {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Warehouse</label>
                    <select name="warehouse_id" class="form-control">
                        <option value="">All</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected((string) ($filters['warehouse_id'] ?? '') === (string) $warehouse->id)>
                                {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Product</label>
                    <select name="product_id" class="form-control">
                        <option value="">All</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" @selected((string) ($filters['product_id'] ?? '') === (string) $product->id)>
                                {{ $product->name }} @if($product->sku)({{ $product->sku }})@endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="">All</option>
                        @foreach(['draft', 'approved', 'cancelled'] as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? null) === $status)>
                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Period</label>
                    <select name="period" class="form-control">
                        <option value="custom" @selected(($filters['period'] ?? 'custom') === 'custom')>Custom</option>
                        <option value="daily" @selected(($filters['period'] ?? null) === 'daily')>Daily</option>
                        <option value="monthly" @selected(($filters['period'] ?? null) === 'monthly')>Monthly</option>
                        <option value="yearly" @selected(($filters['period'] ?? null) === 'yearly')>Yearly</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $filters['start_date'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $filters['end_date'] ?? '' }}">
                </div>
                <div class="col-md-8 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm me-2">Filter</button>
                    <a href="{{ route('admin.purchase_report') }}" class="btn btn-secondary btn-sm me-2">Reset</a>
                    <a href="{{ route('admin.purchase_report', array_merge($queryParams, ['export' => 'xlsx'])) }}" class="btn btn-success btn-sm">
                        Export Excel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body py-2">
                    <small>PO Count</small>
                    <h6 class="mb-0">{{ number_format($totalPurchaseOrders) }}</h6>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body py-2">
                    <small>Qty Ordered</small>
                    <h6 class="mb-0">{{ number_format($totalOrderedQty, 2) }}</h6>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-white">
                <div class="card-body py-2">
                    <small>Qty Received</small>
                    <h6 class="mb-0">{{ number_format($totalReceivedQty, 2) }}</h6>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-secondary text-white">
                <div class="card-body py-2">
                    <small>Ordered Cost</small>
                    <h6 class="mb-0">BDT {{ number_format($totalOrderedCost, 2) }}</h6>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body py-2">
                    <small>Received Cost</small>
                    <h6 class="mb-0">BDT {{ number_format($totalReceivedCost, 2) }}</h6>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive report-sticky-container purchase-report-table">
                @php
                    $footOrderedQty = 0;
                    $footReceivedQty = 0;
                    $footOrderedCost = 0;
                    $footReceivedCost = 0;
                @endphp
                <table class="table table-striped table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Purchase No</th>
                            <th>Supplier</th>
                            <th>Warehouse</th>
                            <th>Product</th>
                            <th>Status</th>
                            <th class="text-end">Ordered</th>
                            <th class="text-end">Received (Net)</th>
                            <th class="text-end">Unit Cost</th>
                            <th class="text-end">Ordered Cost</th>
                            <th class="text-end">Received Cost (Net)</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            @php
                                $variantLabel = trim(implode(' / ', array_filter([
                                    (string) ($item->color ?? ''),
                                    (string) ($item->size ?? ''),
                                    (string) ($item->age ?? ''),
                                ])));
                                $productLabel = $item->product_name ?: $item->item_description ?: '-';
                                $skuLabel = $item->product_sku ?: $item->item_sku ?: '';

                                $rowOrderedQty = (float) ($item->quantity_ordered ?? 0);
                                $rowReceivedQty = (float) ($item->quantity_received ?? 0);
                                $rowOrderedCost = (float) ($item->ordered_cost ?? 0);
                                $rowReceivedCost = (float) ($item->total_cost ?? 0);

                                $footOrderedQty += $rowOrderedQty;
                                $footReceivedQty += $rowReceivedQty;
                                $footOrderedCost += $rowOrderedCost;
                                $footReceivedCost += $rowReceivedCost;
                            @endphp
                            <tr>
                                <td>{{ $item->order_number ?? '-' }}</td>
                                <td>
                                    {{ $item->supplier_name ?? '-' }}
                                    @if(!empty($item->supplier_code))
                                        <br><small class="text-muted">{{ $item->supplier_code }}</small>
                                    @endif
                                </td>
                                <td>{{ $item->warehouse_name ?? '-' }}</td>
                                <td>
                                    {{ $productLabel }}
                                    @if($skuLabel !== '')
                                        <br><small class="text-muted">{{ $skuLabel }}</small>
                                    @endif
                                    @if($variantLabel !== '')
                                        <br><small class="text-muted">{{ $variantLabel }}</small>
                                    @endif
                                </td>
                                <td>{{ ucfirst(str_replace('_', ' ', (string) ($item->status ?? '-'))) }}</td>
                                <td class="text-end">{{ number_format($rowOrderedQty, 2) }}</td>
                                <td class="text-end">{{ number_format($rowReceivedQty, 2) }}</td>
                                <td class="text-end">{{ number_format((float) ($item->unit_cost ?? 0), 2) }}</td>
                                <td class="text-end">{{ number_format($rowOrderedCost, 2) }}</td>
                                <td class="text-end">{{ number_format($rowReceivedCost, 2) }}</td>
                                <td>
                                    {{ !empty($item->purchase_date) ? \Carbon\Carbon::parse($item->purchase_date)->format('Y-m-d') : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted">No purchase rows found for the selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" class="text-end fw-bold">Totals</td>
                            <td class="text-end fw-bold">{{ number_format($footOrderedQty, 2) }}</td>
                            <td class="text-end fw-bold">{{ number_format($footReceivedQty, 2) }}</td>
                            <td></td>
                            <td class="text-end fw-bold">{{ number_format($footOrderedCost, 2) }}</td>
                            <td class="text-end fw-bold">{{ number_format($footReceivedCost, 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            {{ $items->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>

<style>
    /* Dark sticky header with white text */
    .purchase-report-table thead.table-dark th {
        background: #212529 !important;
        color: #ffffff !important;
        border-bottom-color: #444 !important;
    }

    /* Dark sticky footer with white text */
    .purchase-report-table tfoot td {
        background-color: #212529 !important;
        color: #ffffff !important;
        border-top: 2px solid #444 !important;
    }
</style>
@endsection
