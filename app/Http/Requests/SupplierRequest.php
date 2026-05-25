<?php

namespace App\Http\Requests;

use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

abstract class SupplierRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'supplier_code' => ['required', 'string', 'max:20', $this->supplierCodeRule()],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', $this->emailRule()],
            'phone' => ['nullable', 'string', 'max:20'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_person_phone' => ['nullable', 'string', 'max:20'],
            'contact_person_email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'in:active,inactive'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account' => ['nullable', 'string', 'max:50'],
            'bank_routing' => ['nullable', 'string', 'max:50'],
            'opening_date' => ['nullable', 'date', 'before_or_equal:today'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    protected function supplierCodeRule(): Unique
    {
        $rule = Rule::unique('suppliers', 'supplier_code');
        $supplier = $this->resolvedSupplier();

        if ($supplier !== null) {
            $rule->ignore($supplier->getKey());
        }

        return $rule;
    }

    protected function emailRule(): Unique
    {
        $rule = Rule::unique('suppliers', 'email');
        $supplier = $this->resolvedSupplier();

        if ($supplier !== null) {
            $rule->ignore($supplier->getKey());
        }

        return $rule;
    }

    protected function resolvedSupplier(): ?Supplier
    {
        $supplier = $this->route('supplier');

        if ($supplier instanceof Supplier) {
            return $supplier;
        }

        if (is_numeric($supplier)) {
            return Supplier::query()->find((int) $supplier);
        }

        return null;
    }
}
