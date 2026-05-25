<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;

abstract class SupplierDateRangeFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function dateRangeRules(): array
    {
        return [
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    protected function branchFilterRules(): array
    {
        $rules = ['nullable', 'integer'];

        if (Schema::hasTable('branches')) {
            $rules[] = 'exists:branches,id';
        }

        return $rules;
    }

    protected function supplierFilterRules(): array
    {
        return ['nullable', 'integer', 'exists:suppliers,id'];
    }
}
