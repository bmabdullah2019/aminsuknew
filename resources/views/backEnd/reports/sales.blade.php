@extends('backEnd.layouts.master')
@section('title', 'Sales Report')
@section('css')
<link href="{{ asset('public/backEnd') }}/assets/libs/select2/css/select2.min.css" rel="stylesheet">
<link href="{{ asset('public/backEnd/') }}/assets/libs/flatpickr/flatpickr.min.css" rel="stylesheet">
<style>
    @media print {
        header, footer, .no-print, .left-side-menu, .navbar-custom { display: none !important; }
    }
    .statement-card {
        border: 1px solid #cfd8e3;
    }
    .statement-title {
        font-size: 20px;
        color: #2f5c7a;
        font-weight: 600;
        margin-bottom: 12px;
    }
    .statement-filter-wrap {
        border: 1px solid #d9dee7;
        background: #f6f8fb;
        padding: 10px;
        margin-bottom: 10px;
    }
    .statement-table {
        font-size: 12px;
        margin-bottom: 0;
    }
    .statement-table thead th {
        background: #eef2f7;
        border: 1px solid #d7dde8;
        color: #1f2937;
        font-weight: 600;
        white-space: nowrap;
        text-transform: uppercase;
        font-size: 11px;
    }
    .statement-table td {
        border: 1px solid #e1e6ef;
        white-space: nowrap;
    }
    .statement-meta {
        font-size: 12px;
        color: #4b5563;
        margin-bottom: 8px;
    }
</style>
@endsection
@section('content')
<div class="container-fluid">
    <div class="card statement-card">
        <div class="card-body">
            <h4 class="statement-title">Sales Statement</h4>

            <form method="GET" action="{{ route('admin.reports-new.sales') }}" class="statement-filter-wrap no-print">
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label mb-1">Period</label>
                        <select name="period" class="form-control form-control-sm select2">
                            @foreach(['custom' => 'Custom', 'daily' => 'Daily', 'monthly' => 'Monthly', 'yearly' => 'Yearly'] as $val => $label)
                                <option value="{{ $val }}" {{ $filter->period === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1">Sale Date</label>
                        <input type="date" name="start_date" class="form-control form-control-sm flatdate" value="{{ $filter->startDate }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1">To</label>
                        <input type="date" name="end_date" class="form-control form-control-sm flatdate" value="{{ $filter->endDate }}">
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-danger">Show Report</button>
                        <a href="{{ route('admin.reports-new.sales') }}" class="btn btn-sm btn-secondary">Reset</a>
                        <button onclick="window.print()" type="button" class="btn btn-sm btn-primary">Print</button>
                    </div>
                </div>
            </form>

            <div class="statement-meta d-flex justify-content-between">
                <span>From {{ \Carbon\Carbon::parse($filter->startDate)->format('d/m/Y') }} To {{ \Carbon\Carbon::parse($filter->endDate)->format('d/m/Y') }}</span>
                <span>Total Invoice: {{ number_format($summary['total_orders']) }} | Net Sales: {{ number_format($summary['net_sales'], 2) }}</span>
            </div>

            <div class="table-responsive report-sticky-container" id="content-to-export">
                <table class="table table-sm statement-table align-middle">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>Sale Date</th>
                            <th>Sale No</th>
                            <th>Sale Type</th>
                            <th>Customer Name</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">VAT</th>
                            <th class="text-end">Discount</th>
                            <th class="text-end">Net Amount</th>
                            <th class="text-end">Receipt</th>
                            <th class="text-end">Due</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sales as $row)
                            @php
                                $lineAmount = ((float)$row->sale_price) * ((int)$row->qty);
                                $netAmount = $lineAmount;
                                $receipt = 0;
                                $due = $netAmount - $receipt;
                            @endphp
                            <tr>
                                <td>{{ $sales->firstItem() + $loop->index }}</td>
                                <td>{{ \Carbon\Carbon::parse($row->created_at)->format('d/m/Y') }}</td>
                                <td>{{ $row->invoice_id ?? '-' }}</td>
                                <td>Customer</td>
                                <td>{{ $row->customer_name ?? '-' }}</td>
                                <td class="text-end">{{ number_format($lineAmount, 2) }}</td>
                                <td class="text-end">0.00</td>
                                <td class="text-end">0.00</td>
                                <td class="text-end">{{ number_format($netAmount, 2) }}</td>
                                <td class="text-end">{{ number_format($receipt, 2) }}</td>
                                <td class="text-end">{{ number_format($due, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted py-4">
                                    No sales data found for this date range.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-secondary fw-bold">
                            <td colspan="5" class="text-end">Totals</td>
                            <td class="text-end">{{ number_format($sales->sum(fn($r) => ((float)$r->sale_price) * ((int)$r->qty)), 2) }}</td>
                            <td class="text-end">0.00</td>
                            <td class="text-end">0.00</td>
                            <td class="text-end">{{ number_format($sales->sum(fn($r) => ((float)$r->sale_price) * ((int)$r->qty)), 2) }}</td>
                            <td class="text-end">0.00</td>
                            <td class="text-end">{{ number_format($sales->sum(fn($r) => ((float)$r->sale_price) * ((int)$r->qty)), 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="no-print mt-2">{{ $sales->links('pagination::bootstrap-4') }}</div>
        </div>
    </div>
</div>
@endsection
@section('script')
<script src="{{ asset('public/backEnd/') }}/assets/libs/select2/js/select2.min.js"></script>
<script src="{{ asset('public/backEnd/') }}/assets/libs/flatpickr/flatpickr.min.js"></script>
<script>
    $(document).ready(function () {
        $('.select2').select2();
        flatpickr('.flatdate', {});
    });
</script>
@endsection
