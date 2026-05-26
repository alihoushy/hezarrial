@extends('layouts.app')
@section('content')
<section class="space-y-4">
    <h1 class="text-2xl font-black">{{ $title }}</h1>
    <div class="grid gap-3">
        @forelse ($items as $item)
            <div class="rounded-3xl bg-white/85 p-4 dark:bg-slate-900"><strong>{{ $item->name ?? $item->title ?? $item->full_name ?? $item->check_number ?? 'مورد' }}</strong><p class="num mt-2 text-sm text-slate-500">{{ isset($item->amount) ? number_format($item->amount) : '' }}</p></div>
        @empty
            <p class="rounded-3xl bg-white/85 p-4 text-sm text-slate-500">موردی برای گزارش نیست.</p>
        @endforelse
    </div>
</section>
@endsection
