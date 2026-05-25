<?php

namespace App\Http\Requests;

class ReportsHubCustomerLedgerFilterRequest extends ReportsHubDateRangeFilterRequest
{
    public function rules(): array
    {
        return array_merge($this->dateRangeRules(), [
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'branch_id' => $this->branchFilterRules(),
        ]);
    }

    public function filters(): array
    {
        $filters = $this->filtersWithDateRange($this->validated());

        return [
            'start_date' => $filters['start_date'],
            'end_date' => $filters['end_date'],
            'customer_id' => $this->nullableInt($filters['customer_id'] ?? null),
            'branch_id' => $this->nullableInt($filters['branch_id'] ?? null),
        ];
    }
}
