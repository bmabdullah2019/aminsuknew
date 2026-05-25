@extends('admin.layout')

@section('title', 'Edit License')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Edit License #{{ $license->id }}</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.licenses.update', $license) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label for="domain" class="form-label">Domain <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('domain') is-invalid @enderror" 
                               id="domain" 
                               name="domain" 
                               value="{{ old('domain', $license->domain) }}" 
                               placeholder="example.com"
                               required>
                        @error('domain')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Enter domain without www. prefix (it will be normalized automatically)</small>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror" 
                                id="status" 
                                name="status" 
                                required>
                            <option value="active" {{ old('status', $license->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $license->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="suspended" {{ old('status', $license->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="expires_at" class="form-label">Expires At</label>
                        <input type="date" 
                               class="form-control @error('expires_at') is-invalid @enderror" 
                               id="expires_at" 
                               name="expires_at" 
                               value="{{ old('expires_at', $license->expires_at?->format('Y-m-d')) }}">
                        @error('expires_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Leave empty for lifetime license</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">License Key</label>
                        <div class="input-group">
                            <input type="text" 
                                   class="form-control font-monospace" 
                                   value="{{ $license->license_key }}" 
                                   readonly>
                            <button type="button" 
                                    class="btn btn-outline-secondary" 
                                    onclick="navigator.clipboard.writeText('{{ $license->license_key }}')">
                                Copy
                            </button>
                        </div>
                        <small class="text-muted">Created: {{ $license->created_at->format('Y-m-d H:i:s') }}</small>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="regenerate_key" 
                                   name="regenerate_key" 
                                   value="1">
                            <label class="form-check-label text-danger" for="regenerate_key">
                                Regenerate License Key (This will invalidate the current key)
                            </label>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Update License</button>
                        <a href="{{ route('admin.licenses.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
