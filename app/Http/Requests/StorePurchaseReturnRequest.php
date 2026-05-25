<?php

namespace App\Http\Requests;

class StorePurchaseReturnRequest extends PurchaseReturnRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermissionTo('purchase-return-create');
    }
}
