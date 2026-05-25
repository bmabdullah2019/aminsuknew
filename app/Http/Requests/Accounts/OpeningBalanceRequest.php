<?php

namespace App\Http\Requests\Accounts;

use Illuminate\Foundation\Http\FormRequest;

class OpeningBalanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'HeadId' => 'required|array',
            'HeadId.*' => 'required|integer|exists:accounts_head,HeadId',
            'Debit' => 'required|array',
            'Debit.*' => 'required|numeric|min:0',
            'Credit' => 'required|array',
            'Credit.*' => 'required|numeric|min:0',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $debits = $this->input('Debit', []);
            $credits = $this->input('Credit', []);

            $totalDebit = array_sum($debits);
            $totalCredit = array_sum($credits);

            if (abs($totalDebit - $totalCredit) > 0.01) {
                $validator->errors()->add('TotalDebit', 'Total Debit MUST equal Total Credit for establishing valid opening balances. Currently Debit: '.number_format($totalDebit, 2).' and Credit: '.number_format($totalCredit, 2));
            }
        });
    }
}
