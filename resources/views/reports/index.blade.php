@extends('layouts.app')
@section('content')
<section class="space-y-4">
    <div class="glass sticky top-20 rounded-3xl p-5"><h1 class="text-2xl font-black">گزارش‌ها</h1><div class="mt-4 grid grid-cols-2 gap-3"><x-stat label="درآمد ماه" :value="$monthlyIncome" /><x-stat label="هزینه ماه" :value="$monthlyExpense" /></div></div>
    <div class="grid gap-3 sm:grid-cols-2">
        @foreach ([['ماهانه','reports.monthly'],['حساب‌ها','reports.accounts'],['دسته‌بندی‌ها','reports.categories'],['اشخاص','reports.people'],['وام‌ها','reports.loans'],['چک‌ها','reports.checks']] as [$label,$route])
            <a class="tap rounded-3xl bg-white/85 p-5 font-black dark:bg-slate-900" href="{{ route($route) }}">{{ $label }}</a>
        @endforeach
    </div>
    <div class="rounded-3xl bg-white/85 p-4 dark:bg-slate-900">
        <h2 class="mb-3 font-black">خروجی</h2>
        <div class="flex flex-wrap gap-2"><a class="tap rounded-2xl bg-slate-950 px-4 py-3 text-sm font-bold text-white" href="{{ route('exports.transactions.csv') }}">CSV تراکنش‌ها</a><a class="tap rounded-2xl bg-slate-950 px-4 py-3 text-sm font-bold text-white" href="{{ route('exports.transactions.xlsx') }}">Excel تراکنش‌ها</a></div>
    </div>
</section>
@endsection
