<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;

class StoreSupplierAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'adjustment_date' => ['required', 'date', 'before_or_equal:today'],
            'branch_id' => $this->branchRules(),
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'direction' => ['nullable', 'in:debit,credit'],
        ];
    }

    protected function branchRules(): array
    {
        $rules = ['nullable', 'integer'];

        if (Schema::hasTable('branches')) {
            $rules[] = 'exists:branches,id';
        }

        return $rules;
    }
}
