<?php

namespace App\Rules;

use App\Models\WarehouseStock;
use Illuminate\Contracts\Validation\Rule;

class CheckAvailableStock implements Rule
{
    protected $warehouseId;

    protected $productId;

    public function __construct($warehouseId, $productId)
    {
        $this->warehouseId = $warehouseId;
        $this->productId = $productId;
    }

    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value): bool
    {
        $stock = WarehouseStock::where('warehouse_id', $this->warehouseId)
            ->where('product_id', $this->productId)
            ->first();

        if (! $stock) {
            return false;
        }

        return $stock->available_quantity >= $value;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        $stock = WarehouseStock::where('warehouse_id', $this->warehouseId)
            ->where('product_id', $this->productId)
            ->first();

        $available = $stock ? $stock->available_quantity : 0;

        return "Insufficient stock. Available quantity: {$available}";
    }
}
