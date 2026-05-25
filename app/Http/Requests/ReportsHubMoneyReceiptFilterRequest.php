<?php

namespace App\Http\Requests;

class ReportsHubMoneyReceiptFilterRequest extends ReportsHubDateRangeFilterRequest
{
    protected function prepareForValidation(): void
    {
        $this->normalizeFilterStrings([
            'status',
            'method',
            'trx_id',
        ]);
    }

    public function rules(): array
    {
        return array_merge($this->dateRangeRules(), [
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'status' => ['nullable', 'in:paid,pending,failed,cancelled'],
            'method' => ['nullable', 'string', 'max:50'],
            'trx_id' => ['nullable', 'string', 'max:100'],
        ]);
    }

    public function filters(): array
    {
        $filters = $this->filtersWithDateRange($this->validated());

        return [
            'start_date' => $filters['start_date'],
            'end_date' => $filters['end_date'],
            'customer_id' => $this->nullableInt($filters['customer_id'] ?? null),
            'status' => $this->nullableString($filters['status'] ?? null),
            'method' => $this->nullableString($filters['method'] ?? null),
            'trx_id' => $this->nullableString($filters['trx_id'] ?? null),
        ];
    }
}
