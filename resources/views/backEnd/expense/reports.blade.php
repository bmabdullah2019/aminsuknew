@extends('backEnd.layouts.master')
@section('title','Expense Reports')
@section('content')
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.expense.export', request()->query()) }}" class="btn btn-sm btn-success">
                        <i class="mdi mdi-download"></i> Export CSV
                    </a>
                </div>
                <h4 class="page-title">Expense Reports</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">BDT {{ number_format($reportData['total_amount'], 2) }}</h4>
                    <small>Total Expenses</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">BDT {{ number_format($reportData['paid_amount'], 2) }}</h4>
                    <small>Paid Amount</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">BDT {{ number_format($reportData['pending_amount'], 2) }}</h4>
                    <small>Pending Amount</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $reportData['total_expenses'] }}</h4>
                    <small>Total Transactions</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Report Filters</h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date"
                                   value="{{ $filters['start_date'] ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date"
                                   value="{{ $filters['end_date'] ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-control" id="category_id" name="category_id">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ ($filters['category_id'] ?? '') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="warehouse_id" class="form-label">Warehouse</label>
                            <select class="form-control" id="warehouse_id" name="warehouse_id">
                                <option value="">All Warehouses</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" {{ ($filters['warehouse_id'] ?? '') == $warehouse->id ? 'selected' : '' }}>
                                        {{ $warehouse->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="paid" {{ ($filters['status'] ?? '') == 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="pending" {{ ($filters['status'] ?? '') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="approved" {{ ($filters['status'] ?? '') == 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="rejected" {{ ($filters['status'] ?? '') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="payment_method" class="form-label">Payment Method</label>
                            <select class="form-control" id="payment_method" name="payment_method">
                                <option value="">All Methods</option>
                                <option value="cash" {{ ($filters['payment_method'] ?? '') == 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="bank_transfer" {{ ($filters['payment_method'] ?? '') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="cheque" {{ ($filters['payment_method'] ?? '') == 'cheque' ? 'selected' : '' }}>Cheque</option>
                                <option value="card" {{ ($filters['payment_method'] ?? '') == 'card' ? 'selected' : '' }}>Card</option>
                                <option value="other" {{ ($filters['payment_method'] ?? '') == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>

                        @if($suppliers->isNotEmpty())
                            <div class="col-md-4">
                                <label for="supplier_id" class="form-label">Supplier</label>
                                <select class="form-control" id="supplier_id" name="supplier_id">
                                    <option value="">All Suppliers</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ (string) ($filters['supplier_id'] ?? '') === (string) $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}{{ $supplier->supplier_code ? ' (' . $supplier->supplier_code . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @if($purchaseOrders->isNotEmpty())
                            <div class="col-md-4">
                                <label for="purchase_order_id" class="form-label">Purchase Order</label>
                                <select class="form-control" id="purchase_order_id" name="purchase_order_id">
                                    <option value="">All Purchase Orders</option>
                                    @foreach($purchaseOrders as $purchaseOrder)
                                        <option value="{{ $purchaseOrder->id }}" {{ (string) ($filters['purchase_order_id'] ?? '') === (string) $purchaseOrder->id ? 'selected' : '' }}>
                                            {{ $purchaseOrder->po_number }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @if($grns->isNotEmpty())
                            <div class="col-md-4">
                                <label for="grn_id" class="form-label">GRN</label>
                                <select class="form-control" id="grn_id" name="grn_id">
                                    <option value="">All GRNs</option>
                                    @foreach($grns as $grn)
                                        <option value="{{ $grn->id }}" {{ (string) ($filters['grn_id'] ?? '') === (string) $grn->id ? 'selected' : '' }}>
                                            {{ $grn->grn_number }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="mdi mdi-filter"></i> Generate Report
                                </button>
                                <a href="{{ route('admin.expense.index') }}" class="btn btn-secondary">
                                    <i class="mdi mdi-refresh"></i> Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Expenses by Category -->
    @if(!empty($reportData['expenses_by_category']))
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Expenses by Category</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive report-sticky-container">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th class="text-end">Count</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">Paid</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reportData['expenses_by_category'] as $category => $data)
                                <tr>
                                    <td>{{ $category }}</td>
                                    <td class="text-end">{{ $data['count'] }}</td>
                                    <td class="text-end">BDT {{ number_format($data['total_amount'], 2) }}</td>
                                    <td class="text-end">BDT {{ number_format($data['paid_amount'], 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Payment Methods</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive report-sticky-container">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Method</th>
                                    <th class="text-end">Count</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reportData['expenses_by_payment_method'] as $method => $data)
                                <tr>
                                    <td>{{ ucfirst(str_replace('_', ' ', $method)) }}</td>
                                    <td class="text-end">{{ $data['count'] }}</td>
                                    <td class="text-end">BDT {{ number_format($data['total_amount'], 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Recent Expenses -->
    @if($reportData['recent_expenses']->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Recent Expenses (Last 10)</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive report-sticky-container">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Expense #</th>
                                    <th>Date</th>
                                    <th>Category</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reportData['recent_expenses'] as $expense)
                                <tr>
                                    <td>
                                        <strong>{{ $expense->expense_number }}</strong>
                                    </td>
                                    <td>{{ $expense->expense_date->format('d M Y') }}</td>
                                    <td>
                                        <span class="badge bg-info">{{ optional($expense->category)->name ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <strong>BDT {{ number_format($expense->total_amount, 2) }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $expense->payment_method)) }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $expense->status_badge }}">{{ ucfirst($expense->status) }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if(empty($reportData['expenses_by_category']) && $reportData['recent_expenses']->count() == 0)
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center">
                    <i class="mdi mdi-information-outline fa-3x text-muted mb-3"></i>
                    <h5>No expense data found</h5>
                    <p class="text-muted">Try adjusting your filters or create some expenses first.</p>
                    <a href="{{ route('admin.expense.create') }}" class="btn btn-primary">
                        <i class="mdi mdi-plus"></i> Create First Expense
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

