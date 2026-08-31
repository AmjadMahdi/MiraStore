<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session expired — {{ config('app.name') }}</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, sans-serif; background: #f9fafb; color: #1f2937; margin: 0; }
        .wrap { min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1.5rem; text-align: center; }
        .code { font-size: 3rem; font-weight: 700; color: #e11d48; margin: 0; }
        .msg { margin: 0.5rem 0 1.5rem; color: #6b7280; max-width: 24rem; }
        button { background: #e11d48; color: #fff; border: none; padding: 0.6rem 1.25rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 600; cursor: pointer; }
    </style>
</head>
<body>
    <div class="wrap">
        <p class="code">419</p>
        <p class="msg">Your session expired. Please try again.</p>
        <button onclick="window.location.reload()">Reload page</button>
    </div>
</body>
</html>
