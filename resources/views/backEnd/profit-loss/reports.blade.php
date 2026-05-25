@extends('backEnd.layouts.master')
@section('title','Profit & Loss Reports')
@section('content')
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.profit-loss.export', [
                        'report_type' => $report->report_type,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'costing_method' => $report->costing_method,
                        'product_id' => $report->product_id,
                        'warehouse_id' => $report->warehouse_id,
                        'format' => 'xlsx',
                    ]) }}" class="btn btn-sm btn-success">
                        <i class="mdi mdi-download"></i> Export Report
                    </a>
                </div>
                <h4 class="page-title">Profit & Loss Report</h4>
                <p class="text-muted">{{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</p>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <!-- Report Summary -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">Profit & Loss Statement</h6>
                    <small>Costing Method: {{ ucfirst(str_replace('_', ' ', $report->costing_method)) }}</small>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Income Statement</h5>
                            <div class="table-responsive report-sticky-container">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Sales Revenue</strong></td>
                                        <td class="text-end"><strong>BDT {{ number_format($report->sales_revenue, 2) }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td>Less: Cost of Goods Sold</td>
                                        <td class="text-end text-danger">BDT {{ number_format($report->cost_of_goods_sold, 2) }}</td>
                                    </tr>
                                    <tr class="border-top">
                                        <td><strong>Gross Profit</strong></td>
                                        <td class="text-end"><strong class="{{ $report->gross_profit >= 0 ? 'text-success' : 'text-danger' }}">BDT {{ number_format($report->gross_profit, 2) }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td>Less: Operating Expenses</td>
                                        <td class="text-end text-warning">BDT {{ number_format($report->operating_expenses, 2) }}</td>
                                    </tr>
                                    <tr class="border-top border-2">
                                        <td><strong>Net Profit Before Losses</strong></td>
                                        <td class="text-end"><strong>BDT {{ number_format($report->gross_profit - $report->operating_expenses, 2) }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td>Less: Inventory Losses</td>
                                        <td class="text-end text-danger">BDT {{ number_format($report->inventory_losses, 2) }}</td>
                                    </tr>
                                    <tr class="border-top border-2">
                                        <td><strong>Net Profit</strong></td>
                                        <td class="text-end"><strong class="{{ $report->net_profit >= 0 ? 'text-success' : 'text-danger' }}">BDT {{ number_format($report->net_profit, 2) }}</strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h5>Key Metrics</h5>
                            <div class="row">
                                <div class="col-6">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6>{{ number_format($report->gross_margin_percentage, 1) }}%</h6>
                                            <small>Gross Margin</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6>{{ number_format($report->net_margin_percentage, 1) }}%</h6>
                                            <small>Net Margin</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <h6>Loss Breakdown</h6>
                                <div class="progress mb-2">
                                    <div class="progress-bar bg-danger" style="width: {{ $report->inventory_losses > 0 ? min(($report->damage_losses / $report->inventory_losses) * 100, 100) : 0 }}%">
                                        Damage
                                    </div>
                                    <div class="progress-bar bg-warning" style="width: {{ $report->inventory_losses > 0 ? min(($report->expired_losses / $report->inventory_losses) * 100, 100) : 0 }}%">
                                        Expired
                                    </div>
                                    <div class="progress-bar bg-dark" style="width: {{ $report->inventory_losses > 0 ? min(($report->theft_losses / $report->inventory_losses) * 100, 100) : 0 }}%">
                                        Theft
                                    </div>
                                </div>
                                <small class="text-muted">
                                    Damage: BDT {{ number_format($report->damage_losses, 2) }} |
                                    Expired: BDT {{ number_format($report->expired_losses, 2) }} |
                                    Theft: BDT {{ number_format($report->theft_losses, 2) }}
                                </small>
                            </div>

                            <div class="mt-3">
                                <h6>Inventory Value</h6>
                                <div class="row">
                                    <div class="col-6">
                                        <small>FIFO</small>
                                        <br><strong>BDT {{ number_format($report->inventory_value_fifo, 2) }}</strong>
                                    </div>
                                    <div class="col-6">
                                        <small>WAC</small>
                                        <br><strong>BDT {{ number_format($report->inventory_value_wac, 2) }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Metrics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h5>{{ $report->units_sold }}</h5>
                    <small>Units Sold</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h5>BDT {{ number_format($report->cost_of_goods_sold / max($report->units_sold, 1), 2) }}</h5>
                    <small>Avg. COGS per Unit</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h5>{{ number_format($report->additional_metrics['loss_percentage'] ?? 0, 2) }}%</h5>
                    <small>Loss Percentage</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h5>{{ \Carbon\Carbon::parse($report->generated_at)->diffForHumans() }}</h5>
                    <small>Report Generated</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Details -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Report Details</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Report Information</h6>
                            <div class="table-responsive report-sticky-container">
                                <table class="table table-sm">
                                    <tr>
                                        <td>Report Type:</td>
                                        <td><strong>{{ ucfirst($report->report_type) }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td>Period:</td>
                                        <td>{{ $startDate }} to {{ $endDate }}</td>
                                    </tr>
                                    <tr>
                                        <td>Costing Method:</td>
                                        <td>{{ ucfirst(str_replace('_', ' ', $report->costing_method)) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Generated:</td>
                                        <td>{{ $report->generated_at->format('M d, Y H:i') }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h6>Additional Metrics</h6>
                            @if($report->additional_metrics)
                            <div class="table-responsive report-sticky-container">
                                <table class="table table-sm">
                                    @foreach($report->additional_metrics as $key => $value)
                                    <tr>
                                        <td>{{ ucfirst(str_replace('_', ' ', $key)) }}:</td>
                                        <td>
                                            @if(str_contains($key, 'percentage'))
                                                {{ number_format($value, 2) }}%
                                            @else
                                                {{ is_numeric($value) ? number_format($value, 2) : $value }}
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </table>
                            </div>
                            @else
                            <p class="text-muted">No additional metrics available</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
