<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <title>آفلاین هستید | {{ config('app.name') }}</title>
    {{-- Self-contained on purpose: the service worker serves this page when nothing else can load. --}}
    <style>
        :root { color-scheme: light dark; }
        body { margin: 0; min-height: 100dvh; display: grid; place-items: center; padding: 24px; box-sizing: border-box; font-family: Tahoma, system-ui, sans-serif; background: #f7f8fb; color: #111827; text-align: center; }
        @media (prefers-color-scheme: dark) { body { background: #0f1012; color: #f3f4f6; } p { color: #9ca3af; } }
        main { max-width: 24rem; }
        h1 { font-size: 1.5rem; margin: 1.25rem 0 .5rem; }
        p { line-height: 2; color: #6b7280; margin: 0 0 1.5rem; }
        button { height: 3rem; padding: 0 2rem; border: 0; border-radius: .9rem; background: #111827; color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
        @media (prefers-color-scheme: dark) { button { background: #f3f4f6; color: #111827; } }
        svg { width: 4rem; height: 4rem; color: #0f766e; }
    </style>
</head>
<body>
<main>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 8.8a15 15 0 0120 0M5 12.5a10 10 0 0114 0M8.5 16a5 5 0 017 0M12 20h.01M3 3l18 18"/></svg>
    <h1>به اینترنت وصل نیستید</h1>
    <p>هزار ریال برای نمایش اطلاعات مالی‌تان به اینترنت نیاز دارد. اتصال را بررسی کنید و دوباره تلاش کنید.</p>
    <button type="button" onclick="location.reload()">تلاش دوباره</button>
</main>
</body>
</html>
