@extends('admin.layout')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h4 class="mb-0">Create License</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.licenses.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Domain</label>
                        <input type="text" name="domain" class="form-control" placeholder="example.com" required>
                        <div class="form-text">Do not include http:// or https://</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Expires At (Optional)</label>
                        <input type="date" name="expires_at" class="form-control">
                    </div>

                    <button type="submit" class="btn btn-primary">Create License</button>
                    <a href="{{ route('admin.licenses.index') }}" class="btn btn-link">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection