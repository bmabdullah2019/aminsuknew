<!-- Description Section -->
@if((optional($campaign)->short_description && strlen($campaign->short_description) > 15) || 
    (optional($campaign)->description && strlen($campaign->description) > 15))
<section class="rules_sec">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-body">
                        <h2>বিস্তারিত</h2>
                        <div class="campaign-description-content">
                        {!! $campaign->short_description !!}
                        <br><br>
                        {!! $campaign->description !!} 
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endif
