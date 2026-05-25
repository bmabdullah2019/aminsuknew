@extends('backEnd.layouts.master')
@section('title','Daily Expense Summary - ' . $date->format('d M Y'))
@section('content')
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.expense.index') }}" class="btn btn-sm btn-secondary">
                        <i class="mdi mdi-arrow-left"></i> Back to Expenses
                    </a>
                </div>
                <h4 class="page-title">Daily Expense Summary - {{ $date->format('l, d M Y') }}</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <!-- Date Navigation -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('admin.expense.daily-summary', ['date' => $date->copy()->subDay()->format('Y-m-d')]) }}"
                           class="btn btn-outline-primary">
                            <i class="mdi mdi-chevron-left"></i> Previous Day
                        </a>

                        <div class="text-center">
                            <h5 class="mb-0">{{ $date->format('l, d F Y') }}</h5>
                            <small class="text-muted">Daily Expense Summary</small>
                        </div>

                        <a href="{{ route('admin.expense.daily-summary', ['date' => $date->copy()->addDay()->format('Y-m-d')]) }}"
                           class="btn btn-outline-primary">
                            Next Day <i class="mdi mdi-chevron-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $summary['total_expenses'] }}</h4>
                    <small>Total Expenses</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">BDT {{ number_format($summary['total_amount'], 2) }}</h4>
                    <small>Total Amount</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $summary['pending_count'] }}</h4>
                    <small>Pending</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $summary['paid_count'] }}</h4>
                    <small>Paid</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Date Selector -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Quick Date Selection</h6>
                </div>
                <div class="card-body">
                    <div class="btn-group" role="group">
                        <a href="{{ route('admin.expense.daily-summary', ['date' => now()->format('Y-m-d')]) }}"
                           class="btn btn-outline-primary {{ $date->isToday() ? 'active' : '' }}">
                            Today
                        </a>
                        <a href="{{ route('admin.expense.daily-summary', ['date' => now()->subDay()->format('Y-m-d')]) }}"
                           class="btn btn-outline-primary {{ $date->isYesterday() ? 'active' : '' }}">
                            Yesterday
                        </a>
                        <a href="{{ route('admin.expense.daily-summary', ['date' => now()->copy()->startOfWeek()->format('Y-m-d')]) }}"
                           class="btn btn-outline-primary">
                            This Week Start
                        </a>
                        <a href="{{ route('admin.expense.daily-summary', ['date' => now()->copy()->startOfMonth()->format('Y-m-d')]) }}"
                           class="btn btn-outline-primary">
                            This Month Start
                        </a>
                    </div>
                    <div class="mt-3">
                        <form method="GET" class="d-inline">
                            <div class="input-group" style="width: 200px;">
                                <input type="date" class="form-control" name="date" value="{{ $date->format('Y-m-d') }}">
                                <button type="submit" class="btn btn-primary">
                                    <i class="mdi mdi-calendar"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Expenses List -->
    @if($summary['expenses']->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Expenses for {{ $date->format('d M Y') }}</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Expense #</th>
                                    <th>Time</th>
                                    <th>Category</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($summary['expenses'] as $expense)
                                <tr>
                                    <td>
                                        <strong>{{ $expense->expense_number }}</strong>
                                    </td>
                                    <td>{{ $expense->created_at->format('H:i') }}</td>
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
                                    <td>{{ optional($expense->creator)->name ?? 'System' }}</td>
                                    <td>
                                        <a href="{{ route('admin.expense.show', $expense) }}" class="btn btn-sm btn-outline-info">
                                            <i class="mdi mdi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-dark">
                                <tr>
                                    <th colspan="3">TOTAL</th>
                                    <th>BDT {{ number_format($summary['total_amount'], 2) }}</th>
                                    <th colspan="4">{{ $summary['total_expenses'] }} expenses</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center">
                    <i class="mdi mdi-calendar-blank-outline fa-3x text-muted mb-3"></i>
                    <h5>No Expenses Found</h5>
                    <p class="text-muted">No expenses were recorded for {{ $date->format('l, d F Y') }}.</p>
                    <a href="{{ route('admin.expense.create') }}" class="btn btn-primary">
                        <i class="mdi mdi-plus"></i> Add First Expense
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Category Breakdown -->
    @if($summary['expenses']->count() > 0)
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Expenses by Category</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th class="text-end">Count</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $categoryBreakdown = $summary['expenses']->groupBy(function ($expense) {
                                        return optional($expense->category)->name ?? 'Uncategorized';
                                    });
                                @endphp
                                @foreach($categoryBreakdown as $categoryName => $expenses)
                                <tr>
                                    <td>{{ $categoryName }}</td>
                                    <td class="text-end">{{ $expenses->count() }}</td>
                                    <td class="text-end">BDT {{ number_format($expenses->sum('total_amount'), 2) }}</td>
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
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Method</th>
                                    <th class="text-end">Count</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $paymentBreakdown = $summary['expenses']->groupBy('payment_method');
                                @endphp
                                @foreach($paymentBreakdown as $method => $expenses)
                                <tr>
                                    <td>{{ ucfirst(str_replace('_', ' ', $method)) }}</td>
                                    <td class="text-end">{{ $expenses->count() }}</td>
                                    <td class="text-end">BDT {{ number_format($expenses->sum('total_amount'), 2) }}</td>
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
</div>
@endsection

