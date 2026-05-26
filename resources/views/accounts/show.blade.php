@extends('layouts.app')
@section('content')
<section class="space-y-4">
    <div class="glass rounded-3xl p-5">
        <div class="flex items-start justify-between gap-3"><div><p class="text-sm text-slate-500">حساب</p><h1 class="text-2xl font-black">{{ $account->name }}</h1></div><a class="tap rounded-2xl bg-white/70 px-4 py-2 text-sm font-bold" href="{{ route('accounts.edit', $account) }}">ویرایش</a></div>
        <p class="num mt-5 text-3xl font-black">{{ number_format($account->current_balance) }}</p><p class="text-xs text-slate-500">ریال</p>
        <form method="post" action="{{ route('accounts.recalculate', $account) }}" class="mt-4">@csrf<button class="tap rounded-2xl bg-slate-950 px-4 text-sm font-bold text-white">بازسازی مانده</button></form>
    </div>
    <div class="rounded-3xl bg-white/85 p-4 dark:bg-slate-900"><h2 class="mb-3 font-black">گردش حساب</h2>@include('transactions.partials.list', ['transactions' => $transactions]){{ method_exists($transactions, 'links') ? $transactions->links() : '' }}</div>
</section>
@endsection
