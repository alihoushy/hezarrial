@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-md">
    <div class="glass rounded-3xl p-6">
        <h1 class="mb-2 text-2xl font-black">ورود امن</h1>
        <p class="mb-6 text-sm text-slate-500">برای مدیریت مالی شخصی وارد شوید.</p>
        <form method="post" action="{{ route('login') }}" class="space-y-4">@csrf
            <label class="block text-sm font-bold">ایمیل یا موبایل<input name="login" value="{{ old('login') }}" class="mt-2 tap w-full rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800" required autofocus></label>
            <label class="block text-sm font-bold">رمز عبور<input name="password" type="password" class="mt-2 tap w-full rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800" required></label>
            @error('login')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            <button class="tap w-full rounded-2xl bg-slate-950 px-4 font-black text-white dark:bg-white dark:text-slate-950">ورود</button>
        </form>
    </div>
</section>
@endsection
