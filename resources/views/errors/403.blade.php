<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access denied — {{ config('app.name') }}</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, sans-serif; background: #f9fafb; color: #1f2937; margin: 0; }
        .wrap { min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1.5rem; text-align: center; }
        .code { font-size: 3rem; font-weight: 700; color: #e11d48; margin: 0; }
        .msg { margin: 0.5rem 0 1.5rem; color: #6b7280; max-width: 24rem; }
        a { color: #e11d48; font-weight: 600; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="wrap">
        <p class="code">403</p>
        <p class="msg">{{ $exception->getMessage() ?: "You don't have permission to view this page." }}</p>
        <a href="{{ url('/') }}">Back to MiraStore</a>
    </div>
</body>
</html>
