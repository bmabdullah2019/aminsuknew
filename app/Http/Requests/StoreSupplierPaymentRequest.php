<?php

namespace App\Http\Requests;

class StoreSupplierPaymentRequest extends SupplierPaymentRequest
{
    public function rules(): array
    {
        return $this->paymentRules(true);
    }
}
