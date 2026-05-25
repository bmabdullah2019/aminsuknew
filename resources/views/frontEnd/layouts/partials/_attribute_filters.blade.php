@php
    $sizeFilters = $sizeFilters ?? collect();
    $colorFilters = $colorFilters ?? collect();
    $ageFilters = $ageFilters ?? collect();
    $brandFilters = $brandFilters ?? collect();
    $selectedSizeIds = $selectedSizeIds ?? [];
    $selectedColorIds = $selectedColorIds ?? [];
    $selectedAgeIds = $selectedAgeIds ?? [];
    $selectedBrandIds = $selectedBrandIds ?? [];
@endphp

@if($sizeFilters->isNotEmpty() || $colorFilters->isNotEmpty() || $ageFilters->isNotEmpty() || $brandFilters->isNotEmpty())
    <div class="attribute-filter-groups">
        @if($sizeFilters->isNotEmpty())
            <div class="attribute-filter-group">
                <h6 class="attribute-filter-title">Size</h6>
                <ul class="">


                    @foreach ($sizeFilters as $sizeFilter)
                        <li class="subcategory-filter-list">
                            <label for="size-filter-{{ $sizeFilter->id }}" class="subcategory-filter-label">
                                <input class="form-checkbox form-attribute"
                                    id="size-filter-{{ $sizeFilter->id }}"
                                    name="size[]"
                                    value="{{ $sizeFilter->id }}"
                                    type="checkbox"
                                    @if(in_array((int) $sizeFilter->id, $selectedSizeIds, true)) checked @endif />
                                <p class="subcategory-filter-name">{{ $sizeFilter->sizeName }}</p>
                            </label>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($colorFilters->isNotEmpty())
            <div class="attribute-filter-group">
                <h6 class="attribute-filter-title">Color</h6>
                <ul class="">


                    @foreach ($colorFilters as $colorFilter)
                        <li class="subcategory-filter-list">
                            <label for="color-filter-{{ $colorFilter->id }}" class="subcategory-filter-label">
                                <input class="form-checkbox form-attribute"
                                    id="color-filter-{{ $colorFilter->id }}"
                                    name="color[]"
                                    value="{{ $colorFilter->id }}"
                                    type="checkbox"
                                    @if(in_array((int) $colorFilter->id, $selectedColorIds, true)) checked @endif />
                                <p class="subcategory-filter-name">{{ $colorFilter->colorName }}</p>
                            </label>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($ageFilters->isNotEmpty())
            <div class="attribute-filter-group">
                <h6 class="attribute-filter-title">Age</h6>
                <ul class="">


                    @foreach ($ageFilters as $ageFilter)
                        <li class="subcategory-filter-list">
                            <label for="age-filter-{{ $ageFilter->id }}" class="subcategory-filter-label">
                                <input class="form-checkbox form-attribute"
                                    id="age-filter-{{ $ageFilter->id }}"
                                    name="age[]"
                                    value="{{ $ageFilter->id }}"
                                    type="checkbox"
                                    @if(in_array((int) $ageFilter->id, $selectedAgeIds, true)) checked @endif />
                                <p class="subcategory-filter-name">{{ $ageFilter->ageName }}</p>
                            </label>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($brandFilters->isNotEmpty())
            <div class="attribute-filter-group">
                <h6 class="attribute-filter-title">Brand</h6>
                <ul class="space-y-1">

                    @foreach ($brandFilters as $brandFilter)
                        <li class="subcategory-filter-list">
                            <label for="brand-filter-{{ $brandFilter->id }}" class="subcategory-filter-label">
                                <input class="form-checkbox form-attribute"
                                    id="brand-filter-{{ $brandFilter->id }}"
                                    name="brand[]"
                                    value="{{ $brandFilter->id }}"
                                    type="checkbox"
                                    @if(in_array((int) $brandFilter->id, $selectedBrandIds, true)) checked @endif />
                                <p class="subcategory-filter-name">{{ $brandFilter->name }}</p>
                            </label>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endif
