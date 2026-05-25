@extends('backEnd.layouts.master')
@section('title', 'Steadfast Return Requests')
@section('css')
<style>
.sf-returns .sf-return-status {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 50px;
    font-weight: 700;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.sf-returns .sf-return-status.pending { background: #fff3cd; color: #856404; }
.sf-returns .sf-return-status.approved { background: #cce5ff; color: #004085; }
.sf-returns .sf-return-status.processing { background: #d1ecf1; color: #0c5460; }
.sf-returns .sf-return-status.completed { background: #d4edda; color: #155724; }
.sf-returns .sf-return-status.cancelled { background: #f8d7da; color: #721c24; }
.sf-returns .sf-create-form {
    border: 1px solid #e8e8e8;
    border-radius: 12px;
    padding: 24px;
    background: #fafbff;
}
</style>
@endsection

@section('content')
<div class="container-fluid sf-returns">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.steadfast.dashboard') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fe-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
                <h4 class="page-title"><i class="fe-rotate-ccw me-2"></i>Steadfast Return Requests</h4>
            </div>
        </div>
    </div>

    @if(!$configured)
    <div class="row">
        <div class="col-12">
            <div class="alert alert-danger">
                <i class="fe-alert-triangle me-2"></i>Steadfast API not configured.
                <a href="{{ route('admin.courierapi.manage') }}">Configure now</a>
            </div>
        </div>
    </div>
    @else

    {{-- Create Return Request --}}
    <div class="row mb-3">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0"><i class="fe-plus-circle me-2"></i>Create Return Request</h5>
                </div>
                <div class="card-body">
                    @if(isset($createResult))
                        @if(isset($createResult['id']))
                        <div class="alert alert-success">
                            <i class="fe-check-circle me-2"></i>Return request created! ID: {{ $createResult['id'] }}
                        </div>
                        @elseif(isset($createResult['error']))
                        <div class="alert alert-danger">
                            <i class="fe-alert-circle me-2"></i>{{ $createResult['error'] }}
                        </div>
                        @elseif(isset($createResult['message']))
                        <div class="alert alert-warning">
                            <i class="fe-alert-triangle me-2"></i>{{ $createResult['message'] }}
                        </div>
                        @endif
                    @endif

                    <form method="POST" action="{{ route('admin.steadfast.return-requests.create') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Identify By</label>
                            <select name="identifier_type" class="form-select" required>
                                <option value="consignment_id">Consignment ID</option>
                                <option value="invoice">Invoice ID</option>
                                <option value="tracking_code">Tracking Code</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Value</label>
                            <input type="text" name="identifier_value" class="form-control" placeholder="Enter consignment ID, invoice or tracking code..." required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Reason <small class="text-muted">(Optional)</small></label>
                            <textarea name="reason" class="form-control" rows="3" placeholder="Reason for return..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fe-send me-1"></i> Submit Return Request
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Return Request List --}}
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="fe-list me-2"></i>All Return Requests</h5>
                    @if(is_array($returnRequests))
                    <span class="badge bg-primary rounded-pill">{{ count($returnRequests) }}</span>
                    @endif
                </div>
                <div class="card-body p-0">
                    @if(empty($returnRequests))
                    <div class="text-center py-5 text-muted">
                        <i class="fe-inbox" style="font-size:2rem;"></i>
                        <p class="mt-2">No return requests found.</p>
                    </div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Consignment ID</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($returnRequests as $rr)
                                <tr>
                                    <td><strong>{{ $rr['id'] ?? '—' }}</strong></td>
                                    <td>{{ $rr['consignment_id'] ?? '—' }}</td>
                                    <td>{{ $rr['reason'] ?? '—' }}</td>
                                    <td>
                                        <span class="sf-return-status {{ $rr['status'] ?? '' }}">
                                            {{ $rr['status'] ?? '—' }}
                                        </span>
                                    </td>
                                    <td>{{ isset($rr['created_at']) ? \Carbon\Carbon::parse($rr['created_at'])->format('d M Y h:i A') : '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
