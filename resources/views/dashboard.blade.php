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
            <h2 class="mb-3 font-black">درآمد و هزینه ماه</h2>
            <div class="h-64"><canvas id="incomeExpenseChart" aria-label="نمودار درآمد و هزینه ماه"></canvas></div>
        </div>
        <div class="rounded-3xl bg-white/85 p-4 dark:bg-slate-900">
            <h2 class="mb-3 font-black">هزینه بر اساس دسته‌بندی</h2>
            <div class="h-64"><canvas id="categorySpendingChart" aria-label="نمودار هزینه بر اساس دسته‌بندی"></canvas></div>
        </div>
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
<script>
document.addEventListener('DOMContentLoaded', () => {
    const chartText = getComputedStyle(document.documentElement).getPropertyValue('color') || '#111827';
    const trend = @json($dashboard['charts']['income_expense']);
    const categories = @json($dashboard['charts']['category_spending']);

    new Chart(document.getElementById('incomeExpenseChart'), {
        type: 'line',
        data: {
            labels: trend.labels,
            datasets: [
                { label: 'درآمد', data: trend.income, borderColor: '#059669', backgroundColor: 'rgba(5, 150, 105, .12)', tension: .35, fill: true },
                { label: 'هزینه', data: trend.expense, borderColor: '#e11d48', backgroundColor: 'rgba(225, 29, 72, .10)', tension: .35, fill: true },
            ],
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: chartText } } }, scales: { x: { ticks: { color: chartText } }, y: { ticks: { color: chartText } } } },
    });

    new Chart(document.getElementById('categorySpendingChart'), {
        type: 'doughnut',
        data: {
            labels: categories.labels.length ? categories.labels : ['بدون هزینه'],
            datasets: [{ data: categories.values.length ? categories.values : [1], backgroundColor: ['#0f766e', '#e11d48', '#2563eb', '#d97706', '#7c3aed', '#0891b2'] }],
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { color: chartText } } } },
    });
});
</script>
@endsection
