<?php

namespace App\Services\Accounting;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Collection;

class AccountBalanceService
{
    public function signedAmount(Transaction $transaction): string
    {
        $type = $transaction->type instanceof TransactionType
            ? $transaction->type
            : TransactionType::from($transaction->type);

        $amount = (float) $transaction->amount;

        return (string) ($amount * $type->affectsBalance());
    }

    public function apply(Transaction $transaction): void
    {
        $transaction->account()->lockForUpdate()->firstOrFail()
            ->increment('current_balance', $this->signedAmount($transaction));
    }

    public function reverse(Transaction $transaction): void
    {
        $transaction->account()->lockForUpdate()->firstOrFail()
            ->decrement('current_balance', $this->signedAmount($transaction));
    }

    public function recalculate(Account $account, bool $fix = false): array
    {
        $expected = (float) $account->opening_balance;

        Transaction::query()
            ->where('account_id', $account->id)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get()
            ->each(function (Transaction $transaction) use (&$expected): void {
                $expected += (float) $this->signedAmount($transaction);
            });

        $current = (float) $account->current_balance;
        $difference = round($expected - $current, 2);

        if ($fix && abs($difference) > 0.009) {
            $account->forceFill(['current_balance' => $expected])->save();
        }

        return compact('expected', 'current', 'difference');
    }

    public function recalculateAll(bool $fix = false): Collection
    {
        return Account::query()->orderBy('id')->get()->map(function (Account $account) use ($fix): array {
            return ['account' => $account, ...$this->recalculate($account, $fix)];
        });
    }
}
