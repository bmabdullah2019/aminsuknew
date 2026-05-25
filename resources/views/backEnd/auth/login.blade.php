<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Login | {{$generalsetting->name}}</title>
    <link rel="shortcut icon" href="{{asset($generalsetting->favicon)}}" alt="{{$generalsetting->name}}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="{{asset('public/backEnd/')}}/assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="{{asset('public/backEnd/')}}/assets/css/app.min.css" rel="stylesheet" type="text/css">
    <link href="{{asset('public/backEnd/')}}/assets/css/icons.min.css" rel="stylesheet" type="text/css">
    <link href="{{asset('public/backEnd/')}}/assets/css/worldclass-admin.css" rel="stylesheet" type="text/css">
</head>
<body class="wc-admin-shell wc-auth-shell">
    <div class="wc-auth-wrapper wc-admin-login-wrapper">
        <div class="container">
            <div class="row justify-content-center align-items-center g-4">
                <div class="col-lg-6 col-xl-5 d-none d-lg-block">
                    <div class="wc-admin-login-copy">
                        <span class="wc-admin-auth-badge">ERP Admin Portal</span>
                        <h1>Control orders, inventory, customers, and reports from one secure workspace.</h1>
                        <p>Sign in with an authorized admin account to manage day-to-day ecommerce ERP operations.</p>
                        <div class="wc-admin-auth-points">
                            <span><i class="fe-shopping-bag"></i> Orders</span>
                            <span><i class="fe-package"></i> Inventory</span>
                            <span><i class="fe-bar-chart-2"></i> Reports</span>
                            <span><i class="fe-users"></i> Customers</span>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
                    <div class="card wc-auth-card wc-admin-login-card border-0">
                        <div class="card-body p-4 p-sm-5">
                            <div class="text-center mb-4">
                                @if(!empty($generalsetting?->dark_logo))
                                    <img src="{{asset($generalsetting->dark_logo)}}" class="img-fluid mb-3 wc-admin-auth-logo" alt="{{$generalsetting->name}}">
                                @endif
                                <span class="wc-admin-auth-icon"><i class="fe-user-check"></i></span>
                                <h1 class="h5 mb-1">Admin Login</h1>
                                <p class="text-muted mb-0">Enter your credentials to continue.</p>
                            </div>

                            <form method="POST" action="{{route('login')}}">
                                @csrf
                                <div class="mb-3 wc-admin-auth-field">
                                    <label for="email" class="form-label">Email Address</label>
                                    <div class="wc-admin-auth-input">
                                        <i class="fe-mail"></i>
                                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autofocus placeholder="admin@example.com">
                                    </div>
                                    @error('email')
                                        <span class="invalid-feedback d-block" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="mb-3 wc-admin-auth-field">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="wc-admin-auth-input">
                                        <i class="fe-lock"></i>
                                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required placeholder="Enter your password">
                                        <button type="button" class="wc-admin-password-toggle" aria-label="Show password" data-password-toggle>
                                            <i class="fe-eye"></i>
                                        </button>
                                    </div>
                                    @error('password')
                                        <span class="invalid-feedback d-block" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-4 wc-admin-auth-options">
                                    <div class="form-check mb-0">
                                        <input type="checkbox" name="remember" id="checkbox-signin" value="1" class="form-check-input">
                                        <label class="form-check-label" for="checkbox-signin">Remember me</label>
                                    </div>
                                    <span>Authorized users only</span>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 wc-admin-auth-submit">Login</button>
                            </form>

                            <div class="wc-admin-auth-footnote">
                                <i class="fe-shield"></i>
                                This panel is protected by admin permissions and activity controls.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{asset('public/backEnd/')}}/assets/js/vendor.min.js"></script>
    <script src="{{asset('public/backEnd/')}}/assets/js/app.min.js"></script>
    <script>
        document.querySelectorAll("form").forEach(function (form) {
            form.addEventListener("submit", function () {
                if (form.dataset.wcSubmitting === "true") {
                    return;
                }
                form.dataset.wcSubmitting = "true";
                var submit = form.querySelector("button[type='submit']");
                if (submit) {
                    submit.classList.add("wc-btn-loading");
                    submit.disabled = true;
                }
            });
        });

        document.querySelectorAll("[data-password-toggle]").forEach(function (button) {
            button.addEventListener("click", function () {
                var input = document.getElementById("password");
                if (!input) {
                    return;
                }

                var isHidden = input.type === "password";
                input.type = isHidden ? "text" : "password";
                button.setAttribute("aria-label", isHidden ? "Hide password" : "Show password");
                button.innerHTML = isHidden ? '<i class="fe-eye-off"></i>' : '<i class="fe-eye"></i>';
            });
        });
    </script>
</body>
</html>
