<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;

abstract class SupplierPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function paymentRules(bool $branchRequired): array
    {
        return [
            'payment_date' => ['required', 'date', 'before_or_equal:today'],
            'branch_id' => $this->branchRules($branchRequired),
            'amount' => ['required', 'numeric', 'min:0.01'],
            'account_head_id' => ['nullable', 'integer', 'exists:accounts_head,HeadId'],
            'payment_method' => ['required', 'in:cash,bank_transfer,cheque,card,online,other'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'in:pending,completed,cancelled'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $payload = [];

        if (! $this->filled('reference_number') && $this->filled('cheque_number')) {
            $payload['reference_number'] = (string) $this->input('cheque_number');
        }

        if (! $this->filled('bank_account_number') && $this->filled('bank_account')) {
            $payload['bank_account_number'] = (string) $this->input('bank_account');
        }

        if (! empty($payload)) {
            $this->merge($payload);
        }
    }

    protected function branchRules(bool $required): array
    {
        $rules = [$required ? 'required' : 'nullable', 'integer'];

        if (Schema::hasTable('branches')) {
            $rules[] = 'exists:branches,id';
        }

        return $rules;
    }
}
