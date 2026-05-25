@extends('backEnd.layouts.master')
@section('title','Edit Expense Category - ' . $category->name)
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
                <h4 class="page-title">Edit Expense Category - {{ $category->name }}</h4>
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
                    <form action="{{ route('admin.expense-category.update', $category) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                           id="name" name="name" value="{{ old('name', $category->name) }}" placeholder="e.g., Office Supplies" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="code" class="form-label">Category Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('code') is-invalid @enderror"
                                           id="code" name="code" value="{{ old('code', $category->code) }}" placeholder="e.g., OFF-SUP" required>
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
                                      rows="3" placeholder="Optional description of the category">{{ old('description', $category->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sort_order" class="form-label">Sort Order</label>
                                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror"
                                           id="sort_order" name="sort_order" value="{{ old('sort_order', $category->sort_order) }}" min="0">
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
                                               {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
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
                                <i class="mdi mdi-content-save"></i> Update Category
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Category Statistics -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Category Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>Total Expenses:</strong> {{ $category->expenses_count }}
                    </div>
                    <div class="mb-2">
                        <strong>Total Amount:</strong> BDT {{ number_format((float) ($category->total_paid_expenses ?? 0), 2) }}
                    </div>
                    <div class="mb-2">
                        <strong>Status:</strong>
                        @if($category->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-danger">Inactive</span>
                        @endif
                    </div>
                    <div class="mb-2">
                        <strong>Created:</strong> {{ $category->created_at->format('d M Y') }}
                    </div>
                </div>
            </div>

            <!-- Recent Expenses -->
            @if($category->expenses_count > 0)
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Recent Expenses</h6>
                </div>
                <div class="card-body">
                    @forelse($recentExpenses as $expense)
                        <div class="mb-2">
                            <small class="text-muted">{{ $expense->expense_date->format('d M Y') }}</small><br>
                            <strong>{{ $expense->expense_number }}</strong><br>
                            <span class="badge bg-primary">BDT {{ number_format($expense->total_amount, 2) }}</span>
                        </div>
                        <hr>
                    @empty
                        <small class="text-muted">No expenses in this category</small>
                    @endforelse

                    @if($category->expenses_count > 3)
                        <a href="{{ route('admin.expense.index', ['category_id' => $category->id]) }}" class="btn btn-sm btn-outline-primary">
                            View All Expenses
                        </a>
                    @endif
                </div>
            </div>
            @endif

            <!-- Delete Warning -->
            @can('expense-category-delete')
                @if($category->expenses_count == 0)
                <div class="card mt-3 border-danger">
                    <div class="card-header bg-danger text-white">
                        <h6 class="mb-0">Danger Zone</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">This category has no expenses and can be safely deleted.</p>
                        <form action="{{ route('admin.expense-category.delete', $category) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger"
                                    onclick="return confirm('Are you sure you want to delete this category?')">
                                <i class="mdi mdi-delete"></i> Delete Category
                            </button>
                        </form>
                    </div>
                </div>
                @else
                <div class="card mt-3 border-warning">
                    <div class="card-header bg-warning">
                        <h6 class="mb-0">Cannot Delete</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">This category cannot be deleted because it contains {{ $category->expenses_count }} expense(s).</p>
                    </div>
                </div>
                @endif
            @endcan
        </div>
    </div>
</div>
@endsection


