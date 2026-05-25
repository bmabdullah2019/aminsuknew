<?php

namespace App\Models\Accounts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AccountTransaction extends Model
{
    use LogsActivity;

    protected $table = 'accounts_transaction';

    protected $primaryKey = 'TranId';

    public $timestamps = false;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty();
    }

    protected $fillable = [
        'FiscalYearId', 'ComId', 'TranDate', 'TranNo', 'TranAmount',
        'Remarks', 'ApprovalStatus', 'ApprovedBy', 'ApprovedAt', 'ModuleName', 'ModuleId',
        'CreatedBy', 'CreatedAt', 'UpdatedBy', 'UpdatedAt',
        'DeletedBy', 'DeletedAt', 'Validity',
    ];

    protected $casts = [
        'TranId' => 'integer',
        'TranAmount' => 'decimal:2',
        'TranDate' => 'date',
        'Validity' => 'boolean',
    ];

    public function scopeValid($query)
    {
        return $query->where('Validity', 1);
    }

    public function scopeManual($query)
    {
        return $query->where('ModuleName', 'accounts_transaction');
    }

    public function scopeDraft($query)
    {
        return $query->where('ApprovalStatus', 'draft');
    }

    public function scopeApproved($query)
    {
        return $query->where('ApprovalStatus', 'approved');
    }

    public function details(): HasMany
    {
        return $this->hasMany(AccountTransactionDetail::class, 'TranId', 'TranId')
            ->where('Validity', 1);
    }

    /**
     * Generate next voucher number: V-00001, V-00002, etc.
     */
    public static function newVoucherNo(): string
    {
        $max = DB::table('accounts_transaction')
            ->where('ModuleName', 'accounts_transaction')
            ->where('Validity', 1)
            ->max(DB::raw("CAST(SUBSTRING_INDEX(TranNo, '-', -1) AS UNSIGNED)"));

        return 'V-'.str_pad((string) (($max ?: 0) + 1), 5, '0', STR_PAD_LEFT);
    }
}
