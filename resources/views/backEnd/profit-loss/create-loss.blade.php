@extends('backEnd.layouts.master')
@section('title','Report New Loss')
@section('content')
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.profit-loss.losses') }}" class="btn btn-sm btn-secondary">
                        <i class="mdi mdi-arrow-left"></i> Back to Losses
                    </a>
                </div>
                <h4 class="page-title">Report New Loss</h4>
                <p class="text-muted">Record inventory losses for approval</p>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.profit-loss.store-loss') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="entry_date" class="form-label">Loss Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('entry_date') is-invalid @enderror"
                                           id="entry_date" name="entry_date" value="{{ old('entry_date', date('Y-m-d')) }}" required>
                                    @error('entry_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="entry_type" class="form-label">Loss Type <span class="text-danger">*</span></label>
                                    <select class="form-control @error('entry_type') is-invalid @enderror"
                                            id="entry_type" name="entry_type" required>
                                        <option value="">Select Loss Type</option>
                                        <option value="damage" {{ old('entry_type') == 'damage' ? 'selected' : '' }}>Damage</option>
                                        <option value="expired" {{ old('entry_type') == 'expired' ? 'selected' : '' }}>Expired</option>
                                        <option value="theft" {{ in_array(old('entry_type'), ['theft', 'stolen'], true) ? 'selected' : '' }}>Theft</option>
                                        <option value="other" {{ old('entry_type') == 'other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                    @error('entry_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="product_id" class="form-label">Product <span class="text-danger">*</span></label>
                                    <select class="form-control @error('product_id') is-invalid @enderror"
                                            id="product_id" name="product_id" required>
                                        <option value="">Select Product</option>
                                        @foreach($products as $product)
                                        <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                            {{ $product->name }} ({{ $product->product_code ?: ($product->sku ?: 'N/A') }})
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('product_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="warehouse_id" class="form-label">Warehouse <span class="text-danger">*</span></label>
                                    <select class="form-control @error('warehouse_id') is-invalid @enderror"
                                            id="warehouse_id" name="warehouse_id" required>
                                        <option value="">Select Warehouse</option>
                                        @foreach($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}" {{ old('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                            {{ $warehouse->name }} - {{ $warehouse->city ?? 'N/A' }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('warehouse_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0.01" class="form-control @error('quantity') is-invalid @enderror"
                                           id="quantity" name="quantity" value="{{ old('quantity') }}" required>
                                    @error('quantity')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="unit_cost" class="form-label">Unit Cost (BDT) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0.01" class="form-control @error('unit_cost') is-invalid @enderror"
                                           id="unit_cost" name="unit_cost" value="{{ old('unit_cost') }}" required>
                                    @error('unit_cost')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="3" required>{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="reason_details" class="form-label">Reason Details</label>
                            <textarea class="form-control @error('reason_details') is-invalid @enderror"
                                      id="reason_details" name="reason_details" rows="2">{{ old('reason_details') }}</textarea>
                            @error('reason_details')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="evidence_attachments" class="form-label">Evidence Attachments</label>
                            <input type="file" class="form-control @error('evidence_attachments') is-invalid @enderror"
                                   id="evidence_attachments" name="evidence_attachments[]" multiple accept="image/*,.pdf">
                            <small class="text-muted">Upload images or PDF documents as evidence (max 2MB each)</small>
                            @error('evidence_attachments')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Total Loss Amount Display -->
                        <div class="alert alert-info">
                            <strong>Total Loss Amount: BDT <span id="totalAmount">0.00</span></strong>
                            <br>
                            <small>This entry will be submitted for approval before affecting financial reports.</small>
                        </div>

                        <div class="text-end">
                            <a href="{{ route('admin.profit-loss.losses') }}" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Submit for Approval</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Calculate total loss amount
document.getElementById('quantity').addEventListener('input', calculateTotal);
document.getElementById('unit_cost').addEventListener('input', calculateTotal);

function calculateTotal() {
    const quantity = parseFloat(document.getElementById('quantity').value) || 0;
    const unitCost = parseFloat(document.getElementById('unit_cost').value) || 0;
    const total = quantity * unitCost;

    document.getElementById('totalAmount').textContent = total.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Trigger calculation on page load if values exist
document.addEventListener('DOMContentLoaded', function() {
    calculateTotal();
});
</script>
@endsection


