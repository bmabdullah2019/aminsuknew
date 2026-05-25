<?php

namespace App\Http\Requests;

class ReportsHubDailyFilterRequest extends ReportsHubDateRangeFilterRequest
{
    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date'],
            'branch_id' => $this->branchFilterRules(),
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'date' => (string) ($validated['date'] ?? now()->toDateString()),
            'branch_id' => $this->nullableInt($validated['branch_id'] ?? null),
        ];
    }
}
