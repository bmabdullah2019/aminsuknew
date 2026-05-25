<?php

namespace App\Models\Accounts;

use Illuminate\Database\Eloquent\Model;

class AccountSetting extends Model
{
    protected $table = 'accounts_settings';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'Asset', 'Liability', 'Equity', 'Income', 'Expense',
        'Cash', 'Bank', 'Payable', 'AdditionalCost', 'Receivable',
        'CashAdvance', 'UndepositedFund', 'Inventory', 'WorkInProcess',
        'Sales', 'COGS', 'Wastage', 'AdjustProfit', 'AdjustLoss',
        'SalesReturn', 'VATPayable', 'DiscountAllowed',
        'BulkPackCollection', 'OwnerEquity', 'SalaryPayable', 'Salary', 'Validity',
    ];

    protected $casts = [
        'Asset' => 'integer', 'Liability' => 'integer', 'Equity' => 'integer',
        'Income' => 'integer', 'Expense' => 'integer', 'Cash' => 'integer',
        'Bank' => 'integer', 'Payable' => 'integer', 'Receivable' => 'integer',
        'SalesReturn' => 'integer', 'VATPayable' => 'integer', 'DiscountAllowed' => 'integer',
        'Validity' => 'boolean',
    ];

    /**
     * Get the single active settings row.
     */
    public static function current(): ?self
    {
        return static::where('Validity', 1)->first();
    }
}
