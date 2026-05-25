<?php

namespace App\Models\Accounts;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AccountClosing extends Model
{
    use LogsActivity;

    protected $table = 'accounts_closing';

    protected $primaryKey = 'FiscalYearId';

    public $timestamps = false;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty();
    }

    protected $fillable = [
        'ComId', 'OpeningDate', 'ClosingDate', 'Remarks', 'IsClosed',
        'CreatedBy', 'CreatedAt', 'UpdatedBy', 'UpdatedAt',
        'DeletedBy', 'DeletedAt', 'Validity',
    ];

    protected $casts = [
        'FiscalYearId' => 'integer',
        'OpeningDate' => 'date',
        'ClosingDate' => 'date',
        'Validity' => 'boolean',
    ];

    public function scopeValid($query)
    {
        return $query->where('Validity', 1);
    }
}
