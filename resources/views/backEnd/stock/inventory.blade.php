@extends('backEnd.layouts.master')
@section('title', 'Inventory')

@section('content')
@php
    $warehousePayload = $warehouses->map(fn ($warehouse) => [
        'id' => (int) $warehouse->id,
        'name' => (string) $warehouse->name,
        'code' => $warehouse->code ?: substr((string) $warehouse->name, 0, 3),
    ])->values();
@endphp

<div class="container-fluid">
    <div class="card mb-2">
        <div class="card-body">
            <form id="inventoryFilters" class="row g-2 align-items-end">
                <div class="col-md-4 col-lg-3">
                    <label class="form-label mb-1">Search</label>
                    <input type="search" name="search" id="searchInput" class="form-control form-control-sm" placeholder="Product, SKU, code">
                </div>
                <div class="col-md-4 col-lg-2">
                    <label class="form-label mb-1">Warehouse</label>
                    <select name="warehouse_id" id="warehouseFilter" class="form-control form-control-sm">
                        <option value="">All Warehouses</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 col-lg-2">
                    <label class="form-label mb-1">Category</label>
                    <select name="category_id" id="categoryFilter" class="form-control form-control-sm">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 col-lg-2">
                    <label class="form-label mb-1">Status</label>
                    <select name="status" id="statusFilter" class="form-control form-control-sm">
                        <option value="">All Status</option>
                        <option value="in_stock">In Stock</option>
                        <option value="low_stock">Low Stock</option>
                        <option value="out_of_stock">Out of Stock</option>
                    </select>
                </div>
                <div class="col-md-4 col-lg-1">
                    <label class="form-label mb-1">Rows</label>
                    <select name="per_page" id="perPage" class="form-control form-control-sm">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="col-md-4 col-lg-2">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="fe-search"></i> Filter</button>
                        <button type="button" class="btn btn-light btn-sm" id="clearFilters">Clear</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive report-sticky-container">
                <table class="table table-sm table-hover align-middle mb-0" id="inventoryTable">
                    <thead class="table-light">
                        <tr id="inventoryHeader"></tr>
                    </thead>
                    <tbody id="inventoryBody">
                        <tr><td class="text-center py-4">Loading...</td></tr>
                    </tbody>
                    <tfoot id="inventoryTotals" style="display: none;"></tfoot>
                </table>
            </div>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mt-3">
                <div class="text-muted small" id="paginationInfo"></div>
                <div class="btn-group btn-group-sm" id="paginationControls"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('css')
<style>
#inventoryTable th,
    #inventoryTable td {
        white-space: nowrap;
        vertical-align: middle;
    }

    /* Sticky behavior now handled by global report-sticky-container */

    .inventory-product {
        min-width: 220px;
        white-space: normal;
    }

    .inventory-qty {
        font-weight: 700;
    }

    .container-fluid {
        padding-top: 0;
    }
