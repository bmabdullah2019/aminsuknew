@extends('backEnd.layouts.master')

@section('title', 'Shipping Zones')

@section('content')
<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <div class="page-title-box">
        <div class="page-title-right">
          <a href="{{ route('admin.shipping.zones.create') }}" class="btn btn-primary btn-sm rounded-pill">Create Zone</a>
        </div>
        <h4 class="page-title">Shipping Zones</h4>
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
            <th>Areas</th>
            <th>Status</th>
            <th class="text-end">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($zones as $zone)
            <tr>
              <td>{{ $loop->iteration }}</td>
              <td><strong>{{ $zone->name }}</strong></td>
              <td>{{ $zone->slug }}</td>
              <td>{{ $zone->areas_count }}</td>
              <td>
                <span class="badge {{ $zone->status ? 'bg-soft-success text-success' : 'bg-soft-danger text-danger' }}">
                  {{ $zone->status ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="text-end">
                <a href="{{ route('admin.shipping.zones.edit', $zone->id) }}" class="btn btn-xs btn-primary"><i class="fe-edit-1"></i></a>
                <form action="{{ route('admin.shipping.zones.destroy', $zone->id) }}" method="POST" class="d-inline">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-xs btn-danger delete-confirm"><i class="mdi mdi-close"></i></button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center text-muted py-4">No shipping zones found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
