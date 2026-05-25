<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GRN Print - {{ $grn->grn_number }}</title>
    <link rel="stylesheet" href="{{ asset('public/frontEnd/css/bootstrap.min.css') }}">
    <style>
        body {
            background: #f5f7fb;
            color: #212529;
        }
        .sheet {
            max-width: 1100px;
            margin: 24px auto;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 24px;
        }
        .meta-table th {
            width: 220px;
            background: #f8f9fa;
        }
        .table td, .table th {
            vertical-align: middle;
        }
        .print-controls {
            margin-bottom: 16px;
        }
        @media print {
            body {
                background: #fff;
            }
            .sheet {
                max-width: 100%;
                margin: 0;
                border: 0;
                border-radius: 0;
                padding: 0;
            }
            .print-controls {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="d-flex justify-content-between align-items-center print-controls">
            <a href="{{ route('admin.grn.show', $grn->id) }}" class="btn btn-outline-secondary btn-sm">Back</a>
            <button type="button" onclick="window.print();" class="btn btn-primary btn-sm">Print</button>
        </div>

        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h4 class="mb-1">Goods Receipt Note (GRN)</h4>
                <div class="text-muted">Document: {{ $grn->grn_number }}</div>
            </div>
            <div class="text-end">
                <div><strong>Status:</strong> {{ ucfirst((string) $grn->status) }}</div>
                <div><strong>Date:</strong> {{ $grn->grn_date ? $grn->grn_date->format('d M Y') : 'N/A' }}</div>
            </div>
        </div>

        <table class="table table-bordered meta-table mb-4">
            <tbody>
                <tr>
                    <th>GRN Number</th>
                    <td>{{ $grn->grn_number }}</td>
                </tr>
                <tr>
                    <th>Warehouse</th>
                    <td>{{ $grn->warehouse->name ?? 'N/A' }} @if(!empty($grn->warehouse->code)) ({{ $grn->warehouse->code }}) @endif</td>
                </tr>
                <tr>
                    <th>Supplier</th>
                    <td>{{ $grn->supplier->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Invoice Number</th>
                    <td>{{ $grn->invoice_number ?: 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Invoice Date</th>
                    <td>{{ $grn->invoice_date ? $grn->invoice_date->format('d M Y') : 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Received By</th>
                    <td>{{ $grn->receiver->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Approved By</th>
                    <td>{{ $grn->approver->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Approved At</th>
                    <td>{{ $grn->approved_at ? $grn->approved_at->format('d M Y H:i') : 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Notes</th>
                    <td>{{ $grn->notes ?: 'N/A' }}</td>
                </tr>
            </tbody>
        </table>

        <h5 class="mb-2">Items</h5>
        <div class="table-responsive mb-3">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">SL</th>
                        <th>Product</th>
                        <th>SKU</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Unit Cost</th>
                        <th class="text-end">Tax %</th>
                        <th class="text-end">Tax Amount</th>
                        <th class="text-end">Subtotal</th>
                        <th>Batch</th>
                        <th>Expiry</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($grn->items as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->product->name ?? $item->description ?? 'N/A' }}</td>
                            <td>{{ $item->sku ?? 'N/A' }}</td>
                            <td class="text-end">{{ number_format((float) $item->quantity, 2) }}</td>
                            <td class="text-end">BDT {{ number_format((float) $item->unit_cost, 2) }}</td>
                            <td class="text-end">{{ number_format((float) ($item->tax_rate ?? 0), 2) }}%</td>
                            <td class="text-end">BDT {{ number_format((float) ($item->tax_amount ?? 0), 2) }}</td>
                            <td class="text-end">BDT {{ number_format((float) $item->quantity * (float) $item->unit_cost, 2) }}</td>
                            <td>{{ $item->batch_number ?: 'N/A' }}</td>
                            <td>{{ $item->expiry_date ? $item->expiry_date->format('d M Y') : 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted">No items found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="row justify-content-end">
            <div class="col-md-5">
                <table class="table table-bordered">
                    <tr>
                        <th>Subtotal</th>
                        <td class="text-end">BDT {{ number_format((float) ($grn->subtotal ?? 0), 2) }}</td>
                    </tr>
                    <tr>
                        <th>Tax Amount</th>
                        <td class="text-end">BDT {{ number_format((float) ($grn->tax_amount ?? 0), 2) }}</td>
                    </tr>
                    <tr>
                        <th>Shipping Cost</th>
                        <td class="text-end">BDT {{ number_format((float) ($grn->shipping_cost ?? 0), 2) }}</td>
                    </tr>
                    <tr>
                        <th>Other Charges</th>
                        <td class="text-end">BDT {{ number_format((float) ($grn->other_charges ?? 0), 2) }}</td>
                    </tr>
                    <tr class="table-light">
                        <th>Total Amount</th>
                        <td class="text-end"><strong>BDT {{ number_format((float) ($grn->total_amount ?? 0), 2) }}</strong></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
