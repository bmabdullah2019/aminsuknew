@extends('frontEnd.layouts.master')
@section('title', $category->meta_title)
@push('css')
    <link rel="stylesheet" href="{{ asset('public/frontEnd/css/jquery-ui.css') }}" />
@endpush
@push('seo')
    <meta name="app-url" content="{{ route('category', $category->slug) }}" />
    <meta name="robots" content="index, follow" />
    <meta name="description" content="{{ strip_tags($category->meta_description) }}" />
    <meta name="keywords" content="{{ $category->meta_keyword ?? $category->slug }}" />

    <meta name="twitter:card" content="product" />
    <meta name="twitter:site" content="{{ $category->name }}" />
    <meta name="twitter:title" content="{{ $category->name }}" />
    <meta name="twitter:description" content="{{ strip_tags($category->meta_description) }}" />
    <meta name="twitter:creator" content="gomobd.com" />
    <meta property="og:url" content="{{ route('category', $category->slug) }}" />
    <meta name="twitter:image" content="{{ asset($category->image) }}" />

    <meta property="og:title" content="{{ $category->name }}" />
    <meta property="og:type" content="product" />
    <meta property="og:url" content="{{ route('category', $category->slug) }}" />
    <meta property="og:image" content="{{ asset($category->image) }}" />
    <meta property="og:description" content="{{ strip_tags($category->meta_description) }}" />
    <meta property="og:site_name" content="{{ $category->name }}" />
@endpush

@php
    $selectedSubcategories = $selectedSubcategories ?? [];
    $selectedSizeIds = $selectedSizeIds ?? [];
    $selectedColorIds = $selectedColorIds ?? [];
    $selectedAgeIds = $selectedAgeIds ?? [];
    $selectedBrandIds = $selectedBrandIds ?? [];
@endphp

