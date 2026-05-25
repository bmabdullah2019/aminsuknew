@php
    $allCategories = ($categories ?? $menucategories ?? collect());
    $sellzyCategories = $allCategories;
    $categoryIcons = [
        'fresh' => 'fa-apple-whole',
        'organic' => 'fa-apple-whole',
        'baby' => 'fa-baby',
        'vegetable' => 'fa-carrot',
        'meat' => 'fa-fish',
        'fish' => 'fa-fish',
        'dairy' => 'fa-egg',
        'egg' => 'fa-egg',
        'bakery' => 'fa-bread-slice',
        'snack' => 'fa-cookie-bite',
        'rice' => 'fa-bowl-rice',
        'pulse' => 'fa-bowl-food',
        'beverage' => 'fa-bottle-water',
        'juice' => 'fa-bottle-water',
        'frozen' => 'fa-snowflake',
        'sauce' => 'fa-jar',
        'pickle' => 'fa-jar',
        'health' => 'fa-heart-pulse',
        'wellness' => 'fa-heart-pulse',
    ];
@endphp

<aside class="sellzy-side-card" data-sellzy-category-sidebar>
    @forelse($sellzyCategories as $key => $category)
        @php
            $categoryName = strtolower($category->name);
            $icon = 'fa-basket-shopping';
            $subcategories = $category->subcategories ?? collect();
            $hasSubcategories = $subcategories->isNotEmpty();

            foreach ($categoryIcons as $needle => $candidate) {
                if (str_contains($categoryName, $needle)) {
                    $icon = $candidate;
                    break;
                }
            }
            $isHidden = $key >= 10;
        @endphp
        <div class="sellzy-side-item {{ $isHidden ? 'sellzy-side-extra' : '' }}" data-sellzy-category-extra style="{{ $isHidden ? 'display:none;' : '' }}">
            <a href="{{ route('category', $category->slug) }}" class="sellzy-side-link">
                <span><i class="fa-solid {{ $icon }}"></i></span>
                {{ $category->name }}
                @if($hasSubcategories)<i class="fa-solid fa-chevron-right"></i>@endif
            </a>

            @if($hasSubcategories)
                <div class="sellzy-sub-dropdown">
                    @foreach($subcategories as $subcategory)
                        @php
                            $childcategories = $subcategory->childcategories ?? collect();
                            $hasChildcategories = $childcategories->isNotEmpty();
                        @endphp
                        <div class="sellzy-sub-item">
                            <a href="{{ route('subcategory', $subcategory->slug) }}">
                                {{ $subcategory->subcategoryName }}
                                @if($hasChildcategories)<i class="fa-solid fa-chevron-right"></i>@endif
                            </a>

                            @if($hasChildcategories)
                                <div class="sellzy-child-dropdown">
                                    @foreach($childcategories as $childcategory)
                                        <a href="{{ route('products', $childcategory->slug) }}">{{ $childcategory->childcategoryName }}</a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @empty
        <a href="{{ route('shop') }}" class="sellzy-side-link">
            <span><i class="fa-solid fa-basket-shopping"></i></span>
            Shop All
        </a>
    @endforelse

    @if($sellzyCategories->count() > 10)
        <div class="sellzy-side-item sellzy-more-toggle-item">
            <a href="javascript:void(0)" class="sellzy-side-link" data-sellzy-category-toggle aria-expanded="false">
                <span><i class="fa-solid fa-plus-circle" data-sellzy-toggle-icon></i></span>
                <span data-sellzy-toggle-text>More Categories</span>
                <i class="fa-solid fa-chevron-down" data-sellzy-toggle-chevron></i>
            </a>
        </div>
    @endif
</aside>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[data-sellzy-category-sidebar]').forEach(function(sidebar) {
            if (sidebar.dataset.sellzyCategoryInitialized === '1') {
                return;
            }

            sidebar.dataset.sellzyCategoryInitialized = '1';

            const toggleBtn = sidebar.querySelector('[data-sellzy-category-toggle]');
            const extras = Array.from(sidebar.querySelectorAll('[data-sellzy-category-extra]'));

            if (!toggleBtn || extras.length === 0) {
                return;
            }

            const text = sidebar.querySelector('[data-sellzy-toggle-text]');
            const icon = sidebar.querySelector('[data-sellzy-toggle-icon]');
            const chevron = sidebar.querySelector('[data-sellzy-toggle-chevron]');

            const updateState = function(expanded) {
                extras.forEach(function(item) {
                    item.style.display = expanded ? 'block' : 'none';
                });

                if (text) {
                    text.innerText = expanded ? 'Show Less' : 'More Categories';
                }

                if (icon) {
                    icon.classList.toggle('fa-plus-circle', !expanded);
                    icon.classList.toggle('fa-minus-circle', expanded);
                }

                if (chevron) {
                    chevron.classList.toggle('fa-chevron-down', !expanded);
                    chevron.classList.toggle('fa-chevron-up', expanded);
                }

                toggleBtn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            };

            updateState(false);

            toggleBtn.addEventListener('click', function(event) {
                event.preventDefault();
                const isCollapsed = extras[0].style.display === 'none';
                updateState(isCollapsed);
            });
        });
    });
</script>
