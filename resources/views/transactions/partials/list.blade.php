<div class="space-y-2">
@forelse ($transactions as $transaction)
    <a href="{{ route('transactions.show', $transaction) }}" class="flex items-center justify-between gap-3 rounded-2xl bg-slate-50 px-3 py-3 dark:bg-slate-800">
        <div class="min-w-0"><p class="truncate text-sm font-bold">{{ $transaction->description ?: ($transaction->category?->name ?? 'تراکنش') }}</p><p class="text-xs text-slate-500">{{ $transaction->account?->name }} · {{ $transaction->transaction_date?->format('Y-m-d') }}</p></div>
        <p class="num shrink-0 font-black {{ in_array($transaction->type->value, ['expense','transfer_out','debt_payment','loan_installment_payment','check_payment']) ? 'text-rose-600' : 'text-emerald-700' }}">{{ number_format($transaction->amount) }}</p>
    </a>
@empty
    <p class="rounded-2xl bg-slate-50 px-3 py-4 text-sm text-slate-500 dark:bg-slate-800">موردی برای نمایش نیست.</p>
@endforelse
</div>
