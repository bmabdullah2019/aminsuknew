<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_id',
        'warehouse_id',
        'allocated_amount',
        'percentage',
        'notes',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
        'percentage' => 'decimal:2',
    ];

    // Relationships
    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
