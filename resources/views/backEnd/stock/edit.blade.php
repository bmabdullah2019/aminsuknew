@extends('backEnd.layouts.master')
@section('title','Edit Stock Details')
@section('content')
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{route('admin.stock.show', [$stock->warehouse_id, $stock->product_id])}}" class="btn btn-outline-primary rounded-pill"><i class="fe-eye"></i> View Details</a>
                    <a href="{{route('admin.stock.inventory')}}" class="btn btn-info rounded-pill"><i class="fe-grid"></i> Advanced Inventory</a>
                    <a href="{{route('admin.stock.balance')}}" class="btn btn-outline-primary rounded-pill"><i class="fe-list"></i> Stock Balance</a>
                </div>
                <h4 class="page-title">Edit Stock Details</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <!-- Product & Warehouse Info -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Product Information</h5>
                    <div class="text-center mb-3">
                        @if($stock->product->images->first())
                            <img src="{{ asset($stock->product->images->first()->image) }}"
                                 alt="{{ $stock->product->name }}" class="img-fluid rounded" style="max-height: 120px;">
                        @else
                            <div class="bg-light rounded d-flex align-items-center justify-content-center mx-auto"
                                 style="width: 120px; height: 120px;">
                                <i class="fe-image text-muted" style="font-size: 2.5rem;"></i>
                            </div>
                        @endif
                    </div>

                    <table class="table table-sm">
                        <tr>
                            <th>Product:</th>
                            <td>{{ $stock->product->name }}</td>
                        </tr>
                        <tr>
                            <th>SKU:</th>
                            <td>{{ $stock->product->sku ?: 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Code:</th>
                            <td>{{ $stock->product->product_code ?: 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Category:</th>
                            <td>{{ $stock->product->category->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Brand:</th>
                            <td>{{ $stock->product->brand->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Price:</th>
                            <td>৳{{ number_format($stock->product->new_price, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Warehouse Information -->
            <div class="card mt-3">
                <div class="card-body">
                    <h5 class="card-title">Warehouse Information</h5>
                    <table class="table table-sm">
                        <tr>
                            <th>Warehouse:</th>
                            <td>{{ $stock->warehouse->name }}</td>
                        </tr>
                        <tr>
                            <th>Code:</th>
                            <td>{{ $stock->warehouse->code }}</td>
                        </tr>
                        <tr>
                            <th>Location:</th>
                            <td>{{ $stock->warehouse->address ?: 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Current Stock Summary -->
            <div class="card mt-3">
                <div class="card-body">
                    <h5 class="card-title">Current Stock Summary</h5>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="card bg-primary text-white">
                                <div class="card-body py-2">
                                    <h6 class="mt-0 mb-1">{{ number_format($stock->physical_quantity, 2) }}</h6>
                                    <small>Physical</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card bg-success text-white">
                                <div class="card-body py-2">
                                    <h6 class="mt-0 mb-1">{{ number_format($stock->available_quantity, 2) }}</h6>
                                    <small>Available</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row text-center mt-2">
                        <div class="col-6">
                            <div class="card bg-warning text-white">
                                <div class="card-body py-2">
                                    <h6 class="mt-0 mb-1">{{ number_format($stock->reserved_quantity, 2) }}</h6>
                                    <small>Reserved</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card bg-info text-white">
                                <div class="card-body py-2">
                                    <h6 class="mt-0 mb-1">৳{{ number_format($stock->total_value, 2) }}</h6>
                                    <small>Value</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Form -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Edit Stock Details</h5>

                    <form method="POST" action="{{ route('admin.stock.update', [$stock->warehouse_id, $stock->product_id]) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="physical_quantity" class="form-label">
                                        Physical Quantity <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control @error('physical_quantity') is-invalid @enderror"
                                           id="physical_quantity" name="physical_quantity"
                                           value="{{ old('physical_quantity', $stock->physical_quantity) }}"
                                           step="0.01" min="0" required>
                                    @error('physical_quantity')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Actual quantity in physical inventory</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="reserved_quantity" class="form-label">
                                        Reserved Quantity <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control @error('reserved_quantity') is-invalid @enderror"
                                           id="reserved_quantity" name="reserved_quantity"
                                           value="{{ old('reserved_quantity', $stock->reserved_quantity) }}"
                                           step="0.01" min="0" required>
                                    @error('reserved_quantity')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Quantity allocated to pending orders</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="reorder_point" class="form-label">
                                        Reorder Point <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control @error('reorder_point') is-invalid @enderror"
                                           id="reorder_point" name="reorder_point"
                                           value="{{ old('reorder_point', $stock->reorder_point) }}"
                                           step="0.01" min="0" required>
                                    @error('reorder_point')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Minimum quantity before reorder alert</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="reorder_quantity" class="form-label">
                                        Reorder Quantity <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control @error('reorder_quantity') is-invalid @enderror"
                                           id="reorder_quantity" name="reorder_quantity"
                                           value="{{ old('reorder_quantity', $stock->reorder_quantity) }}"
                                           step="0.01" min="0" required>
                                    @error('reorder_quantity')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Suggested quantity to reorder</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="average_cost" class="form-label">Average Cost</label>
                                    <div class="input-group">
                                        <span class="input-group-text">৳</span>
                                        <input type="number" class="form-control @error('average_cost') is-invalid @enderror"
                                               id="average_cost" name="average_cost"
                                               value="{{ old('average_cost', $stock->average_cost) }}"
                                               step="0.01" min="0">
                                    </div>
                                    @error('average_cost')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Cost per unit for inventory valuation</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Calculated Values</label>
                                    <div class="card bg-light">
                                        <div class="card-body py-2">
                                            <small class="text-muted d-block">
                                                Available: <strong id="calc_available">{{ number_format($stock->available_quantity, 2) }}</strong>
                                            </small>
                                            <small class="text-muted d-block">
                                                Total Value: <strong id="calc_value">৳{{ number_format($stock->total_value, 2) }}</strong>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Reason Section -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="reason_category" class="form-label">
                                        Reason Category <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-control @error('reason_category') is-invalid @enderror"
                                            id="reason_category" name="reason_category" required>
                                        <option value="">Select Reason</option>
                                        <option value="audit" {{ old('reason_category') == 'audit' ? 'selected' : '' }}>Physical Count/Audit</option>
                                        <option value="correction" {{ old('reason_category') == 'correction' ? 'selected' : '' }}>Data Correction</option>
                                        <option value="received" {{ old('reason_category') == 'received' ? 'selected' : '' }}>Stock Received</option>
                                        <option value="returned" {{ old('reason_category') == 'returned' ? 'selected' : '' }}>Customer Returns</option>
                                        <option value="damaged" {{ old('reason_category') == 'damaged' ? 'selected' : '' }}>Damaged/Lost Items</option>
                                        <option value="adjustment" {{ old('reason_category') == 'adjustment' ? 'selected' : '' }}>General Adjustment</option>
                                        <option value="other" {{ old('reason_category') == 'other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                    @error('reason_category')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="reason" class="form-label">
                                        Notes/Details <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control @error('reason') is-invalid @enderror"
                                              id="reason" name="reason" rows="2" required
                                              placeholder="Describe the reason for these changes...">{{ old('reason') }}</textarea>
                                    @error('reason')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('admin.stock.show', [$stock->warehouse_id, $stock->product_id]) }}"
                                       class="btn btn-secondary">
                                        <i class="fe-arrow-left"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fe-save"></i> Update Stock Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Stock Status Preview -->
            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="card-title">Stock Status Preview</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center">
                                <div id="status_badge" class="badge fs-6 mb-2 {{ $stock->available_quantity <= 0 ? 'bg-danger' : ($stock->available_quantity <= $stock->reorder_point ? 'bg-warning text-dark' : 'bg-success') }}">
                                    {{ $stock->available_quantity <= 0 ? 'Out of Stock' : ($stock->available_quantity <= $stock->reorder_point ? 'Low Stock Alert' : 'In Stock') }}
                                </div>
                                <small class="text-muted">Current Status</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <h6 id="preview_available">{{ number_format($stock->available_quantity, 2) }}</h6>
                                <small class="text-muted">Available Quantity</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <h6 id="preview_value">৳{{ number_format($stock->total_value, 2) }}</h6>
                                <small class="text-muted">Total Value</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('css')
<style>
.card {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: 1px solid #e9ecef;
}

.form-group label {
    font-weight: 600;
    color: #495057;
}

.input-group-text {
    background-color: #f8f9fa;
    border-color: #ced4da;
}

.card-title {
    color: #495057;
    margin-bottom: 1rem;
}

.badge {
    font-size: 0.75rem;
}

#calc_available, #calc_value {
    color: #495057;
}

#preview_available, #preview_value {
    color: #495057;
    margin-bottom: 0.25rem;
}
</style>
@endpush

@push('js')
<script>
$(document).ready(function() {
    // Auto-calculate available quantity and total value
    function updateCalculations() {
        const physical = parseFloat($('#physical_quantity').val()) || 0;
        const reserved = parseFloat($('#reserved_quantity').val()) || 0;
        const cost = parseFloat($('#average_cost').val()) || 0;

        const available = Math.max(0, physical - reserved);
        const totalValue = physical * cost;

        $('#calc_available').text(available.toFixed(2));
        $('#calc_value').text('৳' + totalValue.toFixed(2));

        $('#preview_available').text(available.toFixed(2));
        $('#preview_value').text('৳' + totalValue.toFixed(2));

        // Update status badge
        let statusClass = 'bg-success';
        let statusText = 'In Stock';

        if (available <= 0) {
            statusClass = 'bg-danger';
            statusText = 'Out of Stock';
        } else if (available <= {{ $stock->reorder_point }}) {
            statusClass = 'bg-warning text-dark';
            statusText = 'Low Stock Alert';
        }

        $('#status_badge').removeClass('bg-success bg-warning text-dark bg-danger')
                         .addClass(statusClass)
                         .text(statusText);
    }

    // Bind calculation updates to input changes
    $('#physical_quantity, #reserved_quantity, #average_cost').on('input', updateCalculations);

    // Initialize calculations
    updateCalculations();

    // Form validation
    $('form').on('submit', function(e) {
        const physical = parseFloat($('#physical_quantity').val()) || 0;
        const reserved = parseFloat($('#reserved_quantity').val()) || 0;

        if (reserved > physical) {
            e.preventDefault();
            toastr.warning('Reserved quantity cannot exceed physical quantity', 'Validation Warning');
            $('#reserved_quantity').focus();
            return false;
        }

        // Warning when reserved equals physical (will make available = 0)
        if (reserved === physical && physical > 0) {
            const confirmSubmit = confirm('Warning: Setting reserved quantity equal to physical quantity will make available stock 0. This may prevent orders from being placed. Do you want to continue?');
            if (!confirmSubmit) {
                e.preventDefault();
                $('#reserved_quantity').focus();
                return false;
            }
        }
    });
});
</script>
@endpush
