<!doctype html>
<html lang="fa" dir="rtl" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f7f8fb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="هزار ریال">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="/icons/icon.svg" type="image/svg+xml">
    <title>{{ $title ?? 'هزار ریال' }}</title>
    <script>
        if (localStorage.theme === 'dark' || (!localStorage.theme && matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased">
    <div class="mx-auto min-h-screen max-w-5xl safe-top safe-bottom px-4 py-4 sm:px-6">
        <header class="sticky top-0 z-30 -mx-4 mb-4 px-4 py-3 glass sm:rounded-3xl">
            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('dashboard') }}" class="text-xl font-black tracking-normal">هزار ریال</a>
                @auth
                    <form method="post" action="{{ route('logout') }}">@csrf
                        <button class="tap rounded-2xl px-4 text-sm font-semibold text-slate-600 dark:text-slate-200">خروج</button>
                    </form>
                @endauth
            </div>
        </header>

        @if (session('status'))
            <div class="mb-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
        @endif

        <main>{{ $slot ?? '' }}@yield('content')</main>
    </div>

    @auth
        <button aria-label="افزودن تراکنش" onclick="document.getElementById('quickAdd').showModal()" class="fixed bottom-24 left-1/2 z-40 flex h-16 w-16 -translate-x-1/2 items-center justify-center rounded-full bg-slate-950 text-3xl font-light text-white shadow-2xl dark:bg-white dark:text-slate-950">+</button>
        <dialog id="quickAdd" class="w-[min(92vw,420px)] rounded-3xl border-0 bg-white p-0 text-right shadow-2xl backdrop:bg-slate-950/35 dark:bg-slate-900">
            <div class="p-4">
                <div class="mb-3 flex items-center justify-between"><strong>ثبت سریع</strong><button onclick="quickAdd.close()" class="tap rounded-full px-3">×</button></div>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ([['درآمد','income'],['هزینه','expense'],['انتقال','transfer_out'],['طلب','receivable_collection'],['بدهی','debt_payment'],['قسط','loan_installment_payment'],['چک','check_payment']] as [$label,$type])
                        <a class="tap rounded-2xl bg-slate-100 px-4 py-3 text-center font-semibold dark:bg-slate-800" href="{{ route('transactions.create', ['type' => $type]) }}">{{ $label }}</a>
                    @endforeach
                </div>
            </div>
        </dialog>
        <nav class="fixed inset-x-0 bottom-0 z-30 mx-auto max-w-5xl px-3 pb-[max(.75rem,env(safe-area-inset-bottom))]">
            <div class="glass grid grid-cols-5 rounded-3xl px-2 py-2 text-center text-xs font-bold text-slate-600 dark:text-slate-200">
                <a class="tap rounded-2xl py-2" href="{{ route('dashboard') }}">خانه</a>
                <a class="tap rounded-2xl py-2" href="{{ route('transactions.index') }}">تراکنش‌ها</a>
                <a class="tap rounded-2xl py-2" href="{{ route('reports.index') }}">گزارش‌ها</a>
                <a class="tap rounded-2xl py-2" href="{{ route('accounts.index') }}">حساب‌ها</a>
                <a class="tap rounded-2xl py-2" href="{{ route('settings.index') }}">بیشتر</a>
            </div>
        </nav>
    @endauth
    @livewireScripts
</body>
</html>
