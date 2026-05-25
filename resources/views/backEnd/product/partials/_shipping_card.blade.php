@php
    $product = $product ?? null;
    $shippingProfiles = collect($shippingProfiles ?? []);
    $selectedShippingType = old('shipping_type', optional($product)->shipping_type);
@endphp

<div class="card product-section-card wc-compact-card mb-3" id="product-shipping-card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0 wc-form-section-title">Shipping Information</h5>
    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#shippingInformationBody" aria-expanded="true" aria-controls="shippingInformationBody">
      Toggle
    </button>
  </div>
  <div class="collapse show" id="shippingInformationBody">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label for="shipping_type" class="form-label">Shipping Type</label>
          <select id="shipping_type" name="shipping_type" class="form-control @error('shipping_type') is-invalid @enderror">
            <option value="" {{ $selectedShippingType === null || $selectedShippingType === '' ? 'selected' : '' }}>Legacy shipping</option>
            <option value="weight_based" {{ $selectedShippingType === 'weight_based' ? 'selected' : '' }}>Weight based</option>
            <option value="fixed_rate" {{ $selectedShippingType === 'fixed_rate' ? 'selected' : '' }}>Fixed rate</option>
            <option value="free_shipping" {{ $selectedShippingType === 'free_shipping' ? 'selected' : '' }}>Free shipping</option>
            <option value="digital" {{ $selectedShippingType === 'digital' ? 'selected' : '' }}>Digital</option>
          </select>
          @error('shipping_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-8 d-flex align-items-end">
          <div class="alert alert-info py-2 px-3 mb-0 w-100 shipping-type-panel" data-shipping-panel="legacy">
            Existing legacy shipping area rate will be used.
          </div>
          <div class="alert alert-success py-2 px-3 mb-0 w-100 shipping-type-panel d-none" data-shipping-panel="free_shipping">
            This product ships for free.
          </div>
          <div class="alert alert-secondary py-2 px-3 mb-0 w-100 shipping-type-panel d-none" data-shipping-panel="digital">
            No shipping required for this digital product.
          </div>
        </div>

        <div class="col-12 shipping-type-panel d-none" data-shipping-panel="weight_based">
          <div class="row g-3">
            <div class="col-md-3">
              <label for="weight" class="form-label">Weight (kg)</label>
              <input type="number" step="0.001" min="0" id="weight" name="weight" class="form-control @error('weight') is-invalid @enderror" value="{{ old('weight', optional($product)->weight) }}">
              @error('weight')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
              <label for="shipping_profile_id" class="form-label">Shipping Profile</label>
              <select id="shipping_profile_id" name="shipping_profile_id" class="form-control select2-single @error('shipping_profile_id') is-invalid @enderror">
                <option value="">Select profile</option>
                @foreach($shippingProfiles as $profile)
                  <option value="{{ $profile->id }}" {{ (string) old('shipping_profile_id', optional($product)->shipping_profile_id) === (string) $profile->id ? 'selected' : '' }}>
                    {{ $profile->name }}{{ $profile->is_default ? ' (Default)' : '' }}
                  </option>
                @endforeach
              </select>
              @error('shipping_profile_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-2">
              <label for="length" class="form-label">Length (cm)</label>
              <input type="number" step="0.01" min="0" id="length" name="length" class="form-control @error('length') is-invalid @enderror" value="{{ old('length', optional($product)->length) }}">
              @error('length')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-2">
              <label for="width" class="form-label">Width (cm)</label>
              <input type="number" step="0.01" min="0" id="width" name="width" class="form-control @error('width') is-invalid @enderror" value="{{ old('width', optional($product)->width) }}">
              @error('width')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-2">
              <label for="height" class="form-label">Height (cm)</label>
              <input type="number" step="0.01" min="0" id="height" name="height" class="form-control @error('height') is-invalid @enderror" value="{{ old('height', optional($product)->height) }}">
              @error('height')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>
        </div>

        <div class="col-md-4 shipping-type-panel d-none" data-shipping-panel="fixed_rate">
          <label for="fixed_shipping_cost" class="form-label">Fixed Shipping Cost</label>
          <input type="number" step="1" min="0" id="fixed_shipping_cost" name="fixed_shipping_cost" class="form-control @error('fixed_shipping_cost') is-invalid @enderror" value="{{ old('fixed_shipping_cost', optional($product)->fixed_shipping_cost) }}">
          @error('fixed_shipping_cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
      </div>
    </div>
  </div>
</div>

@once
<script>
document.addEventListener('DOMContentLoaded', function () {
    const typeSelect = document.getElementById('shipping_type');
    if (!typeSelect) {
        return;
    }

    const panels = Array.prototype.slice.call(document.querySelectorAll('.shipping-type-panel'));

    function toggleShippingPanels() {
        const value = typeSelect.value || 'legacy';

        panels.forEach(function (panel) {
            const isActive = panel.getAttribute('data-shipping-panel') === value;
            panel.classList.toggle('d-none', !isActive);
            panel.querySelectorAll('input, select, textarea').forEach(function (field) {
                field.disabled = !isActive;
            });
        });
    }

    typeSelect.addEventListener('change', toggleShippingPanels);
    toggleShippingPanels();
});
</script>
@endonce
