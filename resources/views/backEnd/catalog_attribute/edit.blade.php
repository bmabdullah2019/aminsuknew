@extends('backEnd.layouts.master')

@section('title', 'Edit Attribute')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.catalog-attributes.index') }}" class="btn btn-secondary rounded-pill">Back</a>
                </div>
                <h4 class="page-title">Edit Catalog Attribute</h4>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.catalog-attributes.update', $attribute->id) }}">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $attribute->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $attribute->slug) }}">
                        @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" min="0" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', (int) $attribute->sort_order) }}">
                        @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Required in Selection</label>
                        <select name="is_required" class="form-control @error('is_required') is-invalid @enderror">
                            <option value="0" @selected(old('is_required', (int) $attribute->is_required) == 0)>No</option>
                            <option value="1" @selected(old('is_required', (int) $attribute->is_required) == 1)>Yes</option>
                        </select>
                        @error('is_required') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-control @error('status') is-invalid @enderror" required>
                            <option value="1" @selected(old('status', (int) $attribute->status) == 1)>Active</option>
                            <option value="0" @selected(old('status', (int) $attribute->status) == 0)>Inactive</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-success">Update Attribute</button>
            </form>
        </div>
    </div>
</div>
@endsection
