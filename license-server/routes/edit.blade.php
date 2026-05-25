@extends('admin.layout')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h4 class="mb-0">Edit License: {{ $license->domain }}</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.licenses.update', $license) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label class="form-label">Domain</label>
                        <input type="text" name="domain" class="form-control" value="{{ $license->domain }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" {{ $license->status == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ $license->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="suspended" {{ $license->status == 'suspended' ? 'selected' : '' }}>Suspended</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Expires At</label>
                        <input type="date" name="expires_at" class="form-control" value="{{ $license->expires_at ? $license->expires_at->format('Y-m-d') : '' }}">
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="regenerate" name="regenerate_key">
                        <label class="form-check-label text-danger" for="regenerate">Regenerate License Key (Warning: This will break the client connection until updated)</label>
                    </div>

                    <button type="submit" class="btn btn-primary">Update License</button>
                    <a href="{{ route('admin.licenses.index') }}" class="btn btn-link">Cancel</a>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">Current License Key</h5>
            </div>
            <div class="card-body">
                <div class="input-group">
                    <input type="text" class="form-control font-monospace" value="{{ $license->license_key }}" readonly>
                    <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(this.previousElementSibling.value)">Copy</button>
                </div>
                <div class="form-text mt-2">Use this key in the client application's .env file (LICENSE_KEY).</div>
            </div>
        </div>
    </div>
</div>
@endsection