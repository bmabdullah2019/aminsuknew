<!-- Campaign Video Section -->
@if($campaign->video)
<section class="camp_video_sec py-4">
    <div class="container">
        <div class="row justify-content-center gy-2 gy-md-4">
            <div class="col-md-8">
                <h2 class="p-2 py-md-3 rounded text-center" style="background-color:black; border:green 2px solid; color:white; font-weight:bolder;">
                    প্রডাক্টের "ভিডিও দেখুন"
                </h2>
            </div>
            <div class="col-md-8 col-sm-12">
                <div class="camp_vid rounded" style="border:5px solid red">
                    <iframe width="100%" height="480" 
                        src="https://www.youtube.com/embed/{{ $campaign->video }}" 
                        title="{{ $campaign->name }}" 
                        frameborder="0" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                        allowfullscreen="">
                    </iframe>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="ord_btn text-center">
                    <a href="#order_form" class="cam_order_now"> অর্ডার করতে ক্লিক করুন <i class="fa-solid fa-hand-point-right"></i> </a>
                </div>
            </div>
        </div>
    </div>
</section>
@endif
