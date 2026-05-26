@extends('layouts.app')
@section('content')
<section class="space-y-4">
    <div class="flex items-center justify-between"><h1 class="text-2xl font-black">حساب‌ها</h1><a class="tap rounded-2xl bg-slate-950 px-4 py-3 text-sm font-bold text-white" href="{{ route('accounts.create') }}">حساب جدید</a></div>
    <div class="grid gap-3 sm:grid-cols-2">
        @forelse ($accounts as $account)
            <a href="{{ route('accounts.show', $account) }}" class="rounded-3xl bg-white/85 p-4 shadow-sm dark:bg-slate-900">
                <div class="flex items-center justify-between"><strong>{{ $account->name }}</strong><span class="text-xs text-slate-500">{{ $account->type->value }}</span></div>
                <p class="num mt-4 text-2xl font-black">{{ number_format($account->current_balance) }}</p>
                <p class="text-xs text-slate-400">ریال</p>
            </a>
        @empty
            <div class="rounded-3xl bg-white/85 p-5 text-sm text-slate-500">هنوز حسابی ثبت نشده است.</div>
        @endforelse
    </div>
</section>
@endsection
