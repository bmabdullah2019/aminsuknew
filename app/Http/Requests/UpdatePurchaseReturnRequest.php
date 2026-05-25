<?php

namespace App\Http\Requests;

class UpdatePurchaseReturnRequest extends PurchaseReturnRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermissionTo('purchase-return-edit');
    }
}
