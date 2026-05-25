<?php

namespace App\Http\Requests;

use App\Rules\CannotTransferToSame;
use App\Rules\CheckAvailableStock;
use App\Rules\WarehouseActive;
use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // Backward compatibility with older field naming.
        if (! $this->filled('estimated_arrival') && $this->filled('expected_date')) {
            $this->merge([
                'estimated_arrival' => $this->input('expected_date'),
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
            'from_warehouse_id' => ['required', 'exists:warehouses,id', new WarehouseActive($this->from_warehouse_id)],
            'to_warehouse_id' => [
                'required',
                'exists:warehouses,id',
                new WarehouseActive($this->to_warehouse_id),
                new CannotTransferToSame($this->from_warehouse_id),
            ],
            'transfer_date' => ['required', 'date', 'before_or_equal:today'],
            'estimated_arrival' => ['nullable', 'date', 'after_or_equal:transfer_date'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) {
                    $index = explode('.', $attribute)[1];
                    $productId = $this->input("items.{$index}.product_id");

                    if ($productId && $this->from_warehouse_id) {
                        $rule = new CheckAvailableStock($this->from_warehouse_id, $productId);
                        if (! $rule->passes($attribute, $value)) {
                            $fail($rule->message());
                        }
                    }
                },
            ],
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
            'to_warehouse_id.required' => 'Destination warehouse is required.',
            'from_warehouse_id.required' => 'Source warehouse is required.',
        ];
    }
}
