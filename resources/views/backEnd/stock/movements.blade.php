@extends('backEnd.layouts.master')
@section('title','Stock Movements')
@section('content')
<div class="container-fluid">
    
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{route('admin.stock.balance')}}" class="btn btn-info rounded-pill"><i class="fe-list"></i> Stock Balance</a>
                </div>
                <h4 class="page-title">Stock Ledger</h4>
            </div>
        </div>
    </div>       
    <!-- end page title --> 
   <div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-12">
                    <form method="GET" action="{{route('admin.stock.movements')}}" class="movement-filter-form">
                        <div class="movement-filter-field movement-filter-warehouse">
                            <div class="form-group">
                                <select name="warehouse_id" class="form-control">
                                    <option value="">All Warehouses</option>
                                    @foreach($warehouses as $wh)
                                        <option value="{{$wh->id}}" {{request('warehouse_id')==$wh->id?'selected':''}}>{{$wh->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="movement-filter-field movement-filter-product">
                            <div class="form-group">
                                <select name="product_id" class="form-control product-select">
                                    @if($selectedProduct)
                                        <option value="{{ $selectedProduct->id }}" selected>{{ $selectedProduct->name }} (SKU: {{ $selectedProduct->sku ?? 'N/A' }})</option>
                                    @else
                                        <option value="">Select Product</option>
                                    @endif
                                </select>
                            </div>
                        </div>

                        <div class="movement-filter-field movement-filter-keyword">
                            <div class="form-group">
                                <input
                                    type="text"
                                    name="search"
                                    value="{{ request('search') }}"
                                    class="form-control"
                                    placeholder="Keyword (name, SKU, code, variant SKU)"
                                    autocomplete="off"
                                />
                            </div>
                        </div>

                        <div class="movement-filter-field movement-filter-date">
                            <div class="form-group">
                                <input type="date" name="start_date" value="{{request('start_date')}}" class="form-control clickable-date-input" placeholder="From Date" onclick="this.showPicker()">
                            </div>
                        </div>
                        <div class="movement-filter-field movement-filter-date">
                            <div class="form-group">
                                <input type="date" name="end_date" value="{{request('end_date')}}" class="form-control clickable-date-input" placeholder="To Date" onclick="this.showPicker()">
                            </div>
                        </div>
                        <div class="movement-filter-field movement-filter-action">
                            <div class="form-group">
                                <button class="btn rounded-pill btn-info">Filter</button>

                                <a href="{{ route('admin.stock.movements') }}" class="btn rounded-pill btn-secondary">Reset</a>
                                <a href="{{ route('admin.stock.movements', array_merge(request()->query(), ['export' => 'xlsx'])) }}" class="btn rounded-pill btn-success">
                                    Export XLSX
                                </a>
                                <a href="{{ route('admin.stock.movements', array_merge(request()->query(), ['export' => 'pdf'])) }}" class="btn rounded-pill btn-primary">
                                    Export PDF
                                </a>
                            </div>
                        </div>
                    </form>
                    </div>
                </div>

                @if(!$filtersApplied)
                    <div class="text-center py-5 border rounded bg-light mb-3">
                        <div class="avatar-lg mx-auto mb-4">
                            <span class="avatar-title bg-soft-info rounded-circle">
                                <i class="fe-filter display-4 text-info"></i>
                            </span>
                        </div>
                        <h4 class="text-dark">Apply Filters to Generate Report</h4>
                        <p class="text-muted mx-auto" style="max-width: 400px;">
                            To ensure optimal performance and relevance, please select a date range or other criteria above to view the Stock Movement History.
                        </p>
                    </div>
                @else
                    <div class="mb-3">
                        <h5 class="mb-1">
                            Stock Ledger # From {{ request('start_date') ?: 'Beginning' }} To {{ request('end_date') ?: now()->toDateString() }}
                        </h5>
                        <div class="text-muted">
                            Item # {{ $selectedProduct ? $selectedProduct->name : 'All' }}
                        </div>
                    </div>
                    <div class="table-responsive report-sticky-container">
                        @if($ledgerMode === 'all')
                        <table class="table nowrap w-100 stock-ledger-table">
                        <thead>
                            <tr>
                                <th>SN</th>
                                <th>Item Name</th>
                                <th>Item Code</th>
                                <th>Item Type</th>
                                <th>Opening</th>
                                <th>Purchase</th>
                                <th>P. Receive</th>
                                <th>S. Return</th>
                                <th>Reject</th>
                                <th>P. Return</th>
                                <th>P. Issue</th>
                                <th>Sales</th>
                                <th>Balance</th>
                            </tr>
                        </thead>

                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end fw-bold">Totals</th>
                                @php
                                    $tOpening = 0;
                                    $tPurchase = 0;
                                    $tPre = 0;
                                    $tSReturn = 0;
                                    $tReject = 0;
                                    $tPReturn = 0;
                                    $tPIssue = 0;
                                    $tSales = 0;
                                    $tBalance = 0;
                                @endphp

                                <th>
                                    @foreach(($summaryRows ?? []) as $r)
                                        @php($tOpening += (float)($r['opening'] ?? 0))
                                        @endphp
                                    @endforeach
                                    {{ number_format($tOpening, 2) }}
                                </th>
                                <th>
                                    @foreach(($summaryRows ?? []) as $r)
                                        @php($tPurchase += (float)($r['purchase'] ?? 0))
                                        @endphp
                                    @endforeach
                                    {{ number_format($tPurchase, 2) }}
                                </th>
                                <th>
                                    @foreach(($summaryRows ?? []) as $r)
                                        @php($tPre += (float)($r['purchase_receive'] ?? 0))
                                        @endphp
                                    @endforeach
                                    {{ number_format($tPre, 2) }}
                                </th>
                                <th>
                                    @foreach(($summaryRows ?? []) as $r)
                                        @php($tSReturn += (float)($r['sales_return'] ?? 0))
                                        @endphp
                                    @endforeach
                                    {{ number_format($tSReturn, 2) }}
                                </th>
                                <th>
                                    @foreach(($summaryRows ?? []) as $r)
                                        @php($tReject += (float)($r['reject'] ?? 0))
                                        @endphp
                                    @endforeach
                                    {{ number_format($tReject, 2) }}
                                </th>
                                <th>
                                    @foreach(($summaryRows ?? []) as $r)
                                        @php($tPReturn += (float)($r['purchase_return'] ?? 0))
                                        @endphp
                                    @endforeach
                                    {{ number_format($tPReturn, 2) }}
                                </th>
                                <th>
                                    @foreach(($summaryRows ?? []) as $r)
                                        @php($tPIssue += (float)($r['purchase_issue'] ?? 0))
                                        @endphp
                                    @endforeach
                                    {{ number_format($tPIssue, 2) }}
                                </th>
                                <th>
                                    @foreach(($summaryRows ?? []) as $r)
                                        @php($tSales += (float)($r['sales'] ?? 0))
                                        @endphp
                                    @endforeach
                                    {{ number_format($tSales, 2) }}
                                </th>
                                <th>
                                    @foreach(($summaryRows ?? []) as $r)
                                        @php($tBalance += (float)($r['balance'] ?? 0))
                                        @endphp
                                    @endforeach
                                    {{ number_format($tBalance, 2) }}
                                </th>
                            </tr>
                        </tfoot>
                    
                        <tbody>
                            @forelse($summaryRows as $row)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><strong>{{ $row['product_name'] }}</strong></td>
                                <td>{{ $row['product_code'] }}</td>
                                <td>{{ $row['item_type'] }}</td>
                                <td>{{ number_format($row['opening'], 2) }}</td>
                                <td>{{ number_format($row['purchase'], 2) }}</td>
                                <td>{{ number_format($row['purchase_receive'], 2) }}</td>
                                <td>{{ number_format($row['sales_return'], 2) }}</td>
                                <td>{{ number_format($row['reject'], 2) }}</td>
                                <td>{{ number_format($row['purchase_return'], 2) }}</td>
                                <td>{{ number_format($row['purchase_issue'], 2) }}</td>
                                <td>{{ number_format($row['sales'], 2) }}</td>
                                <td><strong>{{ number_format($row['balance'], 2) }}</strong></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="13" class="text-center py-4">
                                    <p class="text-muted mb-0">No ledger data found matching the selected filters.</p>
                                </td>
                            </tr>
                            @endforelse
                         </tbody>
                        </table>
                        @else
                        <table class="table nowrap w-100 stock-ledger-table">
                        <thead>
                            <tr>
                                <th style="width:5%">SN</th>
                                <th style="width:15%">Date</th>
                                <th style="width:25%">Type/Ref</th>
                                <th style="width:20%">Item Detail</th>
                                <th style="width:10%" class="text-center">In</th>
                                <th style="width:10%" class="text-center">Out</th>
                                <th style="width:15%" class="text-end">Running Bal</th>
                            </tr>
                        </thead>

                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end fw-bold text-uppercase small">Totals on this page</th>

                                @php
                                    $movementsRows = method_exists($movements, 'items') ? $movements->items() : ($movements ?? []);
                                    $tIn = 0;
                                    $tOut = 0;
                                @endphp

                                <th class="text-center text-success">
                                    @foreach($movementsRows as $m)
                                        @php
                                            $q = (float)($m->quantity ?? 0);
                                            if ($q > 0) { $tIn += $q; }
                                        @endphp
                                    @endforeach
                                    +{{ number_format($tIn, 2) }}
                                </th>

                                <th class="text-center text-danger">
                                    @foreach($movementsRows as $m)
                                        @php
                                            $q = (float)($m->quantity ?? 0);
                                            if ($q < 0) { $tOut += abs($q); }
                                        @endphp
                                    @endforeach
                                    -{{ number_format($tOut, 2) }}
                                </th>

                                <th class="text-end fw-bold text-dark bg-light">
                                    @php $finalBal = (float)$openingBalance + $tIn - $tOut; @endphp
                                    {{ number_format($finalBal, 2) }}
                                </th>
                            </tr>
                        </tfoot>

                        <tbody>
                            @php $runningBal = (float)$openingBalance; @endphp
                            @if(request('start_date'))
                                <tr class="table-info">
                                    <td>1</td>
                                    <td>{{ request('start_date') }}</td>
                                    <td colspan="4"><strong>Opening Balance</strong></td>
                                    <td class="text-end fw-bold">{{ number_format($openingBalance, 2) }}</td>
                                </tr>
                            @endif
                            @forelse($movements as $movement)
                                @php 
                                    $qty = (float)($movement->quantity ?? 0);
                                    $runningBal += $qty;
                                    $variantName = $movement->productVariant ? $movement->productVariant->getDisplayName() : 'Standard';
                                @endphp
                            <tr>
                                <td>{{ (request('start_date') ? $loop->iteration + 1 : $loop->iteration) }}</td>
                                <td><small>{{ $movement->created_at?->format('Y-m-d H:i') ?? 'N/A' }}</small></td>
                                <td>
                                    @if($movement->reference_type && $movement->reference_id)
                                        <span class="badge bg-soft-secondary text-secondary">{{ ucfirst(str_replace('_', ' ', $movement->reference_type)) }} #{{ $movement->reference_id }}</span>
                                    @else
                                        <span class="badge bg-soft-info text-info">{{ ucfirst(str_replace('_', ' ', $movement->type ?? 'N/A')) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-medium text-dark">{{ $movement->product?->name }}</div>
                                    <small class="text-muted">{{ $variantName }}</small>
                                </td>
                                <td class="text-center">
                                    @if($qty > 0)
                                        <span class="text-success fw-bold">+{{ number_format($qty, 2) }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($qty < 0)
                                        <span class="text-danger fw-bold">{{ number_format(abs($qty), 2) }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold">{{ number_format($runningBal, 2) }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <p class="text-muted mb-0">No movements found matching the selected filters.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        </table>
                        @endif
                    </div>
                    @if($ledgerMode === 'single')
                        <div class="custom-paginate">
                            {{$movements->links()}}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
   </div>
</div>
<style>
    /* Custom filter styling preserved, sticky behavior now handled by global report-sticky-container */
    .movement-filter-form {
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        gap: .5rem;
        overflow-x: auto;
        padding-bottom: 4px;
    }

    .movement-filter-field {
        flex: 0 0 auto;
        min-width: 160px;
    }

    .clickable-date-input {
        cursor: pointer;
    }


    .movement-filter-warehouse,
    .movement-filter-product {
        min-width: 220px;
    }

    .movement-filter-date {
        min-width: 160px;
    }

    .movement-filter-action .btn {
        white-space: nowrap;
    }

    .movement-filter-form .form-group {
        margin-bottom: 0;
    }

    @media (max-width: 768px) {
        .movement-filter-form {
            flex-wrap: wrap;
            overflow-x: visible;
        }
    }
</style>
@endsection

@section('script')
<script>
(function () {
    const endpoint = "{{ route('admin.stock.api.search-products') }}";
    const selects = Array.from(document.querySelectorAll('select.product-select'));

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '<')
            .replace(/>/g, '>')
            .replace(/"/g, '"')
            .replace(/'/g, '&#039;');
    }

    function getOptionTextByValue(selectEl, value) {
        const opt = Array.from(selectEl.options || []).find(o => String(o.value) === String(value));
        return opt ? (opt.textContent || '') : '';
    }

    function destroyExistingWidget(selectEl) {
        if (selectEl.dataset.widgetInit === '1') return;

        const parent = selectEl.parentElement;
        if (!parent) return;

        const existingInput = parent.querySelector('input[data-product-search-for="' + selectEl.id + '"]');
        const existingDropdown = parent.querySelector('div[data-product-dropdown-for="' + selectEl.id + '"]');
        if (existingInput) existingInput.remove();
        if (existingDropdown) existingDropdown.remove();
    }

    function initProductSearch(selectEl) {
        if (!selectEl || selectEl.dataset.widgetInit === '1') return;
        // Skip if no id (we need unique references). Give it one if missing.
        if (!selectEl.id) {
            selectEl.id = 'product-select-' + Math.random().toString(16).slice(2);
        }

        const parent = selectEl.parentElement;
        if (!parent) return;

        destroyExistingWidget(selectEl);

        // Create input + dropdown
        const input = document.createElement('input');
        input.type = 'text';
        input.autocomplete = 'off';
        input.className = 'form-control product-search-input';
        input.setAttribute('data-product-search-for', selectEl.id);
        input.placeholder = 'Search product by keyword...';

        const dropdown = document.createElement('div');
        dropdown.className = 'list-group position-absolute product-search-dropdown';
        dropdown.style.zIndex = '9999';
        dropdown.style.display = 'none';
        dropdown.style.maxHeight = '240px';
        dropdown.style.overflowY = 'auto';
        dropdown.setAttribute('data-product-dropdown-for', selectEl.id);

        // Ensure parent can position absolute dropdown
        parent.style.position = parent.style.position || 'relative';

        parent.insertBefore(input, selectEl);
        parent.insertBefore(dropdown, selectEl);

        selectEl.dataset.widgetInit = '1';

        // Pre-fill input if select has a selected value
        if (selectEl.value) {
            input.value = getOptionTextByValue(selectEl, selectEl.value);
        }

        let abortCtrl = null;
        let debounceTimer = null;
        let lastQuery = '';

        function hideDropdown() {
            dropdown.style.display = 'none';
            dropdown.innerHTML = '';
        }

        function renderResults(items) {
            const safeItems = Array.isArray(items) ? items : [];
            if (safeItems.length === 0) {
                dropdown.innerHTML = '<div class="list-group-item text-muted small">No products found</div>';
                dropdown.style.display = 'block';
                return;
            }

            dropdown.innerHTML = safeItems.map(item => {
                const id = item.id ?? item.product_id ?? '';
                const name = item.name ?? item.text ?? item.label ?? '';
                const sku = item.sku ?? item.sku_code ?? '';
                const display = sku ? `${name} (SKU: ${sku})` : name;

                return `
                    <button type="button"
                        class="list-group-item list-group-item-action product-search-item"
                        data-value="${escapeHtml(id)}"
                        data-text="${escapeHtml(display)}">
                        ${escapeHtml(display)}
                    </button>
                `;
            }).join('');
            dropdown.style.display = 'block';
        }

        async function search(q) {
            const query = String(q ?? '').trim();
            if (query.length < 2) {
                hideDropdown();
                return;
            }

            // Cancel previous request
            if (abortCtrl) abortCtrl.abort();
            abortCtrl = new AbortController();

            const url = new URL(endpoint, window.location.origin);
            url.searchParams.set('q', query);
            url.searchParams.set('limit', '25');

            const res = await fetch(url.toString(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: abortCtrl.signal
            });

            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Search failed');

            // select2-like payload: { results: [ {id, text/name, sku} ] }
            const results = data.results || data.data || [];
            return results;
        }

        function setSelected(value, text) {
            selectEl.value = value ? String(value) : '';
            // Update input to match chosen text
            input.value = text ? String(text) : '';
            hideDropdown();
            selectEl.dispatchEvent(new Event('change', { bubbles: true }));
        }

        input.addEventListener('input', function () {
            const q = input.value;
            lastQuery = q;

            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(async () => {
                try {
                    const results = await search(lastQuery);
                    // If user typed again, ignore stale results
                    if (lastQuery !== input.value) return;

                    // Transform results into {id, name, sku} expectation
                    const normalized = results.map(r => ({
                        id: r.id ?? r.product_id ?? '',
                        name: r.name ?? r.text ?? r.label ?? '',
                        sku: r.sku ?? r.sku_code ?? ''
                    }));

                    renderResults(normalized);
                } catch (e) {
                    // silent
                    hideDropdown();
                }
            }, 250);
        });

        input.addEventListener('focus', function () {
            if (input.value.trim().length >= 2) {
                // trigger search quickly on focus if query already present
                input.dispatchEvent(new Event('input'));
            }
        });

        document.addEventListener('click', function (e) {
            const target = e.target;
            if (!target) return;
            const within = dropdown.contains(target) || input.contains(target);
            if (!within) hideDropdown();
        });

        dropdown.addEventListener('click', function (e) {
            const btn = e.target.closest('button.product-search-item');
            if (!btn) return;
            const value = btn.getAttribute('data-value');
            const text = btn.getAttribute('data-text');
            setSelected(value, text);
        });

        // allow clear by typing exact selected or using backspace:
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') hideDropdown();
        });
    }

    selects.forEach(initProductSearch);
})();
</script>
@endsection
