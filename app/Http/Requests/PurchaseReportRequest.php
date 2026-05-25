<?php

namespace App\Http\Requests;

use App\Support\PurchaseReportQueryData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $payload = [];

        foreach (['keyword', 'status', 'period', 'export'] as $field) {
            if (! $this->has($field)) {
                continue;
            }

            $payload[$field] = trim((string) $this->input($field));
        }

        if (! empty($payload)) {
            $this->merge($payload);
        }
    }

    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:255'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'product_id' => [
                'nullable',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('status', 1)),
            ],
            'status' => ['nullable', 'in:draft,pending,approved,ordered,partial_received,received,cancelled'],
            'period' => ['nullable', 'in:daily,monthly,yearly,custom'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'export' => ['nullable', 'in:xlsx'],
        ];
    }

    public function queryData(): PurchaseReportQueryData
    {
        return PurchaseReportQueryData::fromArray($this->validated());
    }
}
