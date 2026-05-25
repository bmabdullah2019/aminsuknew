@extends('backEnd.layouts.master')

@section('title', 'Create Shipping Rate')

@section('content')
<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <div class="page-title-box">
        <div class="page-title-right">
          <a href="{{ route('admin.shipping.rates.index') }}" class="btn btn-primary btn-sm rounded-pill">Manage</a>
        </div>
        <h4 class="page-title">Create Shipping Rate</h4>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <form action="{{ route('admin.shipping.rates.store') }}" method="POST" class="row" data-parsley-validate>
        @csrf
        <div class="col-md-6 mb-3">
          <label for="shipping_zone_id" class="form-label">Zone</label>
          <select id="shipping_zone_id" name="shipping_zone_id" class="form-control @error('shipping_zone_id') is-invalid @enderror" required>
            <option value="">Select zone</option>
            @foreach($zones as $zone)
              <option value="{{ $zone->id }}" {{ (string) old('shipping_zone_id') === (string) $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
            @endforeach
          </select>
          @error('shipping_zone_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6 mb-3">
          <label for="shipping_profile_id" class="form-label">Profile</label>
          <select id="shipping_profile_id" name="shipping_profile_id" class="form-control @error('shipping_profile_id') is-invalid @enderror" required>
            <option value="">Select profile</option>
            @foreach($profiles as $profile)
              <option value="{{ $profile->id }}" {{ (string) old('shipping_profile_id') === (string) $profile->id ? 'selected' : '' }}>{{ $profile->name }}</option>
            @endforeach
          </select>
          @error('shipping_profile_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3 mb-3">
          <label for="min_weight" class="form-label">Min Weight (kg)</label>
          <input type="number" step="0.001" min="0" id="min_weight" name="min_weight" class="form-control @error('min_weight') is-invalid @enderror" value="{{ old('min_weight', 0) }}" required>
          @error('min_weight')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3 mb-3">
          <label for="max_weight" class="form-label">Max Weight (kg)</label>
          <input type="number" step="0.001" min="0" id="max_weight" name="max_weight" class="form-control @error('max_weight') is-invalid @enderror" value="{{ old('max_weight') }}" required>
          @error('max_weight')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3 mb-3">
          <label for="rate" class="form-label">Rate (BDT)</label>
          <input type="number" step="1" min="0" id="rate" name="rate" class="form-control @error('rate') is-invalid @enderror" value="{{ old('rate') }}" required>
          @error('rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3 mb-3">
          <label for="status" class="form-label d-block">Status</label>
          <label class="switch">
            <input type="checkbox" id="status" name="status" value="1" {{ old('status', 1) ? 'checked' : '' }}>
            <span class="slider round"></span>
          </label>
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
@endsection
