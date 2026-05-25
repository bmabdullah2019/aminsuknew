<?php

namespace App\Http\Requests;

class StoreSupplierOverviewPaymentRequest extends SupplierPaymentRequest
{
    public function rules(): array
    {
        return array_merge([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
        ], $this->paymentRules(false));
    }
}
