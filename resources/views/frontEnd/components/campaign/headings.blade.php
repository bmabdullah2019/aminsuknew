<!-- Featured Heading Section -->
@if($campaign->heading_1 || $campaign->heading_2)
<section>
    <div class="container py-2 py-md-4">
        @if($campaign->heading_1)
        <div class="py-2 py-md-4 rounded" style="border:2px dashed green">
            <h2 class="animated-heading text-center">{!! $campaign->heading_1 !!}</h2>
        </div>
        @endif

        @if($campaign->heading_2)
        <div class="py-2 py-md-4 rounded" style="border:2px dashed green; margin-top: 1rem;">
            <h2 class="animated-heading text-center">{!! $campaign->heading_2 !!}</h2>
        </div>
        @endif

        @if($campaign->heading_3)
        <div class="py-2 py-md-4 rounded" style="border:2px dashed green; margin-top: 1rem;">
            <h2 class="animated-heading text-center">{!! $campaign->heading_3 !!}</h2>
        </div>
        @endif
    </div>
</section>
@endif
