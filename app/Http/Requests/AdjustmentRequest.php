<?php

namespace App\Http\Requests;

use App\Rules\WarehouseActive;
use Illuminate\Foundation\Http\FormRequest;

class AdjustmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'exists:warehouses,id', new WarehouseActive($this->warehouse_id)],
            'adjustment_date' => ['required', 'date'],
            'adjustment_type' => ['required', 'in:increase,decrease'],
            'reason' => ['required', 'in:physical_count,damage,expiry,theft,found,correction,migration,other'],
            'reason_details' => ['required', 'string', 'min:20', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id', 'distinct'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'items.required' => 'At least one item is required.',
            'adjustment_type.in' => 'Adjustment type must be either increase or decrease.',
        ];
    }
}
