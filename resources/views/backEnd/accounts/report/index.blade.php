@extends('backEnd.layouts.master')
@section('title', 'Financial Reports')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Financial Reports</h4>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <a href="{{ route('admin.accounts.reports.ledger') }}" class="card h-100 text-decoration-none">
                <div class="card-body">
                    <h5 class="card-title mb-2">General Ledger</h5>
                    <p class="card-text text-muted mb-0">Account-wise movement with opening, transactions, and running balance.</p>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('admin.accounts.reports.trial-balance') }}" class="card h-100 text-decoration-none">
                <div class="card-body">
                    <h5 class="card-title mb-2">Trial Balance</h5>
                    <p class="card-text text-muted mb-0">As-of-date debit and credit balances for all final heads.</p>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('admin.accounts.reports.balance-sheet') }}" class="card h-100 text-decoration-none">
                <div class="card-body">
                    <h5 class="card-title mb-2">Balance Sheet</h5>
                    <p class="card-text text-muted mb-0">Assets, liabilities, and equity position on a specific date.</p>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('admin.accounts.reports.income-statement') }}" class="card h-100 text-decoration-none">
                <div class="card-body">
                    <h5 class="card-title mb-2">Profit &amp; Loss Statement</h5>
                    <p class="card-text text-muted mb-0">Period income, expenses, and net profit/loss summary.</p>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('admin.accounts.reports.cash-flow') }}" class="card h-100 text-decoration-none">
                <div class="card-body">
                    <h5 class="card-title mb-2">Cash Flow Statement</h5>
                    <p class="card-text text-muted mb-0">Cash and bank inflow/outflow movement for a date range.</p>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('admin.accounts.reports.top-sheet') }}" class="card h-100 text-decoration-none">
                <div class="card-body">
                    <h5 class="card-title mb-2">Account Group Summary</h5>
                    <p class="card-text text-muted mb-0">Group-wise opening, debit, credit, and closing balances.</p>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('admin.accounts.reports.voucher-statement') }}" class="card h-100 text-decoration-none">
                <div class="card-body">
                    <h5 class="card-title mb-2">Voucher Register</h5>
                    <p class="card-text text-muted mb-0">Voucher-wise transaction listing for audit and compliance.</p>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('admin.accounts.reports.reconciliation') }}" class="card h-100 text-decoration-none">
                <div class="card-body">
                    <h5 class="card-title mb-2">Reconciliation</h5>
                    <p class="card-text text-muted mb-0">Compare customer, supplier, inventory, and undeposited source balances with GL.</p>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection
