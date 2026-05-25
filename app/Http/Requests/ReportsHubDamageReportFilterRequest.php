<?php

namespace App\Http\Requests;

class ReportsHubDamageReportFilterRequest extends ReportsHubDateRangeFilterRequest
{
    public function rules(): array
    {
        return array_merge($this->dateRangeRules(), [
            'warehouse_id' => ['nullable', 'integer'],
            'product_id' => ['nullable', 'integer'],
        ]);
    }

    public function filters(): array
    {
        $filters = $this->filtersWithDateRange($this->validated());

        return [
            'start_date' => $filters['start_date'],
            'end_date' => $filters['end_date'],
            'warehouse_id' => $this->nullableInt($filters['warehouse_id'] ?? null),
            'product_id' => $this->nullableInt($filters['product_id'] ?? null),
        ];
    }
}
