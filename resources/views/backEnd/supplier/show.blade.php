@extends('backEnd.layouts.master')
@section('title', $supplier->name . ' - Supplier Details')
@section('content')
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{route('admin.supplier.edit', $supplier->id)}}" class="btn btn-warning rounded-pill me-2">
                        <i class="fe-edit"></i> Edit
                    </a>
                    <a href="{{route('admin.supplier.index')}}" class="btn btn-secondary rounded-pill">
                        <i class="fe-arrow-left"></i> Back
                    </a>
                </div>
                <h4 class="page-title">Supplier Details: {{$supplier->name}}</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <!-- Supplier Information -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Supplier Information</h5>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Supplier Code:</label>
                                <p class="mb-0">{{$supplier->supplier_code}}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Status:</label>
                                <p class="mb-0">
                                    <span class="badge bg-{{$supplier->status === 'active' ? 'success' : 'secondary'}}">
                                        {{ucfirst($supplier->status)}}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Email:</label>
                                <p class="mb-0">
                                    @if($supplier->email)
                                        <a href="mailto:{{$supplier->email}}">{{$supplier->email}}</a>
                                    @else
                                        <span class="text-muted">Not provided</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Phone:</label>
                                <p class="mb-0">
                                    @if($supplier->phone)
                                        <a href="tel:{{$supplier->phone}}">{{$supplier->phone}}</a>
                                    @else
                                        <span class="text-muted">Not provided</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    @if($supplier->address)
                    <div class="mb-3">
                        <label class="form-label fw-bold">Address:</label>
                        <p class="mb-0">
                            {{$supplier->address}}
                            @if($supplier->city), {{$supplier->city}}@endif
                            @if($supplier->state), {{$supplier->state}}@endif
                            @if($supplier->country), {{$supplier->country}}@endif
                            @if($supplier->postal_code) - {{$supplier->postal_code}}@endif
                        </p>
                    </div>
                    @endif

                    @if($supplier->contact_person)
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Contact Person:</label>
                                <p class="mb-0">{{$supplier->contact_person}}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Contact Phone:</label>
                                <p class="mb-0">
                                    @if($supplier->contact_person_phone)
                                        <a href="tel:{{$supplier->contact_person_phone}}">{{$supplier->contact_person_phone}}</a>
                                    @else
                                        <span class="text-muted">Not provided</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($supplier->tax_id || $supplier->bank_name)
                    <h6 class="mt-4 mb-3">Financial Information</h6>
                    <div class="row">
                        @if($supplier->tax_id)
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Tax ID/VAT:</label>
                                <p class="mb-0">{{$supplier->tax_id}}</p>
                            </div>
                        </div>
                        @endif
                        @if($supplier->bank_name)
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Bank:</label>
                                <p class="mb-0">
                                    {{$supplier->bank_name}}
                                    @if($supplier->bank_account)<br>Account: {{$supplier->bank_account}}@endif
                                    @if($supplier->bank_routing)<br>Routing: {{$supplier->bank_routing}}@endif
                                </p>
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif

                    @if($supplier->notes)
                    <div class="mb-3">
                        <label class="form-label fw-bold">Notes:</label>
                        <p class="mb-0">{{$supplier->notes}}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="card mt-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Recent Transactions</h5>
                        <a href="{{route('admin.supplier.ledger', $supplier->id)}}" class="btn btn-sm btn-primary">
                            View All
                        </a>
                    </div>

                    @if($supplier->ledger->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($supplier->ledger->take(5) as $entry)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($entry->transaction_date)->format('d M Y') }}</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $entry->transaction_type_label }}</span>
                                    </td>
                                    <td>{{ Str::limit($entry->description, 30) }}</td>
                                    <td class="text-end">
                                        <span class="text-{{ $entry->debit > 0 ? 'danger' : 'success' }}">
                                            {{ $entry->formatted_amount }}
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold">BDT {{ number_format($entry->running_balance, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted mb-0">No transactions found</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Financial Summary -->
        <div class="col-lg-4">
            <!-- Current Balance -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Current Balance</h5>
                    <div class="text-center">
                        <h2 class="text-{{ $supplier->current_balance >= 0 ? 'danger' : 'success' }} mb-0">
                            BDT {{ number_format(abs($supplier->current_balance), 2) }}
                        </h2>
                        <p class="text-muted mb-0">
                            {{ $supplier->current_balance >= 0 ? 'You owe this supplier' : 'Supplier owes you' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Credit Information -->
            <div class="card mt-3">
                <div class="card-body">
                    <h5 class="card-title">Credit Information</h5>
                    <div class="row text-center">
                        <div class="col-6">
                            <h6 class="text-muted">Credit Limit</h6>
                            <p class="mb-0 fw-bold">BDT {{ number_format($supplier->credit_limit, 2) }}</p>
                        </div>
                        <div class="col-6">
                            <h6 class="text-muted">Payment Terms</h6>
                            <p class="mb-0 fw-bold">{{ $supplier->payment_terms_days }} days</p>
                        </div>
                    </div>

                    @if($supplier->is_over_credit_limit)
                    <div class="alert alert-danger mt-3 mb-0">
                        <i class="fe-alert-triangle"></i> Credit limit exceeded!
                    </div>
                    @endif
                </div>
            </div>

            <!-- Aging Summary -->
            <div class="card mt-3">
                <div class="card-body">
                    <h5 class="card-title">Aging Summary</h5>
                    <div class="mb-2">
                        <small class="text-muted">Current (0-{{$supplier->payment_terms_days}} days)</small>
                        <div class="text-end fw-bold text-success">
                            BDT {{ number_format($agingSummary['current'], 2) }}
                        </div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Overdue ({{$supplier->payment_terms_days+1}}-{{$supplier->payment_terms_days+30}} days)</small>
                        <div class="text-end fw-bold text-warning">
                            BDT {{ number_format($agingSummary['overdue_1_30'], 2) }}
                        </div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Overdue ({{$supplier->payment_terms_days+31}}-{{$supplier->payment_terms_days+60}} days)</small>
                        <div class="text-end fw-bold text-danger">
                            BDT {{ number_format($agingSummary['overdue_31_60'], 2) }}
                        </div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Overdue ({{$supplier->payment_terms_days+61}}-{{$supplier->payment_terms_days+90}} days)</small>
                        <div class="text-end fw-bold text-danger">
                            BDT {{ number_format($agingSummary['overdue_61_90'], 2) }}
                        </div>
                    </div>
                    <div>
                        <small class="text-muted">Overdue (90+ days)</small>
                        <div class="text-end fw-bold text-dark">
                            BDT {{ number_format($agingSummary['overdue_90_plus'], 2) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mt-3">
                <div class="card-body">
                    <h5 class="card-title">Quick Actions</h5>
                    <div class="d-grid gap-2">
                        <a href="{{route('admin.supplier.payments.create', $supplier->id)}}" class="btn btn-success btn-sm">
                            <i class="fe-dollar-sign"></i> Make Payment
                        </a>
                        <a href="{{route('admin.supplier.purchase-returns.create', $supplier->id)}}" class="btn btn-warning btn-sm">
                            <i class="fe-refresh-ccw"></i> Purchase Return
                        </a>
                        <button type="button" class="btn btn-info btn-sm w-100" onclick="setOpeningBalance()">
                            <i class="fe-plus-circle"></i> Set Opening Balance
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Opening Balance Modal -->
<div class="modal fade" id="openingBalanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{route('admin.supplier.opening-balance', $supplier->id)}}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Set Opening Balance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="opening_date" class="form-label">Opening Date</label>
                        <input type="date" class="form-control" id="opening_date" name="opening_date"
                               value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="opening_balance" class="form-label">Opening Balance (BDT)</label>
                        <input type="number" step="0.01" class="form-control" id="opening_balance"
                               name="opening_balance" placeholder="0.00" required>
                    </div>
                    <div class="mb-3">
                        <label for="balance_type" class="form-label">Balance Type</label>
                        <select class="form-control" id="balance_type" name="balance_type" required>
                            <option value="debit">Debit (You owe supplier)</option>
                            <option value="credit">Credit (Supplier owes you)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"
                                  placeholder="Opening balance description"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Set Opening Balance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function setOpeningBalance() {
    if (typeof $ !== 'undefined' && typeof $('#openingBalanceModal').modal === 'function') {
        $('#openingBalanceModal').modal('show');
        return;
    }

    const modalEl = document.getElementById('openingBalanceModal');
    if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
        window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
}
</script>
@endsection


