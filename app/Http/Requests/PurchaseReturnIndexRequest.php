<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseReturnIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermissionTo('purchase-return-list');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => is_string($this->input('search'))
                ? trim($this->input('search'))
                : $this->input('search'),
        ]);
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'in:pending,approved,rejected,processed'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'search' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function filters(): array
    {
        return [
            'status' => (string) ($this->validated('status') ?? ''),
            'supplier_id' => filled($this->validated('supplier_id')) ? (int) $this->validated('supplier_id') : null,
            'branch_id' => filled($this->validated('branch_id')) ? (int) $this->validated('branch_id') : null,
            'search' => (string) ($this->validated('search') ?? ''),
        ];
    }
}
