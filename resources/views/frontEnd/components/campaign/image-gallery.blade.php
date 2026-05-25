<!-- Campaign Images Gallery -->
@if($campaign->image_one || $campaign->image_two || $campaign->image_three)
<section>
    <div class="container py-2 py-md-4">
        <div class="row gy-2">
            @if($campaign->image_one)
            <div class="col-sm-6">
                <img class="img-fluid shadow rounded" src="{{ asset($campaign->image_one) }}" alt="{{ $campaign->name }}">
            </div>
            @endif
            @if($campaign->image_two)
            <div class="col-sm-6">
                <img class="img-fluid shadow rounded" src="{{ asset($campaign->image_two) }}" alt="{{ $campaign->name }}">
            </div>
            @endif
        </div>
        @if($campaign->image_three)
        <div class="row gy-2 mt-3">
            <div class="col-sm-12">
                <img class="img-fluid shadow rounded" src="{{ asset($campaign->image_three) }}" alt="{{ $campaign->name }}">
            </div>
        </div>
        @endif
    </div>
</section>
@endif
