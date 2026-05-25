<?php

namespace App\Http\Requests;

class StoreSupplierOverviewPurchaseReturnRequest extends SupplierPurchaseReturnRequest
{
    protected function supplierIdentifierRules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
        ];
    }
}
