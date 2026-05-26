@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-xl">
    <h1 class="mb-4 text-2xl font-black">{{ $transaction->exists ? 'ویرایش تراکنش' : 'ثبت تراکنش' }}</h1>
    <form method="post" action="{{ $transaction->exists ? route('transactions.update', $transaction) : route('transactions.store') }}" class="glass space-y-4 rounded-3xl p-5">
        @csrf @if($transaction->exists) @method('PUT') @endif
        <label class="block text-sm font-bold">نوع<select name="type" class="mt-2 tap w-full rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800">@foreach(['income'=>'درآمد','expense'=>'هزینه','transfer_out'=>'انتقال','adjustment'=>'اصلاح مانده','debt_payment'=>'پرداخت بدهی','receivable_collection'=>'دریافت طلب','loan_installment_payment'=>'پرداخت قسط','check_payment'=>'پرداخت چک','check_receive'=>'دریافت چک'] as $value=>$label)<option value="{{ $value }}" @selected(old('type', request('type', $transaction->type?->value)) === $value)>{{ $label }}</option>@endforeach</select></label>
        <label class="block text-sm font-bold">مبلغ<input name="amount" inputmode="decimal" value="{{ old('amount', $transaction->amount) }}" class="num mt-2 tap w-full rounded-2xl border-0 bg-white/90 px-4 text-xl font-black dark:bg-slate-800" required><span class="mt-1 block text-xs text-slate-500">مبلغ را به ریال وارد کنید. برای تومان یک صفر اضافه کنید.</span></label>
        <label class="block text-sm font-bold">حساب<select name="account_id" class="mt-2 tap w-full rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800">@foreach($accounts as $account)<option value="{{ $account->id }}" @selected(old('account_id', $transaction->account_id) == $account->id)>{{ $account->name }}</option>@endforeach</select></label>
        <label class="block text-sm font-bold">حساب مقصد انتقال<select name="destination_account_id" class="mt-2 tap w-full rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800"><option value="">ندارد</option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->name }}</option>@endforeach</select></label>
        <label class="block text-sm font-bold">دسته‌بندی<select name="category_id" class="mt-2 tap w-full rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800"><option value="">بدون دسته</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id', $transaction->category_id) == $category->id)>{{ $category->name }}</option>@endforeach</select></label>
        <label class="block text-sm font-bold">شخص<select name="person_id" class="mt-2 tap w-full rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800"><option value="">بدون شخص</option>@foreach($people as $person)<option value="{{ $person->id }}" @selected(old('person_id', $transaction->person_id) == $person->id)>{{ $person->full_name }}</option>@endforeach</select></label>
        <label class="block text-sm font-bold">تاریخ<input name="transaction_date" type="date" value="{{ old('transaction_date', optional($transaction->transaction_date)->format('Y-m-d') ?? now()->toDateString()) }}" class="mt-2 tap w-full rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800" required></label>
        <label class="block text-sm font-bold">شرح<textarea name="description" class="mt-2 w-full rounded-2xl border-0 bg-white/90 px-4 py-3 dark:bg-slate-800">{{ old('description', $transaction->description) }}</textarea></label>
        @if ($errors->any())<p class="text-sm text-red-600">{{ $errors->first() }}</p>@endif
        <button class="tap w-full rounded-2xl bg-slate-950 px-4 font-black text-white dark:bg-white dark:text-slate-950">ذخیره</button>
        <button name="save_add_another" value="1" class="tap w-full rounded-2xl bg-white/80 px-4 font-bold dark:bg-slate-800">ذخیره و ثبت بعدی</button>
    </form>
</section>
@endsection
