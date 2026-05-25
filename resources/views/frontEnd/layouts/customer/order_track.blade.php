@extends('frontEnd.layouts.master')
@section('title','Track Your Order')
@push('css')
<style>
/* ── Order Track Form Styles ────────────────────────────── */
.ot-form-section {
    min-height: 40vh;
    background: #f4f7fb;
    display: flex;
    align-items: flex-start;
    margin-top: 12px;
    padding: 48px 0 34px;
}

.ot-form-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 8px 48px rgba(7,139,126,.12), 0 1px 4px rgba(0,0,0,.05);
    overflow: hidden;
    max-width: 480px;
    margin: 0 auto;
    width: 100%;
}

/* Card banner */
.ot-form-banner {
    background: linear-gradient(120deg, #078b7e 0%, #05b89f 100%);
    padding: 20px 24px 16px;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.ot-form-banner::before {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Ccircle cx='30' cy='30' r='28'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
    background-size: 60px 60px;
}

.ot-form-icon {
    position: relative;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: rgba(255,255,255,.18);
    border: 2px solid rgba(255,255,255,.35);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #fff;
    margin-bottom: 12px;
}

.ot-form-banner h1 {
    position: relative;
    font-size: 19px;
    font-weight: 800;
    color: #ffffff !important;
    margin: 0 0 6px;
    letter-spacing: -.3px;
}

.ot-form-banner p {
    position: relative;
    font-size: 12px;
    color: #ffffff !important;
    opacity: .92;
    margin: 0;
}

/* Form body */
.ot-form-body {
    padding: 20px 24px 24px;
}

.ot-form-group {
    margin-bottom: 14px;
}

.ot-form-group label {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: #475569;
    margin-bottom: 6px;
    letter-spacing: .2px;
}

.ot-form-group label span.req {
    color: #ef4444;
    margin-left: 2px;
}

.ot-input-wrap {
    position: relative;
}

.ot-input-wrap .ot-input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 14px;
    color: #94a3b8;
    pointer-events: none;
    transition: color .2s;
}

.ot-input-wrap input:focus ~ .ot-input-icon,
.ot-input-wrap:focus-within .ot-input-icon {
    color: #078b7e;
}

.ot-input-wrap input {
    width: 100%;
    height: 46px;
    padding: 0 16px 0 40px;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    font-size: 14px;
    color: #1e293b;
    background: #f8fafc;
    outline: none;
    transition: border-color .2s, box-shadow .2s, background .2s;
}

.ot-input-wrap input:focus {
    border-color: #078b7e;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(7,139,126,.12);
}

.ot-input-wrap input.is-invalid {
    border-color: #ef4444;
}

.ot-input-wrap input::placeholder {
    color: #cbd5e1;
    font-size: 13px;
}

.invalid-feedback {
    display: block;
    font-size: 12px;
    color: #ef4444;
    margin-top: 5px;
}

/* Divider */
.ot-divider {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 4px 0 14px;
}

.ot-divider::before,
.ot-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #e2e8f0;
}

.ot-divider span {
    font-size: 12px;
    font-weight: 700;
    color: #94a3b8;
    letter-spacing: .5px;
    text-transform: uppercase;
}

/* Submit button */
.ot-submit-btn {
    width: 100%;
    height: 48px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(120deg, #078b7e 0%, #05b89f 100%);
    color: #fff;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: opacity .2s, box-shadow .2s, transform .15s;
    margin-top: 4px;
    letter-spacing: .2px;
}

.ot-submit-btn:hover {
    opacity: .92;
    box-shadow: 0 6px 24px rgba(7,139,126,.38);
    transform: translateY(-1px);
}

.ot-submit-btn:active {
    transform: translateY(0);
}

/* Steps hint */
.ot-steps-hint {
    display: flex;
    justify-content: center;
    gap: 28px;
    margin-top: 18px;
    padding-top: 16px;
    border-top: 1px solid #f1f5f9;
    flex-wrap: wrap;
}

.ot-hint-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    color: #94a3b8;
    font-weight: 600;
    text-align: center;
}

.ot-hint-item i {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #f0fdf9;
    border: 1.5px solid #d1fae5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    color: #078b7e;
}

@media (max-width: 575px) {
    .ot-form-section { padding: 24px 0 32px; }
    .ot-form-banner { padding: 22px 20px 18px; }
    .ot-form-body   { padding: 20px 20px 22px; }
    .ot-steps-hint  { gap: 14px; }
}
</style>
@endpush

@section('content')
<section class="ot-form-section">
    <div class="container">
        <div class="ot-form-card">

            {{-- Banner --}}
            <div class="ot-form-banner">
                <div class="ot-form-icon">
                    <i class="fas fa-truck"></i>
                </div>
                <h1>Track Your Order</h1>
                <p>Enter your phone number or invoice ID to get real-time delivery updates.</p>
            </div>

            {{-- Form --}}
            <div class="ot-form-body">
                <form action="{{ route('customer.order_track_result') }}" method="GET" data-parsley-validate="">

                    {{-- Phone --}}
                    <div class="ot-form-group">
                        <label for="phone">Phone Number</label>
                        <div class="ot-input-wrap">
                            <input
                                type="text"
                                id="phone"
                                name="phone"
                                value="{{ old('phone') }}"
                                placeholder="e.g. 01XXXXXXXXX"
                                class="@error('phone') is-invalid @enderror"
                                autocomplete="tel"
                            >
                            <span class="ot-input-icon"><i class="fas fa-phone-alt"></i></span>
                        </div>
                        @error('phone')
                            <span class="invalid-feedback"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="ot-divider"><span>And</span></div>

                    {{-- Invoice ID --}}
                    <div class="ot-form-group">
                        <label for="invoice_id">Invoice ID</label>
                        <div class="ot-input-wrap">
                            <input
                                type="text"
                                id="invoice_id"
                                name="invoice_id"
                                value="{{ old('invoice_id') }}"
                                placeholder="e.g. INV-20260228123456-1234"
                                class="@error('invoice_id') is-invalid @enderror"
                                autocomplete="off"
                                style="text-transform:uppercase;"
                            >
                            <span class="ot-input-icon"><i class="fas fa-receipt"></i></span>
                        </div>
                        @error('invoice_id')
                            <span class="invalid-feedback"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Submit --}}
                    <button type="submit" class="ot-submit-btn" id="track-order-btn">
                        <i class="fas fa-search"></i> Track Order
                    </button>

                </form>

                {{-- Hint steps --}}
                <div class="ot-steps-hint">
                    <div class="ot-hint-item">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Order Placed</span>
                    </div>
                    <div class="ot-hint-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Confirmed</span>
                    </div>
                    <div class="ot-hint-item">
                        <i class="fas fa-box-open"></i>
                        <span>Processing</span>
                    </div>
                    <div class="ot-hint-item">
                        <i class="fas fa-truck"></i>
                        <span>Shipped</span>
                    </div>
                    <div class="ot-hint-item">
                        <i class="fas fa-home"></i>
                        <span>Delivered</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
@endsection
@push('script')
<script src="{{ asset('public/frontEnd/') }}/js/parsley.min.js"></script>
<script src="{{ asset('public/frontEnd/') }}/js/form-validation.init.js"></script>
<script>
    // Auto-uppercase invoice ID field
    document.getElementById('invoice_id').addEventListener('input', function () {
        var pos = this.selectionStart;
        this.value = this.value.toUpperCase();
        this.setSelectionRange(pos, pos);
    });
</script>
@endpush
