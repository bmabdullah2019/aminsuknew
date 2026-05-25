@extends('backEnd.layouts.master')
@section('title', 'Product Edit')
@section('css')
<link href="{{ asset('public/backEnd/assets/libs/select2/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ asset('public/backEnd/assets/libs/summernote/summernote-lite.min.css') }}" rel="stylesheet" type="text/css" />
<style>
  :root {
    --product-card-border: #d6e4ff;
    --product-card-bg: #f7fbff;
    --variant-card-bg: #f8fafc;
    --variant-card-border: #dbeafe;
    --variant-accent: #0b6bcb;
    --field-height: 48px;
    --field-padding: 12px 16px;
    --field-font: 1rem;
    --label-weight: 600;
    --label-color: #1e3a5f;
    --input-radius: 10px;
  }

  /* Legacy edit styles preservation */
  .increment_btn, .remove_btn, .btn-warning {
    margin-top: -17px;
    margin-bottom: 10px;
  }

  body.wc-admin-shell .product-section-card {
    border: 1px solid var(--product-card-border);
    box-shadow: 0 8px 20px rgba(11, 107, 203, 0.08);
  }

  body.wc-admin-shell .product-section-card .card-header {
    background: linear-gradient(90deg, var(--product-card-bg) 0%, #ffffff 100%);
    border-bottom: 1px solid var(--product-card-border);
    padding: 1.25rem 1.5rem;
  }

  body.wc-admin-shell .variant-card {
    background: var(--variant-card-bg);
    border: 1px solid var(--variant-card-border);
    border-radius: 12px;
    padding: 1.25rem;
  }

  body.wc-admin-shell .variant-builder-grid {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 0.7rem;
    align-items: end;
  }

  body.wc-admin-shell .variant-grid-item { min-width: 0; }
  body.wc-admin-shell .variant-field-color { grid-column: span 3; }
  body.wc-admin-shell .variant-field-size,
  body.wc-admin-shell .variant-field-age,
  body.wc-admin-shell .variant-field-sku { grid-column: span 2; }
  body.wc-admin-shell .variant-field-price { grid-column: span 1; }
  body.wc-admin-shell .variant-field-add { grid-column: span 1; }
  body.wc-admin-shell .variant-field-images { grid-column: 1 / -1; }

  body.wc-admin-shell .variant-card-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.9rem;
  }

  body.wc-admin-shell .variant-tile {
    border: 1px solid #d8e2f5;
    border-radius: 12px;
    background: #fff;
    padding: 0.85rem;
    box-shadow: 0 2px 8px rgba(26, 61, 112, 0.05);
    position: relative;
  }

  body.wc-admin-shell .variant-tile-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.6rem;
    margin-bottom: 0.65rem;
  }

  body.wc-admin-shell .variant-badge {
    background: #edf4ff;
    color: #23436d;
    border: 1px solid #d5e2f5;
    border-radius: 999px;
    padding: 0.28rem 0.58rem;
    font-size: 0.76rem;
    font-weight: 700;
  }

  body.wc-admin-shell .variant-tile-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.55rem 0.75rem;
    margin-bottom: 0.7rem;
  }

  body.wc-admin-shell .variant-tile-field {
    border: 1px solid #e6edf9;
    border-radius: 8px;
    background: #f9fbff;
    padding: 0.45rem 0.55rem;
  }

  body.wc-admin-shell .variant-tile-label {
    display: block;
    color: #627a9a;
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    margin-bottom: 0.14rem;
  }

  body.wc-admin-shell .variant-tile-value {
    color: #233c5f;
    font-size: 0.86rem;
    font-weight: 600;
  }

  body.wc-admin-shell .image-preview-card {
    position: relative;
    width: 80px;
    height: 80px;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid #d6e4ff;
    flex-shrink: 0;
  }

  body.wc-admin-shell .image-preview-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  body.wc-admin-shell .variant-value-picker {
    border: 1px solid #e2eaf6;
    border-radius: 8px;
    background: #fff;
    padding: 0.5rem 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
  }

  body.wc-admin-shell .variant-value-picker-head {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
  }

  body.wc-admin-shell .variant-value-picker-title {
    font-size: 0.84rem;
    font-weight: 700;
    color: #1f3c62;
  }

  body.wc-admin-shell .variant-value-options {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    flex: 1;
  }

  body.wc-admin-shell .variant-value-option {
    border: 1px solid #dce7f7;
    border-radius: 6px;
    background: #f9fbff;
    padding: 0.3rem 0.5rem;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    color: #314a70;
    font-size: 0.82rem;
    font-weight: 600;
  }

  body.wc-admin-shell .variant-attribute-toggle,
  body.wc-admin-shell .variant-builder-value-check,
  body.wc-admin-shell .variant-builder-all-values {
    position: static !important;
    display: inline-block;
    flex: 0 0 18px;
    width: 18px;
    height: 18px;
    min-width: 18px;
    margin: 0 !important;
    vertical-align: middle;
    cursor: pointer;
  }

  body.wc-admin-shell .variant-builder-all-values {
    flex-basis: 16px;
    width: 16px;
    height: 16px;
    min-width: 16px;
  }

  body.wc-admin-shell .image-preview-card .remove-btn {
    position: absolute;
    top: 2px;
    right: 2px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: rgba(220, 53, 69, 0.9);
    color: white;
    border: none;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
  }

  body.wc-admin-shell .wc-type-switch {
    border: 1px solid #d5e2f6;
    border-radius: 10px;
    background: #f8fbff;
    padding: 0.8rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
  }

  body.wc-admin-shell .wc-type-switch .form-check-label {
    font-weight: 700;
    color: #314a70;
    cursor: pointer;
  }

  .variant-builder-preview, .variant-images-preview {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 10px;
  }

  .required-star { color: #dc3545; font-weight: 700; }
  
  .wc-compact-card {
    border: 1px solid #dbe5f5;
    border-radius: 14px;
    box-shadow: 0 8px 22px rgba(18, 56, 103, 0.08);
    overflow: hidden;
  }

  body.wc-admin-shell .wc-status-switch .form-group {
    border: 1px solid #e8eef9;
    border-radius: 8px;
    background: #fff;
    min-height: 44px;
    padding: 0.5rem 0.65rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
  }

  body.wc-admin-shell .wc-status-switch .form-group > label {
    margin-bottom: 0;
    font-size: 0.84rem;
    font-weight: 600;
    color: #3a5174;
  }
</style>
@endsection

@section('content')
@php
  $resolvedEditProductType = old('product_type', $edit_data->has_variant ? 'variable' : 'simple');
  $resolvedEditProductType = strtolower((string) $resolvedEditProductType);
  $resolvedEditProductType = in_array($resolvedEditProductType, ['simple', 'variable'], true) ? $resolvedEditProductType : ($edit_data->has_variant ? 'variable' : 'simple');
  $catalogAttributeCollection = collect($catalogAttributes ?? collect())->map(function ($attribute) {
    $values = collect($attribute->values ?? [])->map(function ($value) {
      return [
        'id' => (int) $value->id,
        'value' => (string) $value->value,
        'meta' => is_array($value->meta ?? null) ? $value->meta : null,
      ];
    })->values()->all();

    return [
      'id' => (int) $attribute->id,
      'name' => (string) $attribute->name,
      'slug' => (string) $attribute->slug,
      'is_required' => (bool) ($attribute->is_required ?? false),
      'values' => $values,
    ];
  })->values();
  $catalogAttributesJson = $catalogAttributeCollection->toJson();
  $catalogAttributeMap = $catalogAttributeCollection
    ->mapWithKeys(fn ($attribute) => [(int) $attribute['id'] => $attribute])
    ->all();
  $catalogValueMap = $catalogAttributeCollection
    ->flatMap(function ($attribute) {
      return collect($attribute['values'] ?? [])->mapWithKeys(function ($value) use ($attribute) {
        return [
          (int) $value['id'] => [
            'id' => (int) $value['id'],
            'value' => (string) $value['value'],
            'attribute_id' => (int) $attribute['id'],
            'attribute_name' => (string) $attribute['name'],
            'attribute_slug' => (string) $attribute['slug'],
          ],
        ];
      });
    })
    ->all();
  $catalogAttributeBySlug = $catalogAttributeCollection
    ->mapWithKeys(fn ($attribute) => [\Illuminate\Support\Str::lower((string) $attribute['slug']) => $attribute])
    ->all();
  $catalogValueLookup = [];
  foreach ($catalogAttributeCollection as $attribute) {
    foreach ($attribute['values'] as $value) {
      $catalogValueLookup[(int) $attribute['id']][\Illuminate\Support\Str::lower(trim((string) $value['value']))] = (int) $value['id'];
    }
  }

  $defaultSelectedAttributeIds = collect($productVariants ?? collect())
    ->flatMap(function ($variant) use ($catalogAttributeBySlug, $catalogValueLookup) {
      $dynamicIds = collect($variant->variantAttributeValues ?? [])
        ->pluck('catalog_attribute_id')
        ->map(fn ($id) => (int) $id)
        ->filter(fn ($id) => $id > 0)
        ->values();

      if ($dynamicIds->isNotEmpty()) {
        return $dynamicIds;
      }

      $legacyIds = collect();
      foreach (['color' => $variant->color, 'size' => $variant->size, 'age' => $variant->age] as $slug => $rawValue) {
        $value = trim((string) $rawValue);
        if ($value === '') {
          continue;
        }

        $attribute = $catalogAttributeBySlug[\Illuminate\Support\Str::lower($slug)] ?? null;
        if (! $attribute) {
          continue;
        }

        $valueId = $catalogValueLookup[(int) $attribute['id']][\Illuminate\Support\Str::lower($value)] ?? null;
        if ($valueId) {
          $legacyIds->push((int) $attribute['id']);
        }
      }

      return $legacyIds;
    })
    ->unique()
    ->values()
    ->all();

  $selectedCatalogAttributeIds = collect(old('selected_attribute_ids', $defaultSelectedAttributeIds))
    ->map(fn ($id) => (int) $id)
    ->filter(fn ($id) => $id > 0)
    ->values()
    ->all();

  $rawEditVariants = old('variants');
  if (! is_array($rawEditVariants)) {
    $rawEditVariants = collect($productVariants ?? collect())->map(function ($variant) use ($catalogAttributeBySlug, $catalogValueLookup, $catalogAttributeMap, $catalogValueMap) {
      $attributeValueMap = collect($variant->variantAttributeValues ?? [])
        ->mapWithKeys(fn ($row) => [(int) $row->catalog_attribute_id => (int) $row->catalog_attribute_value_id])
        ->filter(fn ($valueId) => $valueId > 0);

      if ($attributeValueMap->isEmpty()) {
        foreach (['color' => $variant->color, 'size' => $variant->size, 'age' => $variant->age] as $slug => $rawValue) {
          $value = trim((string) $rawValue);
          if ($value === '') {
            continue;
          }

          $attribute = $catalogAttributeBySlug[\Illuminate\Support\Str::lower($slug)] ?? null;
          if (! $attribute) {
            continue;
          }

          $valueId = $catalogValueLookup[(int) $attribute['id']][\Illuminate\Support\Str::lower($value)] ?? null;
          if ($valueId) {
            $attributeValueMap[(int) $attribute['id']] = (int) $valueId;
          }
        }
      }

      $attributeRows = collect($attributeValueMap)->map(function ($valueId, $attributeId) use ($catalogAttributeMap, $catalogValueMap) {
        $attribute = $catalogAttributeMap[(int) $attributeId] ?? null;
        $value = $catalogValueMap[(int) $valueId] ?? null;

        if (! $attribute || ! $value) {
          return null;
        }

        return [
          'attribute_id' => (int) $attributeId,
          'attribute_name' => (string) $attribute['name'],
          'attribute_slug' => (string) $attribute['slug'],
          'value_id' => (int) $valueId,
          'value' => (string) $value['value'],
        ];
      })->filter()->values()->all();

      $existingImages = collect($variant->variantImages ?? [])
        ->map(function ($image) {
          return ltrim(str_replace('\\', '/', (string) ($image->getRawOriginal('image_path') ?? $image->image_path ?? '')), '/');
        })
        ->filter()
        ->unique()
        ->values()
        ->all();

      return [
        'id' => (int) $variant->id,
        'attribute_value_map' => $attributeValueMap->all(),
        'attribute_rows' => $attributeRows,
        'sku' => (string) ($variant->sku_code ?? ''),
        'price' => (string) ($variant->price ?? ''),
        'existing_images' => $existingImages,
      ];
    })->toArray();
  }

  $initialVariantRows = array_values(array_filter($rawEditVariants, function ($variant) {
    if (! is_array($variant)) {
      return false;
    }

    if (! empty($variant['attribute_value_map']) && is_array($variant['attribute_value_map'])) {
      foreach ($variant['attribute_value_map'] as $valueId) {
        if ((int) $valueId > 0) {
          return true;
        }
      }
    }

    if ((int) ($variant['id'] ?? 0) > 0) {
      return true;
    }

    foreach (['sku', 'price'] as $field) {
      if (trim((string) ($variant[$field] ?? '')) !== '') {
        return true;
      }
    }

    return ! empty($variant['existing_images']) && is_array($variant['existing_images']);
  }));

  $galleryAssetBaseUrl = rtrim(request()->getSchemeAndHttpHost() . request()->getBaseUrl(), '/');
  $galleryFallbackAssetPath = 'public/uploads/default/no-image.png';
  $galleryFallbackAssetUrl = $galleryAssetBaseUrl . '/' . ltrim($galleryFallbackAssetPath, '/');
  $existingGalleryImages = collect([]);
  try {
    $legacyGallery = \Illuminate\Support\Facades\File::glob(public_path('uploads/product/*.{jpg,jpeg,png,webp,gif}'), GLOB_BRACE) ?: [];
    $storageGallery = \Illuminate\Support\Facades\File::glob(public_path('storage/products/gallery/*.{jpg,jpeg,png,webp,gif}'), GLOB_BRACE) ?: [];
    $variantGallery = \Illuminate\Support\Facades\File::glob(public_path('storage/products/variants/*.{jpg,jpeg,png,webp,gif}'), GLOB_BRACE) ?: [];

    $existingGalleryImages = collect(array_merge($storageGallery, $variantGallery, $legacyGallery))
      ->map(function ($absolutePath) {
        $normalized = str_replace('\\', '/', (string) $absolutePath);
        $storageToken = '/public/storage/';
        $uploadsToken = '/public/uploads/';

        if (str_contains($normalized, $storageToken)) {
          return 'storage/' . ltrim((string) str_replace('\\', '/', substr($normalized, strpos($normalized, $storageToken) + strlen($storageToken))), '/');
        }

        if (str_contains($normalized, $uploadsToken)) {
          return 'public/uploads/' . ltrim((string) str_replace('\\', '/', substr($normalized, strpos($normalized, $uploadsToken) + strlen($uploadsToken))), '/');
        }

        return null;
      })
      ->filter()
      ->unique()
      ->values();
  } catch (\Throwable $e) {
    $existingGalleryImages = collect([]);
  }

  $resolveExistingGalleryPreviewUrl = static function (?string $path) use ($galleryAssetBaseUrl, $galleryFallbackAssetUrl): string {
    $normalizedPath = ltrim(str_replace('\\', '/', trim((string) $path)), '/');

    if ($normalizedPath === '') {
      return $galleryFallbackAssetUrl;
    }

    if (\Illuminate\Support\Str::startsWith($normalizedPath, ['http://', 'https://', 'data:'])) {
      return $normalizedPath;
    }

    if (\Illuminate\Support\Str::startsWith($normalizedPath, 'public/')) {
      return $galleryAssetBaseUrl . '/' . ltrim($normalizedPath, '/');
    }

    if (\Illuminate\Support\Str::startsWith($normalizedPath, ['storage/', 'uploads/'])) {
      return $galleryAssetBaseUrl . '/public/' . ltrim($normalizedPath, '/');
    }

    return $galleryAssetBaseUrl . '/public/' . ltrim($normalizedPath, '/');
  };

  $fieldErrorKeys = collect($errors->keys())->reject(function ($key) {
    return \Illuminate\Support\Str::startsWith($key, 'variants.');
  });
@endphp
<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <div class="page-title-box">
        <div class="page-title-right">
          <a href="{{route('admin.products.index')}}" class="btn btn-primary rounded-pill">Manage</a>
        </div>
        <h4 class="page-title">Product Edit</h4>
      </div>
    </div>
  </div>

  
  @if ($fieldErrorKeys->isNotEmpty())
    <div class="alert alert-danger">
      <strong>Please fix the validation errors below.</strong>
    </div>
  @endif

  <form action="{{ route('admin.products.update') }}" method="POST" enctype="multipart/form-data" id="product-create-form" class="wc-form-grid">
    @csrf
    <input type="hidden" value="{{$edit_data->id}}" name="id" />

    <div class="row g-3 wc-create-layout">
      <div class="col-12 col-xl-9">
        <div class="card product-section-card wc-compact-card mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 wc-form-section-title">General</h5>
            <span class="badge rounded-pill text-bg-light">Edit Product</span>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Product Type <span class="required-star">*</span></label>
                <div class="wc-type-switch">
                  <div class="form-check mb-0">
                    <input class="form-check-input js-product-type" type="radio" name="product_type" id="product_type_simple" value="simple" {{ $resolvedEditProductType === 'simple' ? 'checked' : '' }}>
                    <label class="form-check-label" for="product_type_simple">Simple Product</label>
                  </div>
                  <div class="form-check mb-0">
                    <input class="form-check-input js-product-type" type="radio" name="product_type" id="product_type_variable" value="variable" {{ $resolvedEditProductType === 'variable' ? 'checked' : '' }}>
                    <label class="form-check-label" for="product_type_variable">Variable Product</label>
                  </div>
                </div>
              </div>

              <div class="col-md-6">
                <label for="name" class="form-label">Product Name <span class="required-star">*</span></label>
                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $edit_data->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-md-3">
                <label for="category_id" class="form-label">Category <span class="required-star">*</span></label>
                <select id="category_id" name="category_id" class="form-control select2-single @error('category_id') is-invalid @enderror" required>
                  <option value="">Select category</option>
                  @foreach($categories as $category)
                    <option value="{{$category->id}}" @if($edit_data->category_id==$category->id) selected @endif>{{$category->name}}</option>
                    @foreach ($category->childrenCategories as $childCategory)
                      <option value="{{$childCategory->id}}" @if($edit_data->category_id==$childCategory->id) selected @endif>- {{$childCategory->name}}</option>
                    @endforeach
                  @endforeach
                </select>
                @error('category_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
              </div>
              <div class="col-md-3">
                <label for="brand_id" class="form-label">Brand</label>
                <select id="brand_id" name="brand_id" class="form-control select2-single @error('brand_id') is-invalid @enderror">
                  <option value="">Select brand</option>
                  @foreach($brands as $brand)
                    <option value="{{ $brand->id }}" {{ (string) old('brand_id', $edit_data->brand_id) === (string) $brand->id ? 'selected' : '' }}>
                      {{ $brand->name }}
                    </option>
                  @endforeach
                </select>
                @error('brand_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
              </div>

              <div class="col-md-4">
                <label for="subcategory_id" class="form-label">Sub Category</label>
                <select id="subcategory_id" name="subcategory_id" class="form-control select2-single @error('subcategory_id') is-invalid @enderror">
                  <option value="">Select sub category</option>
                  @foreach(($subcategory ?? collect()) as $subcat)
                    <option value="{{ $subcat->id }}" {{ (string) old('subcategory_id', $edit_data->subcategory_id) === (string) $subcat->id ? 'selected' : '' }}>
                      {{ $subcat->subcategoryName }}
                    </option>
                  @endforeach
                </select>
                @error('subcategory_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
              </div>
              <div class="col-md-4">
                <label for="childcategory_id" class="form-label">Child Category</label>
                <select id="childcategory_id" name="childcategory_id" class="form-control select2-single @error('childcategory_id') is-invalid @enderror">
                  <option value="">Select child category</option>
                  @foreach(($childcategory ?? collect()) as $childcat)
                    <option value="{{ $childcat->id }}" {{ (string) old('childcategory_id', $edit_data->childcategory_id) === (string) $childcat->id ? 'selected' : '' }}>
                      {{ $childcat->childcategoryName }}
                    </option>
                  @endforeach
                </select>
                @error('childcategory_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
              </div>
              <div class="col-md-4">
                <label for="purchase_price" class="form-label">Purchase Price <span class="required-star">*</span></label>
                <input type="number" step="0.01" min="0" id="purchase_price" name="purchase_price" class="form-control @error('purchase_price') is-invalid @enderror" value="{{ old('purchase_price', $edit_data->purchase_price) }}" required>
                @error('purchase_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-md-4">
                <label for="old_price" class="form-label">Old Price</label>
                <input type="number" step="0.01" min="0" id="old_price" name="old_price" class="form-control @error('old_price') is-invalid @enderror" value="{{ old('old_price', $edit_data->old_price) }}">
                @error('old_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-md-4 js-simple-only {{ $resolvedEditProductType === 'variable' ? 'd-none' : '' }}">
                <label for="new_price" class="form-label">New Price <span class="required-star">*</span></label>
                <input type="number" step="0.01" min="0" id="new_price" name="new_price" class="form-control @error('new_price') is-invalid @enderror" value="{{ old('new_price', $edit_data->new_price) }}">
                @error('new_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>
        </div>

        @include('backEnd.product.partials._shipping_card', ['product' => $edit_data, 'shippingProfiles' => $shippingProfiles ?? collect()])

        <div class="card product-section-card wc-compact-card mb-3">
          <div class="card-header">
            <h5 class="mb-0 wc-form-section-title">Description</h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-12">
                <label for="short_description" class="form-label">Short Description</label>
                <textarea id="short_description" name="short_description" rows="3" class="form-control @error('short_description') is-invalid @enderror">{{ old('short_description', $edit_data->short_description) }}</textarea>
                @error('short_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12">
                <label for="meta_keyword" class="form-label">Meta Keywords</label>
                <textarea id="meta_keyword" name="meta_keyword" rows="3" class="form-control @error('meta_keyword') is-invalid @enderror">{{ old('meta_keyword', $edit_data->meta_keyword) }}</textarea>
                <div class="wc-form-help">Enter keywords separated by commas (example: shoes, men, running).</div>
                @error('meta_keyword')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12">
                <label for="meta_description" class="form-label">Meta Description</label>
                <textarea id="meta_description" name="meta_description" rows="3" class="form-control @error('meta_description') is-invalid @enderror">{{ old('meta_description', $edit_data->meta_description) }}</textarea>
                @error('meta_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 @error('description') is-invalid-row @enderror">
                <label for="description" class="form-label">Description <span class="required-star">*</span></label>
                <textarea id="description" name="description" rows="6" class="summernote form-control @error('description') is-invalid @enderror" required>{{ old('description', $edit_data->description) }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-12">
                <label for="note" class="form-label">Note</label>
                <textarea id="note" name="note" rows="4" class="form-control @error('note') is-invalid @enderror">{{ old('note', $edit_data->note) }}</textarea>
                @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>
        </div>

        <!-- Stock by Warehouse (Simple Product Only) -->
        <div class="card product-section-card wc-compact-card mb-3 js-simple-only {{ $resolvedEditProductType === 'variable' ? 'd-none' : '' }}">
          <div class="card-header">
            <h5 class="mb-0 wc-form-section-title"><i class="mdi mdi-warehouse me-1"></i> Current Stock Levels (Warehouses)</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-sm table-hover border">
                <thead class="table-light">
                  <tr>
                    <th>Warehouse</th>
                    <th class="text-end">Physical Qty</th>
                    <th class="text-end">Available Qty</th>
                    <th class="text-end">Avg Cost</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($warehouseStocks as $stock)
                    <tr>
                      <td>{{ $stock->warehouse->name ?? 'N/A' }}</td>
                      <td class="text-end">{{ number_format($stock->physical_quantity, 2) }}</td>
                      <td class="text-end">
                        <span class="badge {{ $stock->available_quantity > 0 ? 'bg-success' : 'bg-danger' }}">
                          {{ number_format($stock->available_quantity, 2) }}
                        </span>
                      </td>
                      <td class="text-end">{{ number_format($stock->average_cost, 2) }}</td>
                    </tr>
                  @empty
                    <tr><td colspan="4" class="text-center text-muted py-2">No stock records found</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>
            <small class="text-muted"><i class="mdi mdi-information-outline"></i> Stock is updated via Purchase/Orders/Adjustments.</small>
        </div>
      </div>
    </div> <!-- end col-xl-9 -->

      <div class="col-12 col-xl-3">
        <div class="card product-section-card wc-compact-card wc-sidebar-card mb-3">
          <div class="card-header">
            <h6 class="mb-0">Thumbnail</h6>
          </div>
          <div class="card-body">
            <label for="thumbnail" class="form-label">Product Thumbnail</label>
            <input type="file" id="thumbnail" name="thumbnail" class="form-control @error('thumbnail') is-invalid @enderror" accept="image/*">
            <small class="wc-form-help">JPG/PNG, recommended 800x800px.</small>
            @error('thumbnail')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            
            <div id="thumbnail-preview" class="mt-2" style="border: 1px solid #ddd; padding: 5px; width: 150px; border-radius: 4px; {{ $edit_data->thumbnail ? '' : 'display: none;' }}">
              <img id="thumbnail-img" src="{{ $edit_data->thumbnail ? asset('public/storage/' . $edit_data->thumbnail) : '#' }}" alt="Thumbnail preview" style="max-width: 100%; height: auto; display: block; margin-bottom: 5px;">
              <button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removeThumbnail()">Remove Selection</button>
            </div>
          </div>
        </div>

        <div class="card product-section-card wc-compact-card wc-sidebar-card mb-3">
          <div class="card-header">
            <h6 class="mb-0">Gallery</h6>
          </div>
          <div class="card-body">
            <label for="image" class="form-label">Add Gallery Images</label>
            <input type="file" id="image" name="image[]" class="form-control @error('image') is-invalid @enderror" accept="image/*" multiple>
            <small class="wc-form-help">Upload multiple product images.</small>
            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="open-existing-gallery-btn">
              Select From Existing Gallery
            </button>
            @error('image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            
            <div id="gallery-preview" class="mt-2 d-flex flex-wrap gap-2"></div>
            <div id="existing-gallery-selected" class="d-flex flex-wrap gap-2 mt-2"></div>
            
            <hr class="my-3">
            <h6 class="small fw-bold mb-2">Current Gallery Images</h6>
            <div class="d-flex flex-wrap gap-2">
              @foreach($edit_data->images as $image)
                <div class="image-preview-card">
                  <img src="{{ asset($image->image) }}" alt="Gallery">
                  <button type="button" class="remove-btn" onclick="deleteGalleryImage({{ $image->id }})">&times;</button>
                </div>
              @endforeach
            </div>
          </div>
        </div>

        <div class="card product-section-card wc-compact-card wc-sidebar-card mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Status</h6>
            <span class="badge rounded-pill text-bg-{{ $edit_data->status == 1 ? 'success' : 'secondary' }}">{{ $edit_data->status == 1 ? 'Live' : 'Draft' }}</span>
          </div>
          <div class="card-body wc-toggle-list">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="status" name="status" value="1" {{ old('status', $edit_data->status) ? 'checked' : '' }}>
              <label class="form-check-label" for="status">Published</label>
            </div>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="topsale" name="topsale" value="1" {{ old('topsale', $edit_data->topsale) ? 'checked' : '' }}>
              <label class="form-check-label" for="topsale">Hot Deals</label>
            </div>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="flashsale" name="flashsale" value="1" {{ old('flashsale', $edit_data->flashsale) ? 'checked' : '' }}>
              <label class="form-check-label" for="flashsale">Flash Sales</label>
            </div>
          </div>
        </div>

        <div class="card product-section-card wc-compact-card wc-sidebar-card mb-3">
          <div class="card-header">
            <h6 class="mb-0">Product Details</h6>
          </div>
          <div class="card-body">
            <div class="mb-2">
              <label for="pro_unit" class="form-label">Product Unit</label>
              <input type="text" id="pro_unit" name="pro_unit" class="form-control @error('pro_unit') is-invalid @enderror" value="{{ old('pro_unit', $edit_data->pro_unit) }}">
            </div>
            <div class="mb-2">
              <label for="sold" class="form-label">Sold</label>
              <input type="number" step="1" min="0" id="sold" name="sold" class="form-control @error('sold') is-invalid @enderror" value="{{ old('sold', $edit_data->sold) }}">
            </div>
            <div>
              <label for="pro_video" class="form-label">YouTube Video Link</label>
              <input type="text" id="pro_video" name="pro_video" class="form-control @error('pro_video') is-invalid @enderror" value="{{ old('pro_video', $edit_data->pro_video) }}">
            </div>
          </div>
        </div>
      </div> <!-- end col-xl-3 -->
    </div> <!-- end row -->
    <div class="card product-section-card mb-4 js-variable-only {{ $resolvedEditProductType === 'variable' ? '' : 'd-none' }}" id="variant-section">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Variant Combinations</h5>
        <span class="text-muted small">Catalog Attributes drive every row.</span>
      </div>
      <div class="card-body">
        <div class="alert alert-info mb-3">
          New attributes created in Catalog Attributes will show here automatically. Choose which attributes apply to this product, then add one value per attribute in each variant row.
        </div>

        <div class="variant-card rounded-3 p-3 mb-3">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <div class="variant-title mb-0">Attribute Set</div>
            <span class="badge bg-light text-dark" id="selectedVariantAttributeCount">{{ count($selectedCatalogAttributeIds) }} selected</span>
          </div>

          @if($catalogAttributeCollection->isEmpty())
            <div class="alert alert-warning mb-0">
              No active catalog attributes found.
              <a href="{{ route('admin.catalog-attributes.index') }}" class="alert-link">Create attributes first</a>.
            </div>
          @else
            <div class="attribute-toggle-list">
              @foreach($catalogAttributeCollection as $attribute)
                <label class="attribute-toggle-item {{ in_array((int) $attribute['id'], $selectedCatalogAttributeIds, true) ? 'is-active' : '' }}">
                  <input
                    type="checkbox"
                    class="form-check-input m-0 variant-attribute-toggle"
                    name="selected_attribute_ids[]"
                    value="{{ $attribute['id'] }}"
                    {{ in_array((int) $attribute['id'], $selectedCatalogAttributeIds, true) ? 'checked' : '' }}
                  >
                  <span class="attr-name">{{ $attribute['name'] }}</span>
                </label>
              @endforeach
            </div>
            <div class="small text-muted mt-2" id="selectedVariantAttributeNames"></div>
            @error('selected_attribute_ids')
              <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
            @enderror
          @endif
        </div>

        <div class="variant-card rounded-3 p-3 mb-3">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <div class="variant-title mb-0">Variant Builder</div>
            <span class="badge bg-warning text-dark d-none" id="variantEditState">Editing row</span>
          </div>

          <div class="alert alert-info py-2 px-3 mb-3">
            <small class="mb-0"><strong>Variation stock</strong> is managed under <a href="{{ route('admin.stock.set') }}" target="_blank" rel="noopener">Stock → Set stock</a> or <a href="{{ route('admin.inventory.index') }}" target="_blank" rel="noopener">Inventory</a>, not on this form.</small>
          </div>

          <input type="hidden" id="variantEditingIndex" value="">

          <div class="variant-builder-grid">
            <div class="variant-grid-item" style="grid-column: 1 / -1;">
              <label class="form-label">Attribute Values <span class="required-star">*</span></label>
              <div id="variantBuilderAttributes" class="row g-2"></div>
              <small class="wc-form-help">Choose one value for each enabled attribute, add price/images, then click Add Selected.</small>
            </div>
            <div class="variant-grid-item" style="grid-column: 1 / -1;">
              <div id="variantDraftRows" class="variant-table-wrapper d-none">
                <table class="variant-table">
                  <thead>
                    <tr>
                      <th>Attributes</th>
                      <th>SKU</th>
                      <th>Price</th>
                      <th>Images</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>
            <div class="variant-grid-item variant-field-sku">
              <label class="form-label">SKU</label>
              <input type="text" id="variantSku" class="form-control" placeholder="SKU">
            </div>
            <div class="variant-grid-item variant-field-price">
              <label class="form-label">Price <span class="required-star">*</span></label>
              <input type="number" step="0.01" min="0" id="variantPrice" class="form-control text-end" placeholder="0.00">
            </div>
            <div class="variant-grid-item variant-field-images d-flex align-items-end gap-2 flex-wrap">
              <input type="file" id="variantImages" class="form-control" accept="image/*" multiple style="flex:1;min-width:180px">
              <button type="button" class="btn btn-sm btn-outline-primary" id="open-variant-existing-gallery-btn" style="white-space:nowrap">Existing Gallery</button>
              <div class="variant-builder-preview d-flex flex-wrap gap-2"></div>
              <div class="alert alert-warning mt-0 mb-0 d-none" id="variantMessage" style="flex-basis:100%"></div>
            </div>
            <div class="variant-grid-item variant-field-add d-flex align-items-end">
              <button type="button" class="btn btn-primary w-100" id="add-variant-btn">Add Selected</button>
            </div>
          </div>
        </div>

        <div id="variantContainer" class="variant-card-grid">
          <div class="variant-table-wrapper">
            <table class="variant-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Attributes</th>
                  <th>SKU</th>
                  <th>Price</th>
                  <th>Images</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="variantTableBody">
                @foreach($initialVariantRows as $variantIndex => $variant)
                  @php $skuVal = old('variants.' . $variantIndex . '.sku', $variant['sku'] ?? ''); @endphp
                  @php $priceVal = old('variants.' . $variantIndex . '.price', $variant['price'] ?? ''); @endphp
                  @php
                    $attributeValueMap = collect($variant['attribute_value_map'] ?? [])
                      ->mapWithKeys(fn ($valueId, $attributeId) => [(int) $attributeId => (int) $valueId])
                      ->filter(fn ($valueId) => $valueId > 0)
                      ->all();
                    $attributeSummaryRows = collect($attributeValueMap)->map(function ($valueId, $attributeId) use ($catalogAttributeMap, $catalogValueMap) {
                      $attribute = $catalogAttributeMap[(int) $attributeId] ?? null;
                      $value = $catalogValueMap[(int) $valueId] ?? null;
                      if (!$attribute || !$value) { return null; }
                      return [
                        'attribute_id' => (int) $attributeId,
                        'attribute_name' => (string) $attribute['name'],
                        'attribute_slug' => (string) $attribute['slug'],
                        'value_id' => (int) $valueId,
                        'value' => (string) $value['value'],
                      ];
                    })->filter()->values();
                  @endphp
                  <tr class="variant-row" data-index="{{ $variantIndex }}">
                    <td>
                      <input type="hidden" name="variants[{{ $variantIndex }}][_active]" data-field="_active" value="1">
                      <input type="hidden" name="variants[{{ $variantIndex }}][id]" data-field="id" value="{{ $variant['id'] ?? '' }}">
                      <span class="variant-row-num variant-number">{{ $variantIndex + 1 }}</span>
                    </td>
                    <td>
                      <div class="variant-value-attributes">
                        @if($attributeSummaryRows->isNotEmpty())
                          <div class="d-flex flex-wrap gap-1">
                            @foreach($attributeSummaryRows as $attributeRow)
                              <span class="badge bg-light text-dark border">{{ $attributeRow['attribute_name'] }}: {{ $attributeRow['value'] }}</span>
                            @endforeach
                          </div>
                        @else
                          -
                        @endif
                      </div>
                      <div class="variant-attribute-hidden-host">
                        @foreach($attributeSummaryRows as $attributeRow)
                          <input type="hidden" name="variants[{{ $variantIndex }}][attribute_value_map][{{ $attributeRow['attribute_id'] }}]" data-field="attribute_value_map" data-attribute-id="{{ $attributeRow['attribute_id'] }}" value="{{ $attributeRow['value_id'] }}">
                        @endforeach
                      </div>
                      @error('variants.' . $variantIndex . '.attribute_value_map')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </td>
                    <td>
                      <input type="text" name="variants[{{ $variantIndex }}][sku]" data-field="sku" class="form-control form-control-sm variant-value-sku" value="{{ $skuVal }}" placeholder="SKU">
                    </td>
                    <td>
                      <input type="number" step="0.01" min="0" name="variants[{{ $variantIndex }}][price]" data-field="price" class="form-control form-control-sm text-end variant-value-price" value="{{ $priceVal }}" placeholder="0.00">
                    </td>
                    <td class="variant-row-images-cell">
                      <div class="wc-file-pill">
                        <input type="file" class="wc-file-input-hidden" name="variants[{{ $variantIndex }}][images][]" data-field="images" accept="image/*" multiple>
                        @foreach((array) old('variants.' . $variantIndex . '.existing_images', $variant['existing_images'] ?? []) as $existingImagePath)
                          <input type="hidden" name="variants[{{ $variantIndex }}][existing_images][]" value="{{ $existingImagePath }}" data-field="existing_images">
                        @endforeach
                        <button type="button" class="btn btn-sm btn-outline-primary btn-choose-variant-images">Choose</button>
                        <span class="wc-file-meta variant-images-meta" style="font-size:0.72rem">No files</span>
                      </div>
                      <div class="variant-images-preview d-flex flex-wrap gap-1 mt-1"></div>
                    </td>
                    <td>
                      <div class="variant-row-actions">
                        <button type="button" class="btn btn-sm btn-outline-primary btn-edit-variant">Edit</button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-variant">Remove</button>
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="wc-form-actions mb-5">
      <div class="wc-form-actions-inner">
        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" id="product-submit-btn" class="btn btn-success px-4">
          Update Product
        </button>
      </div>
    </div>
  </form>


  <div class="modal fade" id="existingGalleryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Select Existing Gallery Images</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          @if($existingGalleryImages->isEmpty())
            <div class="alert alert-warning mb-0">No existing gallery images found.</div>
          @else
            <div class="row g-2">
              @foreach($existingGalleryImages as $existingImagePath)
                @php $existingImagePreviewUrl = $resolveExistingGalleryPreviewUrl($existingImagePath); @endphp
                <div class="col-6 col-md-3 col-xl-2">
                  <label class="w-100 border rounded p-2 h-100 existing-gallery-item">
                    <input
                      type="checkbox"
                      class="form-check-input existing-gallery-check"
                      value="{{ $existingImagePath }}"
                    >
                    <img src="{{ $existingImagePreviewUrl }}" class="img-fluid rounded mt-2" style="height: 120px; width:100%; object-fit: cover;" alt="Gallery" onerror="this.onerror=null;this.src='{{ $galleryFallbackAssetUrl }}';">
                  </label>
                </div>
              @endforeach
            </div>
          @endif
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary" id="apply-existing-gallery-btn">Use Selected Images</button>
        </div>
      </div>
    </div>
  </div>

    <div class="modal fade" id="variantExistingGalleryModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Select Variant Images From Existing Gallery</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            @if($existingGalleryImages->isEmpty())
              <div class="alert alert-warning mb-0">No existing gallery images found.</div>
            @else
              <div class="row g-2">
                @foreach($existingGalleryImages as $existingImagePath)
                  @php $existingImagePreviewUrl = $resolveExistingGalleryPreviewUrl($existingImagePath); @endphp
                  <div class="col-6 col-md-3 col-xl-2">
                    <label class="w-100 border rounded p-2 h-100 existing-gallery-item">
                      <input
                        type="checkbox"
                        class="form-check-input variant-existing-gallery-check"
                        value="{{ $existingImagePath }}"
                      >
                      <img src="{{ $existingImagePreviewUrl }}" class="img-fluid rounded mt-2" style="height: 120px; width:100%; object-fit: cover;" alt="Gallery" onerror="this.onerror=null;this.src='{{ $galleryFallbackAssetUrl }}';">
                    </label>
                  </div>
                @endforeach
              </div>
            @endif
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" id="apply-variant-existing-gallery-btn">Use Selected Images</button>
          </div>
        </div>
      </div>
    </div>

    <form id="delete-gallery-image-form" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
        <input type="hidden" name="id" id="delete-image-id">
    </form>
</div>
@endsection

@section('script')
<script src="{{ asset('public/backEnd/assets/libs/parsleyjs/parsley.min.js') }}"></script>
<script src="{{ asset('public/backEnd/assets/js/pages/form-validation.init.js') }}"></script>
<script src="{{ asset('public/backEnd/assets/libs/select2/js/select2.min.js') }}"></script>
<script src="{{ asset('public/backEnd/assets/libs/summernote/summernote-lite.min.js') }}"></script>

<script>
    $(document).ready(function() {
        $('.summernote').summernote({
            height: 200
        });
        $('.select2').select2();
        $('.select2-single').select2();

        // Product type toggle
        function toggleProductTypeSections() {
          const selectedType = $('input.js-product-type:checked').val() || 'simple';
          const isVariable = selectedType === 'variable';

          $('.js-variable-only').toggleClass('d-none', !isVariable);
          $('.js-simple-only').toggleClass('d-none', isVariable);

          $('.js-variable-only').find(':input').prop('disabled', !isVariable);
          $('.js-simple-only').find(':input').not('[name="id"]').prop('disabled', isVariable);
        }

        $('input.js-product-type').on('change', toggleProductTypeSections);
        toggleProductTypeSections();

        // ==================== IMAGE UTILITIES ====================
        function readFileAsDataURL(file, callback) {
          if (!file || !(file instanceof Blob)) return;
          var reader = new FileReader();
          reader.onload = function(e) { callback(e.target.result); };
          reader.readAsDataURL(file);
        }

        const galleryAssetBaseUrl = @json($galleryAssetBaseUrl . '/');
        const galleryPublicBaseUrl = @json($galleryAssetBaseUrl . '/public/');
        const galleryFallbackUrl = @json($galleryFallbackAssetUrl);

        function resolveExistingGalleryPreviewUrl(path) {
          var cleanPath = String(path || '').trim().replace(/\\/g, '/').replace(/^\/+/, '');
          if (!cleanPath) return galleryFallbackUrl;
          if (/^(https?:|data:)/i.test(cleanPath)) return cleanPath;
          if (/^public\//i.test(cleanPath)) return galleryAssetBaseUrl + cleanPath;
          return galleryPublicBaseUrl + cleanPath;
        }

        // Thumbnail Preview
        $('#thumbnail').on('change', function(e) {
          var file = e.target.files && e.target.files[0];
          if (file) {
            readFileAsDataURL(file, function(result) {
              $('#thumbnail-img').attr('src', result);
              $('#thumbnail-preview').show();
            });
          }
        });

        // Gallery Upload Preview
        $('#image').on('change', function() {
          const preview = $('#gallery-preview');
          preview.empty();
          const files = this.files;
          if (files) {
            Array.from(files).forEach((file, index) => {
              readFileAsDataURL(file, function(result) {
                preview.append('<div class="image-preview-card"><img src="' + result + '" alt="Gallery preview"></div>');
              });
            });
          }
        });

        // Existing Gallery Modal Logic
        const existingGalleryModalEl = document.getElementById('existingGalleryModal');
        const existingGalleryModal = existingGalleryModalEl ? new bootstrap.Modal(existingGalleryModalEl) : null;
        const selectedExistingContainer = $('#existing-gallery-selected');

        $('#open-existing-gallery-btn').on('click', function() { if (existingGalleryModal) existingGalleryModal.show(); });
        $('#apply-existing-gallery-btn').on('click', function() {
          $('.existing-gallery-check:checked').each(function() {
            var path = $(this).val();
            if (!path || selectedExistingContainer.find('[data-path="' + path.replace(/"/g, '\\"') + '"]').length) return;
            selectedExistingContainer.append(
              '<div class="image-preview-card existing-gallery-token" data-path="' + path + '">' +
                '<img src="' + resolveExistingGalleryPreviewUrl(path) + '" alt="Existing">' +
                '<button type="button" class="remove-btn remove-existing-gallery-btn">&times;</button>' +
                '<input type="hidden" name="gallery_existing[]" value="' + path + '">' +
              '</div>'
            );
          });
          if (existingGalleryModal) existingGalleryModal.hide();
        });

        $(document).on('click', '.remove-existing-gallery-btn', function() { $(this).closest('.existing-gallery-token').remove(); });

        // ==================== VARIANT BUILDER LOGIC ====================
        const variantContainer = $('#variantTableBody');
        const attributeCatalog = {!! $catalogAttributesJson !!};
        const variantAttributeCount = $('#selectedVariantAttributeCount');
        const variantAttributeNames = $('#selectedVariantAttributeNames');
        let variantBuilderMedia = [];
        let selectedAttributeSnapshot = [];

        const variantComposer = {
          editingIndex: null,
          editState: $('#variantEditState'),
          message: $('#variantMessage'),
          attributeHost: $('#variantBuilderAttributes'),
          draftHost: $('#variantDraftRows'),
          sku: $('#variantSku'),
          price: $('#variantPrice'),
          images: $('#variantImages'),
          addBtn: $('#add-variant-btn'),
          previewContainer: $('.variant-builder-preview')
        };

        const attributeIndex = {};
        const attributeValueIndex = {};

        attributeCatalog.forEach(function(attribute) {
          attributeIndex[String(attribute.id)] = attribute;
          (attribute.values || []).forEach(function(value) {
            attributeValueIndex[String(value.id)] = {
              id: Number(value.id),
              value: String(value.value || ''),
              attribute_id: Number(attribute.id),
              attribute_name: String(attribute.name || 'Attribute'),
              attribute_slug: String(attribute.slug || '')
            };
          });
        });

        function escapeHtml(value) {
          return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        function normalizeExistingGalleryPath(path) {
          var cleanPath = String(path || '').trim().replace(/\\/g, '/').replace(/^\/+/, '');
          if (!cleanPath) return '';
          if (/^public\/storage\//i.test(cleanPath)) return cleanPath.replace(/^public\//i, '');
          if (/^uploads\//i.test(cleanPath)) return 'public/' + cleanPath;
          return cleanPath;
        }

        function showVariantMessage(message) {
          variantComposer.message.text(message || '');
          variantComposer.message.toggleClass('d-none', !message);
        }

        function selectedAttributeIds() {
          const seen = {};
          return $('.variant-attribute-toggle:checked').map(function() {
            return String($(this).val() || '').trim();
          }).get().filter(function(value) {
            if (!value || seen[value]) return false;
            seen[value] = true;
            return true;
          });
        }

        function selectedAttributes() {
          const ids = selectedAttributeIds();
          return ids.map(id => attributeIndex[String(id)]).filter(attribute => !!attribute);
        }

        function updateSelectedAttributeCount() {
          if (!variantAttributeCount.length) return;
          const attrs = selectedAttributes();
          const count = attrs.length;
          variantAttributeCount.text(count ? `${count} selected` : 'None selected');
          if (variantAttributeNames.length) {
            variantAttributeNames.text(count ? 'Selected fields: ' + attrs.map(attribute => attribute.name || 'Attribute').join(', ') : 'No variant fields selected.');
          }
        }

        function renderBuilderAttributeFields(selectedValueMap) {
          const attrs = selectedAttributes();
          const valueMap = selectedValueMap || {};
          updateSelectedAttributeCount();
          if (variantComposer.draftHost && variantComposer.draftHost.length) {
            variantComposer.draftHost.addClass('d-none').find('tbody').empty();
          }
          if (!variantComposer.attributeHost.length) return;
          if (!attrs.length) {
            variantComposer.attributeHost.html('<div class="col-12"><div class="alert alert-warning mb-0">Select attributes above first.</div></div>');
            return;
          }
          const html = attrs.map(function(attribute) {
            const selectedValueId = Number(Array.isArray(valueMap[attribute.id]) ? valueMap[attribute.id][0] : (valueMap[attribute.id] || 0));
            const values = attribute.values || [];
            
            const valueOptions = values.length 
              ? values.map(function(v) {
                  const selected = selectedValueId === Number(v.id) ? ' selected' : '';
                  return `<option value="${v.id}"${selected}>${escapeHtml(v.value)}</option>`;
                }).join('')
              : '';

            return `<div class="col-12 col-md-3"><label class="form-label">${escapeHtml(attribute.name)} <span class="required-star">*</span></label><select class="form-control variant-builder-value-select" data-attribute-id="${attribute.id}" ${values.length ? '' : 'disabled'}><option value="">Select ${escapeHtml(attribute.name)}</option>${valueOptions}</select>${values.length ? '' : '<small class="text-danger">No active values found.</small>'}</div>`;
          }).join('');
          variantComposer.attributeHost.html(html);
        }

        function syncBuilderAllValueCheckboxes() {
          variantComposer.attributeHost.find('.variant-builder-all-values').each(function() {
            const attributeId = Number($(this).data('attributeId') || 0);
            const $values = variantComposer.attributeHost.find(`.variant-builder-value-check[data-attribute-id="${attributeId}"]`);
            const checkedCount = $values.filter(':checked').length;
            this.checked = $values.length > 0 && checkedCount === $values.length;
            this.indeterminate = checkedCount > 0 && checkedCount < $values.length;
          });
        }

        function buildAttributeRowsFromValueMap(valueMap) {
          const rows = [];
          Object.keys(valueMap || {}).forEach(attrId => {
            const valId = valueMap[attrId];
            const attr = attributeIndex[String(attrId)];
            const val = attributeValueIndex[String(valId)];
            if (attr && val) rows.push({ attribute_id: Number(attrId), attribute_name: attr.name, attribute_slug: attr.slug || '', value_id: Number(valId), value: val.value });
          });
          return rows.sort((a,b) => a.attribute_id - b.attribute_id);
        }

        function renderAttributeSummaryHtml(attributeRows) {
          if (!Array.isArray(attributeRows) || !attributeRows.length) return '-';
          return '<div class="d-flex flex-wrap gap-1">' + attributeRows.map(function(row) {
            return '<span class="badge bg-light text-dark border">' + escapeHtml(row.attribute_name) + ': ' + escapeHtml(row.value) + '</span>';
          }).join('') + '</div>';
        }

        function renderAttributeHiddenInputs(index, attributeRows) {
          if (!Array.isArray(attributeRows) || !attributeRows.length) return '';
          return attributeRows.map(function(row) {
            return '<input type="hidden" name="variants[' + index + '][attribute_value_map][' + row.attribute_id + ']" data-field="attribute_value_map" data-attribute-id="' + row.attribute_id + '" value="' + row.value_id + '">';
          }).join('');
        }

        function readRowAttributeValueMap($row) {
          const valueMap = {};
          $row.find('input[data-field="attribute_value_map"]').each(function() {
            const attributeId = Number($(this).data('attributeId') || 0);
            const valueId = Number($(this).val() || 0);
            if (attributeId > 0 && valueId > 0) valueMap[attributeId] = valueId;
          });
          return valueMap;
        }

        function buildCombinationPayloads(valueGroups, groupIndex, valueMap, rows, combinations) {
          if (groupIndex >= valueGroups.length) { combinations.push({ valueMap: Object.assign({}, valueMap), rows: rows.slice() }); return; }
          valueGroups[groupIndex].values.forEach(v => {
            valueMap[v.attribute_id] = v.id;
            rows.push({ attribute_id: v.attribute_id, attribute_name: v.attribute_name, value_id: v.id, value: v.value });
            buildCombinationPayloads(valueGroups, groupIndex + 1, valueMap, rows, combinations);
            rows.pop(); delete valueMap[v.attribute_id];
          });
        }

        function readBuilderAttributePayload() {
          const attrs = selectedAttributes();
          const valueMap = {};
          const rows = [];
          for (let a of attrs) {
            const valueId = Number(variantComposer.attributeHost.find('.variant-builder-value-select[data-attribute-id="' + a.id + '"]').val() || 0);
            const selectedValue = valueId > 0 ? attributeValueIndex[String(valueId)] : null;
            if (!selectedValue) return { error: 'Select value for ' + a.name };
            valueMap[a.id] = valueId;
            rows.push({
              attribute_id: Number(a.id),
              attribute_name: String(a.name || 'Attribute'),
              attribute_slug: String(a.slug || ''),
              value_id: valueId,
              value: String(selectedValue.value || '')
            });
          }
          if (!rows.length) return { error: 'Select attribute values.' };
          return { combinations: [{ valueMap: valueMap, rows: rows }] };
        }

        function variantRowTemplate(index, data) {
          const attrRows = data.attribute_rows || buildAttributeRowsFromValueMap(data.attribute_value_map || {});
          const attrSummary = renderAttributeSummaryHtml(attrRows);
          const attrHidden = renderAttributeHiddenInputs(index, attrRows);
          const existingImages = Array.from(new Set((data.existing_images || [])
            .map(function(path) { return normalizeExistingGalleryPath(path); })
            .filter(function(path) { return path !== ''; })));
          const existingImagesHidden = existingImages.map(p => `<input type="hidden" name="variants[${index}][existing_images][]" value="${escapeHtml(p)}" data-field="existing_images">`).join('');
          
          return `<tr class="variant-row" data-index="${index}"><td><input type="hidden" name="variants[${index}][_active]" data-field="_active" value="1"><input type="hidden" name="variants[${index}][id]" data-field="id" value="${data.id || ''}"><span class="variant-row-num variant-number">${index + 1}</span></td><td><div class="variant-value-attributes">${attrSummary}</div><div class="variant-attribute-hidden-host">${attrHidden}</div></td><td><input type="text" name="variants[${index}][sku]" data-field="sku" class="form-control form-control-sm variant-value-sku" value="${escapeHtml(data.sku || '')}" placeholder="SKU"></td><td><input type="number" step="0.01" min="0" name="variants[${index}][price]" data-field="price" class="form-control form-control-sm text-end variant-value-price" value="${escapeHtml(data.price || '')}" placeholder="0.00"></td><td class="variant-row-images-cell">${existingImagesHidden}<div class="wc-file-pill"><input type="file" class="wc-file-input-hidden" name="variants[${index}][images][]" data-field="images" accept="image/*" multiple><button type="button" class="btn btn-sm btn-outline-primary btn-choose-variant-images">Choose</button><span class="wc-file-meta variant-images-meta" style="font-size:0.72rem">No files</span></div><div class="variant-images-preview d-flex flex-wrap gap-1 mt-1"></div></td><td><div class="variant-row-actions"><button type="button" class="btn btn-sm btn-outline-primary btn-edit-variant">Edit</button><button type="button" class="btn btn-sm btn-outline-danger btn-remove-variant">Remove</button></div></td></tr>`;
        }

        function clearVariantComposer() {
          variantComposer.sku.val(''); variantComposer.price.val('');
          variantComposer.images.val('');
          variantComposer.attributeHost.find('.variant-builder-value-select').val('');
          variantComposer.editingIndex = null; variantBuilderMedia = []; variantComposer.previewContainer.empty();
          variantComposer.addBtn.text('Add Selected'); variantComposer.editState.addClass('d-none');
          showVariantMessage('');
        }

        function reindexVariantRows() {
          variantContainer.find('.variant-row').each(function(index) {
            const $row = $(this);
            $row.attr('data-index', index);
            $row.find('.variant-number').text(index + 1);
            $row.find('[data-field]').each(function() {
              const field = $(this).data('field');
              if (field === 'images') {
                this.name = `variants[${index}][images][]`;
              } else if (field === 'existing_images') {
                this.name = `variants[${index}][existing_images][]`;
              } else if (field === 'attribute_value_map') {
                const attributeId = Number($(this).data('attributeId') || 0);
                this.name = `variants[${index}][attribute_value_map][${attributeId}]`;
              } else {
                this.name = `variants[${index}][${field}]`;
              }
            });
          });
        }

        function variantCombinationKeyFromRows(attributeRows) {
          return (attributeRows || [])
            .map(function(row) { return Number(row.attribute_id || 0) + ':' + Number(row.value_id || 0); })
            .filter(function(part) { return part !== '0:0'; })
            .sort()
            .join('|');
        }

        function variantCombinationExists(attributeRows, ignoreIndex) {
          const candidateKey = variantCombinationKeyFromRows(attributeRows);
          let exists = false;
          variantContainer.find('.variant-row').each(function(index) {
            const rowIndex = Number($(this).attr('data-index') || index);
            if (ignoreIndex !== null && ignoreIndex !== undefined && Number(ignoreIndex) === rowIndex) return;
            const rowKey = variantCombinationKeyFromRows(buildAttributeRowsFromValueMap(readRowAttributeValueMap($(this))));
            if (rowKey && rowKey === candidateKey) {
              exists = true;
              return false;
            }
          });
          return exists;
        }

        function copyBuilderFilesToVariantRow($row) {
          const $rowFileInput = $row.find('input[type="file"][data-field="images"]');
          const builderInput = variantComposer.images[0];
          if (!builderInput || !$rowFileInput.length) return;
          const dt = new DataTransfer();
          Array.from(builderInput.files || []).forEach(function(file) { dt.items.add(file); });
          $rowFileInput[0].files = dt.files;
        }

        function renderVariantDraftRows() {
          if (!variantComposer.draftHost.length) return;
          const $body = variantComposer.draftHost.find('tbody');
          $body.empty();
          const attributePayload = readBuilderAttributePayload();
          if (attributePayload.error || !Array.isArray(attributePayload.combinations)) {
            variantComposer.draftHost.addClass('d-none');
            return;
          }

          attributePayload.combinations.forEach(function(combination, index) {
            $body.append(
              '<tr data-draft-index="' + index + '">' +
                '<td>' + renderAttributeSummaryHtml(combination.rows) + '</td>' +
                '<td><input type="text" class="form-control form-control-sm draft-sku" placeholder="SKU"></td>' +
                '<td><input type="number" step="0.01" min="0" class="form-control form-control-sm text-end draft-price" placeholder="0.00"></td>' +
                '<td><button type="button" class="btn btn-sm btn-outline-primary draft-choose-images">Choose</button><input type="file" class="d-none draft-images" accept="image/*" multiple><span class="small text-muted ms-2 draft-image-meta">No files</span><div class="variant-images-preview d-flex flex-wrap gap-1 mt-1"></div></td>' +
              '</tr>'
            );
          });
          variantComposer.draftHost.toggleClass('d-none', attributePayload.combinations.length === 0);
        }

        function syncSelectedCombinationsToRows() {
          return;
        }

        $('#add-variant-btn').on('click', function() {
          const payload = readBuilderAttributePayload();
          if (payload.error) { showVariantMessage(payload.error); return; }
          const common = {
            sku: variantComposer.sku.val().trim(),
            price: variantComposer.price.val().trim(),
            existing_images: variantBuilderMedia.filter(m => m.type === 'existing').map(m => m.path)
          };
          if (variantComposer.editingIndex === null) {
            let addedCount = 0;
            let skippedCount = 0;
            payload.combinations.forEach((c, combinationIndex) => {
              if (variantCombinationExists(c.rows, null)) {
                skippedCount++;
                return;
              }
              const idx = variantContainer.find('.variant-row').length;
              const $draftRow = variantComposer.draftHost.find('tbody tr').eq(combinationIndex);
              const rowPayload = Object.assign({}, common, {
                sku: $draftRow.length ? String($draftRow.find('.draft-sku').val() || '').trim() : common.sku,
                price: $draftRow.length ? String($draftRow.find('.draft-price').val() || '').trim() : common.price,
                attribute_value_map: c.valueMap,
                attribute_rows: c.rows
              });
              const $row = $(variantRowTemplate(idx, rowPayload));
              variantContainer.append($row);
              if ($draftRow.length && $draftRow.find('.draft-images')[0]?.files?.length) {
                const dt = new DataTransfer();
                Array.from($draftRow.find('.draft-images')[0].files || []).forEach(function(file) { dt.items.add(file); });
                $row.find('input[type="file"][data-field="images"]')[0].files = dt.files;
              } else {
                copyBuilderFilesToVariantRow($row);
              }
              initVariantRowFileMeta($row);
              addedCount++;
            });
            if (addedCount === 0) {
              showVariantMessage(skippedCount ? 'All selected variant combinations already exist.' : 'No variant combinations were generated.');
              return;
            }
            if (skippedCount > 0) {
              showVariantMessage('Skipped ' + skippedCount + ' duplicate combination(s).');
            }
          } else {
            if (payload.combinations.length !== 1) {
              showVariantMessage('Editing one row supports one checked value per selected attribute.');
              return;
            }
            if (variantCombinationExists(payload.combinations[0].rows, variantComposer.editingIndex)) {
              showVariantMessage('This variant combination already exists.');
              return;
            }
            const $target = variantContainer.find('.variant-row[data-index="' + variantComposer.editingIndex + '"]');
            if (!$target.length) {
              clearVariantComposer();
              return;
            }
            $target.find('.variant-attribute-hidden-host').html(renderAttributeHiddenInputs(variantComposer.editingIndex, payload.combinations[0].rows));
            $target.find('.variant-value-attributes').html(renderAttributeSummaryHtml(payload.combinations[0].rows));
            $target.find('input[data-field="sku"]').val(common.sku);
            $target.find('input[data-field="price"]').val(common.price);
          }
          reindexVariantRows();
          clearVariantComposer();
        });

        $(document).on('click', '.draft-choose-images', function() {
          $(this).siblings('.draft-images').trigger('click');
        });

        $(document).on('change', '.draft-images', function() {
          const $row = $(this).closest('tr');
          const files = Array.from(this.files || []);
          $row.find('.draft-image-meta').text(files.length ? files.length + ' image(s) selected' : 'No files');
          const preview = $row.find('.variant-images-preview');
          preview.empty();
          files.forEach(function(file, index) {
            readFileAsDataURL(file, function(result) {
              preview.append('<div class="image-preview-card"><img src="' + result + '" alt="Draft image ' + (index + 1) + '"></div>');
            });
          });
        });

        $(document).on('click', '.btn-remove-variant', function() {
          $(this).closest('.variant-row').remove();
          reindexVariantRows();
        });
        $(document).on('click', '.btn-edit-variant', function() {
          const $row = $(this).closest('.variant-row');
          const rowIndex = Number($row.attr('data-index'));
          variantComposer.editingIndex = Number.isFinite(rowIndex) ? rowIndex : $row.index();
          variantComposer.editState.text('Editing row ' + (variantComposer.editingIndex + 1)).removeClass('d-none');
          variantComposer.addBtn.text('Update');
          const valueMap = {};
          $row.find('input[data-field="attribute_value_map"]').each(function() { valueMap[$(this).data('attributeId')] = $(this).val(); });
          renderBuilderAttributeFields(valueMap);
          variantComposer.sku.val($row.find('input[data-field="sku"]').val());
          variantComposer.price.val($row.find('input[data-field="price"]').val());
          showVariantMessage('To change images, use "Choose" on the row.');
          $('html, body').animate({ scrollTop: $('#variant-section').offset().top - 90 }, 250);
        });

        $(document).on('click', '.btn-choose-variant-images', function() { $(this).closest('.variant-row').find('input[data-field="images"]').trigger('click'); });
        
        function initVariantRowFileMeta($row) {
          const $input = $row.find('input[data-field="images"]');
          const update = () => {
            const uploadCount = ($input[0].files || []).length;
            const existingCount = $row.find('input[data-field="existing_images"]').length;
            const totalCount = uploadCount + existingCount;
            $row.find('.variant-images-meta').text(totalCount ? totalCount + ' image(s) selected' : 'No files');
            renderVariantRowPreview($row);
          };
          $input.off('change.wcVariant').on('change.wcVariant', update);
          update();
        }
        variantContainer.find('.variant-row').each(function() { initVariantRowFileMeta($(this)); });

        function renderVariantRowPreview($row) {
          const preview = $row.find('.variant-images-preview');
          preview.empty();

          $row.find('input[data-field="existing_images"]').each(function(index) {
            const path = normalizeExistingGalleryPath($(this).val());
            if (!path) return;
            preview.append('<div class="image-preview-card"><img src="' + resolveExistingGalleryPreviewUrl(path) + '" alt="Existing image ' + (index + 1) + '"></div>');
          });

          const input = $row.find('input[data-field="images"]')[0];
          Array.from((input && input.files) || []).forEach(function(file, index) {
            readFileAsDataURL(file, function(result) {
              preview.append('<div class="image-preview-card"><img src="' + result + '" alt="Variant upload ' + (index + 1) + '"></div>');
            });
          });
        }

        $(document).on('change', '.variant-attribute-toggle', function() {
          $(this).closest('.attribute-toggle-item').toggleClass('is-active', this.checked);
          const nextSelection = selectedAttributeIds();
          const selectionChanged = JSON.stringify(nextSelection) !== JSON.stringify(selectedAttributeSnapshot);
          if (variantContainer.find('.variant-row').length > 0 && selectionChanged) {
            const shouldReset = window.confirm('Changing selected attributes will clear the current variant rows. Continue?');
            if (!shouldReset) {
              this.checked = !this.checked;
              $(this).closest('.attribute-toggle-item').toggleClass('is-active', this.checked);
              return;
            }
            variantContainer.empty();
            reindexVariantRows();
          }
          selectedAttributeSnapshot = selectedAttributeIds();
          clearVariantComposer();
          renderBuilderAttributeFields({});
        });

        $(document).on('click', '.attribute-toggle-item', function() {
          window.setTimeout(function() {
            updateSelectedAttributeCount();
            renderBuilderAttributeFields({});
          }, 0);
        });

        $(document).on('change', '.variant-builder-value-select', function() {
          showVariantMessage('');
        });

        // Variant Existing Gallery Modal
        const varGalleryModalEl = document.getElementById('variantExistingGalleryModal');
        const varGalleryModal = varGalleryModalEl ? new bootstrap.Modal(varGalleryModalEl) : null;
        function renderVariantBuilderPreview() {
          variantComposer.previewContainer.empty();
          variantBuilderMedia.forEach((m, idx) => {
            if (m.type === 'existing') {
              const url = resolveExistingGalleryPreviewUrl(m.path);
              variantComposer.previewContainer.append('<div class="image-preview-card"><img src="' + url + '"><button type="button" class="remove-btn" onclick="removeBuilderImage(' + idx + ')">&times;</button></div>');
            } else if (m.file) {
              readFileAsDataURL(m.file, function(result) {
                variantComposer.previewContainer.append('<div class="image-preview-card"><img src="' + result + '"><button type="button" class="remove-btn" onclick="removeBuilderImage(' + idx + ')">&times;</button></div>');
              });
            }
          });
        }

        variantComposer.images.off('change.wcBuilderPreview').on('change.wcBuilderPreview', function(e) {
          const existingOnly = variantBuilderMedia.filter(function(item) { return item.type === 'existing'; });
          const uploadItems = Array.from(e.target.files || []).map(function(file) {
            return { type: 'upload', file: file };
          });
          variantBuilderMedia = existingOnly.concat(uploadItems);
          renderVariantBuilderPreview();
        });

        window.removeBuilderImage = function(index) {
          if (index < 0 || index >= variantBuilderMedia.length) return;
          variantBuilderMedia.splice(index, 1);
          const dt = new DataTransfer();
          variantBuilderMedia
            .filter(function(item) { return item.type === 'upload' && item.file; })
            .forEach(function(item) { dt.items.add(item.file); });
          if (variantComposer.images[0]) {
            variantComposer.images[0].files = dt.files;
          }
          renderVariantBuilderPreview();
        };

        $('#open-variant-existing-gallery-btn').on('click', function() {
          $('.variant-existing-gallery-check').prop('checked', false);
          variantBuilderMedia
            .filter(function(item) { return item.type === 'existing'; })
            .forEach(function(item) {
              const itemPath = normalizeExistingGalleryPath(item.path);
              $('.variant-existing-gallery-check').filter(function() {
                return normalizeExistingGalleryPath($(this).val()) === itemPath;
              }).prop('checked', true);
            });
          if (varGalleryModal) varGalleryModal.show();
        });
        $('#apply-variant-existing-gallery-btn').on('click', function() {
          $('.variant-existing-gallery-check:checked').each(function() {
            var path = normalizeExistingGalleryPath($(this).val());
            if (!path) return;
            if (!variantBuilderMedia.some(m => m.type === 'existing' && normalizeExistingGalleryPath(m.path) === path)) {
              variantBuilderMedia.push({ type: 'existing', path: path });
            }
          });
          renderVariantBuilderPreview();
          if (varGalleryModal) varGalleryModal.hide();
        });

        $('#product-create-form').on('submit', function(event) {
          const isVariable = ($('input.js-product-type:checked').val() || 'simple') === 'variable';
          if (!isVariable) return true;

          const selectedAttrs = selectedAttributeIds();
          let rowCount = variantContainer.find('.variant-row').length;

          if (!selectedAttrs.length) {
            event.preventDefault();
            showVariantMessage('Please select at least one catalog attribute.');
            $('html, body').animate({ scrollTop: $('#variant-section').offset().top - 90 }, 250);
            return false;
          }

          if (!rowCount) {
            event.preventDefault();
            if (!$.trim(variantComposer.message.text())) {
              showVariantMessage('Add at least one variant row before saving.');
            }
            $('html, body').animate({ scrollTop: $('#variant-section').offset().top - 90 }, 250);
            return false;
          }

          let firstInvalidMessage = '';
          variantContainer.find('.variant-row').each(function(index) {
            const $row = $(this);
            const attributeCount = $row.find('input[data-field="attribute_value_map"]').length;
            const price = Number($row.find('input[data-field="price"]').val() || 0);
            const uploadCount = ($row.find('input[type="file"][data-field="images"]')[0]?.files || []).length;
            const existingCount = $row.find('input[data-field="existing_images"]').length;

            if (attributeCount !== selectedAttrs.length) {
              firstInvalidMessage = 'Row ' + (index + 1) + ' needs one value for every selected attribute.';
              return false;
            }
            if (!Number.isFinite(price) || price <= 0) {
              firstInvalidMessage = 'Row ' + (index + 1) + ' needs a valid price.';
              return false;
            }
            if (uploadCount + existingCount <= 0) {
              firstInvalidMessage = 'Row ' + (index + 1) + ' needs at least one image.';
              return false;
            }
          });

          if (firstInvalidMessage) {
            event.preventDefault();
            showVariantMessage(firstInvalidMessage);
            $('html, body').animate({ scrollTop: $('#variant-section').offset().top - 90 }, 250);
            return false;
          }

          reindexVariantRows();
          return true;
        });

        selectedAttributeSnapshot = selectedAttributeIds();
        updateSelectedAttributeCount();
        reindexVariantRows();
        renderBuilderAttributeFields({});
    });


    window.removeThumbnail = function() {
      $('#thumbnail').val('');
      $('#thumbnail-preview').hide();
      $('#thumbnail-img').attr('src', '#');
    };

    window.deleteGalleryImage = function(id) {
      if (confirm('Are you sure you want to delete this image?')) {
        const form = $('#delete-gallery-image-form');
        $('#delete-image-id').val(id);
        form.attr('action', "{{ route('admin.products.image.destroy') }}");
        form.submit();
      }
    };
</script>

@endsection