@section('content')
    <section class="product-section">
        <div class="container">
            <div class="sorting-section">
                <div class="row">
                    <div class="col-sm-6">
                        <div class="category-breadcrumb d-flex align-items-center">
                            <a href="{{ route('home') }}">Home</a>
                            <span>/</span>
                            <strong>{{ $category->name }}</strong>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="showing-data">
                                    <span>Showing {{ $products->firstItem() }}-{{ $products->lastItem() }} of
                                        {{ $products->total() }} Results</span>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="filter_sort">
                                    <div class="filter_btn">
                                        <i class="fa fa-list-ul"></i>
                                    </div>
                                    <div class="page-sort">
                                        <form action="" class="sort-form">
                                            <select name="sort" class="form-control form-select sort">
                                                <option value="1" @selected((int) request()->get('sort') === 1)>Product: Latest</option>
                                                <option value="2" @selected((int) request()->get('sort') === 2)>Product: Oldest</option>
                                                <option value="3" @selected((int) request()->get('sort') === 3)>Price: High To Low</option>
                                                <option value="4" @selected((int) request()->get('sort') === 4)>Price: Low To High</option>
                                                <option value="5" @selected((int) request()->get('sort') === 5)>Name: A-Z</option>
                                                <option value="6" @selected((int) request()->get('sort') === 6)>Name: Z-A</option>
                                            </select>
                                            <input type="hidden" name="min_price" value="{{ request()->get('min_price') }}" />
                                            <input type="hidden" name="max_price" value="{{ request()->get('max_price') }}" />
                                            @if (request()->filled('sold'))
                                                <input type="hidden" name="sold" value="{{ request()->get('sold') }}" />
                                            @endif
                                            @foreach ($selectedSubcategories as $subcategoryId)
                                                <input type="hidden" name="subcategory[]" value="{{ $subcategoryId }}" />
                                            @endforeach
                                            @foreach ($selectedSizeIds as $sizeId)
                                                <input type="hidden" name="size[]" value="{{ $sizeId }}" />
                                            @endforeach
                                            @foreach ($selectedColorIds as $colorId)
                                                <input type="hidden" name="color[]" value="{{ $colorId }}" />
                                            @endforeach
                                            @foreach ($selectedAgeIds as $ageId)
                                                <input type="hidden" name="age[]" value="{{ $ageId }}" />
                                            @endforeach
                                            @foreach ($selectedBrandIds as $brandId)
                                                <input type="hidden" name="brand[]" value="{{ $brandId }}" />
                                            @endforeach
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-3 filter_sidebar">
                    <div class="filter_close"><i class="fa fa-long-arrow-left"></i> Filter</div>
                    <form action="" class="attribute-submit">
                        <input type="hidden" name="sort" value="{{ request()->get('sort') }}" />
                        @if (request()->filled('sold'))
                            <input type="hidden" name="sold" value="{{ request()->get('sold') }}" />
                        @endif
                        <div class="sidebar_item wraper__item">
                            <div class="accordion" id="category_sidebar">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapseCat" aria-expanded="true" aria-controls="collapseCat">
                                            {{ $category->name }}
                                        </button>
                                    </h2>
                                    <div id="collapseCat" class="accordion-collapse collapse show"
                                        data-bs-parent="#category_sidebar">
                                        <div class="accordion-body cust_according_body">
                                            <ul>
                                                @foreach ($category->subcategories as $subcat)
                                                    <li>
                                                        <a href="{{ url('subcategory/' . $subcat->slug) }}">
                                                            {{ $subcat->subcategoryName }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="sidebar_item wraper__item">
                            <div class="accordion" id="price_sidebar">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapsePrice" aria-expanded="true"
                                            aria-controls="collapsePrice">
                                            Price
                                        </button>
                                    </h2>
                                    <div id="collapsePrice" class="accordion-collapse collapse show"
                                        data-bs-parent="#price_sidebar">
                                        <div class="accordion-body cust_according_body">
                                            <div class="category-filter-box category__wraper" id="categoryFilterBox">
                                                <div class="category-filter-item">
                                                    <div class="filter-body">
                                                        <div class="slider-box">
                                                            <div class="filter-price-inputs">
                                                                <p class="min-price">
                                                                    Tk <input type="text" name="min_price" id="min_price"
                                                                        readonly />
                                                                </p>
                                                                <p class="max-price">
                                                                    Tk <input type="text" name="max_price" id="max_price"
                                                                        readonly />
                                                                </p>
                                                            </div>
                                                            <div id="price-range" class="slider"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="sidebar_item wraper__item">
                            <div class="accordion" id="filter_sidebar">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapseFilter" aria-expanded="true"
                                            aria-controls="collapseFilter">
                                            Filter
                                        </button>
                                    </h2>
                                    <div id="collapseFilter" class="accordion-collapse collapse show"
                                        data-bs-parent="#filter_sidebar">
                                        <div class="accordion-body cust_according_body">
                                            <div class="filter-body">
                                                <ul class="">

                                                    @foreach ($subcategories as $subcategory)
                                                        <li class="subcategory-filter-list">
                                                            <label for="subcategory-{{ $subcategory->id }}"
                                                                class="subcategory-filter-label">
                                                                <input class="form-checkbox form-attribute"
                                                                    id="subcategory-{{ $subcategory->id }}"
                                                                    name="subcategory[]" value="{{ $subcategory->id }}"
                                                                    type="checkbox"
                                                                    @checked(in_array((int) $subcategory->id, $selectedSubcategories, true)) />
                                                                <p class="subcategory-filter-name">
                                                                    {{ $subcategory->subcategoryName }}
                                                                </p>
                                                            </label>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                                @include('frontEnd.layouts.partials._attribute_filters')
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                    @include('frontEnd.layouts.partials._sidebar_banners')
                </div>
                <div class="col-sm-9">
                    <div class="category-product main_product_inner">
                        @foreach ($products as $key => $value)
                            @include('frontEnd.layouts.partials._product_card', ['value' => $value, 'key' => $key])
                        @endforeach
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

@push('script')
    @include('frontEnd.layouts.partials._listing_filter_script')
@endpush
