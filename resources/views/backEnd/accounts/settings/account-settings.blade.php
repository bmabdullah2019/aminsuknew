@extends('backEnd.layouts.master')
@section('title', 'Account Settings')

@section('content')
@php
    $headOptions = $heads->mapWithKeys(fn ($head) => [
        $head->HeadId => trim(($head->HeadCode ? $head->HeadCode.' - ' : '').$head->HeadName),
    ]);

    $sections = [
        'Account Types' => ['Asset', 'Liability', 'Equity', 'Income', 'Expense'],
        'Control Accounts' => ['Cash', 'Bank', 'Receivable', 'Payable', 'CashAdvance', 'UndepositedFund'],
        'Inventory and Sales' => ['Inventory', 'WorkInProcess', 'Sales', 'COGS', 'SalesReturn', 'Wastage'],
        'Tax, Discount and Adjustments' => ['VATPayable', 'DiscountAllowed', 'AdditionalCost', 'AdjustProfit', 'AdjustLoss', 'BulkPackCollection'],
        'Equity and Payroll' => ['OwnerEquity', 'SalaryPayable', 'Salary'],
    ];

    $labels = [
        'CashAdvance' => 'Customer Advance',
        'UndepositedFund' => 'Undeposited Fund',
        'WorkInProcess' => 'Work In Process',
        'SalesReturn' => 'Sales Return / Contra Sales',
        'VATPayable' => 'VAT Payable',
        'DiscountAllowed' => 'Discount Allowed',
        'AdditionalCost' => 'Additional Cost Clearing',
        'AdjustProfit' => 'Adjustment Profit',
        'AdjustLoss' => 'Adjustment Loss',
        'BulkPackCollection' => 'Bulk Pack Collection',
        'OwnerEquity' => 'Owner Equity',
        'SalaryPayable' => 'Salary Payable',
    ];

    $itemFields = ['Inventory', 'WorkInProcess', 'Sales', 'COGS', 'SalesReturn', 'Wastage'];
@endphp

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Account Settings</h4>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.accounts.settings.update') }}">
        @csrf

        <div class="row">
            <div class="col-xl-10">
                <div class="card">
                    <div class="card-body">
                        <p class="text-muted mb-4">
                            These mappings control automatic financial postings and financial statements. Choose active heads carefully; validation checks the expected account type for receivable, payable, inventory, sales, COGS, returns, tax, and discount accounts.
                        </p>

                        @foreach($sections as $sectionTitle => $fields)
                            <h5 class="mb-3 mt-{{ $loop->first ? '0' : '4' }}">{{ $sectionTitle }}</h5>
                            <div class="row">
                                @foreach($fields as $field)
                                    @if(\Illuminate\Support\Facades\Schema::hasColumn('accounts_settings', $field))
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <label class="form-label fw-semibold" for="{{ $field }}">
                                                {{ $labels[$field] ?? preg_replace('/(?<!^)[A-Z]/', ' $0', $field) }}
                                            </label>
                                            <select name="{{ $field }}" id="{{ $field }}" class="form-select @error($field) is-invalid @enderror">
                                                <option value="">Not mapped</option>
                                                @foreach($headOptions as $headId => $headName)
                                                    <option value="{{ $headId }}" @selected((int) old($field, $settings->{$field} ?? 0) === (int) $headId)>
                                                        {{ $headName }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error($field)
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Item Type Posting Map</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 90px;">Item Type</th>
                                        @foreach($itemFields as $field)
                                            <th>{{ $labels[$field] ?? preg_replace('/(?<!^)[A-Z]/', ' $0', $field) }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($itemSettings as $rowIndex => $itemSetting)
                                        <tr>
                                            <td>
                                                <input type="hidden" name="item_settings[{{ $rowIndex }}][id]" value="{{ $itemSetting->id }}">
                                                <input type="text" name="item_settings[{{ $rowIndex }}][ItemType]" value="{{ old("item_settings.$rowIndex.ItemType", $itemSetting->ItemType) }}" class="form-control @error("item_settings.$rowIndex.ItemType") is-invalid @enderror">
                                                @error("item_settings.$rowIndex.ItemType")
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </td>
                                            @foreach($itemFields as $field)
                                                <td>
                                                    <select name="item_settings[{{ $rowIndex }}][{{ $field }}]" class="form-select @error("item_settings.$rowIndex.$field") is-invalid @enderror">
                                                        <option value="">Not mapped</option>
                                                        @foreach($headOptions as $headId => $headName)
                                                            <option value="{{ $headId }}" @selected((int) old("item_settings.$rowIndex.$field", $itemSetting->{$field} ?? 0) === (int) $headId)>
                                                                {{ $headName }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @error("item_settings.$rowIndex.$field")
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                            @endforeach
                                        </tr>
                                    @empty
                                        @foreach(['R', 'PP', 'P', 'F', 'O'] as $rowIndex => $itemType)
                                            <tr>
                                                <td>
                                                    <input type="text" name="item_settings[{{ $rowIndex }}][ItemType]" value="{{ $itemType }}" class="form-control">
                                                </td>
                                                @foreach($itemFields as $field)
                                                    <td>
                                                        <select name="item_settings[{{ $rowIndex }}][{{ $field }}]" class="form-select">
                                                            <option value="">Not mapped</option>
                                                            @foreach($headOptions as $headId => $headName)
                                                                <option value="{{ $headId }}">{{ $headName }}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-info mb-0">
                            Sales returns use the item-specific <strong>Sales Return</strong>, <strong>Inventory</strong>, and <strong>COGS</strong> mappings first. If an item type mapping is missing, the global settings are used.
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mb-4">
                    <button type="submit" class="btn btn-primary">Save Account Settings</button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
