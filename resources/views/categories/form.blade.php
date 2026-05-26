@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-xl">
    <h1 class="mb-4 text-2xl font-black">{{ $category->exists ? 'ویرایش دسته‌بندی' : 'دسته‌بندی جدید' }}</h1>
    <form method="post" action="{{ $category->exists ? route('categories.update', $category) : route('categories.store') }}" class="glass space-y-4 rounded-3xl p-5">
        @csrf
        @if ($category->exists) @method('PUT') @endif
        <label class="block text-sm font-bold">نام
            <input name="name" value="{{ old('name', $category->name) }}" class="mt-2 tap w-full rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800" required>
        </label>
        <label class="block text-sm font-bold">نوع
            <select name="type" class="mt-2 tap w-full rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800">
                <option value="income" @selected(old('type', $category->type?->value) === 'income')>درآمد</option>
                <option value="expense" @selected(old('type', $category->type?->value) === 'expense')>هزینه</option>
            </select>
        </label>
        <label class="block text-sm font-bold">والد
            <select name="parent_id" class="mt-2 tap w-full rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800">
                <option value="">ندارد</option>
                @foreach ($parents as $parent)
                    <option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id) == $parent->id)>{{ $parent->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="block text-sm font-bold">رنگ
            <input name="color" value="{{ old('color', $category->color) }}" placeholder="#14b8a6" class="mt-2 tap w-full rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800">
        </label>
        @if ($errors->any())<p class="text-sm text-red-600">{{ $errors->first() }}</p>@endif
        <button class="tap w-full rounded-2xl bg-slate-950 px-4 font-black text-white dark:bg-white dark:text-slate-950">ذخیره</button>
    </form>
</section>
@endsection
