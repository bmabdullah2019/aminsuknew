<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class PurchaseReturnRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $items = $this->input('items', []);

        if (is_array($items)) {
            foreach ($items as $index => $item) {
                if (! is_array($item)) {
                    continue;
                }

                if (($item['product_variant_id'] ?? null) === '') {
                    $items[$index]['product_variant_id'] = null;
                }
            }
        }

        $this->merge([
            'purchase_order_id' => $this->input('purchase_order_id') === ''
                ? null
                : $this->input('purchase_order_id'),
            'description' => is_string($this->input('description'))
                ? trim($this->input('description'))
                : $this->input('description'),
            'items' => $items,
        ]);
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'exists:branches,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'purchase_order_id' => ['nullable', 'exists:purchase_orders,id'],
            'return_date' => ['required', 'date'],
            'return_reason' => ['required', 'in:damaged,quality_issue,wrong_item,quantity_mismatch,expired,other'],
            'description' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => 'Branch is required',
            'branch_id.exists' => 'Selected branch does not exist',
            'supplier_id.required' => 'Supplier is required',
            'supplier_id.exists' => 'Selected supplier does not exist',
            'return_date.required' => 'Return date is required',
            'return_date.date' => 'Return date must be a valid date',
            'return_reason.required' => 'Return reason is required',
            'return_reason.in' => 'Selected return reason is invalid',
            'items.required' => 'At least one item is required',
            'items.min' => 'At least one item is required',
            'items.*.product_id.required' => 'Product is required for each item',
            'items.*.product_id.exists' => 'Selected product does not exist',
            'items.*.quantity.required' => 'Quantity is required for each item',
            'items.*.quantity.numeric' => 'Quantity must be a valid number',
            'items.*.quantity.min' => 'Quantity must be greater than 0',
            'items.*.unit_price.required' => 'Unit price is required for each item',
            'items.*.unit_price.numeric' => 'Unit price must be a valid number',
            'items.*.unit_price.min' => 'Unit price cannot be negative',
        ];
    }

    public function purchaseReturnData(): array
    {
        $validated = $this->validated();
        $items = array_map(function (array $item): array {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];

            return [
                'product_id' => (int) $item['product_id'],
                'product_variant_id' => filled($item['product_variant_id'] ?? null)
                    ? (int) $item['product_variant_id']
                    : null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_amount' => round($quantity * $unitPrice, 2),
            ];
        }, $validated['items']);

        $totalReturnAmount = array_reduce(
            $items,
            fn (float $carry, array $item): float => $carry + (float) $item['line_amount'],
            0.0
        );

        return [
            'branch_id' => (int) $validated['branch_id'],
            'supplier_id' => (int) $validated['supplier_id'],
            'purchase_order_id' => filled($validated['purchase_order_id'] ?? null)
                ? (int) $validated['purchase_order_id']
                : null,
            'return_date' => (string) $validated['return_date'],
            'return_reason' => (string) $validated['return_reason'],
            'description' => filled($validated['description'] ?? null)
                ? (string) $validated['description']
                : null,
            'items' => $items,
            'total_return_amount' => round($totalReturnAmount, 2),
        ];
    }
}
