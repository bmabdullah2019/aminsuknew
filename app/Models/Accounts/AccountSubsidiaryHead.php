<?php

namespace App\Models\Accounts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountSubsidiaryHead extends Model
{
    protected $table = 'accounts_subsidiary_head';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'SubId', 'ComId', 'HeadId',
        'CreatedBy', 'CreatedAt', 'UpdatedBy', 'UpdatedAt',
        'DeletedBy', 'DeletedAt', 'Validity',
    ];

    protected $casts = [
        'SubId' => 'integer',
        'HeadId' => 'integer',
        'Validity' => 'boolean',
    ];

    public function scopeValid($query)
    {
        return $query->where('Validity', 1);
    }

    public function subsidiary(): BelongsTo
    {
        return $this->belongsTo(AccountSubsidiary::class, 'SubId', 'SubId');
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(AccountHead::class, 'HeadId', 'HeadId');
    }
}
