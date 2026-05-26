@extends('layouts.app')
@section('content')
<section class="space-y-4">
    <div class="glass rounded-3xl p-5">
        <p class="text-sm text-slate-500">سلام {{ auth()->user()->name }}</p>
        <h1 class="mt-1 text-2xl font-black">نمای کلی مالی امروز</h1>
        <div class="mt-5 grid grid-cols-2 gap-3">
            <x-stat label="درآمد ماه" :value="$dashboard['income']" />
            <x-stat label="هزینه ماه" :value="$dashboard['expense']" />
            <x-stat label="خالص ماه" :value="$dashboard['net']" />
            <x-stat label="مانده کل" :value="$dashboard['total_balance']" />
        </div>
    </div>
    <div class="flex gap-3 overflow-x-auto pb-2">
        @forelse ($dashboard['accounts'] as $account)
            <a href="{{ route('accounts.show', $account) }}" class="min-w-52 rounded-3xl bg-white/85 p-4 shadow-sm dark:bg-slate-900">
                <p class="text-sm text-slate-500">{{ $account->name }}</p>
                <p class="num mt-3 text-xl font-black">{{ number_format($account->current_balance) }}</p>
                <p class="text-xs text-slate-400">ریال</p>
            </a>
        @empty
            <a href="{{ route('accounts.create') }}" class="tap rounded-3xl bg-white/85 p-4 font-bold">اولین حساب را بسازید</a>
        @endforelse
    </div>
    <div class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-3xl bg-white/85 p-4 dark:bg-slate-900">
            <h2 class="mb-3 font-black">تراکنش‌های اخیر</h2>
            @include('transactions.partials.list', ['transactions' => $dashboard['recent_transactions']])
        </div>
        <div class="rounded-3xl bg-white/85 p-4 dark:bg-slate-900">
            <h2 class="mb-3 font-black">هشدارهای نزدیک</h2>
            @foreach (['reminders' => 'یادآوری', 'installments' => 'قسط', 'checks' => 'چک', 'debts' => 'طلب/بدهی'] as $key => $label)
                @foreach ($dashboard[$key] as $item)
                    <div class="mb-2 rounded-2xl bg-slate-50 px-3 py-2 text-sm dark:bg-slate-800">{{ $label }}: {{ $item->title ?? $item->description ?? $item->check_number ?? $item->amount ?? 'مورد باز' }}</div>
                @endforeach
            @endforeach
        </div>
    </div>
</section>
@endsection
