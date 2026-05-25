<?php

namespace App\Http\Requests;

class ReportsHubStatementFilterRequest extends ReportsHubDateRangeFilterRequest
{
    protected function prepareForValidation(): void
    {
        $this->normalizeFilterStrings([
            'status',
            'payment_method',
            'transaction_type',
        ]);
    }

    public function rules(): array
    {
        return array_merge($this->dateRangeRules(), [
            'branch_id' => $this->branchFilterRules(),
            'supplier_id' => $this->supplierFilterRules(),
            'status' => ['nullable', 'string', 'max:30'],
            'payment_method' => ['nullable', 'string', 'max:30'],
            'transaction_type' => ['nullable', 'string', 'max:50'],
        ]);
    }

    public function filters(): array
    {
        $filters = $this->filtersWithDateRange($this->validated());

        return [
            'start_date' => $filters['start_date'],
            'end_date' => $filters['end_date'],
            'branch_id' => $this->nullableInt($filters['branch_id'] ?? null),
            'supplier_id' => $this->nullableInt($filters['supplier_id'] ?? null),
            'status' => $this->nullableString($filters['status'] ?? null),
            'payment_method' => $this->nullableString($filters['payment_method'] ?? null),
            'transaction_type' => $this->nullableString($filters['transaction_type'] ?? null),
        ];
    }
}
