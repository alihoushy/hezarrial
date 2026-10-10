@props([
    'title',
    'description',
    'path' => null,          {{-- canonical path, e.g. "/features"; defaults to the current one --}}
    'image' => null,
    'type' => 'website',
    'noindex' => false,
    'schema' => [],          {{-- extra JSON-LD objects for this page --}}
    'breadcrumbs' => [],     {{-- [['label' => 'خانه', 'url' => '/'], ...], the last one is the current page --}}
    'published' => null,
    'modified' => null,
])
@php
    $base = rtrim(config('app.url'), '/');
    $canonical = $base.($path ?? '/'.ltrim(request()->path(), '/'));
    $canonical = rtrim($canonical, '/') ?: $base;
    $fullTitle = $title === config('app.name') ? $title : $title.' | '.config('app.name');
    $ogImage = $image ? (str_starts_with($image, 'http') ? $image : $base.$image) : $base.'/images/og-default.png';
    $registrationOpen = \App\Support\Registration::isOpen();
    $nav = [
        ['/features', 'ویژگی‌ها'],
        ['/pricing', 'قیمت‌ها'],
        ['/blog', 'مجله'],
        ['/help', 'راهنما'],
        ['/learn', 'آموزش'],
        ['/download', 'دانلود'],
    ];
    $graph = [
        ['@type' => 'Organization', '@id' => $base.'/#organization', 'name' => config('app.name'), 'url' => $base.'/', 'logo' => $base.'/icons/icon-512.png', 'sameAs' => ['https://github.com/alihoushy/hezarrial']],
        ['@type' => 'WebSite', '@id' => $base.'/#website', 'url' => $base.'/', 'name' => config('app.name'), 'inLanguage' => 'fa-IR', 'publisher' => ['@id' => $base.'/#organization']],
    ];
    if (count($breadcrumbs) > 1) {
        $graph[] = ['@type' => 'BreadcrumbList', 'itemListElement' => collect($breadcrumbs)->values()->map(fn ($crumb, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $crumb['label'], 'item' => $base.$crumb['url']])->all()];
    }
    $jsonLd = ['@context' => 'https://schema.org', '@graph' => [...$graph, ...$schema]];
@endphp
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $canonical }}">
    @if ($noindex)
        <meta name="robots" content="noindex, follow">
    @else
        <meta name="robots" content="index, follow, max-image-preview:large">
    @endif
    <meta name="theme-color" content="#f5f6f8">
    <meta name="color-scheme" content="light dark">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:locale" content="fa_IR">
    <meta property="og:type" content="{{ $type }}">
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    @if ($published)
        <meta property="article:published_time" content="{{ $published }}">
    @endif
    @if ($modified)
        <meta property="article:modified_time" content="{{ $modified }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $fullTitle }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="/icons/icon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <link rel="alternate" type="application/rss+xml" title="{{ config('app.name') }}" href="{{ $base }}/blog/feed.xml">
    <script nonce="{{ Vite::cspNonce() }}">
        // Follows the visitor's system theme; there is nothing to flash because the page is static.
        if (matchMedia('(prefers-color-scheme: dark)').matches) document.documentElement.classList.add('dark');
        // Offline page only (see public/sw.js); it never stores pages or data.
        if ('serviceWorker' in navigator) addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
    </script>
    @vite('resources/css/site.css')
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    {{ $head ?? '' }}
</head>
<body class="min-h-dvh bg-background font-sans text-foreground antialiased">
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:start-3 focus:top-3 focus:z-50 focus:rounded-lg focus:bg-card focus:px-4 focus:py-2 focus:shadow-lg">پرش به محتوا</a>

