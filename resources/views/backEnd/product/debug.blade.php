@extends('backEnd.layouts.master')

@section('title', 'Debug - Product Create Form')

@section('content')
<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h4>Product Create Form Debug Info</h4>
        </div>
        <div class="card-body">
            <h5>Categories Count: <strong>{{ count($categories) }}</strong></h5>
            
            @if(count($categories) > 0)
                <h5 class="mt-3">Categories List:</h5>
                <ul class="list-group">
                    @foreach($categories as $category)
                        <li class="list-group-item">
                            ID: {{ $category->id }} | 
                            Name: {{ $category->name }} | 
                            Status: {{ $category->status }} | 
                            Parent ID: {{ $category->parent_id }}
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="alert alert-danger">No categories found!</div>
            @endif

            <h5 class="mt-4">Brands Count: <strong>{{ count($brands) }}</strong></h5>
            <h5>Colors Count: <strong>{{ count($colors) }}</strong></h5>
            <h5>Sizes Count: <strong>{{ count($sizes) }}</strong></h5>
            <h5>Ages Count: <strong>{{ count($ages) }}</strong></h5>

            <div class="mt-4">
                <h5>Test the actual form:</h5>
                <a href="{{ route('admin.products.create') }}" class="btn btn-primary">Go to Product Create Form</a>
            </div>
        </div>
    </div>
</div>
@endsection
