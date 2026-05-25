<!-- Dynamic Hero Slider with Sidebar Categories Layout -->
<section class="hero-slider-section">
    <div class="container">
        <div class="hero-slider-wrapper">
            <!-- Dynamic Sidebar Categories -->
            @include('frontEnd.layouts.components.sidebar-categories', ['menucategories' => $menucategories])

            <!-- Dynamic Hero Slider -->
            <div class="hero-slider-content">
                @if($sliders && $sliders->count() > 0)
                    <div class="hero-slider owl-carousel" id="heroSlider">
                        @foreach($sliders as $slider)
                        <div class="slider-item" data-link="{{ $slider->link ?? '#' }}">
                            <img src="{{ asset($slider->image) }}" alt="Hero Slider" />
                        </div>
                        @endforeach
                    </div>
                @else
                    <!-- Fallback: Display a placeholder with gradient -->
                    <div class="hero-slider-placeholder">
                        <div style="width: 100%; height: 400px; background: linear-gradient(135deg, #d1fae5 0%, #fef3c7 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                            <div style="text-align: center; color: #1a6b6b;">
                                <i class="fas fa-images" style="font-size: 80px; margin-bottom: 20px; display: block; opacity: 0.5;"></i>
                                <p style="font-size: 18px; font-weight: 600;">No Sliders Available</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Owl Carousel for Hero Slider
    if (jQuery && jQuery().owlCarousel) {
        jQuery('#heroSlider').owlCarousel({
            loop: true,
            margin: 0,
            autoplay: true,
            autoplayTimeout: 5000,
            autoplayHoverPause: true,
            nav: true,
            navText: ['<i class="fas fa-chevron-left"></i>', '<i class="fas fa-chevron-right"></i>'],
            dots: true,
            responsive: {
                0: {
                    items: 1
                },
                600: {
                    items: 1
                },
                1024: {
                    items: 1
                }
            },
            onChanged: function(event) {
                // Add click handler for slider items
                jQuery('#heroSlider .owl-item').off('click').on('click', function() {
                    var currentItem = jQuery(this);
                    if (currentItem.hasClass('active')) {
                        var link = currentItem.find('img').closest('.slider-item').data('link');
                        if (link && link !== '#') {
                            window.location.href = link;
                        }
                    }
                });
            }
        });

        // Add click handler for slider items
        jQuery('#heroSlider .owl-item').on('click', function() {
            var currentItem = jQuery(this);
            if (currentItem.hasClass('active')) {
                var link = currentItem.find('img').closest('.slider-item').data('link');
                if (link && link !== '#') {
                    window.location.href = link;
                }
            }
        });
    }
});
</script>
