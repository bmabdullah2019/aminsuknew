<?php

namespace App\Http\Requests;

use App\Support\SupplierReportsQueryData;
use Illuminate\Foundation\Http\FormRequest;

class SupplierReportsQueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', 'max:20'],
            'export' => ['nullable', 'string', 'max:10'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $payload = [];

        foreach (['type', 'export'] as $field) {
            if (! $this->has($field)) {
                continue;
            }

            $payload[$field] = strtolower(trim((string) $this->input($field)));
        }

        if (! empty($payload)) {
            $this->merge($payload);
        }
    }

    public function queryData(): SupplierReportsQueryData
    {
        return SupplierReportsQueryData::fromArray($this->validated());
    }
}
