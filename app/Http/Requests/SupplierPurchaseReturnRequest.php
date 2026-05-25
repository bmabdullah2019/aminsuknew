<?php

namespace App\Http\Requests;

use App\Models\PurchaseItem;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

abstract class SupplierPurchaseReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'return_date' => ['required', 'date', 'before_or_equal:today'],
            'total_amount' => ['required', 'numeric', 'min:0.01'],
            'return_reason' => ['nullable', 'required_without:reason', 'in:damaged,wrong_item,quality_issue,over_supply,other,damaged_goods,over_supplied,expired'],
            'reason' => ['nullable', 'required_without:return_reason', 'in:damaged,wrong_item,quality_issue,over_supply,other,damaged_goods,over_supplied,expired'],
            'original_purchase_id' => $this->originalPurchaseRules(),
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'in:draft,approved,completed'],
        ];

        if ($this->supportsItemizedReturns()) {
            $rules = array_merge($rules, [
                'items' => ['required', 'array', 'min:1'],
                'items.*.purchase_item_id' => ['required', 'integer', 'distinct', 'exists:purchase_items,id'],
                'items.*.warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
                'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
                'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
                'items.*.notes' => ['nullable', 'string', 'max:500'],
            ]);
        }

        return array_merge($this->supplierIdentifierRules(), $rules);
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $supplier = $this->supplier();
                if (! $supplier) {
                    return;
                }

                $originalPurchaseId = (int) ($this->validated('original_purchase_id') ?? 0);
                if ($originalPurchaseId > 0 && Schema::hasTable('purchase_orders')) {
                    $belongsToSupplier = PurchaseOrder::query()
                        ->where('id', $originalPurchaseId)
                        ->where('supplier_id', $supplier->id)
                        ->exists();

                    if (! $belongsToSupplier) {
                        $validator->errors()->add('original_purchase_id', 'Selected purchase does not belong to this supplier.');

                        return;
                    }
                }

                if (! $this->supportsItemizedReturns()) {
                    return;
                }

                $items = $this->validated('items', []);
                if (! is_array($items) || empty($items)) {
                    return;
                }

                $purchaseItemIds = collect($items)
                    ->pluck('purchase_item_id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                if ($purchaseItemIds->isEmpty()) {
                    return;
                }

                $itemQuery = PurchaseItem::query()
                    ->whereIn('id', $purchaseItemIds->all())
                    ->whereHas('purchaseOrder', function ($query) use ($supplier) {
                        $query->where('supplier_id', $supplier->id);
                    });

                if ($originalPurchaseId > 0) {
                    $itemQuery->where('purchase_order_id', $originalPurchaseId);
                }

                $matchedCount = (int) $itemQuery->count();
                if ($matchedCount !== $purchaseItemIds->count()) {
                    $validator->errors()->add('items', 'One or more selected items are invalid for this supplier/purchase.');
                }
            },
        ];
    }

    public function purchaseReturnData(): array
    {
        $validated = $this->validated();

        if ($this->supportsItemizedReturns()) {
            $lineTotal = round((float) collect($validated['items'] ?? [])->sum(function ($row) {
                $quantity = (float) ($row['quantity'] ?? 0);
                $unitCost = (float) ($row['unit_cost'] ?? 0);

                return max(0, $quantity) * max(0, $unitCost);
            }), 2);

            if ($lineTotal > 0) {
                $validated['total_amount'] = $lineTotal;
            }
        }

        return $validated;
    }

    public function supplier(): ?Supplier
    {
        $supplier = $this->route('supplier');
        if ($supplier instanceof Supplier) {
            return $supplier;
        }

        $supplierId = (int) ($this->input('supplier_id') ?? 0);
        if ($supplierId <= 0) {
            return null;
        }

        return Supplier::query()->find($supplierId);
    }

    protected function supplierIdentifierRules(): array
    {
        return [];
    }

    protected function originalPurchaseRules(): array
    {
        $rules = ['nullable', 'integer'];

        if (Schema::hasTable('purchase_orders')) {
            $rules[] = 'exists:purchase_orders,id';
        }

        return $rules;
    }

    protected function supportsItemizedReturns(): bool
    {
        return Schema::hasTable('supplier_purchase_return_items')
            && Schema::hasTable('purchase_items')
            && Schema::hasTable('warehouses');
    }
}
