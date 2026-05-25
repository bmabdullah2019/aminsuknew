@extends('admin.layout')

@section('title', 'Licenses')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Licenses</h2>
    <a href="{{ route('admin.licenses.create') }}" class="btn btn-primary">Create New License</a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Domain</th>
                        <th>Status</th>
                        <th>Expires At</th>
                        <th>Last Checked</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($licenses as $license)
                        <tr>
                            <td>{{ $license->id }}</td>
                            <td>{{ $license->domain }}</td>
                            <td>
                                <span class="badge bg-{{ $license->status === 'active' ? 'success' : ($license->status === 'suspended' ? 'warning' : 'secondary') }}">
                                    {{ ucfirst($license->status) }}
                                </span>
                            </td>
                            <td>{{ $license->expires_at ? $license->expires_at->format('Y-m-d') : 'Never' }}</td>
                            <td>{{ $license->last_checked_at ? $license->last_checked_at->diffForHumans() : 'Never' }}</td>
                            <td>{{ $license->created_at->format('Y-m-d') }}</td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('admin.licenses.edit', $license) }}" class="btn btn-outline-primary">Edit</a>
                                    <form action="{{ route('admin.licenses.rotate', $license) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-warning" onclick="return confirm('Rotate license key? This will invalidate the current key.')">Rotate Key</button>
                                    </form>
                                    <form action="{{ route('admin.licenses.destroy', $license) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Delete this license?')">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">No licenses found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($licenses->hasPages())
            <div class="mt-3">
                {{ $licenses->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
