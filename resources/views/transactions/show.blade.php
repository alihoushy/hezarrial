@extends('layouts.app')
@section('content')
<section class="glass rounded-3xl p-5">
    <p class="text-sm text-slate-500">جزئیات تراکنش</p><h1 class="mt-1 text-2xl font-black">{{ $transaction->description ?: 'تراکنش' }}</h1>
    <p class="num mt-5 text-3xl font-black">{{ number_format($transaction->amount) }}</p><p class="text-xs text-slate-500">ریال</p>
    <div class="mt-5 space-y-2 text-sm"><p>حساب: {{ $transaction->account?->name }}</p><p>دسته: {{ $transaction->category?->name ?: 'بدون دسته' }}</p><p>تاریخ: {{ $transaction->transaction_date?->format('Y-m-d') }}</p></div>
    <div class="mt-5 flex gap-2"><a class="tap rounded-2xl bg-white/80 px-4 py-3 font-bold" href="{{ route('transactions.edit', $transaction) }}">ویرایش</a><form method="post" action="{{ route('transactions.destroy', $transaction) }}">@csrf @method('DELETE')<button class="tap rounded-2xl bg-rose-600 px-4 font-bold text-white">حذف</button></form></div>
</section>
@endsection
