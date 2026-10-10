@props(['title' => 'امروز مدیریت مالی‌تان را ساده کنید', 'text' => 'رایگان شروع کنید؛ حساب بسازید و اولین تراکنش را در کمتر از یک دقیقه ثبت کنید.'])
@php($registrationOpen = \App\Support\Registration::isOpen())
<section class="mx-auto mt-20 max-w-6xl px-4">
    <div class="rounded-3xl bg-brand px-6 py-12 text-center text-brand-foreground sm:px-12">
        <h2 class="text-2xl leading-10 font-extrabold sm:text-3xl">{{ $title }}</h2>
        <p class="mx-auto mt-3 max-w-xl leading-8 opacity-90">{{ $text }}</p>
        <div class="mt-7 flex flex-wrap justify-center gap-3">
            <a href="{{ $registrationOpen ? '/register' : '/login' }}" class="inline-flex h-12 items-center rounded-xl bg-white px-6 font-bold text-zinc-900">{{ $registrationOpen ? 'ساخت حساب رایگان' : 'ورود به برنامه' }}</a>
            <a href="/features" class="inline-flex h-12 items-center rounded-xl border border-white/40 px-6 font-semibold">دیدن ویژگی‌ها</a>
        </div>
    </div>
</section>
