@extends('layouts.admin')

@section('content')
@php
    $adminSiteName = data_get($generalsetting ?? null, 'name') ?: config('app.name', 'Laravel');
    $adminBrandLogo = data_get($generalsetting ?? null, 'dark_logo') ?: data_get($generalsetting ?? null, 'white_logo');
    $adminLogoUrl = !empty($adminBrandLogo)
        ? (preg_match('~^(https?:)?//~', $adminBrandLogo) ? $adminBrandLogo : asset($adminBrandLogo))
        : null;
@endphp
<div class="container">
    <div class="app-login-grid">
        <section class="app-login-panel">
            <span class="app-login-badge">{{ $adminSiteName }} Admin</span>
            <h1>Sign in to manage your ecommerce ERP.</h1>
            <p>Access orders, stock, products, customers, payments, and reports from one secure admin workspace.</p>

            <div class="app-login-modules" aria-label="Admin modules">
                <span>Orders</span>
                <span>Inventory</span>
                <span>Reports</span>
                <span>Customers</span>
            </div>
        </section>

        <section class="app-login-card">
            <div class="app-login-head">
                <span class="app-login-icon">
                    @if(!empty($adminLogoUrl))
                        <img src="{{ $adminLogoUrl }}" alt="{{ $adminSiteName }}" />
                    @else
                        {{ Str::upper(Str::substr($adminSiteName, 0, 1)) }}
                    @endif
                </span>
                <div>
                    <h2>{{ __('Admin Login') }}</h2>
                    <p>Use your authorized admin credentials.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">{{ __('Email Address') }}</label>
                    <div class="app-login-input">
                        <span>@</span>
                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" placeholder="admin@example.com" required autocomplete="email" autofocus>
                    </div>
                    @error('email')
                        <span class="invalid-feedback d-block" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">{{ __('Password') }}</label>
                    <div class="app-login-input">
                        <span>*</span>
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" placeholder="Enter your password" required autocomplete="current-password">
                        <button type="button" class="app-password-toggle" data-password-toggle aria-label="Show password">Show</button>
                    </div>
                    @error('password')
                        <span class="invalid-feedback d-block" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="app-login-options">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label" for="remember">{{ __('Remember Me') }}</label>
                    </div>
                    <span>Authorized users only</span>
                </div>

                <button type="submit" class="btn btn-primary w-100 app-login-submit">
                    {{ __('Login') }}
                </button>
            </form>

            <div class="app-login-note">
                This panel is protected by admin roles and permission controls.
            </div>
        </section>
    </div>
</div>
@endsection
