@extends('backEnd.layouts.master')
@section('title','Loss Entry Details')
@section('content')
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.profit-loss.losses') }}" class="btn btn-sm btn-secondary me-2">
                        <i class="mdi mdi-arrow-left"></i> Back to Losses
                    </a>
                    @if($loss->status === 'pending')
                    <form method="POST" action="{{ route('admin.profit-loss.approve-loss', $loss) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to approve this loss entry?')">
                            <i class="mdi mdi-check"></i> Approve Loss
                        </button>
                    </form>
                    <button type="button" class="btn btn-sm btn-danger" onclick="rejectLoss()">
                        <i class="mdi mdi-close"></i> Reject Loss
                    </button>
                    @endif
                </div>
                <h4 class="page-title">Loss Entry Details</h4>
                <p class="text-muted">Entry #{{ $loss->entry_number }}</p>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <!-- Loss Details -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Loss Entry Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Entry Number</label>
                                <p class="mb-0">{{ $loss->entry_number }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Entry Date</label>
                                <p class="mb-0">{{ $loss->entry_date->format('M d, Y') }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Loss Type</label>
                                <p class="mb-0">
                                    <span class="badge bg-{{ $loss->entry_type_color }} fs-6">
                                        {{ ucfirst($loss->entry_type === 'stolen' ? 'theft' : $loss->entry_type) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Status</label>
                                <p class="mb-0">
                                    <span class="badge bg-{{ $loss->status_color }} fs-6">
                                        {{ ucfirst($loss->status) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Product</label>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-light rounded me-3">
                                        <i class="mdi mdi-package-variant font-20 text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ optional($loss->product)->name ?? 'N/A' }}</h6>
                                        <small class="text-muted">{{ optional($loss->product)->product_code ?? optional($loss->product)->sku ?? 'N/A' }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Warehouse</label>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-light rounded me-3">
                                        <i class="mdi mdi-storefront font-20 text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ optional($loss->warehouse)->name ?? 'N/A' }}</h6>
                                        <small class="text-muted">{{ optional($loss->warehouse)->city ?? 'N/A' }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Quantity</label>
                                <p class="mb-0 h5">{{ number_format($loss->quantity, 2) }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Unit Cost</label>
                                <p class="mb-0 h5">BDT {{ number_format($loss->unit_cost, 2) }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Total Loss Amount</label>
                                <p class="mb-0 h4 text-danger">BDT {{ number_format($loss->total_loss_amount, 2) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <p class="mb-0">{{ $loss->description }}</p>
                    </div>

                    @if($loss->reason_details)
                    <div class="mb-3">
                        <label class="form-label fw-bold">Reason Details</label>
                        <p class="mb-0">{{ $loss->reason_details }}</p>
                    </div>
                    @endif

                    @if($loss->evidence_attachments && count($loss->evidence_attachments) > 0)
                    <div class="mb-3">
                        <label class="form-label fw-bold">Evidence Attachments</label>
                        <div class="row">
                            @foreach($loss->evidence_attachments as $attachment)
                            <div class="col-md-3 mb-2">
                                <div class="card">
                                    <div class="card-body text-center p-2">
                                        @if(strtolower(pathinfo($attachment, PATHINFO_EXTENSION)) === 'pdf')
                                            <i class="mdi mdi-file-pdf font-24 text-danger"></i>
                                            <p class="mb-0 small">PDF Document</p>
                                        @else
                                            <i class="mdi mdi-file-image font-24 text-primary"></i>
                                            <p class="mb-0 small">Image</p>
                                        @endif
                                        <a href="{{ asset('storage/' . $attachment) }}" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                                            <i class="mdi mdi-eye"></i> View
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Action Panel -->
        <div class="col-md-4">
            <!-- Reported By -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Reported By</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-light rounded-circle me-3">
                            <span class="avatar-title bg-primary rounded-circle">
                                {{ substr(optional($loss->reporter)->name ?? 'S', 0, 1) }}
                            </span>
                        </div>
                        <div>
                            <h6 class="mb-0">{{ optional($loss->reporter)->name ?? 'System' }}</h6>
                            <small class="text-muted">Reported {{ $loss->created_at->diffForHumans() }}</small>
                        </div>
                    </div>
                </div>
            </div>

            @if($loss->approver)
            <!-- Approved By -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Approved By</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-light rounded-circle me-3">
                            <span class="avatar-title bg-success rounded-circle">
                                {{ substr(optional($loss->approver)->name ?? 'A', 0, 1) }}
                            </span>
                        </div>
                        <div>
                            <h6 class="mb-0">{{ optional($loss->approver)->name ?? 'N/A' }}</h6>
                            <small class="text-muted">Approved {{ optional($loss->approved_at)->diffForHumans() ?? 'N/A' }}</small>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Timeline -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Timeline</h6>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">Loss Reported</h6>
                                <small class="text-muted">{{ $loss->created_at->format('M d, Y H:i') }}</small>
                                <p class="mb-0">Reported by {{ optional($loss->reporter)->name ?? 'System' }}</p>
                            </div>
                        </div>

                        @if($loss->status === 'approved' && $loss->approved_at)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">Loss Approved</h6>
                                <small class="text-muted">{{ $loss->approved_at->format('M d, Y H:i') }}</small>
                                <p class="mb-0">Approved by {{ optional($loss->approver)->name ?? 'N/A' }}</p>
                            </div>
                        </div>
                        @elseif($loss->status === 'rejected')
                        <div class="timeline-item">
                            <div class="timeline-marker bg-danger"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">Loss Rejected</h6>
                                <small class="text-muted">{{ $loss->updated_at->format('M d, Y H:i') }}</small>
                                <p class="mb-0">Status changed to rejected</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Loss Modal -->
@if($loss->status === 'pending')
<div class="modal fade" id="rejectLossModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.profit-loss.reject-loss', $loss) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Reject Loss Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="reject_reason" class="form-label">Reason for Rejection</label>
                        <textarea class="form-control" id="reject_reason" name="reason" rows="3" required minlength="5"
                                  placeholder="Please provide a reason for rejecting this loss entry..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Loss Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function rejectLoss() {
    new bootstrap.Modal(document.getElementById('rejectLossModal')).show();
}
</script>
@endif
@endsection

