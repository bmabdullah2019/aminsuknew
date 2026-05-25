<!-- Dynamic Sidebar Categories Component -->
<aside class="sidebar-categories">
    <div class="categories-header">
        <h3 class="categories-title">
            <i class="fas fa-th-large"></i>
            Categories
        </h3>
    </div>

    @if($menucategories && $menucategories->count() > 0)
    <nav class="categories-nav">
        <ul class="categories-list">
            @foreach($menucategories as $category)
            <li class="category-item-sidebar">
                @php
                    $icons = [
                        'vegetables' => 'fas fa-leaf',
                        'fruits' => 'fas fa-apple-alt',
                        'dairy' => 'fas fa-cheese',
                        'meat' => 'fas fa-drumstick-bite',
                        'bakery' => 'fas fa-bread-slice',
                        'beverages' => 'fas fa-wine-glass',
                        'snacks' => 'fas fa-popcorn',
                        'grocery' => 'fas fa-shopping-basket',
                        'frozen' => 'fas fa-snowflake',
                        'health' => 'fas fa-spa',
                        'organic' => 'fas fa-leaf',
                        'baby' => 'fas fa-baby',
                        'health & wellness' => 'fas fa-heartbeat',
                        'fresh & organic' => 'fas fa-carrot',
                        'fresh vegetables' => 'fas fa-carrot',
                        'meat & fish' => 'fas fa-fish',
                        'dairy & eggs' => 'fas fa-egg',
                        'bakery & snacks' => 'fas fa-bread-slice',
                        'rice, pulses' => 'fas fa-bowl-rice',
                        'beverages & juices' => 'fas fa-wine-glass',
                        'frozen foods' => 'fas fa-snowflake',
                        'sauces, pickels' => 'fas fa-jar',
                        'baby food & care' => 'fas fa-baby-carriage',
                    ];
                    
                    $catName = strtolower($category->name);
                    $icon = 'fas fa-cube';
                    
                    foreach($icons as $key => $iconClass) {
                        if(strpos($catName, $key) !== false) {
                            $icon = $iconClass;
                            break;
                        }
                    }
                    
                    $hasSubcategories = $category->subcategories && $category->subcategories->count() > 0;
                @endphp

                <a href="{{ route('category', $category->slug) }}" class="category-link-sidebar">
                    <span class="category-icon-sidebar">
                        <i class="{{ $icon }}"></i>
                    </span>
                    <span class="category-text">{{ $category->name }}</span>
                    @if($hasSubcategories)
                    <span class="category-toggle">
                        <i class="fas fa-chevron-right"></i>
                    </span>
                    @endif
                </a>

                @if($hasSubcategories)
                <ul class="subcategories-list">
                    @foreach($category->subcategories as $subcategory)
                    <li class="subcategory-item">
                        @php $hasChildcategories = $subcategory->childcategories && $subcategory->childcategories->count() > 0; @endphp
                        <a href="{{ route('subcategory', $subcategory->slug) }}" class="subcategory-link">
                            <span>{{ $subcategory->subcategoryName }}</span>
                            @if($hasChildcategories)
                            <i class="fas fa-chevron-right"></i>
                            @endif
                        </a>

                        @if($hasChildcategories)
                        <ul class="childcategories-list">
                            @foreach($subcategory->childcategories as $childcat)
                            <li class="childcategory-item">
                                <a href="{{ route('products', $childcat->slug) }}" class="childcategory-link">
                                    {{ $childcat->childcategoryName }}
                                </a>
                            </li>
                            @endforeach
                        </ul>
                        @endif
                    </li>
                    @endforeach
                </ul>
                @endif
            </li>
            @endforeach
        </ul>
    </nav>
    @else
    <div class="categories-empty">
        <p>No categories available</p>
    </div>
    @endif
</aside>
