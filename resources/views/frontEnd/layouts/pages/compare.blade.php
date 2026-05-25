@extends('frontEnd.layouts.master')

@section('title', 'Compare Products')
@push('seo')
    <meta name="robots" content="noindex, nofollow" />
    <meta name="description" content="Compare Products - {{ $generalsetting->name ?? config('app.name') }}" />
@endpush
@push('css')
<style>
    .compare-section .compare-card {
        background: #fff;
        border: 1px solid #dce8fb;
        border-radius: 12px;
        box-shadow: 0 10px 20px rgba(10, 42, 112, 0.08);
        overflow: hidden;
    }

    .compare-section .compare-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 14px 16px;
        border-bottom: 1px solid #e8f0fd;
    }

    .compare-section .compare-table-wrap {
        overflow-x: auto;
    }

    .compare-section .compare-table {
        margin: 0;
        min-width: 860px;
    }

    .compare-section .compare-table th {
        width: 210px;
        color: #153a72;
        font-weight: 700;
        background: #f4f8ff;
        border-color: #e3ecfb;
        vertical-align: middle;
    }

    .compare-section .compare-table td {
        vertical-align: top;
        border-color: #e9f0fd;
    }

    .compare-product-media {
        display: flex;
        gap: 10px;
        align-items: flex-start;
    }

    .compare-product-media img {
        width: 72px;
        height: 72px;
        border-radius: 10px;
        object-fit: cover;
        border: 1px solid #dde9fb;
        background: #f8fbff;
    }

    .compare-product-name {
        color: #173a72;
        font-weight: 600;
        line-height: 1.4;
    }

    .compare-product-price {
        color: #0f3e93;
        font-weight: 700;
    }

    .compare-product-price del {
        color: #7d90b0;
        margin-right: 6px;
        font-weight: 500;
    }

    .compare-actions {
        display: grid;
        gap: 8px;
    }

    .compare-empty {
        background: #fff;
        border: 1px dashed #cbdcf8;
        border-radius: 12px;
        padding: 32px 18px;
        text-align: center;
        color: #4d648b;
    }
</style>
@endpush

@section('content')
<section class="product-section compare-section">
    <div class="container">
        <div class="sorting-section">
            <div class="row">
                <div class="col-sm-6">
                    <div class="category-breadcrumb d-flex align-items-center">
                        <a href="{{ route('home') }}">Home</a>
                        <span>/</span>
                        <strong>Compare Products</strong>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="showing-data">
                        <span>{{ $items->count() }} item(s) in comparison list</span>
                    </div>
                </div>
            </div>
        </div>

        @if($items->isEmpty())
            <div class="compare-empty">
                <h5 class="mb-2">No products in comparison list</h5>
                <p class="mb-3">Add products from listing pages to compare price and attributes.</p>
                <a href="{{ route('shop') }}" class="view_more_btn">Continue Shopping</a>
            </div>
        @else
            <div class="compare-card">
                <div class="compare-header">
                    <h5 class="mb-0">Product Comparison</h5>
                    <button type="button" class="btn btn-outline-danger btn-sm compare_clear">Clear All</button>
                </div>
                <div class="compare-table-wrap">
                    <table class="table compare-table">
                        <tbody>
                            <tr>
                                <th>Product</th>
                                @foreach($items as $entry)
                                    <td>
                                        <div class="compare-product-media">
                                            <img src="{{ asset($entry['product']->display_image) }}" alt="{{ $entry['product']->name }}" />
                                            <div>
                                                <a href="{{ route('product', $entry['product']->slug) }}" class="compare-product-name">
                                                    {{ $entry['product']->name }}
                                                </a>
                                                <div class="mt-1">
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-danger compare_remove"
                                                        data-rowid="{{ $entry['row_id'] }}">
                                                        Remove
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                <th>Price</th>
                                @foreach($items as $entry)
                                    <td class="compare-product-price">
                                        @if((float) ($entry['product']->old_price ?? 0) > (float) ($entry['product']->new_price ?? 0))
                                            <del>&#2547;{{ number_format((float) $entry['product']->old_price, 2) }}</del>
                                        @endif
                                        &#2547;{{ number_format((float) $entry['product']->new_price, 2) }}
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                <th>Category</th>
                                @foreach($items as $entry)
                                    <td>{{ optional($entry['product']->category)->name ?? 'N/A' }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <th>Color</th>
                                @foreach($items as $entry)
                                    @php
                                        $colorNames = $entry['product']->procolors
                                            ->pluck('color.colorName')
                                            ->filter()
                                            ->unique()
                                            ->values();
                                    @endphp
                                    <td>{{ $colorNames->isNotEmpty() ? $colorNames->implode(', ') : 'N/A' }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <th>Size</th>
                                @foreach($items as $entry)
                                    @php
                                        $sizeNames = $entry['product']->prosizes
                                            ->pluck('size.sizeName')
                                            ->filter()
                                            ->unique()
                                            ->values();
                                    @endphp
                                    <td>{{ $sizeNames->isNotEmpty() ? $sizeNames->implode(', ') : 'N/A' }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <th>Age</th>
                                @foreach($items as $entry)
                                    @php
                                        $ageNames = $entry['product']->ages
                                            ->pluck('ageName')
                                            ->filter()
                                            ->unique()
                                            ->values();
                                    @endphp
                                    <td>{{ $ageNames->isNotEmpty() ? $ageNames->implode(', ') : 'N/A' }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <th>Stock</th>
                                @foreach($items as $entry)
                                    <td>
                                        @if((float) ($entry['product']->available_stock ?? 0) > 0)
                                            <span class="badge bg-success">In Stock</span>
                                        @else
                                            <span class="badge bg-danger">Stock Out</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                <th>Action</th>
                                @foreach($items as $entry)
                                    <td>
                                        <div class="compare-actions">
                                            <a href="{{ route('product', $entry['product']->slug) }}" class="btn btn-outline-primary btn-sm">
                                                View Details
                                            </a>
                                            <button type="button"
                                                class="btn btn-primary btn-sm addcartbutton"
                                                data-id="{{ $entry['product']->id }}">
                                                Add To Cart
                                            </button>
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
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
