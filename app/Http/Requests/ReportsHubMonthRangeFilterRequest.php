<?php

namespace App\Http\Requests;

class ReportsHubMonthRangeFilterRequest extends ReportsHubDateRangeFilterRequest
{
    public function rules(): array
    {
        return [
            'from_month' => ['nullable', 'date_format:Y-m'],
            'to_month' => ['nullable', 'date_format:Y-m', 'after_or_equal:from_month'],
            'branch_id' => $this->branchFilterRules(),
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'from_month' => (string) ($validated['from_month'] ?? now()->subMonths(5)->format('Y-m')),
            'to_month' => (string) ($validated['to_month'] ?? now()->format('Y-m')),
            'branch_id' => $this->nullableInt($validated['branch_id'] ?? null),
        ];
    }
}
