<!doctype html>
@php($theme = $page['props']['settings']['theme'] ?? 'system')
@php($locale = $page['props']['locale'] ?? ['code' => 'fa', 'dir' => 'rtl'])
<html lang="{{ $locale['code'] }}" dir="{{ $locale['dir'] }}" @class(['dark' => $theme === 'dark'])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#f5f6f8">
    <meta name="color-scheme" content="light dark">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="هزار ریال">
    <meta name="format-detection" content="telephone=no">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="/icons/icon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/icons/icon.svg">

    {{-- Apply the saved theme before first paint so a dark user never sees a light flash. --}}
    <script>
        (function () {
            var theme = @json($theme);
            var dark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            var root = document.documentElement;
            root.classList.toggle('dark', dark);
            var meta = document.querySelector('meta[name="theme-color"]');
            if (meta) meta.setAttribute('content', dark ? '#0f1012' : '#f5f6f8');
        })();
    </script>

    @routes
    @viteReactRefresh
    @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
    <x-inertia::head>
        <title data-inertia>هزار ریال</title>
    </x-inertia::head>
</head>
<body>
    <x-inertia::app />
</body>
</html>
