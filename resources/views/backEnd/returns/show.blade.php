@extends('backEnd.layouts.master')
@section('title','Return Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right d-flex gap-2">
                    @if(in_array($return->return_status, ['draft', 'pending'], true))
                    <a href="{{ route('admin.returns.edit', $return) }}" class="btn btn-sm btn-warning">
                        <i class="mdi mdi-pencil"></i> Edit
                    </a>
                    @endif
                    <a href="{{ route('admin.returns.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="mdi mdi-arrow-left"></i> Back
                    </a>
                </div>
                <h4 class="page-title">Return {{ $return->return_number }}</h4>
                <p class="text-muted mb-0">
                    <span class="badge bg-{{ $return->status_color }}">{{ $return->status_label }}</span>
                </p>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Order</h6>
                    <div class="fw-semibold">{{ optional($return->order)->invoice_id ?? 'N/A' }}</div>
                    <small class="text-muted">Customer: {{ optional($return->customer)->name ?? 'N/A' }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Reason</h6>
                    <div class="fw-semibold">{{ optional($return->returnReason)->reason_name ?? 'Unknown' }}</div>
                    <small class="text-muted">Source: {{ strtoupper($return->return_source) }} / {{ ucfirst($return->return_type) }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Return Value</h6>
                    <div class="fw-semibold">BDT {{ number_format((float) $return->total_return_value, 2) }}</div>
                    <small class="text-muted">Refund method: {{ strtoupper((string) ($return->refund_method ?? 'none')) }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Refund Amount</h6>
                    <div class="fw-semibold">BDT {{ number_format((float) $return->refund_amount, 2) }}</div>
                    <small class="text-muted">Created: {{ optional($return->created_at)->format('d M Y H:i') }}</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Returned Items</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Warehouse</th>
                                    <th class="text-end">Qty</th>
                                    <th>Condition</th>
                                    <th class="text-end">Restock</th>
                                    <th class="text-end">Damage</th>
                                    <th class="text-end">Refund</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($return->returnItems as $item)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ optional($item->product)->name ?? optional($item->product)->product_name ?? 'Unknown Product' }}</div>
                                        <small class="text-muted">{{ optional($item->product)->sku ?? optional($item->product)->product_code ?? 'N/A' }}</small>
                                        @if($item->notes)
                                        <div><small class="text-muted">{{ $item->notes }}</small></div>
                                        @endif
                                    </td>
                                    <td>{{ optional($item->warehouse)->name ?? optional($item->warehouse)->warehouse_name ?? 'N/A' }}</td>
                                    <td class="text-end">{{ number_format((float) $item->return_quantity, 2) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $item->condition_color }}">{{ ucfirst($item->return_condition) }}</span>
                                    </td>
                                    <td class="text-end">{{ number_format((float) $item->restock_quantity, 2) }}</td>
                                    <td class="text-end">{{ number_format((float) $item->damage_quantity, 2) }}</td>
                                    <td class="text-end">BDT {{ number_format((float) $item->refund_amount, 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No return items found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Actions</h6>
                </div>
                <div class="card-body">
                    @if(in_array($return->return_status, ['draft', 'pending'], true))
                    <form method="POST" action="{{ route('admin.returns.approve', $return) }}" class="mb-2">
                        @csrf
                        <button type="submit" class="btn btn-success w-100">Approve</button>
                    </form>

                    <form method="POST" action="{{ route('admin.returns.reject', $return) }}" class="mb-2">
                        @csrf
                        <textarea name="notes" class="form-control form-control-sm mb-2" rows="2" placeholder="Reject reason" required minlength="5"></textarea>
                        <button type="submit" class="btn btn-danger w-100">Reject</button>
                    </form>

                    <form method="POST" action="{{ route('admin.returns.cancel', $return) }}">
                        @csrf
                        <textarea name="notes" class="form-control form-control-sm mb-2" rows="2" placeholder="Cancellation reason" required minlength="5"></textarea>
                        <button type="submit" class="btn btn-outline-danger w-100">Cancel Return</button>
                    </form>
                    @elseif($return->return_status === 'approved')
                    <form method="POST" action="{{ route('admin.returns.process', $return) }}" class="mb-2">
                        @csrf
                        <select name="refund_method" class="form-select form-select-sm mb-2">
                            <option value="none">No Refund</option>
                            <option value="cash">Cash</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="credit">Credit</option>
                            <option value="voucher">Voucher</option>
                        </select>
                        <textarea name="notes" class="form-control form-control-sm mb-2" rows="2" placeholder="Processing note"></textarea>
                        <button type="submit" class="btn btn-primary w-100">Process Return</button>
                    </form>

                    <form method="POST" action="{{ route('admin.returns.complete', $return) }}" class="mb-2">
                        @csrf
                        <select name="refund_method" class="form-select form-select-sm mb-2">
                            <option value="none">No Refund</option>
                            <option value="cash">Cash</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="credit">Credit</option>
                            <option value="voucher">Voucher</option>
                        </select>
                        <textarea name="notes" class="form-control form-control-sm mb-2" rows="2" placeholder="Completion note"></textarea>
                        <button type="submit" class="btn btn-success w-100">Complete Return</button>
                    </form>

                    <form method="POST" action="{{ route('admin.returns.cancel', $return) }}">
                        @csrf
                        <textarea name="notes" class="form-control form-control-sm mb-2" rows="2" placeholder="Cancellation reason" required minlength="5"></textarea>
                        <button type="submit" class="btn btn-outline-danger w-100">Cancel Return</button>
                    </form>
                    @elseif($return->return_status === 'processing')
                    <form method="POST" action="{{ route('admin.returns.complete', $return) }}">
                        @csrf
                        <select name="refund_method" class="form-select form-select-sm mb-2">
                            <option value="none">No Refund</option>
                            <option value="cash">Cash</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="credit">Credit</option>
                            <option value="voucher">Voucher</option>
                        </select>
                        <textarea name="notes" class="form-control form-control-sm mb-2" rows="2" placeholder="Completion note"></textarea>
                        <button type="submit" class="btn btn-success w-100">Complete Return</button>
                    </form>
                    @else
                    <p class="text-muted mb-0">No actions available for this status.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Audit Timeline</h6>
                </div>
                <div class="card-body">
                    @forelse($timeline as $log)
                    <div class="d-flex mb-3 pb-3 border-bottom">
                        <div class="me-3">
                            <span class="badge bg-{{ $log->action_color }}">
                                <i class="mdi mdi-{{ $log->action_icon }}"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">{{ ucfirst($log->action_type) }}</div>
                            <div class="text-muted small">{{ $log->status_change_description }}</div>
                            @if($log->notes)
                            <div class="small mt-1">{{ $log->notes }}</div>
                            @endif
                            <div class="small text-muted mt-1">
                                By {{ optional($log->performer)->name ?? 'System' }} on {{ optional($log->performed_at)->format('d M Y H:i') }}
                            </div>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted mb-0">No timeline events found.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
