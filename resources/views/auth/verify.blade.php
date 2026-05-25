@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-11 col-md-9 col-lg-6">
            <div class="card auth-card">
                <div class="card-header">{{ __('Verify Your Email Address') }}</div>

                <div class="card-body">
                    @if (session('resent'))
                        <div class="alert alert-success" role="alert">
                            {{ __('A fresh verification link has been sent to your email address.') }}
                        </div>
                    @endif

                    <p class="mb-2">{{ __('Before proceeding, please check your email for a verification link.') }}</p>
                    <p class="mb-3">{{ __('If you did not receive the email, you can request another one.') }}</p>

                    @php
                        $verificationResendRoute = Route::has('verification.resend')
                            ? 'verification.resend'
                            : (Route::has('verification.send') ? 'verification.send' : null);
                    @endphp

                    @if ($verificationResendRoute)
                        <form method="POST" action="{{ route($verificationResendRoute) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">
                                {{ __('Resend Verification Email') }}
                            </button>
                        </form>
                    @else
                        <div class="alert alert-warning mb-0">
                            {{ __('Verification resend route is not available in this environment.') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
