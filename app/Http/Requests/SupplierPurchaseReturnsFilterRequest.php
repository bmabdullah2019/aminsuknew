<?php

namespace App\Http\Requests;

class SupplierPurchaseReturnsFilterRequest extends SupplierDateRangeFilterRequest
{
    public function rules(): array
    {
        return array_merge($this->dateRangeRules(), [
            'status' => ['nullable', 'in:draft,approved,completed'],
            'return_reason' => ['nullable', 'in:damaged,wrong_item,quality_issue,over_supply,other,damaged_goods,over_supplied,expired'],
        ]);
    }
}
