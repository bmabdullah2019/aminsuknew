@extends('admin.layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Licenses</h2>
    <a href="{{ route('admin.licenses.create') }}" class="btn btn-primary">Create New License</a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Domain</th>
                    <th>Status</th>
                    <th>Expires At</th>
                    <th>Last Checked</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($licenses as $license)
                <tr>
                    <td class="ps-4 fw-bold">{{ $license->domain }}</td>
                    <td>
                        <span class="badge bg-{{ $license->status === 'active' ? 'success' : ($license->status === 'suspended' ? 'danger' : 'secondary') }}">
                            {{ ucfirst($license->status) }}
                        </span>
                    </td>
                    <td>{{ $license->expires_at ? $license->expires_at->format('Y-m-d') : 'Never' }}</td>
                    <td>{{ $license->last_checked_at ? $license->last_checked_at->diffForHumans() : 'Never' }}</td>
                    <td class="text-end pe-4">
                        <a href="{{ route('admin.licenses.edit', $license) }}" class="btn btn-sm btn-outline-primary me-1">Edit</a>
                        <form action="{{ route('admin.licenses.destroy', $license) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">No licenses found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $licenses->links() }}
</div>
@endsection