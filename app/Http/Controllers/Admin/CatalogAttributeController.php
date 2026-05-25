<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogAttribute;
use App\Models\CatalogAttributeValue;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CatalogAttributeController extends Controller
{
    public function __construct()
    {
        $this->middleware('role_or_permission:Admin');
    }

    public function index()
    {
        $attributes = CatalogAttribute::query()
            ->withCount('values')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('backEnd.catalog_attribute.index', compact('attributes'));
    }

    public function create()
    {
        return view('backEnd.catalog_attribute.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_required' => 'nullable|boolean',
            'status' => 'required|boolean',
        ]);

        $slug = $this->ensureUniqueAttributeSlug(
            trim((string) ($validated['slug'] ?? '')) !== ''
                ? trim((string) $validated['slug'])
                : Str::slug((string) $validated['name'])
        );

        CatalogAttribute::query()->create([
            'name' => trim((string) $validated['name']),
            'slug' => $slug,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_required' => (bool) ($validated['is_required'] ?? false),
            'status' => (bool) $validated['status'],
        ]);

        Toastr::success('Attribute created successfully', 'Success');

        return redirect()->route('admin.catalog-attributes.index');
    }

    public function edit(int $id)
    {
        $attribute = CatalogAttribute::query()->findOrFail($id);

        return view('backEnd.catalog_attribute.edit', compact('attribute'));
    }

    public function update(Request $request, int $id)
    {
        $attribute = CatalogAttribute::query()->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('catalog_attributes', 'slug')->ignore($attribute->id),
            ],
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_required' => 'nullable|boolean',
            'status' => 'required|boolean',
        ]);

        $slugSource = trim((string) ($validated['slug'] ?? ''));
        if ($slugSource === '') {
            $slugSource = Str::slug((string) $validated['name']);
        }
        $slug = $this->ensureUniqueAttributeSlug($slugSource, $attribute->id);

        $attribute->update([
            'name' => trim((string) $validated['name']),
            'slug' => $slug,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_required' => (bool) ($validated['is_required'] ?? false),
            'status' => (bool) $validated['status'],
        ]);

        Toastr::success('Attribute updated successfully', 'Success');

        return redirect()->route('admin.catalog-attributes.index');
    }

    public function destroy(Request $request, int $id)
    {
        $attribute = CatalogAttribute::query()->findOrFail($id);
        $attribute->delete();

        Toastr::success('Attribute deleted successfully', 'Success');

        return redirect()->route('admin.catalog-attributes.index');
    }

    public function values(int $attributeId)
    {
        $attribute = CatalogAttribute::query()
            ->with(['values' => function ($query) {
                $query->orderBy('sort_order')->orderBy('value');
            }])
            ->findOrFail($attributeId);

        return view('backEnd.catalog_attribute.values', compact('attribute'));
    }

    public function valueStore(Request $request, int $attributeId)
    {
        $attribute = CatalogAttribute::query()->findOrFail($attributeId);

        $validated = $request->validate([
            'value' => 'required|string|max:150',
            'slug' => 'nullable|string|max:150|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            'meta_color_code' => 'nullable|string|max:20',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'status' => 'required|boolean',
        ]);

        $slugSource = trim((string) ($validated['slug'] ?? ''));
        if ($slugSource === '') {
            $slugSource = Str::slug((string) $validated['value']);
        }
        $slug = $this->ensureUniqueValueSlug((int) $attribute->id, $slugSource);

        $meta = $this->buildValueMeta($attribute->slug, (string) ($validated['meta_color_code'] ?? ''));

        CatalogAttributeValue::query()->create([
            'catalog_attribute_id' => (int) $attribute->id,
            'value' => trim((string) $validated['value']),
            'slug' => $slug,
            'meta' => $meta,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'status' => (bool) $validated['status'],
        ]);

        Toastr::success('Attribute value created successfully', 'Success');

        return redirect()->route('admin.catalog-attributes.values', $attribute->id);
    }

    public function valueUpdate(Request $request, int $attributeId, int $valueId)
    {
        $attribute = CatalogAttribute::query()->findOrFail($attributeId);
        $value = CatalogAttributeValue::query()
            ->where('catalog_attribute_id', $attribute->id)
            ->findOrFail($valueId);

        $validated = $request->validate([
            'value' => 'required|string|max:150',
            'slug' => [
                'nullable',
                'string',
                'max:150',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('catalog_attribute_values', 'slug')
                    ->where(fn ($query) => $query->where('catalog_attribute_id', $attribute->id))
                    ->ignore($value->id),
            ],
            'meta_color_code' => 'nullable|string|max:20',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'status' => 'required|boolean',
        ]);

        $slugSource = trim((string) ($validated['slug'] ?? ''));
        if ($slugSource === '') {
            $slugSource = Str::slug((string) $validated['value']);
        }
        $slug = $this->ensureUniqueValueSlug((int) $attribute->id, $slugSource, $value->id);

        $meta = $this->buildValueMeta($attribute->slug, (string) ($validated['meta_color_code'] ?? ''));

        $value->update([
            'value' => trim((string) $validated['value']),
            'slug' => $slug,
            'meta' => $meta,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'status' => (bool) $validated['status'],
        ]);

        Toastr::success('Attribute value updated successfully', 'Success');

        return redirect()->route('admin.catalog-attributes.values', $attribute->id);
    }

    public function valueDestroy(int $attributeId, int $valueId)
    {
        $attribute = CatalogAttribute::query()->findOrFail($attributeId);
        $value = CatalogAttributeValue::query()
            ->where('catalog_attribute_id', $attribute->id)
            ->findOrFail($valueId);

        $value->delete();
        Toastr::success('Attribute value deleted successfully', 'Success');

        return redirect()->route('admin.catalog-attributes.values', $attribute->id);
    }

    private function ensureUniqueAttributeSlug(string $slugSource, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($slugSource);
        if ($baseSlug === '') {
            $baseSlug = 'attribute';
        }

        $candidate = $baseSlug;
        $counter = 1;

        while (
            CatalogAttribute::query()
                ->when($ignoreId, fn ($query) => $query->where('id', '<>', $ignoreId))
                ->where('slug', $candidate)
                ->exists()
        ) {
            $counter++;
            $candidate = $baseSlug.'-'.$counter;
        }

        return $candidate;
    }

    private function ensureUniqueValueSlug(int $attributeId, string $slugSource, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($slugSource);
        if ($baseSlug === '') {
            $baseSlug = 'value';
        }

        $candidate = $baseSlug;
        $counter = 1;

        while (
            CatalogAttributeValue::query()
                ->where('catalog_attribute_id', $attributeId)
                ->when($ignoreId, fn ($query) => $query->where('id', '<>', $ignoreId))
                ->where('slug', $candidate)
                ->exists()
        ) {
            $counter++;
            $candidate = $baseSlug.'-'.$counter;
        }

        return $candidate;
    }

    private function buildValueMeta(string $attributeSlug, string $colorCode): ?array
    {
        if (Str::lower($attributeSlug) !== 'color') {
            return null;
        }

        $colorCode = trim($colorCode);
        if ($colorCode === '') {
            return null;
        }

        return [
            'color_code' => $colorCode,
        ];
    }
}
