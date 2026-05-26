@extends('layouts.app')
@section('content')
<section class="space-y-4">
    <h1 class="text-2xl font-black">تراکنش‌های تکرارشونده</h1>
    <form method="post" action="{{ route('recurring.store') }}" class="glass grid gap-3 rounded-3xl p-4">
        @csrf
        <input name="title" placeholder="عنوان" class="tap rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800">
        <select name="type" class="tap rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800">
            <option value="expense">هزینه</option>
            <option value="income">درآمد</option>
            <option value="transfer">انتقال</option>
            <option value="debt">طلب/بدهی</option>
            <option value="loan_installment">قسط</option>
        </select>
        <select name="account_id" class="tap rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800">
            @foreach ($accounts as $account)<option value="{{ $account->id }}">{{ $account->name }}</option>@endforeach
        </select>
        <select name="category_id" class="tap rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800">
            <option value="">بدون دسته</option>
            @foreach ($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach
        </select>
        <input name="amount" inputmode="decimal" placeholder="مبلغ" class="tap rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800">
        <select name="frequency" class="tap rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800">
            <option value="monthly">ماهانه</option>
            <option value="weekly">هفتگی</option>
            <option value="daily">روزانه</option>
            <option value="yearly">سالانه</option>
            <option value="custom">سفارشی</option>
        </select>
        <input name="next_run_date" type="date" class="tap rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800">
        <textarea name="description" placeholder="توضیح" class="w-full rounded-2xl border-0 bg-white/90 px-4 py-3 dark:bg-slate-800"></textarea>
        <button class="tap rounded-2xl bg-slate-950 font-bold text-white">ثبت تکرارشونده</button>
    </form>
    @foreach ($items as $item)
        <div class="rounded-3xl bg-white/85 p-4 dark:bg-slate-900">
            <div class="flex items-start justify-between gap-3">
                <div><strong>{{ $item->title }}</strong><p class="num mt-1 text-sm text-slate-500">{{ number_format($item->amount) }} · {{ $item->next_run_date?->format('Y-m-d') }}</p></div>
                <form method="post" action="{{ route('recurring.update', $item) }}">@csrf @method('PATCH')
                    <input type="hidden" name="is_active" value="{{ $item->is_active ? 0 : 1 }}">
                    <button class="tap rounded-2xl bg-slate-100 px-4 text-sm font-bold dark:bg-slate-800">{{ $item->is_active ? 'توقف' : 'فعال' }}</button>
                </form>
            </div>
        </div>
    @endforeach
</section>
@endsection
