<?php

namespace App\Http\Requests;

class SupplierPaymentsFilterRequest extends SupplierDateRangeFilterRequest
{
    public function rules(): array
    {
        return array_merge($this->dateRangeRules(), [
            'payment_method' => ['nullable', 'in:cash,bank_transfer,cheque,card,online'],
            'status' => ['nullable', 'in:pending,completed,cancelled'],
        ]);
    }
}
