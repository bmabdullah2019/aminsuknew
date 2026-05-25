@extends('backEnd.layouts.master')
@section('title','Expense Activity Log')
@section('content')
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.expense.index') }}" class="btn btn-sm btn-secondary">
                        <i class="mdi mdi-arrow-left"></i> Back to Expenses
                    </a>
                </div>
                <h4 class="page-title">Expense Activity Log</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $activityData['total_activities'] }}</h4>
                    <small>Total Activities</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $activityData['activities_by_action']['created'] ?? 0 }}</h4>
                    <small>Expenses Created</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $activityData['activities_by_action']['approved'] ?? 0 }}</h4>
                    <small>Expenses Approved</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $activityData['activities_by_action']['paid'] ?? 0 }}</h4>
                    <small>Expenses Paid</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Activity Filters</h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="user_id" class="form-label">User</label>
                            <select class="form-control" id="user_id" name="user_id">
                                <option value="">All Users</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ ($filters['user_id'] ?? '') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="action" class="form-label">Action</label>
                            <select class="form-control" id="action" name="action">
                                <option value="">All Actions</option>
                                <option value="created" {{ ($filters['action'] ?? '') == 'created' ? 'selected' : '' }}>Created</option>
                                <option value="updated" {{ ($filters['action'] ?? '') == 'updated' ? 'selected' : '' }}>Updated</option>
                                <option value="approved" {{ ($filters['action'] ?? '') == 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="rejected" {{ ($filters['action'] ?? '') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                <option value="paid" {{ ($filters['action'] ?? '') == 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="allocated" {{ ($filters['action'] ?? '') == 'allocated' ? 'selected' : '' }}>Allocated</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="days" class="form-label">Time Period</label>
                            <select class="form-control" id="days" name="days">
                                <option value="7" {{ ($filters['days'] ?? '') == '7' ? 'selected' : '' }}>Last 7 days</option>
                                <option value="30" {{ ($filters['days'] ?? '30') == '30' ? 'selected' : '' }}>Last 30 days</option>
                                <option value="90" {{ ($filters['days'] ?? '') == '90' ? 'selected' : '' }}>Last 90 days</option>
                                <option value="all" {{ ($filters['days'] ?? '') === 'all' ? 'selected' : '' }}>All time</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="mdi mdi-filter"></i> Filter
                                </button>
                                <a href="{{ route('admin.expense.activity-log') }}" class="btn btn-secondary">
                                    <i class="mdi mdi-refresh"></i> Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Charts -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Activities by Action</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Action</th>
                                    <th class="text-end">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activityData['activities_by_action'] as $action => $count)
                                <tr>
                                    <td>
                                        <span class="badge bg-{{ match($action) {
                                            'created' => 'success',
                                            'updated' => 'info',
                                            'approved' => 'warning',
                                            'rejected' => 'danger',
                                            'paid' => 'primary',
                                            'allocated' => 'secondary',
                                            default => 'light'
                                        } }}">
                                            {{ ucfirst($action) }}
                                        </span>
                                    </td>
                                    <td class="text-end">{{ $count }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Activities by User</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th class="text-end">Activities</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activityData['activities_by_user'] as $user => $count)
                                <tr>
                                    <td>{{ $user }}</td>
                                    <td class="text-end">{{ $count }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Log Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Activity Timeline</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Date & Time</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Expense</th>
                                    <th>Description</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activityData['logs'] as $log)
                                <tr>
                                    <td>
                                        <strong>{{ $log->created_at->format('d M Y') }}</strong><br>
                                        <small class="text-muted">{{ $log->created_at->format('H:i:s') }}</small>
                                    </td>
                                    <td>
                                        <strong>{{ optional($log->user)->name ?? 'System' }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ match($log->action) {
                                            'created' => 'success',
                                            'updated' => 'info',
                                            'approved' => 'warning',
                                            'rejected' => 'danger',
                                            'paid' => 'primary',
                                            'allocated' => 'secondary',
                                            default => 'light'
                                        } }}">
                                            {{ ucfirst($log->action) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($log->expense)
                                            <a href="{{ route('admin.expense.show', $log->expense) }}" class="text-primary">
                                                {{ $log->expense->expense_number }}
                                            </a>
                                            <br>
                                            <small class="text-muted">{{ optional($log->expense->category)->name ?? 'N/A' }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $log->description }}
                                        @if($log->old_values || $log->new_values)
                                            <br>
                                            <small class="text-muted">
                                                <em>Changes recorded</em>
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        <code>{{ $log->ip_address }}</code>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">
                                        <i class="mdi mdi-information-outline"></i>
                                        No activities found matching your criteria.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($activityData['logs']->hasPages())
                    <div class="d-flex justify-content-center mt-3">
                        {{ $activityData['logs']->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
