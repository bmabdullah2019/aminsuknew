<?php

namespace App\Http\Requests;

class StoreSupplierRequest extends SupplierRequest
{
    public function authorize(): bool
    {
        return auth()->check() && $this->user()?->can('supplier-create');
    }
}
