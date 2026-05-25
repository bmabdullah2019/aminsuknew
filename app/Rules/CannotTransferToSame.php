<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class CannotTransferToSame implements Rule
{
    protected $fromWarehouseId;

    public function __construct($fromWarehouseId)
    {
        $this->fromWarehouseId = $fromWarehouseId;
    }

    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value): bool
    {
        return $this->fromWarehouseId != $value;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'Cannot transfer stock to the same warehouse.';
    }
}
