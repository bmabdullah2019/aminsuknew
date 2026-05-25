<?php

namespace App\Services;

use App\Models\CatalogAttributeValue;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\WarehouseStock;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class VariantAttributeService
{
    public function normalizeValueIds(array $valueIds): array
    {
        $normalized = collect($valueIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        sort($normalized);

        return $normalized;
    }

    public function buildCombinationKey(array $normalizedValueIds): string
    {
        if (empty($normalizedValueIds)) {
            return sha1('variant-empty');
        }

        return sha1(implode('-', $normalizedValueIds));
    }

    /**
     * @return array{color:string|null,size:string|null,age:string|null}
     */
    public function resolveLegacyAxesFromValueRows(Collection $valueRows): array
    {
        $axes = [
            'color' => null,
            'size' => null,
            'age' => null,
        ];

        foreach ($valueRows as $row) {
            $slug = Str::lower(trim((string) ($row->attribute_slug ?? '')));
            $value = trim((string) ($row->value ?? ''));
            if (! array_key_exists($slug, $axes) || $value === '') {
                continue;
            }
            $axes[$slug] = $value;
        }

        return $axes;
    }

    public function formatVariantLabel(ProductVariant $variant): string
    {
        $valueRows = $variant->dynamicAttributeCollection()->map(function ($row) {
            return (object) [
                'attribute_name' => (string) ($row->attribute->name ?? ''),
                'attribute_slug' => (string) ($row->attribute->slug ?? ''),
                'value' => (string) ($row->value->value ?? ''),
            ];
        });

        if ($valueRows->isNotEmpty()) {
            return $valueRows->pluck('value')->implode(' / ');
        }

        $parts = array_filter([
            trim((string) ($variant->color ?? '')),
            trim((string) ($variant->size ?? '')),
            trim((string) ($variant->age ?? '')),
        ]);

        return empty($parts) ? ('Variant #'.$variant->id) : implode(' / ', $parts);
    }

    public function getValueRowsByIds(array $valueIds): Collection
    {
        $normalizedIds = $this->normalizeValueIds($valueIds);
        if (empty($normalizedIds)) {
            return collect();
        }

        return CatalogAttributeValue::query()
            ->whereIn('catalog_attribute_values.id', $normalizedIds)
            ->join('catalog_attributes', 'catalog_attributes.id', '=', 'catalog_attribute_values.catalog_attribute_id')
            ->where('catalog_attributes.status', true)
            ->where('catalog_attribute_values.status', true)
            ->orderBy('catalog_attributes.sort_order')
            ->orderBy('catalog_attribute_values.sort_order')
            ->get([
                'catalog_attribute_values.id as value_id',
                'catalog_attribute_values.catalog_attribute_id as attribute_id',
                'catalog_attribute_values.value',
                'catalog_attribute_values.meta',
                'catalog_attributes.name as attribute_name',
                'catalog_attributes.slug as attribute_slug',
            ]);
    }

    public function buildProductVariantPayload(Product $product, ?int $warehouseId = null): array
    {
        $variants = ProductVariant::query()
            ->where('product_id', (int) $product->id)
            ->where(function ($query) {
                $query->where('status', 'active')
                    ->orWhere('status', 1)
                    ->orWhere('status', '1');
            })
            ->with([
                'variantAttributeValues.attribute',
                'variantAttributeValues.value',
                'primaryVariantImage',
                'variantImages',
            ])
            ->orderBy('id')
            ->get();

        if ($variants->isEmpty()) {
            return [
                'has_variant' => false,
                'has_dynamic_attributes' => false,
                'attribute_groups' => [],
                'variants' => [],
            ];
        }

        $variantIds = $variants->pluck('id')->map(fn ($id) => (int) $id)->all();

        // 1. Get stock from inventories table (Optionally filtered by warehouse)
        $inventoryQuery = Inventory::query()->whereIn('product_variant_id', $variantIds);
        if ($warehouseId) {
            $inventoryQuery->where('warehouse_id', $warehouseId);
        }

        $sellableStockByVariantId = $inventoryQuery
            ->selectRaw('product_variant_id, COALESCE(SUM(CASE WHEN (quantity_available - quantity_reserved) > 0 THEN (quantity_available - quantity_reserved) ELSE 0 END), 0) AS sellable_stock')
            ->groupBy('product_variant_id')
            ->pluck('sellable_stock', 'product_variant_id')
            ->map(fn ($stock) => (float) $stock);

        // 2. For variants with no inventory records, fall back to warehouse_stock data
        $variantsWithoutInventory = array_diff($variantIds, array_keys($sellableStockByVariantId->all()));
        if (! empty($variantsWithoutInventory)) {
            $warehouseStockQuery = WarehouseStock::query()->whereIn('product_variant_id', $variantsWithoutInventory);
            if ($warehouseId) {
                $warehouseStockQuery->where('warehouse_id', $warehouseId);
            }

            $warehouseStockFallback = $warehouseStockQuery
                ->selectRaw('product_variant_id, COALESCE(SUM(CASE WHEN (physical_quantity - reserved_quantity) > 0 THEN (physical_quantity - reserved_quantity) ELSE 0 END), 0) AS sellable_stock')
                ->groupBy('product_variant_id')
                ->pluck('sellable_stock', 'product_variant_id')
                ->map(fn ($stock) => (float) $stock);

            // Use union (not merge) so integer variant_id keys are preserved.
            // array_merge reindexes numeric keys, which would corrupt the lookup map.
            $sellableStockByVariantId = $sellableStockByVariantId->union($warehouseStockFallback);
        }

        // Single-warehouse view: product create writes both `inventories` and `warehouse_stock`; rows can drift.
        // Take the higher sellable figure so PDP matches checkout reservation (which reads both tables).
        if ($warehouseId) {
            $warehouseLedgerSellable = WarehouseStock::query()
                ->where('warehouse_id', $warehouseId)
                ->whereIn('product_variant_id', $variantIds)
                ->whereNotNull('product_variant_id')
                ->groupBy('product_variant_id')
                ->selectRaw('product_variant_id')
                ->selectRaw('COALESCE(SUM(CASE WHEN (physical_quantity - reserved_quantity) > 0 THEN (physical_quantity - reserved_quantity) ELSE 0 END), 0) AS sellable_stock')
                ->pluck('sellable_stock', 'product_variant_id')
                ->map(fn ($stock) => (float) $stock);

            foreach ($variantIds as $variantId) {
                $invSellable = (float) ($sellableStockByVariantId[$variantId] ?? 0);
                $wsSellable = (float) ($warehouseLedgerSellable[$variantId] ?? 0);
                $sellableStockByVariantId[$variantId] = max($invSellable, $wsSellable);
            }
        }

        $attributeGroups = [];
        $hasDynamicAttributes = false;

        $variantRows = $variants->map(function (ProductVariant $variant) use (
            &$attributeGroups,
            &$hasDynamicAttributes,
            $sellableStockByVariantId,
            $product
        ) {
            $dynamicRows = $variant->dynamicAttributeCollection()->map(function ($row) {
                return [
                    'attribute_id' => (int) ($row->catalog_attribute_id ?? 0),
                    'attribute_name' => (string) ($row->attribute->name ?? ''),
                    'attribute_slug' => Str::lower(trim((string) ($row->attribute->slug ?? ''))),
                    'value_id' => (int) ($row->catalog_attribute_value_id ?? 0),
                    'value' => (string) ($row->value->value ?? ''),
                    'meta' => is_array($row->value->meta ?? null) ? $row->value->meta : null,
                    'attribute_sort_order' => (int) ($row->attribute->sort_order ?? 0),
                    'value_sort_order' => (int) ($row->value->sort_order ?? 0),
                ];
            })->values();

            $attributeMap = [];
            foreach ($dynamicRows as $row) {
                if (! empty($row['attribute_slug']) && ! empty($row['value'])) {
                    $attributeMap[$row['attribute_slug']] = $row['value'];
                }

                if ($row['attribute_id'] > 0 && $row['value_id'] > 0) {
                    $hasDynamicAttributes = true;
                    $attributeGroups[$row['attribute_id']]['attribute_id'] = $row['attribute_id'];
                    $attributeGroups[$row['attribute_id']]['attribute_name'] = $row['attribute_name'];
                    $attributeGroups[$row['attribute_id']]['attribute_slug'] = $row['attribute_slug'];
                    $attributeGroups[$row['attribute_id']]['sort_order'] = $row['attribute_sort_order'];
                    $attributeGroups[$row['attribute_id']]['values'][$row['value_id']] = [
                        'value_id' => $row['value_id'],
                        'value' => $row['value'],
                        'meta' => $row['meta'],
                        'sort_order' => $row['value_sort_order'],
                    ];
                }
            }

            if (! isset($attributeMap['color']) && trim((string) ($variant->color ?? '')) !== '') {
                $val = trim((string) $variant->color);
                $attributeMap['color'] = $val;

                // Add to groups for UI rendering if not already handled by dynamic system
                $hasDynamicAttributes = true;
                $attributeGroups['color_legacy']['attribute_id'] = 'color_legacy';
                $attributeGroups['color_legacy']['attribute_name'] = 'Color';
                $attributeGroups['color_legacy']['attribute_slug'] = 'color';
                $attributeGroups['color_legacy']['values'][$val] = [
                    'value_id' => $val,
                    'value' => $val,
                    'sort_order' => 0,
                ];
                $dynamicRows->push([
                    'attribute_id' => 'color_legacy',
                    'attribute_name' => 'Color',
                    'attribute_slug' => 'color',
                    'value_id' => $val,
                    'value' => $val,
                    'meta' => null,
                    'attribute_sort_order' => 0,
                    'value_sort_order' => 0,
                ]);
            }
            if (! isset($attributeMap['size']) && trim((string) ($variant->size ?? '')) !== '') {
                $val = trim((string) $variant->size);
                $attributeMap['size'] = $val;

                $hasDynamicAttributes = true;
                $attributeGroups['size_legacy']['attribute_id'] = 'size_legacy';
                $attributeGroups['size_legacy']['attribute_name'] = 'Size';
                $attributeGroups['size_legacy']['attribute_slug'] = 'size';
                $attributeGroups['size_legacy']['values'][$val] = [
                    'value_id' => $val,
                    'value' => $val,
                    'sort_order' => 0,
                ];
                $dynamicRows->push([
                    'attribute_id' => 'size_legacy',
                    'attribute_name' => 'Size',
                    'attribute_slug' => 'size',
                    'value_id' => $val,
                    'value' => $val,
                    'meta' => null,
                    'attribute_sort_order' => 0,
                    'value_sort_order' => 0,
                ]);
            }
            if (! isset($attributeMap['age']) && trim((string) ($variant->age ?? '')) !== '') {
                $val = trim((string) $variant->age);
                $attributeMap['age'] = $val;

                $hasDynamicAttributes = true;
                $attributeGroups['age_legacy']['attribute_id'] = 'age_legacy';
                $attributeGroups['age_legacy']['attribute_name'] = 'Age';
                $attributeGroups['age_legacy']['attribute_slug'] = 'age';
                $attributeGroups['age_legacy']['values'][$val] = [
                    'value_id' => $val,
                    'value' => $val,
                    'sort_order' => 0,
                ];
                $dynamicRows->push([
                    'attribute_id' => 'age_legacy',
                    'attribute_name' => 'Age',
                    'attribute_slug' => 'age',
                    'value_id' => $val,
                    'value' => $val,
                    'meta' => null,
                    'attribute_sort_order' => 0,
                    'value_sort_order' => 0,
                ]);
            }

            $variantImage = trim((string) (optional($variant->primaryVariantImage)->image_path ?? ''));
            if ($variantImage === '') {
                $variantImage = trim((string) (optional($variant->variantImages->first())->image_path ?? ''));
            }
            if ($variantImage === '') {
                $variantImage = trim((string) ($variant->image ?? ''));
            }

            // Fallback to product level image if variant has nothing specific
            if ($variantImage === '') {
                $productImage = $product->image;
                if (! $productImage && $product->relationLoaded('images')) {
                    $productImage = $product->images->first();
                }
                $variantImage = trim((string) ($productImage->image ?? ''));
            }

            if ($variantImage !== '' && ! Str::startsWith($variantImage, ['http://', 'https://', 'data:', 'storage/', 'public/'])) {
                // If it doesn't start with any standard prefix, it's likely a storage path
                $variantImage = 'storage/'.ltrim($variantImage, '/');
            }

            $variantImage = $this->normalizePublicAssetPath($variantImage);

            return [
                'id' => (int) $variant->id,
                'product_id' => (int) $variant->product_id,
                'sku_code' => (string) ($variant->sku_code ?? ''),
                'price' => (float) ($variant->price ?? 0),
                'cost_price' => (float) ($variant->cost_price ?? 0),
                'image' => $variantImage,
                'sellable_stock' => (float) ($sellableStockByVariantId[(int) $variant->id] ?? 0),
                'label' => $this->formatVariantLabel($variant),
                'color' => (string) ($attributeMap['color'] ?? ''),
                'size' => (string) ($attributeMap['size'] ?? ''),
                'age' => (string) ($attributeMap['age'] ?? ''),
                'attributes' => $attributeMap,
                'attribute_values' => $dynamicRows,
            ];
        })->values();

        $normalizedAttributeGroups = collect($attributeGroups)
            ->sortBy(fn ($group) => (int) ($group['sort_order'] ?? 0))
            ->values()
            ->map(function ($group) {
                $values = collect($group['values'] ?? [])
                    ->sortBy(fn ($value) => (int) ($value['sort_order'] ?? 0))
                    ->values()
                    ->map(fn ($value) => [
                        'value_id' => $value['value_id'],
                        'value' => (string) $value['value'],
                        'meta' => $value['meta'] ?? null,
                    ])
                    ->all();

                return [
                    'attribute_id' => $group['attribute_id'] ?? 0,
                    'attribute_name' => (string) ($group['attribute_name'] ?? ''),
                    'attribute_slug' => (string) ($group['attribute_slug'] ?? ''),
                    'values' => $values,
                ];
            })
            ->groupBy(function ($group) {
                $slug = Str::lower(trim((string) ($group['attribute_slug'] ?? '')));

                return $slug !== '' ? 'slug:'.$slug : 'id:'.(string) ($group['attribute_id'] ?? '');
            })
            ->map(function (Collection $groups) {
                $firstGroup = $groups->first();
                $values = $groups
                    ->flatMap(fn ($group) => $group['values'] ?? [])
                    ->filter(fn ($value) => trim((string) ($value['value'] ?? '')) !== '')
                    ->unique(fn ($value) => Str::lower(trim((string) ($value['value'] ?? ''))))
                    ->values()
                    ->all();

                return [
                    'attribute_id' => $firstGroup['attribute_id'] ?? 0,
                    'attribute_name' => (string) ($firstGroup['attribute_name'] ?? ''),
                    'attribute_slug' => (string) ($firstGroup['attribute_slug'] ?? ''),
                    'values' => $values,
                ];
            })
            ->values()
            ->all();

        return [
            'has_variant' => true,
            'has_dynamic_attributes' => $hasDynamicAttributes,
            'attribute_groups' => $normalizedAttributeGroups,
            'variants' => $variantRows->all(),
        ];
    }

    private function normalizePublicAssetPath(string $path): string
    {
        $normalized = ltrim(str_replace('\\', '/', trim($path)), '/');
        if ($normalized === '') {
            return '';
        }

        if (Str::startsWith($normalized, ['http://', 'https://', 'data:', 'public/'])) {
            return $normalized;
        }

        if (Str::startsWith($normalized, ['storage/', 'uploads/'])) {
            return 'public/'.$normalized;
        }

        return $normalized;
    }
}
