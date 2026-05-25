@php
    $siteName = $generalsetting->name ?? config('app.name');
    $seoTitle = "";
    $seoDescription = "";
    $showExtraContent = true;

    // Detect Page Type and Generate Content
    if (isset($category) && request()->routeIs('category')) {
        $seoTitle = $category->meta_title ?? $category->name;
        $seoDescription = $category->meta_description ?? '';
        $showExtraContent = false;
    } elseif (isset($subcategory) && request()->routeIs('subcategory')) {
        $seoTitle = $subcategory->meta_title ?? $subcategory->subcategoryName;
        $seoDescription = $subcategory->meta_description ?? '';
        $showExtraContent = false;
    } elseif (isset($childcategory) && request()->routeIs('products')) {
        $seoTitle = $childcategory->meta_title ?? $childcategory->childcategoryName;
        $seoDescription = $childcategory->meta_description ?? '';
        $showExtraContent = false;
    } elseif (request()->routeIs('home')) {
        $seoTitle = "Best Online Shopping in Bangladesh - " . $siteName;
        $categories = ($menucategories ?? collect())->take(5)->pluck('name')->implode(', ');
        $seoDescription = "Welcome to <strong>{$siteName}</strong>, your trusted destination for premium quality products in Bangladesh. We offer a wide variety of items across categories like <strong>{$categories}</strong> and much more. Enjoy a seamless shopping experience with secure payments, fast delivery, and excellent customer service.";
    } elseif (isset($product) && request()->routeIs('product')) {
        $prodName = $product->name ?? 'Product';
        $seoTitle = "{$prodName} Online in Bangladesh";
        $seoDescription = "Get the best deal on <strong>{$prodName}</strong> at <strong>{$siteName}</strong>. This premium <strong>{$prodName}</strong> is designed to provide maximum value and performance. Order online now to get the best price in Bangladesh with reliable shipping and authentic quality guarantee.";
    } elseif (request()->routeIs('products')) {
        // General products
        $seoTitle = "Explore Our Collection - " . $siteName;
        $seoDescription = "Explore the wide range of products available at <strong>{$siteName}</strong>. From daily essentials to premium luxury items, we have everything you need. Quality products at the most affordable prices in Bangladesh.";
    } else {
        // Fallback
        $seoTitle = $siteName . " - Quality You Can Trust";
        $seoDescription = "Shop at <strong>{$siteName}</strong> for the best selection of products in Bangladesh. We are committed to providing our customers with top-notch items, great discounts, and a hassle-free shopping journey.";
    }
@endphp

<section class="seo-footer-content" @if(empty($seoDescription)) style="display:none;" @endif>
    <div class="container">
        <div class="seo-content-wrapper">
            @if(!empty($seoTitle))
            <h2 class="seo-heading">{{ $seoTitle }}</h2>
            @endif
            <div class="seo-text-container" id="seoTextContainer">
                <div class="seo-description">
                    {!! $seoDescription !!}
                </div>
                @if($showExtraContent)
                <div class="seo-extra-content" id="seoExtraContent">
                    <p>
                        At <strong>{{ $siteName }}</strong>, we prioritize customer satisfaction. Whether you are looking for electronics, fashion, home decor, or daily groceries, our platform is designed to make your life easier. We bridge the gap between quality and affordability, ensuring that every purchase you make is worth it. Stay tuned for our seasonal sales and hot deals to save more on your favorite items.
                    </p>
                </div>
                <button class="seo-read-more-btn" onclick="toggleSeoContent()" id="seoReadMoreBtn">Read More</button>
                @endif
            </div>
        </div>
    </div>
</section>

<style>
    .seo-footer-content {
        padding: 40px 0;
        background-color: #f9fafb;
        border-top: 1px solid #eee;
        border-bottom: 1px solid #eee;
        margin-top: 20px;
    }
    .seo-content-wrapper {
        max-width: 1000px;
        margin: 0 auto;
        text-align: center;
    }
    .seo-heading {
        font-size: 20px;
        font-weight: 700;
        color: #333;
        margin-bottom: 15px;
        text-transform: capitalize;
    }
    .seo-description {
        font-size: 14px;
        line-height: 1.8;
        color: #666;
        margin-bottom: 10px;
    }
    .seo-extra-content {
        display: none;
        font-size: 14px;
        line-height: 1.8;
        color: #666;
        margin-top: 10px;
    }
    .seo-read-more-btn {
        background: none;
        border: none;
        color: #ff5722;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        padding: 5px 10px;
        text-decoration: underline;
        transition: color 0.3s;
    }
    .seo-read-more-btn:hover {
        color: #e64a19;
    }
    .seo-text-container.active .seo-extra-content {
        display: block;
    }
</style>

<script>
    function toggleSeoContent() {
        const container = document.getElementById('seoTextContainer');
        const btn = document.getElementById('seoReadMoreBtn');
        const extra = document.getElementById('seoExtraContent');
        
        if (container.classList.contains('active')) {
            container.classList.remove('active');
            btn.innerText = 'Read More';
        } else {
            container.classList.add('active');
            btn.innerText = 'Read Less';
        }
    }
</script>
