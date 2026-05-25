<?php

namespace App\Http\Requests;

use App\Rules\CheckAvailableStock;
use App\Rules\WarehouseActive;
use Illuminate\Foundation\Http\FormRequest;

class LossRequest extends FormRequest
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
            'loss_date' => ['required', 'date'],
            'loss_type' => ['required', 'in:damage,expiry,theft,missing,quality_issue,other'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id', 'distinct'],
            'items.*.quantity' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) {
                    $index = explode('.', $attribute)[1];
                    $productId = $this->input("items.{$index}.product_id");

                    if ($productId && $this->warehouse_id) {
                        $rule = new CheckAvailableStock($this->warehouse_id, $productId);
                        if (! $rule->passes($attribute, $value)) {
                            $fail($rule->message());
                        }
                    }
                },
            ],
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
            'loss_type.in' => 'Loss type must be one of: damage, expiry, theft, missing, quality issue, other.',
        ];
    }
}
