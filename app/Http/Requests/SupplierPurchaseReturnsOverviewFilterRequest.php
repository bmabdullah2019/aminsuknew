<?php

namespace App\Http\Requests;

class SupplierPurchaseReturnsOverviewFilterRequest extends SupplierDateRangeFilterRequest
{
    public function rules(): array
    {
        return array_merge($this->dateRangeRules(), [
            'supplier_id' => $this->supplierFilterRules(),
            'branch_id' => $this->branchFilterRules(),
            'status' => ['nullable', 'in:draft,approved,completed'],
            'return_reason' => ['nullable', 'in:damaged,wrong_item,quality_issue,over_supply,other,damaged_goods,over_supplied,expired'],
        ]);
    }
}
