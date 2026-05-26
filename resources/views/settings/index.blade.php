@extends('layouts.app')
@section('content')
<section class="space-y-4">
    <h1 class="text-2xl font-black">بیشتر</h1>
    <form method="post" action="{{ route('settings.update') }}" class="glass grid gap-3 rounded-3xl p-4">
        @csrf @method('PUT')
        <h2 class="font-black">تنظیمات نمایش و امنیت</h2>
        <label class="block text-sm font-bold">نمایش ارز
            <select name="currency_display" class="mt-2 tap w-full rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800">
                @foreach (['both' => 'ریال و تومان', 'rial' => 'فقط ریال', 'toman' => 'فقط تومان'] as $value => $label)
                    <option value="{{ $value }}" @selected(($settings['currency_display'] ?? 'both') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="flex tap items-center gap-3 rounded-2xl bg-white/70 px-4 text-sm font-bold dark:bg-slate-800">
            <input type="checkbox" name="persian_digits" value="1" @checked($settings['persian_digits'] ?? true)>
            نمایش اعداد فارسی
        </label>
        <label class="block text-sm font-bold">تم
            <select name="theme" class="mt-2 tap w-full rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800">
                @foreach (['system' => 'سیستم', 'light' => 'روشن', 'dark' => 'تاریک'] as $value => $label)
                    <option value="{{ $value }}" @selected(($settings['theme'] ?? 'system') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="block text-sm font-bold">زمان انقضای نشست
            <input name="session_timeout_minutes" inputmode="numeric" value="{{ $settings['session_timeout_minutes'] ?? 120 }}" class="mt-2 tap w-full rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800">
        </label>
        <label class="block text-sm font-bold">تراکنش‌های تکرارشونده
            <select name="recurring_mode" class="mt-2 tap w-full rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800">
                <option value="suggestion" @selected(($settings['recurring_mode'] ?? 'suggestion') === 'suggestion')>فقط پیشنهاد بساز</option>
                <option value="automatic" @selected(($settings['recurring_mode'] ?? 'suggestion') === 'automatic')>خودکار، بعد از تایید دستی</option>
            </select>
        </label>
        <button class="tap rounded-2xl bg-slate-950 font-bold text-white dark:bg-white dark:text-slate-950">ذخیره تنظیمات</button>
    </form>
    <div class="grid gap-3">
        @foreach ([['دسته‌بندی‌ها','categories.index'],['اشخاص','people.index'],['طلب و بدهی','debts.index'],['وام و اقساط','loans.index'],['چک‌ها','checks.index'],['بودجه‌بندی','budgets.index'],['یادآوری‌ها','reminders.index'],['تکرارشونده‌ها','recurring.index'],['وارد کردن داده','imports.index'],['پشتیبان‌گیری','backups.index']] as [$label,$route])
            <a class="tap block rounded-3xl bg-white/85 p-4 font-black dark:bg-slate-900" href="{{ route($route) }}">{{ $label }}</a>
        @endforeach
    </div>
</section>
@endsection
