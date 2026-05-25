<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>License Server</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f0f5ff;
            --surface: #ffffff;
            --text: #17233c;
            --muted: #5b6d8d;
            --primary: #1447ad;
            --primary-strong: #0b2e75;
            --accent: #f28e1c;
            --border: #d7e4fb;
            --radius-lg: 24px;
            --radius-md: 14px;
            --shadow: 0 20px 45px rgba(12, 42, 105, 0.16);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "IBM Plex Sans", "Segoe UI", sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 6% 4%, #dce9ff 0, transparent 36%),
                radial-gradient(circle at 100% 0, #e7f0ff 0, transparent 34%),
                linear-gradient(180deg, #f8fbff 0, #edf4ff 55%, #eaf2ff 100%);
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .shell {
            width: min(1080px, 100%);
            background: linear-gradient(145deg, #ffffff 0, #f9fbff 100%);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 26px;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(90deg, #f8fbff 0, #f3f8ff 100%);
        }

        .brand {
            font-family: "Space Grotesk", sans-serif;
            font-weight: 700;
            font-size: 21px;
            letter-spacing: 0.01em;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .brand::before {
            content: "";
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent) 0, #f5b262 100%);
            box-shadow: 0 0 0 4px rgba(242, 142, 28, 0.2);
        }

        .status {
            font-size: 13px;
            font-weight: 600;
            color: #1d7f44;
            background: #e9f8ef;
            border: 1px solid #bde9cd;
            border-radius: 999px;
            padding: 6px 10px;
        }

        .hero {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 24px;
            padding: 36px 30px 30px;
        }

        .hero > * {
            min-width: 0;
        }

        .hero h1 {
            margin: 0;
            font-family: "Space Grotesk", sans-serif;
            font-size: clamp(30px, 4.2vw, 48px);
            line-height: 1.08;
            color: var(--primary-strong);
        }

        .hero p {
            margin: 14px 0 0;
            color: var(--muted);
            font-size: 16px;
            line-height: 1.7;
            max-width: 58ch;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .actions {
            margin-top: 24px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            border: 0;
            border-radius: 12px;
            padding: 11px 18px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
        }

        .btn-primary {
            color: #fff;
            background: linear-gradient(135deg, var(--primary) 0, #2d67cb 100%);
            box-shadow: 0 10px 18px rgba(20, 71, 173, 0.25);
        }

        .btn-soft {
            color: var(--primary-strong);
            background: #eff4ff;
            border: 1px solid #d5e2fb;
        }

        .panel {
            border: 1px solid var(--border);
            border-radius: 16px;
            background: #fff;
            padding: 16px;
            box-shadow: 0 10px 20px rgba(12, 42, 105, 0.1);
        }

        .panel h2 {
            margin: 0 0 10px;
            font-family: "Space Grotesk", sans-serif;
            font-size: 18px;
            color: var(--primary-strong);
        }

        .list {
            margin: 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 8px;
        }

        .list li {
            border: 1px solid #e5edff;
            border-radius: 10px;
            padding: 10px;
            font-size: 14px;
            color: #2d3f63;
            background: #fbfdff;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .metrics {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            padding: 0 30px 30px;
        }

        .metric {
            border: 1px solid var(--border);
            border-radius: 14px;
            background: linear-gradient(180deg, #fff 0, #f8fbff 100%);
            padding: 14px;
        }

        .metric strong {
            font-family: "Space Grotesk", sans-serif;
            color: var(--primary-strong);
            display: block;
            font-size: 22px;
            line-height: 1;
        }

        .metric span {
            color: var(--muted);
            font-size: 13px;
        }

        @media (max-width: 900px) {
            .hero {
                grid-template-columns: 1fr;
            }

            .metrics {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <main class="shell">
        <div class="topbar">
            <div class="brand">License Server</div>
            <div class="status">Service Online</div>
        </div>

        <section class="hero">
            <div>
                <h1>Centralized licensing with clean admin controls.</h1>
                <p>
                    Manage license keys, domain activation, and expiration lifecycles from one secure place.
                    This server is configured for production-friendly validation workflows.
                </p>
                <div class="actions">
                    <a href="{{ route('login') }}" class="btn btn-primary">Open Admin Login</a>
                    <a href="{{ route('login.form') }}" class="btn btn-soft">Go To Sign In</a>
                </div>
            </div>
            <aside class="panel">
                <h2>Core Features</h2>
                <ul class="list">
                    <li>Domain-bound key validation</li>
                    <li>Status controls: active, inactive, suspended</li>
                    <li>Key rotation with instant invalidation</li>
                    <li>Expiration tracking and audit visibility</li>
                </ul>
            </aside>
        </section>

        <section class="metrics">
            <article class="metric">
                <strong>99.9%</strong>
                <span>Service Availability Target</span>
            </article>
            <article class="metric">
                <strong>Secure</strong>
                <span>Tokenized Verification Flow</span>
            </article>
            <article class="metric">
                <strong>Fast</strong>
                <span>Low-latency API Validation</span>
            </article>
        </section>
    </main>
</body>
</html>
