<?php

namespace App\Http\Requests;

abstract class ReportsHubDateRangeFilterRequest extends SupplierDateRangeFilterRequest
{
    protected function normalizeFilterStrings(array $fields): void
    {
        $payload = [];

        foreach ($fields as $field) {
            if (! $this->has($field)) {
                continue;
            }

            $payload[$field] = trim((string) $this->input($field));
        }

        if (! empty($payload)) {
            $this->merge($payload);
        }
    }

    protected function filtersWithDateRange(array $validated): array
    {
        return array_merge($validated, [
            'start_date' => $validated['start_date'] ?? now()->startOfMonth()->toDateString(),
            'end_date' => $validated['end_date'] ?? now()->endOfMonth()->toDateString(),
        ]);
    }

    protected function nullableInt(mixed $value): ?int
    {
        return filled($value) ? (int) $value : null;
    }

    protected function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}
