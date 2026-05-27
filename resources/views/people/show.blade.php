@extends('layouts.app')
@section('content')
<section class="space-y-4">
    <div class="glass rounded-3xl p-5">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-sm text-slate-500">پرونده شخص</p>
                <h1 class="text-2xl font-black">{{ $person->full_name }}</h1>
                <p class="mt-2 text-sm text-slate-500">{{ $person->mobile }}</p>
            </div>
            <a class="tap rounded-2xl bg-white/80 px-4 py-3 font-bold" href="{{ route('people.edit',$person) }}">ویرایش</a>
        </div>
        <div class="mt-5 grid grid-cols-3 gap-2">
            <x-stat label="بدهی من" :value="$summary['payable']" />
            <x-stat label="طلب من" :value="$summary['receivable']" />
            <x-stat label="خالص" :value="$summary['net']" />
        </div>
    </div>
    <div class="rounded-3xl bg-white/85 p-4 dark:bg-slate-900">
        <h2 class="mb-3 font-black">موارد باز</h2>
        @forelse ($summary['open_items'] as $debt)
            <div class="mb-2 rounded-2xl bg-slate-50 px-3 py-3 dark:bg-slate-800">
                <div class="flex items-center justify-between gap-3">
                    <strong>{{ $debt->type->value === 'payable' ? 'بدهی' : 'طلب' }}</strong>
                    <span class="num font-black">{{ number_format($debt->remaining_amount) }}</span>
                </div>
                <p class="mt-1 text-xs text-slate-500">{{ $debt->due_date?->format('Y-m-d') ?: 'بدون سررسید' }}</p>
            </div>
        @empty
            <p class="rounded-2xl bg-slate-50 px-3 py-4 text-sm text-slate-500 dark:bg-slate-800">مورد بازی وجود ندارد.</p>
        @endforelse
    </div>
    <div class="rounded-3xl bg-white/85 p-4 dark:bg-slate-900">
        <h2 class="mb-3 font-black">تراکنش‌های مرتبط</h2>
        @include('transactions.partials.list',['transactions'=>$person->transactions])
    </div>
</section>
@endsection
