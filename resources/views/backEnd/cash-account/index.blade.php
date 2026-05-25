@extends('backEnd.layouts.master')
@section('title','Cash Accounts')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.cash-accounts.create') }}" class="btn btn-sm btn-primary">
                        <i class="mdi mdi-plus"></i> Add Cash Account
                    </a>
                </div>
                <h4 class="page-title">Cash Accounts</h4>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Name or account number">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Branch</label>
                    <select name="branch_id" class="form-control">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ (string) request('branch_id') === (string) $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="">All</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2 align-self-end">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Name</th>
                            <th>Branch</th>
                            <th>Account No</th>
                            <th class="text-end">Opening</th>
                            <th class="text-end">Current</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cashAccounts as $cashAccount)
                            <tr>
                                <td>{{ $cashAccount->name }}</td>
                                <td>{{ $cashAccount->branch?->name ?? 'N/A' }}</td>
                                <td>{{ $cashAccount->account_number ?: 'N/A' }}</td>
                                <td class="text-end">{{ number_format((float) $cashAccount->opening_balance, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $cashAccount->current_balance, 2) }}</td>
                                <td>
                                    @if($cashAccount->status)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.cash-accounts.edit', $cashAccount->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="mdi mdi-pencil"></i> Edit
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No cash account found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($cashAccounts->hasPages())
                <div class="d-flex justify-content-center">
                    {{ $cashAccounts->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

