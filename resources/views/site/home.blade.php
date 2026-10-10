@php
    $registrationOpen = \App\Support\Registration::isOpen();
    $faq = collect(require base_path('content/faq.php'))->flatten(1)->take(4);
    $app = [
        '@type' => 'SoftwareApplication',
        'name' => config('app.name'),
        'applicationCategory' => 'FinanceApplication',
        'operatingSystem' => 'Web, iOS, Android',
        'inLanguage' => ['fa-IR', 'en'],
        'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IRR'],
        'url' => config('app.url').'/',
    ];
@endphp
<x-site.layout
    title="هزار ریال"
    description="نرم‌افزار حسابداری شخصی فارسی و رایگان: حساب‌ها، درآمد و هزینه، بدهی و طلب، چک و اقساط وام را روی گوشی و کامپیوتر ثبت و گزارش بگیرید."
    path="/"
    :schema="[$app]">

    <section class="mx-auto grid max-w-6xl items-center gap-12 px-4 pt-14 pb-8 lg:grid-cols-2 lg:pt-24">
        <div>
            <p class="mb-4 inline-flex items-center gap-2 rounded-full border border-border bg-card px-4 py-1.5 text-sm text-muted-foreground">
                <span class="size-2 rounded-full bg-income"></span> رایگان، متن‌باز و فارسی
            </p>
            <h1 class="text-4xl leading-[1.35] font-extrabold sm:text-5xl sm:leading-[1.3]">حسابداری شخصی<br><span class="text-brand">ساده، دقیق و همیشه همراه شما</span></h1>
            <p class="mt-6 max-w-xl text-lg leading-9 text-muted-foreground">حساب‌های بانکی، درآمد و هزینه، بدهی و طلب، چک‌ها و اقساط وام را یک‌جا ثبت کنید و هر لحظه ببینید پولتان کجاست؛ با تقویم شمسی و طراحی موبایل‌اول.</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ $registrationOpen ? '/register' : '/login' }}" class="inline-flex h-12 items-center rounded-xl bg-primary px-7 font-bold text-primary-foreground">{{ $registrationOpen ? 'شروع رایگان' : 'ورود به برنامه' }}</a>
                <a href="/features" class="inline-flex h-12 items-center rounded-xl border border-border bg-card px-7 font-semibold">ویژگی‌ها</a>
            </div>
            <ul class="mt-8 flex flex-wrap gap-x-6 gap-y-2 text-sm text-muted-foreground">
                <li>بدون نیاز به اطلاعات بانکی</li><li>خروجی کامل داده‌ها همیشه رایگان</li><li>کد منبع در GitHub</li>
            </ul>
        </div>

        {{-- A drawn preview of the app: no screenshot to load, so the page stays light and the headline paints first. --}}
        <div class="mx-auto w-full max-w-sm" aria-hidden="true">
            <div class="rounded-[2rem] border border-border bg-card p-4 shadow-xl">
                <div class="rounded-2xl bg-muted p-4">
                    <p class="text-xs text-muted-foreground">جمع موجودی</p>
                    <p class="mt-1 text-3xl font-extrabold" dir="ltr">۱۲٬۴۵۰٬۰۰۰ <span class="text-sm font-medium text-muted-foreground">ریال</span></p>
                    <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-xl bg-card p-3"><p class="text-xs text-muted-foreground">درآمد ماه</p><p class="mt-1 font-bold text-emerald-700 dark:text-emerald-400">۸٬۰۰۰٬۰۰۰</p></div>
                        <div class="rounded-xl bg-card p-3"><p class="text-xs text-muted-foreground">هزینه ماه</p><p class="mt-1 font-bold text-expense">۳٬۲۰۰٬۰۰۰</p></div>
                    </div>
                </div>
                <ul class="mt-3 divide-y divide-border/70 text-sm">
                    <li class="flex items-center justify-between py-3"><span class="flex items-center gap-3"><span class="grid size-9 place-items-center rounded-full bg-expense/10 text-expense">−</span>خوراک</span><span class="font-semibold text-expense">۴۵۰٬۰۰۰</span></li>
                    <li class="flex items-center justify-between py-3"><span class="flex items-center gap-3"><span class="grid size-9 place-items-center rounded-full bg-income/10 text-income">+</span>حقوق</span><span class="font-semibold text-emerald-700 dark:text-emerald-400">۸٬۰۰۰٬۰۰۰</span></li>
                    <li class="flex items-center justify-between py-3"><span class="flex items-center gap-3"><span class="grid size-9 place-items-center rounded-full bg-warning/15 text-warning">!</span>چک سررسید ۱۵ آذر</span><span class="font-semibold">۵٬۰۰۰٬۰۰۰</span></li>
                </ul>
            </div>
        </div>
    </section>

    <section class="mx-auto mt-20 max-w-6xl px-4">
        <h2 class="text-center text-3xl leading-10 font-extrabold">هر چه برای مدیریت پول لازم دارید</h2>
        <p class="mx-auto mt-3 max-w-2xl text-center leading-8 text-muted-foreground">از ثبت یک قهوه تا اقساط وام مسکن؛ همه در یک برنامه، با همان حسابی که روی گوشی و کامپیوتر دارید.</p>
        <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($features as $slug => $feature)
                <a href="/features/{{ $slug }}" class="group rounded-2xl border border-border bg-card p-6 transition-shadow hover:shadow-md">
                    <span class="grid size-12 place-items-center rounded-xl bg-brand/10 text-brand"><x-site.icon :path="$feature['icon']" /></span>
                    <h3 class="mt-4 text-lg font-bold group-hover:text-brand">{{ $feature['title'] }}</h3>
                    <p class="mt-2 text-sm leading-7 text-muted-foreground">{{ $feature['summary'] }}</p>
                </a>
            @endforeach
        </div>
        <p class="mt-6 text-center text-sm text-muted-foreground">در راه: @foreach ($planned as [$name]) {{ $name }}{{ $loop->last ? '' : '، ' }}@endforeach. <a href="/features#planned" class="text-brand underline underline-offset-4">جزئیات</a></p>
    </section>

    <section class="mx-auto mt-24 grid max-w-6xl items-center gap-10 px-4 lg:grid-cols-2">
        <div>
            <h2 class="text-3xl leading-10 font-extrabold">اطلاعات شما مال شماست</h2>
            <p class="mt-4 leading-9 text-muted-foreground">رمز اینترنت‌بانک، CVV2 یا شماره‌ی کامل کارت هرگز خواسته نمی‌شود. برنامه به بانک وصل نمی‌شود، داده‌ها رمزنگاری و فقط برای خود شما قابل دیدن است و هر زمان خروجی کامل می‌گیرید یا حسابتان را حذف می‌کنید.</p>
            <a href="/security" class="mt-5 inline-block font-semibold text-brand underline underline-offset-4">امنیت اطلاعات در هزار ریال</a>
        </div>
        <ul class="grid gap-3 text-sm sm:grid-cols-2">
            @foreach (['ورود دومرحله‌ای (Authenticator)', 'رمز عبور با Argon2id', 'پشتیبان‌ها رمزنگاری‌شده', 'جداسازی کامل داده‌ی کاربران', 'خروج خودکار پس از بی‌فعالیتی', 'کد منبع باز و قابل بررسی'] as $item)
                <li class="flex items-center gap-3 rounded-xl border border-border bg-card px-4 py-3"><span class="grid size-6 place-items-center rounded-full bg-income/15 text-income">✓</span>{{ $item }}</li>
            @endforeach
        </ul>
    </section>

    @if ($posts->isNotEmpty())
        <section class="mx-auto mt-24 max-w-6xl px-4">
            <div class="flex items-end justify-between gap-4">
                <h2 class="text-3xl leading-10 font-extrabold">از مجله</h2>
                <a href="/blog" class="text-sm font-semibold text-brand">همه‌ی مقاله‌ها ←</a>
            </div>
            <div class="mt-8 grid gap-5 md:grid-cols-3">
                @foreach ($posts as $post)
                    <x-site.article-card :article="$post" />
                @endforeach
            </div>
        </section>
    @endif

    <section class="mx-auto mt-24 max-w-3xl px-4">
        <h2 class="text-center text-3xl leading-10 font-extrabold">سؤالات متداول</h2>
        <div class="mt-8 divide-y divide-border rounded-2xl border border-border bg-card">
            @foreach ($faq as [$question, $answer])
                <details class="group p-5"><summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold [&::-webkit-details-marker]:hidden">{{ $question }}<span class="text-xl text-muted-foreground transition-transform group-open:rotate-45" aria-hidden="true">+</span></summary><p class="mt-3 leading-8 text-muted-foreground">{{ $answer }}</p></details>
            @endforeach
        </div>
        <p class="mt-5 text-center text-sm"><a href="/faq" class="font-semibold text-brand">همه‌ی سؤالات</a></p>
    </section>

    <x-site.cta />
</x-site.layout>
