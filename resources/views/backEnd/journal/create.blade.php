@extends('backEnd.layouts.master')
@section('title','Create Journal Entry')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.journal.index') }}" class="btn btn-sm btn-secondary">
                        <i class="mdi mdi-arrow-left"></i> Back
                    </a>
                </div>
                <h4 class="page-title">Create Journal Entry</h4>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.journal.store') }}">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Branch</label>
                        <select name="branch_id" class="form-control @error('branch_id') is-invalid @enderror" required>
                            <option value="">Select branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ (string) old('branch_id') === (string) $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }} ({{ $branch->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" class="form-control @error('date') is-invalid @enderror" value="{{ old('date', now()->format('Y-m-d')) }}" required>
                        @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Reference Type</label>
                        <input type="text" name="reference_type" class="form-control @error('reference_type') is-invalid @enderror" value="{{ old('reference_type') }}" placeholder="order/purchase/expense">
                        @error('reference_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Reference ID</label>
                        <input type="number" min="1" name="reference_id" class="form-control @error('reference_id') is-invalid @enderror" value="{{ old('reference_id') }}">
                        @error('reference_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="2" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <h5 class="mb-3">Journal Lines</h5>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle" id="journal-lines-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width:50%">Account</th>
                                <th style="width:20%">Debit</th>
                                <th style="width:20%">Credit</th>
                                <th style="width:10%" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $oldItems = old('items', [[], []]); @endphp
                            @foreach($oldItems as $index => $item)
                                <tr>
                                    <td>
                                        <select name="items[{{ $index }}][account_id]" class="form-control" required>
                                            <option value="">Select account</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}" {{ (string) ($item['account_id'] ?? '') === (string) $account->id ? 'selected' : '' }}>
                                                    {{ strtoupper($account->type) }} - {{ $account->code }} - {{ $account->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="items[{{ $index }}][debit]" class="form-control" value="{{ $item['debit'] ?? '' }}">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="items[{{ $index }}][credit]" class="form-control" value="{{ $item['credit'] ?? '' }}">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-row">X</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mb-3">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-line-btn">Add Line</button>
                </div>

                <div class="text-end">
                    <a href="{{ route('admin.journal.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Journal Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<template id="journal-line-template">
    <tr>
        <td>
            <select class="form-control account-select" required>
                <option value="">Select account</option>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}">{{ strtoupper($account->type) }} - {{ $account->code }} - {{ $account->name }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" step="0.01" min="0" class="form-control debit-input"></td>
        <td><input type="number" step="0.01" min="0" class="form-control credit-input"></td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row">X</button></td>
    </tr>
</template>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.querySelector('#journal-lines-table tbody');
    const template = document.getElementById('journal-line-template');

    function refreshIndices() {
        const rows = tableBody.querySelectorAll('tr');
        rows.forEach((row, index) => {
            row.querySelector('.account-select')?.setAttribute('name', `items[${index}][account_id]`);
            row.querySelector('.debit-input')?.setAttribute('name', `items[${index}][debit]`);
            row.querySelector('.credit-input')?.setAttribute('name', `items[${index}][credit]`);
        });
    }

    document.getElementById('add-line-btn').addEventListener('click', function () {
        const fragment = template.content.cloneNode(true);
        tableBody.appendChild(fragment);
        refreshIndices();
    });

    tableBody.addEventListener('click', function (event) {
        if (event.target.classList.contains('remove-row')) {
            const rows = tableBody.querySelectorAll('tr');
            if (rows.length <= 2) {
                return;
            }
            event.target.closest('tr').remove();
            refreshIndices();
        }
    });
});
</script>
@endsection

