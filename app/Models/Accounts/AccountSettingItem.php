<?php

namespace App\Models\Accounts;

use Illuminate\Database\Eloquent\Model;

class AccountSettingItem extends Model
{
    protected $table = 'accounts_settings_item';

    public $timestamps = false;

    protected $fillable = [
        'ItemType', 'Inventory', 'WorkInProcess', 'Sales',
        'COGS', 'SalesReturn', 'Wastage', 'Validity',
    ];

    protected $casts = [
        'Validity' => 'boolean',
    ];

    public function scopeValid($query)
    {
        return $query->where('Validity', 1);
    }
}
