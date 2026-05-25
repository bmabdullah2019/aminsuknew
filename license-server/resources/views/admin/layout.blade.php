<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Panel') - License Server</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --ls-bg: #eef4ff;
            --ls-surface: #ffffff;
            --ls-primary: #1648ac;
            --ls-primary-dark: #0c2f78;
            --ls-border: #d6e3fb;
            --ls-text: #1a2743;
            --ls-muted: #60708e;
            --ls-shadow: 0 18px 36px rgba(12, 44, 108, 0.12);
        }

        body {
            margin: 0;
            font-family: "IBM Plex Sans", "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at 0% 0%, #dce9ff 0, transparent 40%),
                radial-gradient(circle at 100% 0%, #e6f0ff 0, transparent 35%),
                var(--ls-bg);
            color: var(--ls-text);
        }

        .admin-nav {
            border-bottom: 1px solid var(--ls-border);
            background: linear-gradient(90deg, #0f327f 0, #1648ac 52%, #2f69ce 100%);
            box-shadow: 0 12px 28px rgba(8, 29, 78, 0.28);
        }

        .admin-nav .navbar-brand {
            font-family: "Space Grotesk", sans-serif;
            font-size: 1.1rem;
            letter-spacing: 0.01em;
        }

        .admin-shell {
            padding: 24px 0 30px;
        }

        .admin-shell .card,
        .admin-shell .table,
        .admin-shell .alert {
            border-color: var(--ls-border);
        }

        .admin-shell .card {
            border-radius: 16px;
            box-shadow: var(--ls-shadow);
            background: linear-gradient(180deg, #ffffff 0, #f9fbff 100%);
        }

        .admin-shell .card-header {
            border-bottom: 1px solid var(--ls-border);
            background: #f6f9ff;
            font-weight: 600;
        }

        .admin-shell .btn-primary {
            border: 0;
            background: linear-gradient(135deg, var(--ls-primary) 0, #2d67cb 100%);
            box-shadow: 0 10px 18px rgba(22, 72, 172, 0.24);
        }

        .admin-shell .btn-outline-primary {
            color: var(--ls-primary-dark);
            border-color: #b9cdf2;
        }

        .admin-shell .table thead th {
            background: #f3f8ff;
            color: var(--ls-primary-dark);
            border-bottom-color: var(--ls-border);
            font-weight: 700;
            white-space: nowrap;
        }

        .admin-shell .table td {
            color: var(--ls-text);
            vertical-align: middle;
        }

        .admin-shell .form-control,
        .admin-shell .form-select {
            border-color: #c8d8f4;
            border-radius: 10px;
            min-height: 42px;
        }

        .admin-shell .form-control:focus,
        .admin-shell .form-select:focus {
            border-color: var(--ls-primary);
            box-shadow: 0 0 0 0.2rem rgba(22, 72, 172, 0.18);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark admin-nav">
        <div class="container-fluid px-3 px-md-4">
            <span class="navbar-brand mb-0">License Server Admin</span>
            <form action="{{ route('logout') }}" method="POST" class="d-flex">
                @csrf
                <button type="submit" class="btn btn-sm btn-light text-primary-emphasis fw-semibold">Logout</button>
            </form>
        </div>
    </nav>

    <div class="admin-shell">
        <div class="container-fluid px-3 px-md-4">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
