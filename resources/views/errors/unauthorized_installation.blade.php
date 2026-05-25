<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unauthorized Installation</title>
    <style>
        :root {
            --bg: #0b1220;
            --card: rgba(255,255,255,0.06);
            --border: rgba(255,255,255,0.12);
            --text: rgba(255,255,255,0.92);
            --muted: rgba(255,255,255,0.70);
            --accent: #667eea;
            --accent2: #764ba2;
        }

        html, body { height: 100%; }
        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji";
            background: radial-gradient(1200px 600px at 20% 10%, rgba(102,126,234,0.35), transparent 55%),
                        radial-gradient(900px 600px at 80% 20%, rgba(118,75,162,0.30), transparent 60%),
                        var(--bg);
            color: var(--text);
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .card {
            width: 100%;
            max-width: 720px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 30px 80px rgba(0,0,0,0.45);
            backdrop-filter: blur(10px);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: rgba(255,255,255,0.04);
            font-size: 12px;
            color: var(--muted);
            letter-spacing: 0.02em;
        }

        .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            box-shadow: 0 0 0 4px rgba(102,126,234,0.18);
        }

        h1 {
            margin: 14px 0 8px 0;
            font-size: 28px;
            line-height: 1.2;
        }

        p {
            margin: 8px 0;
            color: var(--muted);
            line-height: 1.6;
        }

        .domain {
            margin-top: 14px;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px dashed rgba(255,255,255,0.25);
            background: rgba(0,0,0,0.20);
            color: var(--text);
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 13px;
            overflow-x: auto;
        }

        .footer {
            margin-top: 18px;
            font-size: 12px;
            color: rgba(255,255,255,0.55);
        }

        .footer code {
            color: rgba(255,255,255,0.75);
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="badge"><span class="dot"></span> License Enforcement</div>
        <h1>Unauthorized Installation</h1>

        <p>
            This installation is not authorized to run on the current domain.
            If you believe this is an error, please contact the software vendor / license administrator.
        </p>

        <div class="domain">
            Domain: {{ $domain ?? 'unknown' }}
        </div>

        <div class="footer">
            <p>HTTP <code>403</code> · Error Code <code>LIC-403</code></p>
        </div>
    </div>
</body>
</html>
