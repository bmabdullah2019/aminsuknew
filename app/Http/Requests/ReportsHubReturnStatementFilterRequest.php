<?php

namespace App\Http\Requests;

class ReportsHubReturnStatementFilterRequest extends ReportsHubDateRangeFilterRequest
{
    protected function prepareForValidation(): void
    {
        $this->normalizeFilterStrings([
            'status',
            'return_source',
        ]);
    }

    public function rules(): array
    {
        return array_merge($this->dateRangeRules(), [
            'branch_id' => $this->branchFilterRules(),
            'status' => ['nullable', 'string', 'max:30'],
            'return_source' => ['nullable', 'string', 'max:30'],
        ]);
    }

    public function filters(): array
    {
        $filters = $this->filtersWithDateRange($this->validated());

        return [
            'start_date' => $filters['start_date'],
            'end_date' => $filters['end_date'],
            'branch_id' => $this->nullableInt($filters['branch_id'] ?? null),
            'status' => $this->nullableString($filters['status'] ?? null),
            'return_source' => $this->nullableString($filters['return_source'] ?? null),
        ];
    }
}
