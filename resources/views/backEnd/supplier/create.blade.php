@extends('backEnd.layouts.master')
@section('title','Create Supplier')
@section('content')
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{route('admin.supplier.index')}}" class="btn btn-secondary rounded-pill"><i class="fe-arrow-left"></i> Back</a>
                </div>
                <h4 class="page-title">Create New Supplier</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{route('admin.supplier.store')}}" id="supplierForm">
                        @csrf

                        <div class="row">
                            <!-- Basic Information -->
                            <div class="col-md-6">
                                <h5 class="mb-3">Basic Information</h5>

                                <div class="mb-3">
                                    <label for="supplier_code" class="form-label">Supplier Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('supplier_code') is-invalid @enderror"
                                           id="supplier_code" name="supplier_code"
                                           value="{{old('supplier_code')}}"
                                           placeholder="SUP-001" required>
                                    @error('supplier_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="name" class="form-label">Supplier Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                           id="name" name="name"
                                           value="{{old('name')}}"
                                           placeholder="Enter supplier name" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                           id="email" name="email"
                                           value="{{old('email')}}"
                                           placeholder="supplier@example.com">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">Phone</label>
                                            <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                                   id="phone" name="phone"
                                                   value="{{old('phone')}}"
                                                   placeholder="+8801XXXXXXXXX">
                                            @error('phone')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="mobile" class="form-label">Mobile</label>
                                            <input type="text" class="form-control @error('mobile') is-invalid @enderror"
                                                   id="mobile" name="mobile"
                                                   value="{{old('mobile')}}"
                                                   placeholder="+8801XXXXXXXXX">
                                            @error('mobile')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-control @error('status') is-invalid @enderror" id="status" name="status">
                                        <option value="active" {{old('status', 'active') == 'active' ? 'selected' : ''}}>Active</option>
                                        <option value="inactive" {{old('status') == 'inactive' ? 'selected' : ''}}>Inactive</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Address Information -->
                            <div class="col-md-6">
                                <h5 class="mb-3">Address Information</h5>

                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control @error('address') is-invalid @enderror"
                                              id="address" name="address" rows="3"
                                              placeholder="Enter full address">{{old('address')}}</textarea>
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="city" class="form-label">City</label>
                                            <input type="text" class="form-control @error('city') is-invalid @enderror"
                                                   id="city" name="city"
                                                   value="{{old('city')}}"
                                                   placeholder="Dhaka">
                                            @error('city')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="postal_code" class="form-label">Postal Code</label>
                                            <input type="text" class="form-control @error('postal_code') is-invalid @enderror"
                                                   id="postal_code" name="postal_code"
                                                   value="{{old('postal_code')}}"
                                                   placeholder="1205">
                                            @error('postal_code')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="state" class="form-label">State/Division</label>
                                            <input type="text" class="form-control @error('state') is-invalid @enderror"
                                                   id="state" name="state"
                                                   value="{{old('state')}}"
                                                   placeholder="Dhaka">
                                            @error('state')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="country" class="form-label">Country</label>
                                            <input type="text" class="form-control @error('country') is-invalid @enderror"
                                                   id="country" name="country"
                                                   value="{{old('country', 'Bangladesh')}}"
                                                   placeholder="Bangladesh">
                                            @error('country')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Contact Person -->
                            <div class="col-md-6">
                                <h5 class="mb-3">Contact Person</h5>

                                <div class="mb-3">
                                    <label for="contact_person" class="form-label">Contact Person Name</label>
                                    <input type="text" class="form-control @error('contact_person') is-invalid @enderror"
                                           id="contact_person" name="contact_person"
                                           value="{{old('contact_person')}}"
                                           placeholder="John Doe">
                                    @error('contact_person')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="contact_person_phone" class="form-label">Contact Phone</label>
                                            <input type="text" class="form-control @error('contact_person_phone') is-invalid @enderror"
                                                   id="contact_person_phone" name="contact_person_phone"
                                                   value="{{old('contact_person_phone')}}"
                                                   placeholder="+8801XXXXXXXXX">
                                            @error('contact_person_phone')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="contact_person_email" class="form-label">Contact Email</label>
                                            <input type="email" class="form-control @error('contact_person_email') is-invalid @enderror"
                                                   id="contact_person_email" name="contact_person_email"
                                                   value="{{old('contact_person_email')}}"
                                                   placeholder="contact@supplier.com">
                                            @error('contact_person_email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Financial Information -->
                            <div class="col-md-6">
                                <h5 class="mb-3">Financial Information</h5>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="credit_limit" class="form-label">Credit Limit (BDT)</label>
                                            <input type="number" step="0.01" class="form-control @error('credit_limit') is-invalid @enderror"
                                                   id="credit_limit" name="credit_limit"
                                                   value="{{old('credit_limit', 0)}}"
                                                   placeholder="0.00">
                                            @error('credit_limit')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="payment_terms_days" class="form-label">Payment Terms (Days)</label>
                                            <input type="number" class="form-control @error('payment_terms_days') is-invalid @enderror"
                                                   id="payment_terms_days" name="payment_terms_days"
                                                   value="{{old('payment_terms_days', 30)}}"
                                                   placeholder="30">
                                            @error('payment_terms_days')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="tax_id" class="form-label">Tax ID / VAT Number</label>
                                    <input type="text" class="form-control @error('tax_id') is-invalid @enderror"
                                           id="tax_id" name="tax_id"
                                           value="{{old('tax_id')}}"
                                           placeholder="Enter tax/VAT registration number">
                                    @error('tax_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="bank_name" class="form-label">Bank Name</label>
                                            <input type="text" class="form-control @error('bank_name') is-invalid @enderror"
                                                   id="bank_name" name="bank_name"
                                                   value="{{old('bank_name')}}"
                                                   placeholder="ABC Bank Ltd">
                                            @error('bank_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="bank_account" class="form-label">Bank Account</label>
                                            <input type="text" class="form-control @error('bank_account') is-invalid @enderror"
                                                   id="bank_account" name="bank_account"
                                                   value="{{old('bank_account')}}"
                                                   placeholder="1234567890123">
                                            @error('bank_account')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="bank_routing" class="form-label">Bank Routing/SWIFT</label>
                                    <input type="text" class="form-control @error('bank_routing') is-invalid @enderror"
                                           id="bank_routing" name="bank_routing"
                                           value="{{old('bank_routing')}}"
                                           placeholder="ABCBDDH">
                                    @error('bank_routing')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror"
                                              id="notes" name="notes" rows="3"
                                              placeholder="Additional notes about the supplier">{{old('notes')}}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-end">
                                    <a href="{{route('admin.supplier.index')}}" class="btn btn-secondary me-2">Cancel</a>
                                    <button type="submit" class="btn btn-primary">Create Supplier</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('supplierForm').addEventListener('submit', function(e) {
    // Auto-generate supplier code if empty
    const codeField = document.getElementById('supplier_code');
    if (!codeField.value) {
        const timestamp = Date.now();
        codeField.value = 'SUP-' + timestamp.toString().slice(-3).padStart(3, '0');
    }
});
</script>
@endsection


