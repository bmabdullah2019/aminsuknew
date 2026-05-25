<?php

namespace App\Models\Accounts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AccountSubsidiary extends Model
{
    use LogsActivity;

    protected $table = 'accounts_subsidiary';

    protected $primaryKey = 'SubId';

    public $timestamps = false;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty();
    }

    protected $fillable = [
        'ComId', 'SubCode', 'SubName', 'Description', 'Status',
        'CreatedBy', 'CreatedAt', 'UpdatedBy', 'UpdatedAt',
        'DeletedBy', 'DeletedAt', 'Validity',
    ];

    protected $casts = [
        'SubId' => 'integer',
        'ComId' => 'integer',
        'Validity' => 'boolean',
    ];

    public function scopeValid($query)
    {
        return $query->where('Validity', 1);
    }

    public function scopeActive($query)
    {
        return $query->where('Status', 'A');
    }

    /**
     * Generate next subsidiary code: "00001", "00002", etc.
     */
    public static function newSubCode(): string
    {
        $max = DB::table('accounts_subsidiary')
            ->where('Validity', 1)
            ->max(DB::raw('CAST(SubCode AS UNSIGNED)'));

        return str_pad((string) (($max ?: 0) + 1), 5, '0', STR_PAD_LEFT);
    }

    /**
     * Heads assigned to this subsidiary.
     */
    public function heads(): BelongsToMany
    {
        return $this->belongsToMany(
            AccountHead::class,
            'accounts_subsidiary_head',
            'SubId',
            'HeadId',
            'SubId',
            'HeadId'
        )->wherePivot('Validity', 1);
    }
}
