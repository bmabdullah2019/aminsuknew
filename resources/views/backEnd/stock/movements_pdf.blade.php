<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Ledger PDF</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h2 { margin: 0 0 10px; font-size: 16px; }
        .meta { margin-bottom: 12px; }
        .meta div { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 6px 6px; vertical-align: top; }
        th { background: #f2f2f2; text-align: left; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <h2>Stock Ledger</h2>

    <div class="meta">
        <div><strong>From:</strong> {{ $startDate ?: 'Beginning' }} <strong>To:</strong> {{ $endDate ?: now()->toDateString() }}</div>
        <div><strong>Warehouse:</strong> {{ $warehouseName ?: 'All Warehouses' }}</div>
        <div><strong>Item:</strong> {{ $selectedProductName ?: 'All' }}</div>
    </div>

    @if($ledgerMode === 'all')
        <table>
            <thead>
                <tr>
                    <th style="width:50px;">SN</th>
                    <th>Item Name</th>
                    <th style="width:160px;">Item Code</th>
                    <th style="width:90px;">Item Type</th>
                    <th class="text-right" style="width:90px;">Opening</th>
                    <th class="text-right" style="width:90px;">Purchase</th>
                    <th class="text-right" style="width:110px;">P. Receive</th>
                    <th class="text-right" style="width:90px;">S. Return</th>
                    <th class="text-right" style="width:90px;">Reject</th>
                    <th class="text-right" style="width:90px;">P. Return</th>
                    <th class="text-right" style="width:90px;">P. Issue</th>
                    <th class="text-right" style="width:90px;">Sales</th>
                    <th class="text-right" style="width:90px;">Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse($summaryRows as $row)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td><strong>{{ $row['product_name'] }}</strong></td>
                        <td>{{ $row['product_code'] }}</td>
                        <td>{{ $row['item_type'] }}</td>
                        <td class="text-right">{{ number_format($row['opening'], 2) }}</td>
                        <td class="text-right">{{ number_format($row['purchase'], 2) }}</td>
                        <td class="text-right">{{ number_format($row['purchase_receive'], 2) }}</td>
                        <td class="text-right">{{ number_format($row['sales_return'], 2) }}</td>
                        <td class="text-right">{{ number_format($row['reject'], 2) }}</td>
                        <td class="text-right">{{ number_format($row['purchase_return'], 2) }}</td>
                        <td class="text-right">{{ number_format($row['purchase_issue'], 2) }}</td>
                        <td class="text-right">{{ number_format($row['sales'], 2) }}</td>
                        <td class="text-right"><strong>{{ number_format($row['balance'], 2) }}</strong></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13" class="text-center">No ledger data found matching the selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width:40px;">SN</th>
                    <th style="width:160px;">Date</th>
                    <th style="width:220px;">Warehouse</th>
                    <th style="width:260px;">Product</th>
                    <th style="width:110px;">Type</th>
                    <th style="width:160px;">Reference Type</th>
                    <th style="width:130px;">Reference ID</th>
                    <th class="text-right" style="width:110px;">Quantity</th>
                    <th class="text-right" style="width:110px;">Unit Cost</th>
                    <th class="text-right" style="width:130px;">Balance After</th>
                    <th style="width:220px;">Notes</th>
                    <th style="width:120px;">Created By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements as $movement)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td>{{ optional($movement->created_at)->format('Y-m-d H:i:s') ?? 'N/A' }}</td>
                        <td>{{ $movement->warehouse?->name ?? 'N/A' }}</td>
                        <td>
                            {{ $movement->product?->name ?? '' }}{{ $movement->productVariant ? ' ('.$movement->productVariant->getDisplayName().')' : '' }}
                        </td>
                        <td>{{ ucfirst(str_replace('_', ' ', $movement->type ?? 'N/A')) }}</td>
                        <td>{{ $movement->reference_type ?? 'N/A' }}</td>
                        <td>{{ $movement->reference_id ?? 'N/A' }}</td>
                        <td class="text-right">{{ number_format((float) ($movement->quantity ?? 0), 2) }}</td>
                        <td class="text-right">{{ number_format((float) ($movement->unit_cost ?? 0), 2) }}</td>
                        <td class="text-right"><strong>{{ number_format((float) ($movement->balance_after ?? 0), 2) }}</strong></td>
                        <td>{{ $movement->notes ?? '' }}</td>
                        <td>{{ $movement->creator?->name ?? 'N/A' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="text-center">No movements found matching the selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif
</body>
</html>
