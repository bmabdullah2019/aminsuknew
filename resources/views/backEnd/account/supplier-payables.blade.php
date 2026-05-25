@extends('backEnd.layouts.master')
@section('title', 'Supplier Payables')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Supplier Payables</h4>
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
                    <label class="form-label">Supplier</label>
                    <select name="supplier_id" class="form-control">
                        <option value="">All</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ (string) request('supplier_id') === (string) $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->supplier_code }} - {{ $supplier->name }}
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
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                           placeholder="Supplier code or name">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sort</label>
                    <select name="sort" class="form-control">
                        <option value="due_desc" {{ request('sort', 'due_desc') === 'due_desc' ? 'selected' : '' }}>Due (High to Low)</option>
                        <option value="due_asc" {{ request('sort') === 'due_asc' ? 'selected' : '' }}>Due (Low to High)</option>
                        <option value="purchase_desc" {{ request('sort') === 'purchase_desc' ? 'selected' : '' }}>Purchase (High to Low)</option>
                        <option value="purchase_asc" {{ request('sort') === 'purchase_asc' ? 'selected' : '' }}>Purchase (Low to High)</option>
                        <option value="paid_desc" {{ request('sort') === 'paid_desc' ? 'selected' : '' }}>Paid (High to Low)</option>
                        <option value="paid_asc" {{ request('sort') === 'paid_asc' ? 'selected' : '' }}>Paid (Low to High)</option>
                        <option value="supplier_asc" {{ request('sort') === 'supplier_asc' ? 'selected' : '' }}>Supplier (A-Z)</option>
                        <option value="supplier_desc" {{ request('sort') === 'supplier_desc' ? 'selected' : '' }}>Supplier (Z-A)</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm me-2">Filter</button>
                    <a href="{{ route('admin.accounts.supplier-payables') }}" class="btn btn-secondary btn-sm me-2">Reset</a>
                    <a href="{{ route('admin.accounts.supplier-payables', array_merge(request()->query(), ['export' => 'xlsx'])) }}" class="btn btn-success btn-sm">
                        Export Excel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body py-2">
                    <small>Purchase Total</small>
                    <h5 class="mb-0">BDT {{ number_format($summary['purchase_total'] ?? 0, 2) }}</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body py-2">
                    <small>Paid Amount</small>
                    <h5 class="mb-0">BDT {{ number_format($summary['paid_amount'] ?? 0, 2) }}</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body py-2">
                    <small>Due Amount</small>
                    <h5 class="mb-0">BDT {{ number_format($summary['due_amount'] ?? 0, 2) }}</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body py-2">
                    <small>Suppliers With Rows</small>
                    <h5 class="mb-0">{{ number_format($summary['supplier_count'] ?? 0) }}</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Branch</th>
                            <th>Supplier</th>
                            <th class="text-end">Purchase</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Due</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>{{ $row->branch_code ?: 'N/A' }}</td>
                                <td>{{ $row->supplier_code ?: 'N/A' }} - {{ $row->supplier_name ?: 'Unknown' }}</td>
                                <td class="text-end">BDT {{ number_format((float) $row->purchase_total, 2) }}</td>
                                <td class="text-end">BDT {{ number_format((float) $row->paid_amount, 2) }}</td>
                                <td class="text-end">BDT {{ number_format((float) $row->due_amount, 2) }}</td>
                                <td>
                                    @if($row->supplier_id)
                                        <a href="{{ route('admin.supplier.show', $row->supplier_id) }}" class="btn btn-sm btn-outline-primary me-1">Supplier</a>
                                        @if((float) $row->due_amount > 0.01)
                                            <a href="{{ route('admin.supplier.payments.create', $row->supplier_id) }}?branch_id={{ (int) $row->branch_id }}&amount={{ number_format((float) $row->due_amount, 2, '.', '') }}" class="btn btn-sm btn-outline-success">
                                                Pay Now
                                            </a>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No payable rows found.</td>
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
