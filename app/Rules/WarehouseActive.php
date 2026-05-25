<?php

namespace App\Rules;

use App\Models\Warehouse;
use Illuminate\Contracts\Validation\Rule;

class WarehouseActive implements Rule
{
    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value): bool
    {
        if (! $value) {
            return false;
        }

        $warehouse = Warehouse::find($value);

        return $warehouse && $warehouse->is_active;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'The selected warehouse is not active.';
    }
}
