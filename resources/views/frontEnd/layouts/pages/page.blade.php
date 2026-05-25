@extends('frontEnd.layouts.master')
@section('title', $page->meta_title ?: $page->name)
@push('seo')
    <meta name="description" content="{{ $page->meta_description ?: Str::limit(strip_tags($page->description), 160) }}" />
    <meta name="keywords" content="{{ $page->meta_keyword ?: $page->name }}" />
    <meta property="og:title" content="{{ $page->meta_title ?: $page->name }}" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ url()->current() }}" />
    <meta property="og:description" content="{{ $page->meta_description ?: Str::limit(strip_tags($page->description), 160) }}" />
@endpush
@section('content')

<section class="comn_sec">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="cmn_menu">
                    <ul>
                        @foreach($cmnmenu as $key=>$value)
                        <li>
                            <a href="{{route('page',$value->slug)}}">{{$value->name}}</a>
                        </li>
                        @endforeach
                        <li>
                            <a href="{{route('contact')}}">Contact Us</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="createpage-section">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="page-content">
                    <div class="page-title mb-2">
                        <h5>{{$page->title}}</h5>
                    </div>
                    <div class="page-description">
                        {!! $page->description !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
@push('seo_content')
@if(!empty($page->meta_description))
<section class="homeproduct">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="meta_des">
                    {!! $page->meta_description !!}
                </div>
            </div>
        </div>
    </div>
</section>
@endif
@endpush
