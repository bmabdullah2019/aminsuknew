@extends('backEnd.layouts.master')

@section('title', 'Create Shipping Zone')

@section('css')
<link href="{{ asset('public/backEnd/assets/libs/select2/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <div class="page-title-box">
        <div class="page-title-right">
          <a href="{{ route('admin.shipping.zones.index') }}" class="btn btn-primary btn-sm rounded-pill">Manage</a>
        </div>
        <h4 class="page-title">Create Shipping Zone</h4>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <form action="{{ route('admin.shipping.zones.store') }}" method="POST" class="row" data-parsley-validate>
        @csrf
        <div class="col-md-6 mb-3">
          <label for="name" class="form-label">Zone Name</label>
          <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
          @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6 mb-3">
          <label for="status" class="form-label d-block">Status</label>
          <label class="switch">
            <input type="checkbox" id="status" name="status" value="1" {{ old('status', 1) ? 'checked' : '' }}>
            <span class="slider round"></span>
          </label>
        </div>
        <div class="col-12 mb-3">
          <label for="shipping_charge_ids" class="form-label">Legacy Shipping Areas</label>
          <select id="shipping_charge_ids" name="shipping_charge_ids[]" class="form-control select2-multi" multiple>
            @foreach($shippingCharges as $charge)
              <option value="{{ $charge->id }}" {{ collect(old('shipping_charge_ids', []))->contains((string) $charge->id) ? 'selected' : '' }}>
                {{ $charge->name }} - BDT {{ number_format((float) $charge->amount, 2) }}
              </option>
            @endforeach
          </select>
          @error('shipping_charge_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 mb-3">
          <label for="custom_areas" class="form-label">Custom Areas</label>
          <textarea id="custom_areas" name="custom_areas" rows="5" class="form-control @error('custom_areas') is-invalid @enderror" placeholder="One area per line">{{ old('custom_areas') }}</textarea>
          @error('custom_areas')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-success">Submit</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('script')
<script src="{{ asset('public/backEnd/assets/libs/parsleyjs/parsley.min.js') }}"></script>
<script src="{{ asset('public/backEnd/assets/js/pages/form-validation.init.js') }}"></script>
<script src="{{ asset('public/backEnd/assets/libs/select2/js/select2.min.js') }}"></script>
<script>
  $(function () {
    $('.select2-multi').select2({ width: '100%', placeholder: 'Search and select areas' });
  });
</script>
@endsection
