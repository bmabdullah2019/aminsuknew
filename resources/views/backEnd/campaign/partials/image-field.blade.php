@php
    $selectedExisting = old($existingName, '');
    $previewPath = $selectedExisting ?: ($current ?? '');
@endphp

<div class="{{ $column ?? 'col-sm-6' }} mb-3">
    <div class="form-group campaign-image-field">
        <label for="{{ $field }}" class="form-label">{{ $label }}</label>
        <input type="file" class="form-control @error($field) is-invalid @enderror" name="{{ $field }}" id="{{ $field }}">
        <input type="hidden" name="{{ $existingName }}" id="{{ $existingName }}" value="{{ $selectedExisting }}">
        <div class="d-flex flex-wrap gap-2 mt-2">
            <button type="button"
                    class="btn btn-sm btn-outline-primary campaign-gallery-open"
                    data-mode="single"
                    data-target-input="{{ $existingName }}"
                    data-preview="{{ $field }}_preview">
                Choose from Gallery
            </button>
            <button type="button"
                    class="btn btn-sm btn-light campaign-gallery-clear"
                    data-target-input="{{ $existingName }}"
                    data-preview="{{ $field }}_preview">
                Clear Gallery Image
            </button>
        </div>
        <div class="campaign-image-preview mt-2" id="{{ $field }}_preview">
            @if($previewPath)
                <img src="{{ asset($previewPath) }}" alt="" class="edit-image border">
                <div class="small text-muted mt-1">{{ $previewPath }}</div>
            @endif
        </div>
        @error($field)
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
        @error($existingName)
            <span class="text-danger small d-block mt-1">{{ $message }}</span>
        @enderror
    </div>
</div>
