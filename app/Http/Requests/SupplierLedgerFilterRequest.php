<?php

namespace App\Http\Requests;

class SupplierLedgerFilterRequest extends SupplierDateRangeFilterRequest
{
    public function rules(): array
    {
        return array_merge($this->dateRangeRules(), [
            'transaction_type' => ['nullable', 'in:opening_balance,purchase,payment,purchase_return,adjustment'],
        ]);
    }
}
