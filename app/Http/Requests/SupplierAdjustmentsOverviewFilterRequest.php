<?php

namespace App\Http\Requests;

class SupplierAdjustmentsOverviewFilterRequest extends SupplierDateRangeFilterRequest
{
    public function rules(): array
    {
        return array_merge($this->dateRangeRules(), [
            'supplier_id' => $this->supplierFilterRules(),
            'branch_id' => $this->branchFilterRules(),
        ]);
    }
}
