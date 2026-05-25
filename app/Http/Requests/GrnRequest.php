<?php

namespace App\Http\Requests;

use App\Rules\WarehouseActive;
use Illuminate\Foundation\Http\FormRequest;

class GrnRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // Backward compatibility with earlier payloads.
        if (! $this->filled('grn_date') && $this->filled('received_date')) {
            $this->merge([
                'grn_date' => $this->input('received_date'),
            ]);
        }
    }

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
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'grn_date' => ['required', 'date', 'before_or_equal:today'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'invoice_date' => ['nullable', 'date', 'before_or_equal:today'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'other_charges' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'items.*.sku' => ['nullable', 'string', 'max:100'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.ordered_quantity' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.batch_number' => ['nullable', 'string', 'max:100'],
            'items.*.expiry_date' => ['nullable', 'date'],
            'items.*.color' => ['nullable', 'string', 'max:50'],
            'items.*.size' => ['nullable', 'string', 'max:50'],
            'items.*.age' => ['nullable', 'string', 'max:50'],
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
            'grn_date.required' => 'GRN date is required.',
            'supplier_id.exists' => 'Selected supplier is invalid.',
            'items.required' => 'At least one item is required.',
            'items.*.product_id.exists' => 'One or more selected products are invalid.',
            'items.*.quantity.min' => 'Quantity must be greater than 0.',
            'items.*.unit_cost.required' => 'Unit cost is required for every line item.',
        ];
    }
}
