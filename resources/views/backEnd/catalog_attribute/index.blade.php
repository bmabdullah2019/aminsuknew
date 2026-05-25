@extends('backEnd.layouts.master')

@section('title', 'Catalog Attributes')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.catalog-attributes.create') }}" class="btn btn-primary rounded-pill">
                        <i class="fe-plus"></i> Add Attribute
                    </a>
                </div>
                <h4 class="page-title">Catalog Attributes</h4>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Values</th>
                            <th>Sort</th>
                            <th>Required</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($attributes as $attribute)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $attribute->name }}</td>
                                <td><code>{{ $attribute->slug }}</code></td>
                                <td>{{ (int) $attribute->values_count }}</td>
                                <td>{{ (int) $attribute->sort_order }}</td>
                                <td>
                                    @if ($attribute->is_required)
                                        <span class="badge bg-info">Yes</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($attribute->status)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </td>
                                <td class="d-flex gap-2">
                                    <a href="{{ route('admin.catalog-attributes.values', $attribute->id) }}" class="btn btn-sm btn-outline-primary">
                                        Values
                                    </a>
                                    <a href="{{ route('admin.catalog-attributes.edit', $attribute->id) }}" class="btn btn-sm btn-outline-warning">
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('admin.catalog-attributes.destroy', $attribute->id) }}" onsubmit="return confirm('Delete this attribute and all values?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">No attributes found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
