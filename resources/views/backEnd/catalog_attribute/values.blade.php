@extends('backEnd.layouts.master')

@section('title', 'Attribute Values')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.catalog-attributes.index') }}" class="btn btn-secondary rounded-pill">Back</a>
                </div>
                <h4 class="page-title">Values: {{ $attribute->name }}</h4>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-3">Add Value</h5>
            <form method="POST" action="{{ route('admin.catalog-attributes.values.store', $attribute->id) }}">
                @csrf
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <input type="text" name="value" class="form-control @error('value') is-invalid @enderror" placeholder="Value (e.g. Red, XL, 6-8 Years)" value="{{ old('value') }}" required>
                        @error('value') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-2 mb-2">
                        <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" placeholder="Slug (optional)" value="{{ old('slug') }}">
                    </div>
                    <div class="col-md-2 mb-2">
                        <input type="number" name="sort_order" min="0" class="form-control @error('sort_order') is-invalid @enderror" placeholder="Sort" value="{{ old('sort_order', 0) }}">
                    </div>
                    <div class="col-md-2 mb-2">
                        <select name="status" class="form-control @error('status') is-invalid @enderror" required>
                            <option value="1" @selected(old('status', 1) == 1)>Active</option>
                            <option value="0" @selected(old('status', 1) == 0)>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button type="submit" class="btn btn-success w-100">Add</button>
                    </div>
                </div>
                @if (strtolower($attribute->slug) === 'color')
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <input type="text" name="meta_color_code" class="form-control" placeholder="Color hex (optional), e.g. #ff0000" value="{{ old('meta_color_code') }}">
                        </div>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Value</th>
                            <th>Slug</th>
                            <th>Meta</th>
                            <th>Sort</th>
                            <th>Status</th>
                            <th>Update</th>
                            <th>Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($attribute->values as $value)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td colspan="6">
                                    <form method="POST" action="{{ route('admin.catalog-attributes.values.update', [$attribute->id, $value->id]) }}" class="row g-2">
                                        @csrf
                                        @method('PUT')
                                        <div class="col-md-3">
                                            <input type="text" name="value" class="form-control" value="{{ $value->value }}" required>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="text" name="slug" class="form-control" value="{{ $value->slug }}">
                                        </div>
                                        <div class="col-md-2">
                                            @php $metaColor = is_array($value->meta ?? null) ? ($value->meta['color_code'] ?? '') : ''; @endphp
                                            <input type="text" name="meta_color_code" class="form-control" value="{{ $metaColor }}" placeholder="#hex">
                                        </div>
                                        <div class="col-md-1">
                                            <input type="number" name="sort_order" min="0" class="form-control" value="{{ (int) $value->sort_order }}">
                                        </div>
                                        <div class="col-md-2">
                                            <select name="status" class="form-control" required>
                                                <option value="1" @selected((int) $value->status === 1)>Active</option>
                                                <option value="0" @selected((int) $value->status === 0)>Inactive</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="submit" class="btn btn-outline-primary btn-sm w-100">Save</button>
                                        </div>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('admin.catalog-attributes.values.destroy', [$attribute->id, $value->id]) }}" onsubmit="return confirm('Delete this value?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">No values found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
