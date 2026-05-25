@extends('frontEnd.layouts.master')
@section('title','Customer Login')
@section('content')
<section class="auth-section wc-customer-auth">
    <div class="container">
        <div class="wc-auth-layout">
            <div class="wc-auth-copy">
                <span class="wc-commerce-kicker">Customer Account</span>
                <h1>Login to manage your orders</h1>
                <p>Track orders, save delivery details, and checkout faster with your customer account.</p>
                <ul>
                    <li><i class="fa-solid fa-box-open"></i> Order history and tracking</li>
                    <li><i class="fa-solid fa-location-dot"></i> Faster delivery information</li>
                    <li><i class="fa-solid fa-shield-halved"></i> Secure customer access</li>
                </ul>
            </div>

            <div class="form-content wc-auth-card-front">
                <div class="wc-auth-card-head">
                    <span><i class="fa-regular fa-circle-user"></i></span>
                    <div>
                        <p class="auth-title">Customer Login</p>
                        <small>Use your phone number and password.</small>
                    </div>
                </div>

                <form action="{{route('customer.signin')}}" method="POST" data-parsley-validate="">
                    @csrf
                    <div class="form-group mb-3">
                        <label for="phone">Mobile Number</label>
                        <input type="number" id="phone" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone') }}" placeholder="01XXXXXXXXX" required>
                        @error('phone')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="password">Password</label>
                        <input type="password" id="password" class="form-control @error('password') is-invalid @enderror" name="password" placeholder="Enter password" required>
                        @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="wc-auth-row">
                        <a href="{{route('customer.forgot.password')}}" class="forget-link">
                            <i class="fa-solid fa-unlock"></i> Forgot password?
                        </a>
                    </div>

                    <div class="form-group mb-3">
                        <button type="submit" class="submit-btn">Login</button>
                    </div>
                </form>

                <div class="register-now no-account">
                    <p>New customer?</p>
                    <a href="{{route('customer.register')}}"><i class="fa-solid fa-user-plus"></i> Create Account</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
@push('script')
<script src="{{asset('public/frontEnd/')}}/js/parsley.min.js"></script>
<script src="{{asset('public/frontEnd/')}}/js/form-validation.init.js"></script>
@endpush
