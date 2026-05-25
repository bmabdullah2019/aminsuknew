@extends('backEnd.layouts.master')

@section('title', 'Shipping Profiles')

@section('content')
<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <div class="page-title-box">
        <div class="page-title-right">
          <a href="{{ route('admin.shipping.profiles.create') }}" class="btn btn-primary btn-sm rounded-pill">Create Profile</a>
        </div>
        <h4 class="page-title">Shipping Profiles</h4>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body table-responsive">
      <table class="table table-striped align-middle mb-0">
        <thead>
          <tr>
            <th>SL</th>
            <th>Name</th>
            <th>Slug</th>
            <th>Products</th>
            <th>Status</th>
            <th class="text-end">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($profiles as $profile)
            <tr>
              <td>{{ $loop->iteration }}</td>
              <td>
                <strong>{{ $profile->name }}</strong>
                @if($profile->is_default)
                  <span class="badge bg-info ms-1">Default</span>
                @endif
                @if($profile->description)
                  <div class="text-muted small">{{ \Illuminate\Support\Str::limit($profile->description, 80) }}</div>
                @endif
              </td>
              <td>{{ $profile->slug }}</td>
              <td>{{ $profile->products_count }}</td>
              <td>
                <span class="badge {{ $profile->status ? 'bg-soft-success text-success' : 'bg-soft-danger text-danger' }}">
                  {{ $profile->status ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="text-end">
                <a href="{{ route('admin.shipping.profiles.edit', $profile->id) }}" class="btn btn-xs btn-primary"><i class="fe-edit-1"></i></a>
                <form action="{{ route('admin.shipping.profiles.destroy', $profile->id) }}" method="POST" class="d-inline">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-xs btn-danger delete-confirm"><i class="mdi mdi-close"></i></button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center text-muted py-4">No shipping profiles found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
