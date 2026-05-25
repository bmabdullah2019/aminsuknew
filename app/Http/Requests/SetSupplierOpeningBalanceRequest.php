<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetSupplierOpeningBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'opening_date' => ['required', 'date', 'before_or_equal:today'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'balance_type' => ['required', 'in:debit,credit'],
            'description' => ['nullable', 'string'],
        ];
    }
}
