<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class OrderStateTransition extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'order_id',
        'branch_id',
        'from_status',
        'to_status',
        'actor_type',
        'actor_id',
        'source',
        'reason',
        'meta',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'from_status' => 'integer',
        'to_status' => 'integer',
        'actor_id' => 'integer',
        'meta' => 'array',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    protected static function booted(): void
    {
        static::creating(function (self $transition): void {
            if (! self::hasBranchColumn()) {
                unset($transition->branch_id);
            }
        });
    }

    private static function hasBranchColumn(): bool
    {
        static $hasBranchColumn = null;

        if ($hasBranchColumn === null) {
            $table = (new self)->getTable();
            $hasBranchColumn = Schema::hasTable($table) && Schema::hasColumn($table, 'branch_id');
        }

        return (bool) $hasBranchColumn;
    }
}