<header class="sticky top-0 z-40 border-b border-border/70 bg-background/85 backdrop-blur-xl">
    <div class="mx-auto flex h-16 max-w-6xl items-center gap-4 px-4">
        <a href="/" class="flex items-center gap-2.5 font-extrabold" aria-label="{{ config('app.name') }}">
            <img src="/icons/icon.svg" alt="" width="36" height="36" class="size-9 rounded-xl ring-1 ring-foreground/10">
            <span class="text-lg">{{ config('app.name') }}</span>
        </a>

        <nav class="ms-6 hidden items-center gap-1 text-sm font-medium md:flex" aria-label="منوی اصلی">
            @foreach ($nav as [$url, $label])
                <a href="{{ $url }}" class="rounded-lg px-3 py-2 text-foreground/80 transition-colors hover:bg-muted hover:text-foreground @if (request()->is(ltrim($url, '/').'*')) bg-muted text-foreground @endif">{{ $label }}</a>
            @endforeach
        </nav>

        <div class="ms-auto flex items-center gap-2">
            {{-- Public pages run without a session (no cookie, no database row per visitor), so the
                 header cannot know who is signed in: /login sends signed-in users straight to /app. --}}
            <a href="/login" class="hidden h-10 items-center rounded-xl px-4 text-sm font-semibold hover:bg-muted sm:inline-flex">ورود</a>
            @if ($registrationOpen)
                <a href="/register" class="inline-flex h-10 items-center rounded-xl bg-primary px-4 text-sm font-bold text-primary-foreground">شروع رایگان</a>
            @else
                <a href="/login" class="inline-flex h-10 items-center rounded-xl bg-primary px-4 text-sm font-bold text-primary-foreground sm:hidden">ورود</a>
            @endif

            <details class="group relative md:hidden">
                <summary class="flex size-10 cursor-pointer list-none items-center justify-center rounded-xl hover:bg-muted [&::-webkit-details-marker]:hidden" aria-label="منو">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </summary>
                <div class="absolute end-0 top-12 w-60 rounded-2xl border border-border bg-card p-2 shadow-xl">
                    @foreach ($nav as [$url, $label])
                        <a href="{{ $url }}" class="block rounded-xl px-4 py-3 text-sm font-medium hover:bg-muted">{{ $label }}</a>
                    @endforeach
                    <a href="/login" class="block rounded-xl px-4 py-3 text-sm font-medium hover:bg-muted">ورود</a>
                </div>
            </details>
        </div>
    </div>
</header>

<main id="main">
    @if (count($breadcrumbs) > 1)
        <x-site.breadcrumbs :items="$breadcrumbs" />
    @endif
    {{ $slot }}
</main>

<footer class="mt-24 border-t border-border bg-card/60">
    <div class="mx-auto grid max-w-6xl gap-10 px-4 py-14 sm:grid-cols-2 lg:grid-cols-4">
        <div class="flex flex-col gap-3">
            <a href="/" class="flex items-center gap-2.5 font-extrabold">
                <img src="/icons/icon.svg" alt="" width="32" height="32" class="size-8 rounded-lg" loading="lazy">
                {{ config('app.name') }}
            </a>
            <p class="text-sm leading-7 text-muted-foreground">حسابداری شخصی فارسی، موبایل‌اول و متن‌باز. حساب‌ها، چک‌ها، اقساط و بدهی‌هایتان را یک‌جا ببینید.</p>
        </div>
        <div>
            <p class="mb-3 text-sm font-bold">محصول</p>
            <ul class="flex flex-col gap-2 text-sm text-muted-foreground">
                <li><a class="hover:text-foreground" href="/features">ویژگی‌ها</a></li>
                <li><a class="hover:text-foreground" href="/pricing">قیمت‌ها</a></li>
                <li><a class="hover:text-foreground" href="/download">دانلود و نصب</a></li>
                <li><a class="hover:text-foreground" href="/changelog">تغییرات نسخه‌ها</a></li>
                <li><a class="hover:text-foreground" href="/tools/loan-calculator">ماشین‌حساب اقساط وام</a></li>
            </ul>
        </div>
        <div>
            <p class="mb-3 text-sm font-bold">منابع</p>
            <ul class="flex flex-col gap-2 text-sm text-muted-foreground">
                <li><a class="hover:text-foreground" href="/blog">مجله</a></li>
                <li><a class="hover:text-foreground" href="/help">راهنمای استفاده</a></li>
                <li><a class="hover:text-foreground" href="/learn">آموزش‌ها</a></li>
                <li><a class="hover:text-foreground" href="/faq">سؤالات متداول</a></li>
            </ul>
        </div>
        <div>
            <p class="mb-3 text-sm font-bold">شرکت</p>
            <ul class="flex flex-col gap-2 text-sm text-muted-foreground">
                <li><a class="hover:text-foreground" href="/about">درباره‌ی ما</a></li>
                <li><a class="hover:text-foreground" href="/contact">تماس با ما</a></li>
                <li><a class="hover:text-foreground" href="/security">امنیت اطلاعات</a></li>
                <li><a class="hover:text-foreground" href="/privacy">حریم خصوصی</a></li>
                <li><a class="hover:text-foreground" href="/terms">شرایط استفاده</a></li>
                <li><a class="hover:text-foreground" href="https://github.com/alihoushy/hezarrial" rel="noopener">کد منبع در GitHub</a></li>
            </ul>
        </div>
    </div>
    <div class="border-t border-border/70">
        <p class="mx-auto max-w-6xl px-4 py-5 text-xs text-muted-foreground">© {{ \App\Support\Digits::persian(\Morilog\Jalali\Jalalian::now()->format('Y')) }} {{ config('app.name') }}. همه‌ی حقوق محفوظ است.</p>
    </div>
</footer>
</body>
</html>
