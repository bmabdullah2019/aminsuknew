@extends('backEnd.layouts.master')
@section('title', 'Customer Receivables')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Customer Receivables</h4>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-3">
                    <label class="form-label">Branch</label>
                    <select name="branch_id" class="form-control">
                        <option value="">All</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ (string) request('branch_id') === (string) $branch->id ? 'selected' : '' }}>
                                {{ $branch->code }} - {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Customer</label>
                    <select name="customer_id" class="form-control">
                        <option value="">All</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ (string) request('customer_id') === (string) $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }} @if($customer->phone)({{ $customer->phone }})@endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Only Due</label>
                    <select name="only_due" class="form-control">
                        <option value="1" {{ request('only_due', '1') === '1' ? 'selected' : '' }}>Yes</option>
                        <option value="0" {{ request('only_due') === '0' ? 'selected' : '' }}>No</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm me-2">Filter</button>
                    <a href="{{ route('admin.accounts.customer-receivables') }}" class="btn btn-secondary btn-sm me-2">Reset</a>
                    <a href="{{ route('admin.accounts.customer-receivables', array_merge(request()->query(), ['export' => 'xlsx'])) }}" class="btn btn-success btn-sm">
                        Export Excel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body py-2">
                    <small>Order Total</small>
                    <h6 class="mb-0">BDT {{ number_format($summary['order_total'] ?? 0, 2) }}</h6>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body py-2">
                    <small>Paid</small>
                    <h6 class="mb-0">BDT {{ number_format($summary['paid_amount'] ?? 0, 2) }}</h6>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-white">
                <div class="card-body py-2">
                    <small>Refund</small>
                    <h6 class="mb-0">BDT {{ number_format($summary['refund_amount'] ?? 0, 2) }}</h6>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-danger text-white">
                <div class="card-body py-2">
                    <small>Due</small>
                    <h6 class="mb-0">BDT {{ number_format($summary['due_amount'] ?? 0, 2) }}</h6>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body py-2">
                    <small>Customers</small>
                    <h6 class="mb-0">{{ number_format($summary['customer_count'] ?? 0) }}</h6>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-secondary text-white">
                <div class="card-body py-2">
                    <small>Orders</small>
                    <h6 class="mb-0">{{ number_format($summary['order_count'] ?? 0) }}</h6>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive report-sticky-container">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Branch</th>
                            <th>Order</th>
                            <th>Customer</th>
                            <th class="text-end">Order Total</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Refund</th>
                            <th class="text-end">Due</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>{{ $row->branch_code ?: 'N/A' }}</td>
                                <td>
                                    {{ $row->invoice_id ?: ('#' . $row->order_id) }}
                                    <a href="{{ route('admin.orders', ['slug' => 'all']) }}" class="btn btn-xs btn-outline-primary ms-1">Orders</a>
                                </td>
                                <td>{{ $row->customer_name ?: 'Unknown' }}</td>
                                <td class="text-end">BDT {{ number_format((float) $row->order_total, 2) }}</td>
                                <td class="text-end">BDT {{ number_format((float) $row->paid_amount, 2) }}</td>
                                <td class="text-end">BDT {{ number_format((float) $row->refund_amount, 2) }}</td>
                                <td class="text-end">BDT {{ number_format((float) $row->due_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No receivable rows found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $rows->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
