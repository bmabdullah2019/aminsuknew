<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Locked | {{$generalsetting->name ?? 'Admin Panel'}}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="shortcut icon" href="{{asset('public/backEnd/')}}/assets/images/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="{{asset('public/backEnd/')}}/assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="{{asset('public/backEnd/')}}/assets/css/app.min.css" rel="stylesheet" type="text/css" id="app-style">
    <link href="{{asset('public/backEnd/')}}/assets/css/icons.min.css" rel="stylesheet" type="text/css">
    <link href="{{asset('public/backEnd/')}}/assets/css/worldclass-admin.css" rel="stylesheet" type="text/css">
</head>
<body class="wc-admin-shell wc-auth-shell">
    <div class="wc-auth-wrapper">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
                    <div class="card wc-auth-card border-0">
                        <div class="card-body p-4 p-sm-5">
                            <div class="text-center mb-4">
                                <div class="wc-lock-icon mb-3">
                                    <i class="fe-lock"></i>
                                </div>
                                <h1 class="h5 mb-1">Session Locked</h1>
                                <p class="text-muted mb-0">Enter your password to unlock the panel.</p>
                            </div>

                            <form method="POST" action="{{route('unlocked')}}">
                                @csrf
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <input type="password" id="password" class="form-control @error('password') is-invalid @enderror" name="password" value="{{ old('password') }}" required autocomplete="password" autofocus placeholder="Enter your password">
                                        <span class="input-group-text"><i class="fe-eye-off"></i></span>
                                    </div>
                                    @error('password')
                                        <span class="invalid-feedback d-block" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="form-check mb-4">
                                    <input type="checkbox" class="form-check-input" id="checkbox-signin" value="1" name="remember" checked>
                                    <label class="form-check-label" for="checkbox-signin">Remember me</label>
                                </div>

                                <button class="btn btn-primary w-100" type="submit">Unlock</button>
                            </form>
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
    </script>
</body>
</html>
