@extends('layouts.app')
@section('content')
<section class="space-y-4">
    <div class="flex items-center justify-between"><h1 class="text-2xl font-black">تراکنش‌ها</h1><a class="tap rounded-2xl bg-slate-950 px-4 py-3 text-sm font-bold text-white" href="{{ route('transactions.create') }}">ثبت تراکنش</a></div>
    <div class="rounded-3xl bg-white/85 p-4 dark:bg-slate-900">@include('transactions.partials.list', ['transactions' => $transactions])<div class="mt-4">{{ $transactions->links() }}</div></div>
</section>
@endsection
