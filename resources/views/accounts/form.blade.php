@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-xl">
    <h1 class="mb-4 text-2xl font-black">{{ $account->exists ? 'ویرایش حساب' : 'حساب جدید' }}</h1>
    <form method="post" action="{{ $account->exists ? route('accounts.update', $account) : route('accounts.store') }}" class="glass space-y-4 rounded-3xl p-5">
        @csrf @if($account->exists) @method('PUT') @endif
        <label class="block text-sm font-bold">نام حساب<input name="name" value="{{ old('name', $account->name) }}" class="mt-2 tap w-full rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800" required></label>
        <label class="block text-sm font-bold">نوع<select name="type" class="mt-2 tap w-full rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800">@foreach(['bank'=>'بانک','cash'=>'نقد','wallet'=>'کیف پول','card'=>'کارت','other'=>'سایر'] as $value=>$label)<option value="{{ $value }}" @selected(old('type', $account->type?->value) === $value)>{{ $label }}</option>@endforeach</select></label>
        <label class="block text-sm font-bold">نام بانک<input name="bank_name" value="{{ old('bank_name', $account->bank_name) }}" class="mt-2 tap w-full rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800"></label>
        <label class="block text-sm font-bold">۴ رقم آخر کارت<input name="card_last_four" inputmode="numeric" value="{{ old('card_last_four', $account->card_last_four) }}" class="mt-2 tap w-full rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800"></label>
        <label class="block text-sm font-bold">مانده افتتاحیه<input name="opening_balance" inputmode="decimal" value="{{ old('opening_balance', $account->opening_balance ?? 0) }}" class="num mt-2 tap w-full rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800" required></label>
        @if ($errors->any())<p class="text-sm text-red-600">{{ $errors->first() }}</p>@endif
        <button class="tap w-full rounded-2xl bg-slate-950 px-4 font-black text-white dark:bg-white dark:text-slate-950">ذخیره</button>
    </form>
</section>
@endsection
