<!-- Campaign Product & Reviews Section -->
<section>
    <div class="container">
        <!-- Product Details -->
        @if($campaign->name)
        <div class="row mb-4">
            <div class="col-sm-12">
                <div class="campro_inn">
                    <div class="campro_head">
                        <h2>{{ $campaign->name }}</h2>
                    </div>
                    <div class="campro_img_slider owl-carousel">
                        @if($campaign->image_one)
                        <div class="campro_img_item">
                            <img src="{{ asset($campaign->image_one) }}" alt="{{ $campaign->name }}">
                        </div> 
                        @endif
                        @if($campaign->image_two)
                        <div class="campro_img_item">
                            <img src="{{ asset($campaign->image_two) }}" alt="{{ $campaign->name }}">
                        </div> 
                        @endif
                        @if($campaign->image_three)
                        <div class="campro_img_item">
                            <img src="{{ asset($campaign->image_three) }}" alt="{{ $campaign->name }}">
                        </div>
                        @endif
                    </div>
                    <div class="col-sm-12 mt-3">
                        <div class="ord_btn text-center">
                            <a href="#order_form" class="cam_order_now"> অর্ডার করতে ক্লিক করুন <i class="fa-solid fa-hand-point-right"></i> </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Customer Reviews -->
        @if($campaign->review && $campaign->images->count() > 0)
        <div class="row">
            <div class="col-sm-12">
                <div class="rev_inn">
                    <h2 class="campaign_offer">{{ $campaign->review }}</h2>
                    <div class="review_slider owl-carousel">
                        @foreach($campaign->images as $image)
                        <div class="review_item">
                            <img src="{{ asset($image->image) }}" alt="{{ $campaign->name }}" style="max-height: 300px; object-fit: cover;">
                        </div>
                        @endforeach
                    </div>
                    <div class="col-sm-12 mt-3">
                        <div class="ord_btn text-center">
                            <a href="#order_form" class="cam_order_now"> অর্ডার করতে ক্লিক করুন <i class="fa-solid fa-hand-point-right"></i> </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</section>
