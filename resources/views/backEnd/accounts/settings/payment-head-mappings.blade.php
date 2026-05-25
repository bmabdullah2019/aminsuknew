@extends('backEnd.layouts.master')
@section('title', 'Payment Head Mapping Settings')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Payment to Accounts Head Mapping</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-10 col-xl-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <ul class="nav nav-pills card-header-pills">
                        <li class="nav-item">
                            <a class="nav-link {{ $context === \App\Models\PaymentHeadMapping::CONTEXT_SUPPLIER_PAYMENT ? 'active' : '' }}" 
                               href="{{ route('admin.accounts.payment-head-mappings.index', ['context' => \App\Models\PaymentHeadMapping::CONTEXT_SUPPLIER_PAYMENT, 'branch_id' => $branchId]) }}">
                                Supplier Payment
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $context === \App\Models\PaymentHeadMapping::CONTEXT_CUSTOMER_PAYMENT ? 'active' : '' }}" 
                               href="{{ route('admin.accounts.payment-head-mappings.index', ['context' => \App\Models\PaymentHeadMapping::CONTEXT_CUSTOMER_PAYMENT, 'branch_id' => $branchId]) }}">
                                Customer Payment
                            </a>
                        </li>
                    </ul>

                    <form method="GET" action="{{ route('admin.accounts.payment-head-mappings.index') }}" class="d-flex align-items-center">
                        <input type="hidden" name="context" value="{{ $context }}">
                        <label for="branch_id" class="me-2 text-muted mb-0">Branch Map:</label>
                        <select name="branch_id" id="branch_id" class="form-select form-select-sm" onchange="this.form.submit()" style="width: auto;">
                            <option value="">Global Mapping (All Branches)</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((int) $branchId === $branch->id)>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
                
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Set which cash, bank, card, cheque, or online Accounts Head will receive the payment posting for the
                        <strong>{{ ucwords(str_replace('_', ' ', $context)) }}</strong> context.
                        @if($branchId)
                            These settings will only apply to <strong>{{ $branches->firstWhere('id', $branchId)->name ?? 'this branch' }}</strong>.
                        @else
                            These are the <strong>Global</strong> fallback mappings.
                        @endif
                    </p>
                    <p class="text-muted small mb-3">
                        Example: if Customer Payment + Cash is mapped to <strong>Cash in Hand</strong>, then a received customer payment by cash will debit that head. The receivable or payable side is controlled by the <strong>{{ $controlHeadLabel }}</strong> below. Branch mapping overrides the global fallback. Enable <strong>Strict Lock</strong> when users must not change the mapped head during entry.
                    </p>

                    <form method="POST" action="{{ route('admin.accounts.payment-head-mappings.update') }}">
                        @csrf
                        <input type="hidden" name="context" value="{{ $context }}">
                        <input type="hidden" name="branch_id" value="{{ $branchId }}">

                        <div class="row mb-3">
                            <div class="col-lg-6">
                                <label for="control_head_id" class="form-label fw-semibold">{{ $controlHeadLabel }}</label>
                                <select name="control_head_id" id="control_head_id" class="form-select @error('control_head_id') is-invalid @enderror">
                                    <option value="">Use default legacy control head</option>
                                    @foreach($accountHeads as $head)
                                        <option value="{{ $head->HeadId }}" @selected((int) ($controlHeadId ?? 0) === (int) $head->HeadId)>
                                            {{ $head->HeadCode }} - {{ $head->HeadName }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('control_head_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    @if($context === \App\Models\PaymentHeadMapping::CONTEXT_CUSTOMER_PAYMENT)
                                        Sales invoice and customer receipt journals will use this receivable head on the control side.
                                    @else
                                        Purchase accrual and supplier payment journals will use this payable head on the control side.
                                    @endif
                                    @if($branchId)
                                        This control head is still global; only the payment method mapping below is branch-specific.
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 25%;">Payment Method</th>
                                        <th>Accounts Head</th>
                                        <th style="width: 15%; text-align: center;">Strict Lock</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($methods as $methodKey => $methodLabel)
                                        @php
                                            $mapping = $existing[$methodKey] ?? null;
                                        @endphp
                                        <tr>
                                            <td>
                                                <strong>{{ $methodLabel }}</strong>
                                                <div class="small text-muted">{{ $methodKey }}</div>
                                            </td>
                                            <td>
                                                <select name="mappings[{{ $methodKey }}]" class="form-select @error('mappings.' . $methodKey) is-invalid @enderror">
                                                    <option value="">No auto mapping</option>
                                                    @foreach($accountHeads as $head)
                                                        <option value="{{ $head->HeadId }}" @selected((int) optional($mapping)->account_head_id === (int) $head->HeadId)>
                                                            {{ $head->HeadCode }} - {{ $head->HeadName }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('mappings.' . $methodKey)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check form-switch d-flex justify-content-center">
                                                    <input class="form-check-input" type="checkbox" role="switch" 
                                                           name="locks[{{ $methodKey }}]" value="1" 
                                                           id="lock_{{ $methodKey }}"
                                                           @checked(optional($mapping)->is_locked)>
                                                </div>
                                                <small class="text-muted" style="font-size: 11px;">Prevent User Override</small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn-primary">
                                Save Mappings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
