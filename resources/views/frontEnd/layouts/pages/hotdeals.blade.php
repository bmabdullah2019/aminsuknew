@extends('frontEnd.layouts.master')
@php
    $pageTitle = $pageTitle ?? 'Hot Deals';
    $showTimer = $showTimer ?? true;
@endphp
@section('title', $pageTitle)
@push('seo')
    <meta name="description" content="{{ $pageTitle }} - {{ $generalsetting->meta_description ?? 'Best deals and offers' }}" />
    <meta name="keywords" content="{{ $generalsetting->meta_keyword ?? 'hot deals, offers, discount' }}" />
    <meta property="og:title" content="{{ $pageTitle }} - {{ $generalsetting->name ?? config('app.name') }}" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ url()->current() }}" />
    <meta property="og:description" content="{{ $pageTitle }} - {{ $generalsetting->meta_description ?? 'Best deals and offers' }}" />
@endpush
@push('css')
<link rel="stylesheet" href="{{ asset('public/frontEnd/css/jquery-ui.css') }}" />
<style>
    .hotdeals-five-per-row {
        grid-template-columns: repeat(5, minmax(0, 1fr));
    }

    @media (max-width: 1199.98px) {
        .hotdeals-five-per-row {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }

    @media (max-width: 991.98px) {
        .hotdeals-five-per-row {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 767.98px) {
        .hotdeals-five-per-row {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 479.98px) {
        .hotdeals-five-per-row {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<section class="product-section">
    <div class="container">
        <div class="sorting-section">
            <div class="row">
                <div class="col-sm-6">
                    <div class="category-breadcrumb d-flex align-items-center">
                        <a href="{{ route('home') }}">Home</a>
                        <span>/</span>
                        <strong>{{ $pageTitle }}</strong>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="showing-data">
                                <span>Showing {{ $products->firstItem() ?? 0 }}-{{ $products->lastItem() ?? 0 }} of {{ $products->total() }} Results</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mobile-filter-toggle">
                                <i class="fa fa-list-ul"></i><span>filter</span>
                            </div>
                            <div class="page-sort">
                                <form action="" class="sort-form">
                                    <select name="sort" class="form-control form-select sort">
                                        <option value="1" @if(request()->get('sort') == 1) selected @endif>Product: Latest</option>
                                        <option value="2" @if(request()->get('sort') == 2) selected @endif>Product: Oldest</option>
                                        <option value="3" @if(request()->get('sort') == 3) selected @endif>Price: High To Low</option>
                                        <option value="4" @if(request()->get('sort') == 4) selected @endif>Price: Low To High</option>
                                        <option value="5" @if(request()->get('sort') == 5) selected @endif>Name: A-Z</option>
                                        <option value="6" @if(request()->get('sort') == 6) selected @endif>Name: Z-A</option>
                                    </select>
                                    <input type="hidden" name="min_price" value="{{ request()->get('min_price') }}" />
                                    <input type="hidden" name="max_price" value="{{ request()->get('max_price') }}" />
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            @if($showTimer)
                <div class="col-sm-12">
                    <div class="offer_timer" id="simple_timer"></div>
                </div>
            @endif

            <div class="col-sm-12">
                <div class="category-product main_product_inner hotdeals-five-per-row">
                    @forelse($products as $key => $value)
                        @include('frontEnd.layouts.partials._product_card', ['value' => $value, 'key' => $key])
                    @empty
                        <div class="col-sm-12">
                            <p class="text-center">No products found.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="custom_paginate">
                    {{ $products->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
</section>
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
    $(".sort").change(function () {
        $('#loading').show();
        $(".sort-form").submit();
    });
</script>

@if($showTimer)
    <script>
        $("#simple_timer").syotimer({
            date: new Date(2015, 0, 1),
            layout: "hms",
            doubleNumbers: false,
            effectType: "opacity",
            periodUnit: "d",
            periodic: true,
            periodInterval: 1
        });
    </script>
@endif
@endpush
