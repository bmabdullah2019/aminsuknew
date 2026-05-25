@extends('backEnd.layouts.master')
@section('title','Expense Categories')
@section('content')
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    @can('expense-category-create')
                        <a href="{{ route('admin.expense-category.create') }}" class="btn btn-sm btn-primary">
                            <i class="mdi mdi-plus"></i> Add Category
                        </a>
                    @endcan
                </div>
                <h4 class="page-title">Expense Categories</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $categoryStats['total_categories'] }}</h4>
                    <small>Total Categories</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $categoryStats['active_categories'] }}</h4>
                    <small>Active Categories</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $categoryStats['inactive_categories'] }}</h4>
                    <small>Inactive Categories</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $categoryStats['total_expenses'] }}</h4>
                    <small>Total Expenses</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Filters</h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search"
                                   value="{{ request('search') }}" placeholder="Search by name or code">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="mdi mdi-filter"></i> Filter
                                </button>
                                <a href="{{ route('admin.expense-category.index') }}" class="btn btn-secondary">
                                    <i class="mdi mdi-refresh"></i> Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Expense Categories</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end">Expenses</th>
                                    <th class="text-end">Total Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($categories as $category)
                                <tr>
                                    <td>
                                        <strong>{{ $category->code }}</strong>
                                    </td>
                                    <td>
                                        <strong>{{ $category->name }}</strong>
                                    </td>
                                    <td>{{ $category->description ?: 'N/A' }}</td>
                                    <td class="text-center">
                                        @if($category->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-info">{{ $category->expenses_count }}</span>
                                    </td>
                                    <td class="text-end">
                                        <strong>BDT {{ number_format((float) ($category->total_paid_expenses ?? 0), 2) }}</strong>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            @can('expense-category-edit')
                                                <a href="{{ route('admin.expense-category.edit', $category) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                                    <i class="mdi mdi-pencil"></i>
                                                </a>
                                            @endcan
                                            @can('expense-category-delete')
                                                @if($category->expenses_count == 0)
                                                    <button type="button" class="btn btn-sm btn-outline-danger" title="Delete"
                                                            onclick='deleteCategory({{ $category->id }}, @json($category->name))'>
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                @endif
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        <i class="mdi mdi-information-outline"></i>
                                        No expense categories found.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($categories->hasPages())
                    <div class="d-flex justify-content-center mt-3">
                        {{ $categories->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the category "<strong id="categoryName"></strong>"?</p>
                <div class="alert alert-warning">
                    <i class="mdi mdi-alert-circle"></i>
                    This action cannot be undone.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Category</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const expenseCategoryDeleteRouteTemplate = @json(route('admin.expense-category.delete', ['category' => '__CATEGORY__']));

function deleteCategory(categoryId, categoryName) {
    document.getElementById('categoryName').textContent = categoryName;
    document.getElementById('deleteForm').action = expenseCategoryDeleteRouteTemplate.replace('__CATEGORY__', categoryId);
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
@endsection


