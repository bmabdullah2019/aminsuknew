@extends('frontEnd.layouts.master')
@section('title','Hot Deals')
@push('seo')
    <meta name="description" content="Hot Deals - {{ $generalsetting->meta_description ?? 'Best offers and discounts' }}" />
    <meta name="keywords" content="{{ $generalsetting->meta_keyword ?? 'deals, offers, discount' }}" />
    <meta property="og:title" content="Hot Deals - {{ $generalsetting->name ?? config('app.name') }}" />
    <meta property="og:url" content="{{ url()->current() }}" />
@endpush
@push('css')
<link rel="stylesheet" href="{{asset('public/frontEnd/css/jquery-ui.css')}}" />
@endpush 
@section('content')



@endsection
@push('seo_content')
@if(!empty($generalsetting->meta_description))
<section class="homeproduct">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="meta_des">
                    {!! $generalsetting->meta_description !!}
                </div>
            </div>
        </div>
    </div>
</section>
@endif
@endpush
@push('script')
<script>
    $(".sort").change(function(){
       $('#loading').show();
       $(".sort-form").submit();
    })
</script>
@endpush