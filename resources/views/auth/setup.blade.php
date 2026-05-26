@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-md">
    <div class="glass rounded-3xl p-6">
        <h1 class="mb-2 text-2xl font-black">راه‌اندازی هزار ریال</h1>
        <p class="mb-6 text-sm text-slate-500">ثبت‌نام فقط برای اولین کاربر فعال است.</p>
        <form method="post" action="{{ route('setup') }}" class="space-y-4">@csrf
            <label class="block text-sm font-bold">نام<input name="name" value="{{ old('name') }}" class="mt-2 tap w-full rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800" required></label>
            <label class="block text-sm font-bold">ایمیل<input name="email" type="email" value="{{ old('email') }}" class="mt-2 tap w-full rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800"></label>
            <label class="block text-sm font-bold">موبایل<input name="mobile" value="{{ old('mobile') }}" class="mt-2 tap w-full rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800"></label>
            <label class="block text-sm font-bold">رمز عبور<input name="password" type="password" class="mt-2 tap w-full rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800" required></label>
            <label class="block text-sm font-bold">تکرار رمز عبور<input name="password_confirmation" type="password" class="mt-2 tap w-full rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800" required></label>
            @if ($errors->any())<p class="text-sm text-red-600">{{ $errors->first() }}</p>@endif
            <button class="tap w-full rounded-2xl bg-slate-950 px-4 font-black text-white dark:bg-white dark:text-slate-950">ساخت حساب خصوصی</button>
        </form>
    </div>
</section>
@endsection
