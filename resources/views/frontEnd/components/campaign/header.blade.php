<!-- Campaign Header Section -->
<section style="background-image: radial-gradient(at center center, #139525 28%, #0E320F 79%)">
    <div class="container py-2 py-md-4">
        <div class="row gy-2">
            <div class="col-md-7">
                <h4 class="text-light text-center py-2 py-md-4 fw-bolder">
                    {!! $campaign->top_title_1 !!} 
                    <span class="text-warning">{!! $campaign->top_title_2 !!}</span>
                </h4>
            </div>
            <div class="col-md-5">
                <div class="countdown-container">
                    <div class="countdown" id="countdown">
                        <div class="row g-1">
                            @foreach(['days' => 'Days', 'hours' => 'Hours', 'minutes' => 'Minutes', 'seconds' => 'Seconds'] as $unit => $label)
                            <div class="col-3">
                                <div class="counter-card">
                                    <div id="{{ $unit }}"></div>
                                    <span>{{ $label }}</span>
                                </div> 
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
