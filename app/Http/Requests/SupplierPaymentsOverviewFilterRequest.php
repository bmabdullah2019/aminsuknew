<?php

namespace App\Http\Requests;

class SupplierPaymentsOverviewFilterRequest extends SupplierDateRangeFilterRequest
{
    public function rules(): array
    {
        return array_merge($this->dateRangeRules(), [
            'supplier_id' => $this->supplierFilterRules(),
            'branch_id' => $this->branchFilterRules(),
            'account_head_id' => ['nullable', 'integer', 'exists:accounts_head,HeadId'],
            'payment_method' => ['nullable', 'in:cash,bank_transfer,cheque,card,online'],
            'status' => ['nullable', 'in:pending,completed,cancelled'],
        ]);
    }
}
