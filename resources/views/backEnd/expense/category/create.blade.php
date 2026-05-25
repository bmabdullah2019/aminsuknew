@extends('backEnd.layouts.master')
@section('title','Create Expense Category')
@section('content')
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.expense-category.index') }}" class="btn btn-sm btn-secondary">
                        <i class="mdi mdi-arrow-left"></i> Back to Categories
                    </a>
                </div>
                <h4 class="page-title">Create Expense Category</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Category Details</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.expense-category.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                           id="name" name="name" value="{{ old('name') }}" placeholder="e.g., Office Supplies" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="code" class="form-label">Category Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('code') is-invalid @enderror"
                                           id="code" name="code" value="{{ old('code') }}" placeholder="e.g., OFF-SUP" required>
                                    <small class="form-text text-muted">Unique code for the category (e.g., OFF-SUP, UTIL)</small>
                                    @error('code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                      rows="3" placeholder="Optional description of the category">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sort_order" class="form-label">Sort Order</label>
                                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror"
                                           id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}" min="0">
                                    <small class="form-text text-muted">Lower numbers appear first in lists</small>
                                    @error('sort_order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                               {{ old('is_active', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            Active Category
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">Inactive categories won't be available for new expenses</small>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('admin.expense-category.index') }}" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-content-save"></i> Create Category
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Category Guidelines -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Category Guidelines</h6>
                </div>
                <div class="card-body">
                    <h6>Best Practices:</h6>
                    <ul class="mb-0">
                        <li>Use clear, descriptive names</li>
                        <li>Keep codes short but meaningful</li>
                        <li>Group similar expenses together</li>
                        <li>Use consistent naming patterns</li>
                    </ul>

                    <hr>

                    <h6>Examples:</h6>
                    <ul class="mb-0">
                        <li><strong>OFF-SUP:</strong> Office Supplies</li>
                        <li><strong>UTIL:</strong> Utilities</li>
                        <li><strong>TRANS:</strong> Transportation</li>
                        <li><strong>MKT-ADV:</strong> Marketing & Advertising</li>
                    </ul>
                </div>
            </div>

            <!-- Existing Categories -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Existing Categories</h6>
                </div>
                <div class="card-body">
                    @forelse($existingCategories as $category)
                        <div class="mb-2">
                            <strong>{{ $category->code }}:</strong> {{ $category->name }}
                        </div>
                        <hr>
                    @empty
                        <small class="text-muted">No categories exist yet</small>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