</style>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const state = {
        page: 1,
        data: [],
        pagination: null,
    };

    const warehouses = @json($warehousePayload);
    const endpoints = {
        data: @json(route('admin.stock.inventory-data')),
    };

    const filtersForm = document.getElementById('inventoryFilters');
    const header = document.getElementById('inventoryHeader');
    const body = document.getElementById('inventoryBody');
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationControls = document.getElementById('paginationControls');

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function getVisibleWarehouses() {
        const selected = document.getElementById('warehouseFilter').value;
        return selected ? warehouses.filter((warehouse) => String(warehouse.id) === selected) : [];
    }

    function renderHeader() {
        const visibleWarehouses = getVisibleWarehouses();
        let html = `
            <th>Product</th>
            <th>Category</th>
            <th>Price</th>
            <th>Available</th>
            <th>Status</th>
        `;

        visibleWarehouses.forEach((warehouse) => {
            html += `<th title="Code: ${escapeHtml(warehouse.code)}">${escapeHtml(warehouse.name)}</th>`;
        });

        header.innerHTML = html;
    }

    function getStatus(product) {
        const available = Number(product.total_available || 0);
        if (available <= 0) {
            return ['Out', 'danger'];
        }
        if (available <= 10) {
            return ['Low', 'warning'];
        }
        return ['In Stock', 'success'];
    }

    function renderTable(products) {
        renderHeader();
        const visibleWarehouses = getVisibleWarehouses();
        const totalsEl = document.getElementById('inventoryTotals');

        // Always build totals footer (even if no rows) based on currently loaded page data
        const totals = {
            totalStock: 0,
            totalAvailable: 0,
        };

        if (!products.length) {
            body.innerHTML = `<tr><td colspan="${5 + visibleWarehouses.length}" class="text-center py-4">No products found.</td></tr>`;
            totalsEl.style.display = 'table-footer-group';
            totalsEl.innerHTML = `<tr>
                <td colspan="3" class="text-end fw-bold">Totals</td>
                <td class="inventory-qty fw-bold">${totals.totalAvailable.toFixed(2)}</td>
                <td></td>
                ${visibleWarehouses.map(() => `<td></td>`).join('')}
            </tr>`;
            return;
        }

        products.forEach((p) => {
            totals.totalStock += Number(p.total_stock || 0);
            totals.totalAvailable += Number(p.total_available || 0);
        });

        body.innerHTML = products.map((product) => {
            const [statusText, statusColor] = getStatus(product);

            const stockByWarehouseId = new Map((product.stock_data || []).map((item) => [String(item.warehouse_id), item]));

            const optimizedStockCells = visibleWarehouses.map((warehouse) => {
                const stock = stockByWarehouseId.get(String(warehouse.id));
                const physical = Number(stock?.physical_quantity || 0);
                const available = Number(stock?.available_quantity || 0);
                const text = available > 0 && available !== physical
                    ? `${available.toFixed(1)}/${physical.toFixed(1)}`
                    : physical.toFixed(1);

                return `<td class="${physical <= 0 ? 'text-muted' : ''}">${text}</td>`;
            }).join('');

            return `
                <tr>
                    <td class="inventory-product">
                        <strong>${escapeHtml(product.name)}</strong>
                        <div class="text-muted small">SKU: ${escapeHtml(product.sku || 'N/A')} | Code: ${escapeHtml(product.product_code || 'N/A')}</div>
                    </td>
                    <td>${escapeHtml(product.category || 'N/A')}</td>
                    <td>${Number(product.new_price || 0).toFixed(2)}</td>
                    <td class="inventory-qty ${Number(product.total_available || 0) <= 0 ? 'text-danger' : 'text-success'}">${Number(product.total_available || 0).toFixed(2)}</td>
                    <td><span class="badge bg-${statusColor}">${statusText}</span></td>
                    ${optimizedStockCells}
                </tr>
            `;
        }).join('');

        totalsEl.style.display = 'table-footer-group';
        totalsEl.innerHTML = `<tr>
            <td colspan="3" class="text-end fw-bold">Totals</td>
            <td class="inventory-qty fw-bold">${totals.totalAvailable.toFixed(2)}</td>
            <td></td>
            ${visibleWarehouses.map(() => `<td></td>`).join('')}
        </tr>`;
    }

    function buildQuery() {
        const params = new URLSearchParams(new FormData(filtersForm));
        // One-page mode: disable backend pagination by forcing a very large per_page.
        params.delete('page');
        params.set('per_page', '1000000');
        return params;
    }

    async function loadInventory() {
        body.innerHTML = '<tr><td class="text-center py-4">Loading...</td></tr>';

        try {
            const response = await fetch(`${endpoints.data}?${buildQuery().toString()}`, {
                headers: { 'Accept': 'application/json' },
            });
            const payload = await response.json();

            if (!payload.success) {
                throw new Error(payload.message || 'Failed to load inventory');
            }

            state.data = payload.data || [];
            state.pagination = payload.pagination || {};
            renderTable(state.data);

            // Hide pagination controls to show everything on one page
            paginationInfo.style.display = 'none';
            paginationControls.style.display = 'none';
        } catch (error) {
            body.innerHTML = `<tr><td class="text-center text-danger py-4">${escapeHtml(error.message)}</td></tr>`;
        }
    }

    function renderPagination() {
        const p = state.pagination || {};
        const current = Number(p.current_page || 1);
        const last = Number(p.last_page || 1);
        const total = p.total !== undefined && p.total !== null ? Number(p.total) : null;

        paginationInfo.textContent = total !== null
            ? `Page ${current} of ${last} | ${total} records`
            : `Page ${current} of ${last}`;

        paginationControls.innerHTML = '';

        const prev = document.createElement('button');
        prev.type = 'button';
        prev.className = 'btn btn-outline-secondary';
        prev.textContent = 'Prev';
        prev.disabled = current <= 1;
        prev.addEventListener('click', () => {
            state.page = Math.max(1, current - 1);
            loadInventory();
        });

        const next = document.createElement('button');
        next.type = 'button';
        next.className = 'btn btn-outline-secondary';
        next.textContent = 'Next';
        next.disabled = current >= last;
        next.addEventListener('click', () => {
            state.page = current + 1;
            loadInventory();
        });

        paginationControls.append(prev, next);
    }

    filtersForm.addEventListener('submit', function (event) {
        event.preventDefault();
        state.page = 1;
        loadInventory();
    });

    ['warehouseFilter', 'categoryFilter', 'statusFilter', 'perPage'].forEach((id) => {
        document.getElementById(id).addEventListener('change', () => {
            state.page = 1;
            loadInventory();
        });
    });

    document.getElementById('clearFilters').addEventListener('click', function () {
        filtersForm.reset();
        state.page = 1;
        loadInventory();
    });

    loadInventory();
});
</script>
@endsection
