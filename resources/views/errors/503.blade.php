<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحت الصيانة — {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', ui-sans-serif, system-ui, sans-serif; background: #f9fafb; color: #1f2937; margin: 0; }
        .wrap { min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1.5rem; text-align: center; }
        .code { font-size: 3rem; font-weight: 700; color: #4f46e5; margin: 0; }
        .msg { margin: 0.5rem 0 1.5rem; color: #6b7280; max-width: 24rem; }
    </style>
</head>
<body>
    <div class="wrap">
        <p class="code">503</p>
        <p class="msg">{{ config('app.name') }} متوقف مؤقتاً للصيانة. سنعود قريباً.</p>
    </div>
</body>
</html>
