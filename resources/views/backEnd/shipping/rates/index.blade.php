@extends('backEnd.layouts.master')

@section('title', 'Shipping Rates')

@section('content')
<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <div class="page-title-box">
        <div class="page-title-right">
          <a href="{{ route('admin.shipping.rates.create') }}" class="btn btn-primary btn-sm rounded-pill">Create Rate</a>
        </div>
        <h4 class="page-title">Shipping Rates</h4>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
          <label for="zone_id" class="form-label">Zone</label>
          <select id="zone_id" name="zone_id" class="form-control">
            <option value="">All zones</option>
            @foreach($zones as $zone)
              <option value="{{ $zone->id }}" {{ (string) request('zone_id') === (string) $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <label for="profile_id" class="form-label">Profile</label>
          <select id="profile_id" name="profile_id" class="form-control">
            <option value="">All profiles</option>
            @foreach($profiles as $profile)
              <option value="{{ $profile->id }}" {{ (string) request('profile_id') === (string) $profile->id ? 'selected' : '' }}>{{ $profile->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <button type="submit" class="btn btn-primary">Filter</button>
          <a href="{{ route('admin.shipping.rates.index') }}" class="btn btn-light">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-body table-responsive">
      <table class="table table-striped align-middle mb-0">
        <thead>
          <tr>
            <th>SL</th>
            <th>Zone</th>
            <th>Profile</th>
            <th>Weight Range</th>
            <th>Rate</th>
            <th>Status</th>
            <th class="text-end">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rates as $rate)
            <tr>
              <td>{{ $loop->iteration }}</td>
              <td>{{ optional($rate->zone)->name ?? 'N/A' }}</td>
              <td>{{ optional($rate->profile)->name ?? 'N/A' }}</td>
              <td>{{ $rate->min_weight }} - {{ $rate->max_weight }} kg</td>
              <td>BDT {{ number_format((float) $rate->rate, 2) }}</td>
              <td>
                <span class="badge {{ $rate->status ? 'bg-soft-success text-success' : 'bg-soft-danger text-danger' }}">
                  {{ $rate->status ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="text-end">
                <a href="{{ route('admin.shipping.rates.edit', $rate->id) }}" class="btn btn-xs btn-primary"><i class="fe-edit-1"></i></a>
                <form action="{{ route('admin.shipping.rates.destroy', $rate->id) }}" method="POST" class="d-inline">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-xs btn-danger delete-confirm"><i class="mdi mdi-close"></i></button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center text-muted py-4">No shipping rates found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
