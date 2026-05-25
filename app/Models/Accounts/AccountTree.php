<?php

namespace App\Models\Accounts;

use Illuminate\Database\Eloquent\Model;

class AccountTree extends Model
{
    protected $table = 'accounts_tree';

    protected $primaryKey = 'Serial';

    public $timestamps = false;

    protected $fillable = [
        'Serial', 'HeadId', 'ParentId', 'AccType', 'HeadCode', 'HeadName',
        'Label', 'HasChild', 'ParentHead', 'Description', 'Validity',
    ];

    protected $casts = [
        'Serial' => 'integer',
        'HeadId' => 'integer',
        'ParentId' => 'integer',
        'AccType' => 'integer',
        'Label' => 'integer',
        'HasChild' => 'boolean',
        'Validity' => 'boolean',
    ];

    public function scopeValid($query)
    {
        return $query->where('Validity', 1);
    }
}
