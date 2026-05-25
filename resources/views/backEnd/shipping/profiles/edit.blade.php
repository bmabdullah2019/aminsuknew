@extends('backEnd.layouts.master')

@section('title', 'Edit Shipping Profile')

@section('content')
<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <div class="page-title-box">
        <div class="page-title-right">
          <a href="{{ route('admin.shipping.profiles.index') }}" class="btn btn-primary btn-sm rounded-pill">Manage</a>
        </div>
        <h4 class="page-title">Edit Shipping Profile</h4>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <form action="{{ route('admin.shipping.profiles.update', $profile->id) }}" method="POST" class="row" data-parsley-validate>
        @csrf
        <div class="col-md-6 mb-3">
          <label for="name" class="form-label">Profile Name</label>
          <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $profile->name) }}" required>
          @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6 mb-3">
          <label for="status" class="form-label d-block">Status</label>
          <label class="switch">
            <input type="checkbox" id="status" name="status" value="1" {{ old('status', $profile->status) ? 'checked' : '' }}>
            <span class="slider round"></span>
          </label>
        </div>
        <div class="col-12 mb-3">
          <label for="description" class="form-label">Description</label>
          <textarea id="description" name="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description', $profile->description) }}</textarea>
          @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 mb-3">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1" {{ old('is_default', $profile->is_default) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_default">Set as default profile</label>
          </div>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-success">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('script')
<script src="{{ asset('public/backEnd/') }}/assets/libs/parsleyjs/parsley.min.js"></script>
<script src="{{ asset('public/backEnd/') }}/assets/js/pages/form-validation.init.js"></script>
@endsection
