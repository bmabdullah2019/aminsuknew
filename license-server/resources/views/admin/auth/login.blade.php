<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - License Server</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --ls-primary: #1548ad;
            --ls-primary-dark: #0b2f78;
            --ls-border: #d5e3fb;
            --ls-text: #1a2743;
        }

        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 22px;
            font-family: "IBM Plex Sans", "Segoe UI", sans-serif;
            color: var(--ls-text);
            background:
                radial-gradient(circle at 8% 6%, #dce9ff 0, transparent 38%),
                radial-gradient(circle at 100% 0, #e7f0ff 0, transparent 35%),
                linear-gradient(180deg, #f7fbff 0, #edf4ff 58%, #eaf2ff 100%);
        }

        .login-card {
            width: min(440px, 100%);
            border: 1px solid var(--ls-border);
            border-radius: 20px;
            background: linear-gradient(180deg, #ffffff 0, #f9fbff 100%);
            box-shadow: 0 24px 44px rgba(12, 43, 108, 0.16);
            overflow: hidden;
        }

        .login-card .card-body {
            padding: 28px;
        }

        .title {
            margin: 0 0 6px;
            font-family: "Space Grotesk", sans-serif;
            font-size: 28px;
            color: var(--ls-primary-dark);
        }

        .subtitle {
            margin: 0 0 20px;
            color: #627292;
            font-size: 14px;
        }

        .form-label {
            color: #20355f;
            font-weight: 600;
        }

        .form-control {
            border-color: #c9daf5;
            border-radius: 11px;
            min-height: 44px;
        }

        .form-control:focus {
            border-color: var(--ls-primary);
            box-shadow: 0 0 0 0.2rem rgba(21, 72, 173, 0.18);
        }

        .btn-login {
            border: 0;
            border-radius: 11px;
            min-height: 44px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--ls-primary) 0, #2d68cc 100%);
            box-shadow: 0 10px 18px rgba(21, 72, 173, 0.24);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="card-body">
            <h1 class="title">Admin Login</h1>
            <p class="subtitle">Sign in to manage license keys and domain access.</p>

            @if($errors->any())
                <div class="alert alert-danger">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" name="email" id="email" class="form-control" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" name="password" id="password" class="form-control" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-login">Login</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
