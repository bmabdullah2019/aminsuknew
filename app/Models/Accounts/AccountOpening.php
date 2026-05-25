<?php

namespace App\Models\Accounts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AccountOpening extends Model
{
    use LogsActivity;

    protected $table = 'accounts_opening';

    protected $primaryKey = null;

    public $incrementing = false;

    public $timestamps = false;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty();
    }

    protected $fillable = [
        'OpeningDate', 'FiscalYearId', 'ComId', 'BatchId',
        'TranHead', 'SubId', 'CustId', 'SupId', 'EmpId',
        'Debit', 'Credit', 'ModuleName', 'ModuleId',
        'CreatedBy', 'CreatedAt', 'UpdatedBy', 'UpdatedAt',
        'DeletedBy', 'DeletedAt', 'Validity',
    ];

    protected $casts = [
        'Debit' => 'decimal:2',
        'Credit' => 'decimal:2',
        'Validity' => 'boolean',
    ];

    public function scopeValid($query)
    {
        return $query->where('Validity', 1);
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(AccountHead::class, 'TranHead', 'HeadId');
    }

    public function subsidiary(): BelongsTo
    {
        return $this->belongsTo(AccountSubsidiary::class, 'SubId', 'SubId');
    }
}
