@extends('backEnd.layouts.master')
@section('title','Subsidiaries')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.accounts.subsidiary.create') }}" class="btn btn-sm btn-primary"><i class="mdi mdi-plus"></i> New Subsidiary</a>
                </div>
                <h4 class="page-title">Subsidiaries</h4>
            </div>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-6"><input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search code or name"></div>
                <div class="col-md-2 align-self-end"><button class="btn btn-primary" type="submit">Filter</button></div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark"><tr><th>Code</th><th>Name</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                        @forelse($subsidiaries as $s)
                        <tr>
                            <td>{{ $s->SubCode }}</td>
                            <td>{{ $s->SubName }}</td>
                            <td><span class="badge bg-{{ $s->Status === 'A' ? 'success' : 'danger' }}">{{ $s->Status === 'A' ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.accounts.subsidiary.edit', $s->SubId) }}" class="btn btn-sm btn-outline-primary"><i class="mdi mdi-pencil"></i></a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted">No subsidiaries found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($subsidiaries->hasPages())<div class="d-flex justify-content-center">{{ $subsidiaries->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection
