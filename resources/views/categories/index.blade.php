@extends('layouts.app')
@section('content')
<section class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-black">دسته‌بندی‌ها</h1>
        <a class="tap rounded-2xl bg-slate-950 px-4 py-3 text-sm font-bold text-white" href="{{ route('categories.create') }}">دسته جدید</a>
    </div>
    <div class="grid gap-3 sm:grid-cols-2">
        @forelse ($categories as $category)
            <div class="rounded-3xl bg-white/85 p-4 dark:bg-slate-900">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <strong>{{ $category->name }}</strong>
                        <p class="mt-1 text-xs text-slate-500">{{ $category->type->value === 'income' ? 'درآمد' : 'هزینه' }}</p>
                    </div>
                    <a class="tap rounded-2xl bg-slate-100 px-4 py-2 text-sm font-bold dark:bg-slate-800" href="{{ route('categories.edit', $category) }}">ویرایش</a>
                </div>
            </div>
        @empty
            <p class="rounded-3xl bg-white/85 p-4 text-sm text-slate-500">دسته‌بندی‌ای ثبت نشده است.</p>
        @endforelse
    </div>
</section>
@endsection
