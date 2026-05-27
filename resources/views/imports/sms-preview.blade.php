@extends('layouts.app')
@section('content')
<section class="glass rounded-3xl p-5">
    <h1 class="text-2xl font-black">پیش‌نمایش پیامک</h1>
    <dl class="mt-4 space-y-2 text-sm"><div>مبلغ: <span class="num font-black">{{ number_format($parsed['amount'] ?? 0) }}</span></div><div>نوع: {{ $parsed['type'] === 'income' ? 'درآمد' : ($parsed['type'] === 'expense' ? 'هزینه' : 'نامشخص') }}</div><div>مانده: <span class="num">{{ isset($parsed['balance']) ? number_format($parsed['balance']) : 'نامشخص' }}</span></div><div>کارت: {{ $parsed['card_last_four'] ?? 'نامشخص' }}</div><div>اطمینان: {{ $parsed['confidence'] }}</div></dl>
    <form method="post" action="{{ route('imports.sms-confirm') }}" class="mt-5">@csrf<input type="hidden" name="amount" value="{{ $parsed['amount'] }}"><input type="hidden" name="type" value="{{ $parsed['type'] }}"><button class="tap rounded-2xl bg-slate-950 px-4 font-bold text-white">تایید پیش‌نمایش</button></form>
</section>
@endsection
