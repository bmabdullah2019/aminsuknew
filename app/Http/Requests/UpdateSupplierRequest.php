<?php

namespace App\Http\Requests;

class UpdateSupplierRequest extends SupplierRequest
{
    public function authorize(): bool
    {
        return auth()->check() && $this->user()?->can('supplier-edit');
    }
}
