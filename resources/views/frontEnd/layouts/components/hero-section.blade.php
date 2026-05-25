<!-- Hero Section with Categories and Slider -->
<section class="hero-section">
    <div class="container">
        <div class="hero-content">
            <div class="hero-text">
                @if($heroData['badge'] ?? null)
                <div class="hero-badge">
                    {{ $heroData['badge'] }}
                </div>
                @endif
                
                <h1>
                    {{ $heroData['title'] ?? 'Welcome to Our Store' }}
                    @if($heroData['highlight'] ?? null)
                        <span>{{ $heroData['highlight'] }}</span>
                    @endif
                </h1>
                
                @if($heroData['description'] ?? null)
                <p class="hero-description">
                    {{ $heroData['description'] }}
                </p>
                @endif
                
                @if($heroData['cta_text'] ?? null)
                <a href="{{ $heroData['cta_link'] ?? 'javascript:void(0)' }}" class="hero-cta">
                    {{ $heroData['cta_text'] }}
                    <i class="fas fa-arrow-right"></i>
                </a>
                @endif
            </div>
            
            @if($heroData['image'] ?? null)
            <div class="hero-image">
                @php
                    $imagePath = $heroData['image'];
                    $fullPath = public_path($imagePath);
                    $imageExists = file_exists($fullPath);
                @endphp
                @if($imageExists)
                    <img src="{{ asset($imagePath) }}" alt="Hero Image" />
                @else
                    <!-- Fallback: Display a placeholder with gradient -->
                    <div style="width: 100%; height: 400px; background: linear-gradient(135deg, #d1fae5 0%, #fef3c7 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <div style="text-align: center; color: #1a6b6b;">
                            <i class="fas fa-box-open" style="font-size: 80px; margin-bottom: 20px; display: block; opacity: 0.5;"></i>
                            <p style="font-size: 18px; font-weight: 600;">Hero Image Coming Soon</p>
                        </div>
                    </div>
                @endif
            </div>
            @endif
        </div>
    </div>
</section>

<!-- Category Showcase with Icons -->
@if($frontcategory->count() > 0)
<section class="category-showcase">
    <div class="container">
        <h2 class="category-showcase-title">Shop by Category</h2>
        <div class="category-grid">
            @foreach($frontcategory as $category)
            <a href="{{ route('category', $category->slug) }}" class="category-item">
                <div class="category-icon">
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
                            'rice, pulses' => 'fas fa-bowlWithSpoon',
                            'beverages & juices' => 'fas fa-wine-glass',
                            'frozen foods' => 'fas fa-snowflake',
                            'sauces, pickels' => 'fas fa-jar',
                            'baby food & care' => 'fas fa-baby-carriage',
                        ];
                        
                        $categoryName = strtolower($category->name);
                        $icon = 'fas fa-cube';
                        
                        foreach($icons as $key => $iconClass) {
                            if(strpos($categoryName, $key) !== false) {
                                $icon = $iconClass;
                                break;
                            }
                        }
                    @endphp
                    <i class="{{ $icon }}"></i>
                </div>
                <span class="category-name">{{ $category->name }}</span>
            </a>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- Hero Slider (Optional - if you want dedicated slider section) -->
@if($sliders && $sliders->count() > 0)
<section class="mb-5">
    <div class="container">
        <div class="hero-slider-container">
            <div class="hero-slider owl-carousel">
                @foreach($sliders as $slider)
                <div class="slider-item">
                    <img src="{{ asset($slider->image) }}" alt="Slider" />
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif
