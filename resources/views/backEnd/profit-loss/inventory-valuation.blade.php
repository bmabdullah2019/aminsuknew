@extends('backEnd.layouts.master')
@section('title','Inventory Valuation Report')
@section('content')
<div class="container-fluid">

    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <form method="GET" class="d-inline">
                        <div class="row g-2">
                            <div class="col-auto">
                                <input
                                    type="date"
                                    name="valuation_date"
                                    value="{{ $valuationDate }}"
                                    class="form-control form-control-sm"
                                    required
                                >
                            </div>
                            <div class="col-auto">
                                <select name="costing_method" class="form-select form-select-sm">
                                    <option value="fifo" {{ $costingMethod === 'fifo' ? 'selected' : '' }}>FIFO</option>
                                    <option value="weighted_average" {{ $costingMethod === 'weighted_average' ? 'selected' : '' }}>Weighted Average</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <select name="product_id" class="form-select form-select-sm">
                                    <option value="">All Products</option>
                                    @foreach($products as $product)
                                    <option value="{{ $product->id }}" {{ (string) request('product_id') === (string) $product->id ? 'selected' : '' }}>
                                        {{ $product->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-auto">
                                <select name="warehouse_id" class="form-select form-select-sm">
                                    <option value="">All Warehouses</option>
                                    @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" {{ (string) request('warehouse_id') === (string) $warehouse->id ? 'selected' : '' }}>
                                        {{ $warehouse->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-sm btn-primary">Run</button>
                            </div>
                        </div>
                    </form>
                </div>
                <h4 class="page-title">Inventory Valuation Report</h4>
                <p class="text-muted">
                    {{ ucfirst(str_replace('_', ' ', $costingMethod)) }} valuation as of
                    {{ \Carbon\Carbon::parse($valuationDate)->format('M d, Y') }}
                </p>
            </div>
        </div>
    </div>

    @php
        $selectedTotalKey = $costingMethod === 'weighted_average' ? 'total_value_wac' : 'total_value_fifo';
        $selectedUnitKey = $costingMethod === 'weighted_average' ? 'unit_cost_wac' : 'unit_cost_fifo';
    @endphp

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">BDT {{ number_format($valuationSummary['total_inventory_value'] ?? 0, 2) }}</h4>
                    <small>Total Inventory Value</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $valuationSummary['products_in_stock'] ?? 0 }}</h4>
                    <small>Products In Stock</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ number_format($valuationSummary['total_units'] ?? 0, 2) }}</h4>
                    <small>Total Units</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">BDT {{ number_format($valuationSummary['average_unit_cost'] ?? 0, 2) }}</h4>
                    <small>Average Unit Cost</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">FIFO vs Weighted Average</h6>
                    <div>
                        <a href="{{ route('admin.profit-loss.export', [
                            'report_type' => 'custom',
                            'start_date' => $valuationDate,
                            'end_date' => $valuationDate,
                            'costing_method' => $costingMethod,
                            'format' => 'xlsx'
                        ]) }}" class="btn btn-sm btn-outline-primary">
                            <i class="mdi mdi-download"></i> Export Excel
                        </a>
                        <a href="{{ route('admin.profit-loss.costing-comparison', [
                            'start_date' => $valuationDate,
                            'end_date' => $valuationDate,
                        ]) }}" class="btn btn-sm btn-outline-info">
                            <i class="mdi mdi-compare"></i> Compare Methods
                        </a>
                        <button type="button" onclick="window.print();" class="btn btn-sm btn-outline-secondary">
                            <i class="mdi mdi-printer"></i> Print
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @php
                        $fifoTotal = (float) ($valuationSummary['fifo_total_value'] ?? 0);
                        $wacTotal = (float) ($valuationSummary['wac_total_value'] ?? 0);
                    @endphp
                    <div class="row">
                        <div class="col-md-4">
                            <h6>FIFO Value</h6>
                            <p class="mb-0 h5 text-primary">BDT {{ number_format($fifoTotal, 2) }}</p>
                        </div>
                        <div class="col-md-4">
                            <h6>Weighted Average Value</h6>
                            <p class="mb-0 h5 text-success">BDT {{ number_format($wacTotal, 2) }}</p>
                        </div>
                        <div class="col-md-4">
                            <h6>Difference</h6>
                            <p class="mb-0 h5 {{ ($fifoTotal - $wacTotal) >= 0 ? 'text-warning' : 'text-danger' }}">
                                BDT {{ number_format(abs($fifoTotal - $wacTotal), 2) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Detailed Inventory Valuation</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Product</th>
                                    <th>Warehouse</th>
                                    <th class="text-end">Quantity</th>
                                    <th class="text-end">Unit Cost ({{ $costingMethod === 'weighted_average' ? 'WAC' : 'FIFO' }})</th>
                                    <th class="text-end">Total Value</th>
                                    <th class="text-center">Stock Level</th>
                                    <th class="text-end">Valuation Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($valuationItems as $item)
                                <tr>
                                    <td>
                                        <strong>{{ $item['product_name'] }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $item['product_code'] }}</small>
                                    </td>
                                    <td>
                                        {{ $item['warehouse_name'] }}
                                        <br>
                                        <small class="text-muted">{{ $item['warehouse_city'] ?? 'N/A' }}</small>
                                    </td>
                                    <td class="text-end">{{ number_format($item['quantity_on_hand'], 2) }}</td>
                                    <td class="text-end">BDT {{ number_format($item[$selectedUnitKey], 2) }}</td>
                                    <td class="text-end"><strong>BDT {{ number_format($item[$selectedTotalKey], 2) }}</strong></td>
                                    <td class="text-center">
                                        @if($item['stock_level'] === 'low')
                                            <span class="badge bg-danger">Low</span>
                                        @elseif($item['stock_level'] === 'medium')
                                            <span class="badge bg-warning">Medium</span>
                                        @else
                                            <span class="badge bg-success">High</span>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ $item['last_valuation_date'] ?? '-' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="mdi mdi-information-outline font-24 text-muted"></i>
                                        <p class="text-muted mb-0">No inventory stock found for the selected filters.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-dark">
                                <tr>
                                    <th colspan="4" class="text-end">TOTAL INVENTORY VALUE:</th>
                                    <th class="text-end">BDT {{ number_format($valuationSummary['total_inventory_value'] ?? 0, 2) }}</th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Warehouse-wise Valuation</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Warehouse</th>
                                    <th class="text-end">Products Count</th>
                                    <th class="text-end">Total Units</th>
                                    <th class="text-end">Inventory Value</th>
                                    <th class="text-end">% of Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($warehouseValuation as $warehouseItem)
                                <tr>
                                    <td>{{ $warehouseItem['warehouse_name'] }}</td>
                                    <td class="text-end">{{ $warehouseItem['products_count'] }}</td>
                                    <td class="text-end">{{ number_format($warehouseItem['total_units'], 2) }}</td>
                                    <td class="text-end"><strong>BDT {{ number_format($warehouseItem['inventory_value'], 2) }}</strong></td>
                                    <td class="text-end">{{ number_format($warehouseItem['percentage_of_total'], 2) }}%</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-3">
                                        <small class="text-muted">No warehouse valuation data available.</small>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
