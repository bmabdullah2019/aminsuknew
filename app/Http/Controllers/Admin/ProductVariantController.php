<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogAttribute;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantAttributeValue;
use App\Models\VariantImage;
use App\Services\VariantAttributeService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductVariantController extends Controller
{
    public function __construct(private readonly VariantAttributeService $variantAttributeService)
    {
        $this->middleware('role_or_permission:Admin');
    }

    public function edit(int $productId)
    {
        $product = Product::query()
            ->with(['image'])
            ->findOrFail($productId);

        $attributes = CatalogAttribute::query()
            ->active()
            ->with(['values' => function ($query) {
                $query->active()->orderBy('sort_order')->orderBy('value');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $variants = ProductVariant::query()
            ->where('product_id', $product->id)
            ->with(['variantAttributeValues.attribute', 'variantAttributeValues.value', 'variantImages'])
            ->orderByDesc('id')
            ->get()
            ->map(function (ProductVariant $variant) {
                $valueIds = $variant->variantAttributeValues
                    ->pluck('catalog_attribute_value_id')
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $id > 0)
                    ->values()
                    ->all();

                $primaryPath = (string) ($this->resolveVariantPrimaryImagePath($variant) ?? '');
                if (\Illuminate\Support\Str::startsWith($primaryPath, 'storage/')) {
                    $primaryPath = 'public/' . $primaryPath;
                }
                return [
                    'id' => (int) $variant->id,
                    'sku_code' => (string) ($variant->sku_code ?? ''),
                    'price' => (float) ($variant->price ?? 0),
                    'cost_price' => (float) ($variant->cost_price ?? 0),
                    'status' => (string) ($variant->status ?? 'active'),
                    'image' => (string) ($this->resolveVariantPrimaryImagePath($variant) ?? ''),
                    'image_url' => ($primaryPath !== '')
                        ? asset($primaryPath)
                        : '',
                    'attribute_value_ids' => $valueIds,
                    'label' => $this->variantAttributeService->formatVariantLabel($variant),
                ];
            })
            ->values();

        $selectedAttributeIds = $variants
            ->flatMap(fn ($row) => collect($row['attribute_value_ids']))
            ->map(function ($valueId) {
                return DB::table('catalog_attribute_values')
                    ->where('id', (int) $valueId)
                    ->value('catalog_attribute_id');
            })
            ->filter(fn ($attributeId) => ! is_null($attributeId))
            ->map(fn ($attributeId) => (int) $attributeId)
            ->unique()
            ->values()
            ->all();

        return view('backEnd.product.variants', compact(
            'product',
            'attributes',
            'variants',
            'selectedAttributeIds'
        ));
    }

    public function update(Request $request, int $productId)
    {
        $product = Product::query()->findOrFail($productId);

        $validated = $request->validate([
            'selected_attribute_ids' => 'required|array|min:1',
            'selected_attribute_ids.*' => 'required|integer|exists:catalog_attributes,id',
            'variants' => 'required|array|min:1',
            'variants.*.id' => 'nullable|integer',
            'variants.*.sku_code' => 'required|string|max:100',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.cost_price' => 'nullable|numeric|min:0',
            'variants.*.status' => 'required|in:active,inactive',
            'variants.*.attribute_value_map' => 'required|array|min:1',
            'variants.*.attribute_value_map.*' => 'nullable|integer|exists:catalog_attribute_values,id',
            'variants.*.images' => 'nullable|array',
            'variants.*.images.*' => 'nullable|image|max:4096',
        ]);

        $selectedAttributeIds = collect($validated['selected_attribute_ids'])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($selectedAttributeIds)) {
            return back()
                ->withErrors(['selected_attribute_ids' => 'At least one attribute is required.'])
                ->withInput();
        }

        $existingVariantIds = ProductVariant::query()
            ->where('product_id', $product->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $requestCombinationKeys = [];
        $requestSkuCodes = [];

        DB::transaction(function () use (
            $validated,
            $product,
            $selectedAttributeIds,
            $existingVariantIds,
            &$requestCombinationKeys,
            &$requestSkuCodes
        ) {
            foreach ($validated['variants'] as $index => $row) {
                $variantId = isset($row['id']) ? (int) $row['id'] : 0;
                if ($variantId > 0 && ! in_array($variantId, $existingVariantIds, true)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "variants.{$index}.id" => 'Invalid variant row.',
                    ]);
                }

                $skuCode = Str::upper(trim((string) $row['sku_code']));
                if ($skuCode === '') {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "variants.{$index}.sku_code" => 'SKU is required.',
                    ]);
                }

                $skuRequestKey = Str::lower($skuCode);
                if (in_array($skuRequestKey, $requestSkuCodes, true)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "variants.{$index}.sku_code" => 'Duplicate SKU in request payload.',
                    ]);
                }
                $requestSkuCodes[] = $skuRequestKey;

                $skuConflict = ProductVariant::query()
                    ->where('sku_code', $skuCode)
                    ->when($variantId > 0, fn ($query) => $query->where('id', '<>', $variantId))
                    ->exists();

                if ($skuConflict) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "variants.{$index}.sku_code" => 'SKU already exists.',
                    ]);
                }

                $attributeValueMap = collect($row['attribute_value_map'] ?? [])
                    ->mapWithKeys(fn ($valueId, $attributeId) => [(int) $attributeId => (int) $valueId]);

                $normalizedValueIds = [];
                foreach ($selectedAttributeIds as $attributeId) {
                    $selectedValueId = (int) ($attributeValueMap[$attributeId] ?? 0);
                    if ($selectedValueId <= 0) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            "variants.{$index}.attribute_value_map.{$attributeId}" => 'Select value for every chosen attribute.',
                        ]);
                    }
                    $normalizedValueIds[] = $selectedValueId;
                }

                $normalizedValueIds = $this->variantAttributeService->normalizeValueIds($normalizedValueIds);
                $valueRows = $this->variantAttributeService->getValueRowsByIds($normalizedValueIds);

                if ($valueRows->count() !== count($normalizedValueIds)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "variants.{$index}.attribute_value_map" => 'One or more selected values are invalid.',
                    ]);
                }

                $rowAttributeIds = $valueRows->pluck('attribute_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
                sort($rowAttributeIds);
                $expectedAttributeIds = $selectedAttributeIds;
                sort($expectedAttributeIds);

                if ($rowAttributeIds !== $expectedAttributeIds) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "variants.{$index}.attribute_value_map" => 'Selected values must match the selected attributes.',
                    ]);
                }

                $combinationKey = $this->variantAttributeService->buildCombinationKey($normalizedValueIds);
                if (in_array($combinationKey, $requestCombinationKeys, true)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "variants.{$index}.attribute_value_map" => 'Duplicate variant combination in request payload.',
                    ]);
                }
                $requestCombinationKeys[] = $combinationKey;

                $combinationConflict = ProductVariant::query()
                    ->where('product_id', $product->id)
                    ->where('combination_key', $combinationKey)
                    ->when($variantId > 0, fn ($query) => $query->where('id', '<>', $variantId))
                    ->exists();

                if ($combinationConflict) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "variants.{$index}.attribute_value_map" => 'This variant combination already exists.',
                    ]);
                }

                $axes = $this->variantAttributeService->resolveLegacyAxesFromValueRows($valueRows);

                $variant = $variantId > 0
                    ? ProductVariant::query()->whereKey($variantId)->firstOrFail()
                    : new ProductVariant;

                $variant->product_id = $product->id;
                $variant->sku_code = $skuCode;
                $variant->combination_key = $combinationKey;
                $variant->price = (float) ($row['price'] ?? 0);
                $variant->cost_price = (float) ($row['cost_price'] ?? 0);
                $variant->status = (string) $row['status'];
                $variant->color = $axes['color'];
                $variant->size = $axes['size'];
                $variant->age = $axes['age'];

                $variant->save();
                $this->syncVariantImages($variant, $row);

                ProductVariantAttributeValue::query()
                    ->where('product_variant_id', $variant->id)
                    ->delete();

                foreach ($valueRows as $valueRow) {
                    ProductVariantAttributeValue::query()->create([
                        'product_variant_id' => (int) $variant->id,
                        'catalog_attribute_id' => (int) $valueRow->attribute_id,
                        'catalog_attribute_value_id' => (int) $valueRow->value_id,
                    ]);
                }
            }

            $hasActiveVariant = ProductVariant::query()
                ->where('product_id', $product->id)
                ->where('status', 'active')
                ->exists();

            $product->has_variant = $hasActiveVariant;
            $product->save();
        });

        Toastr::success('Product variants updated successfully', 'Success');

        return redirect()->route('admin.products.variants.edit', $product->id);
    }

    private function syncVariantImages(ProductVariant $variant, array $row): void
    {
        $uploadedImages = $row['images'] ?? [];
        if (! is_array($uploadedImages) || empty($uploadedImages)) {
            if (! $variant->variantImages()->exists() && ! empty($variant->image)) {
                $this->materializeLegacyImageAsVariantImage($variant);
            }

            return;
        }

        $currentMaxSortOrder = (int) $variant->variantImages()->max('sort_order');
        $hasPrimary = $variant->variantImages()->where('is_primary', true)->exists();

        foreach ($uploadedImages as $uploadedImage) {
            if (! $uploadedImage || ! $uploadedImage->isValid()) {
                continue;
            }

            $storedPath = $uploadedImage->store('products/variants', 'public');
            $this->syncStorageFile($storedPath);
            $currentMaxSortOrder++;

            VariantImage::query()->create([
                'product_variant_id' => $variant->id,
                'image_path' => $storedPath,
                'is_primary' => ! $hasPrimary,
                'sort_order' => $currentMaxSortOrder,
            ]);

            if (! $hasPrimary) {
                $hasPrimary = true;
            }
        }

        $this->enforceSinglePrimaryImage($variant);
        $variant->image = $this->resolveVariantPrimaryImagePath($variant) ?? '';
        $variant->save();
    }

    private function materializeLegacyImageAsVariantImage(ProductVariant $variant): void
    {
        $normalizedPath = $this->normalizeStoragePath((string) $variant->image);
        if ($normalizedPath === '') {
            return;
        }

        VariantImage::query()->create([
            'product_variant_id' => $variant->id,
            'image_path' => $normalizedPath,
            'is_primary' => true,
            'sort_order' => 1,
        ]);
    }

    private function enforceSinglePrimaryImage(ProductVariant $variant): void
    {
        $images = $variant->variantImages()->orderBy('sort_order')->orderBy('id')->get();
        if ($images->isEmpty()) {
            return;
        }

        $primaryImage = $images->firstWhere('is_primary', true) ?? $images->first();
        VariantImage::query()
            ->where('product_variant_id', $variant->id)
            ->where('id', '<>', $primaryImage->id)
            ->where('is_primary', true)
            ->update(['is_primary' => false]);

        if (! $primaryImage->is_primary) {
            $primaryImage->is_primary = true;
            $primaryImage->save();
        }
    }

    private function resolveVariantPrimaryImagePath(ProductVariant $variant): ?string
    {
        $variant->loadMissing(['primaryVariantImage', 'variantImages']);
        $primaryPath = $variant->primaryVariantImage?->image_path
            ?? $variant->variantImages->first()?->image_path
            ?? $variant->image;

        $normalizedPath = $this->normalizeStoragePath((string) $primaryPath);

        return $normalizedPath !== '' ? $normalizedPath : null;
    }

    private function normalizeStoragePath(string $path): string
    {
        $normalized = ltrim(str_replace('\\', '/', trim($path)), '/');
        if ($normalized === '') {
            return '';
        }

        if (Str::startsWith($normalized, 'storage/')) {
            return $normalized;
        }

        if (Str::startsWith($normalized, 'public/')) {
            $normalized = Str::after($normalized, 'public/');
        }

        if (Storage::disk('public')->exists($normalized)) {
            return 'storage/'.$normalized;
        }

        if (Storage::disk('public')->exists('products/variants/'.$normalized)) {
            return 'storage/products/variants/'.$normalized;
        }

        return Str::startsWith($normalized, 'products/') ? 'storage/'.$normalized : '';
    }

    private function syncStorageFile(string $storagePath): void
    {
        try {
            $sourceFile = storage_path('app/public/'.$storagePath);
            $publicFile = base_path('public/storage/'.$storagePath);

            if (! file_exists($sourceFile) || file_exists($publicFile)) {
                return;
            }

            $publicDir = dirname($publicFile);
            if (! is_dir($publicDir)) {
                @mkdir($publicDir, 0755, true);
            }

            @copy($sourceFile, $publicFile);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
