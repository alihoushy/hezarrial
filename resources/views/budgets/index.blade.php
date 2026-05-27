@extends('layouts.app')
@section('content')
<section class="space-y-4">
    <h1 class="text-2xl font-black">بودجه‌بندی</h1>
    <form method="post" action="{{ route('budgets.store') }}" class="glass grid gap-3 rounded-3xl p-4">
        @csrf
        <input name="title" placeholder="عنوان بودجه" class="tap rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800">
        <select name="category_id" class="tap rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800"><option value="">همه هزینه‌ها</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>
        <input name="amount" inputmode="decimal" placeholder="مبلغ بودجه" class="tap rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800">
        <input name="start_date" type="date" class="tap rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800">
        <input name="end_date" type="date" class="tap rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800">
        <button class="tap rounded-2xl bg-slate-950 font-bold text-white">ثبت بودجه</button>
    </form>
    @foreach($budgets as $budget)
        @php($item = $progress[$budget->id])
        <div class="rounded-3xl bg-white/85 p-4 dark:bg-slate-900">
            <div class="flex items-center justify-between gap-3">
                <div><strong>{{ $budget->title }}</strong><p class="mt-1 text-xs text-slate-500">{{ $budget->category?->name ?? 'همه هزینه‌ها' }}</p></div>
                <p class="num font-black">{{ $item['percent'] }}٪</p>
            </div>
            <div class="mt-4 h-3 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                <div class="h-full rounded-full {{ $item['over_threshold'] ? 'bg-rose-600' : 'bg-emerald-600' }}" style="width: {{ min(100, $item['percent']) }}%"></div>
            </div>
            <div class="mt-3 grid grid-cols-3 gap-2 text-xs text-slate-500">
                <span>بودجه: <b class="num text-slate-900 dark:text-white">{{ number_format($budget->amount) }}</b></span>
                <span>مصرف: <b class="num text-slate-900 dark:text-white">{{ number_format($item['spent']) }}</b></span>
                <span>مانده: <b class="num text-slate-900 dark:text-white">{{ number_format($item['remaining']) }}</b></span>
            </div>
        </div>
    @endforeach
</section>
@endsection
