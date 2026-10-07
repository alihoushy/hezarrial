<?php

namespace App\Services\Accounting;

use App\Enums\CategoryType;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    public function __construct(
        private readonly AccountBalanceService $balances,
        private readonly AuditLogService $audit,
    ) {}

    public function create(User $user, array $data): Transaction
    {
        return DB::transaction(function () use ($user, $data): Transaction {
            $this->assertCategoryMatchesType($user, $data);

            $transaction = Transaction::create([
                ...$data,
                'user_id' => $user->id,
                'amount' => abs((float) $data['amount']),
                'source' => $data['source'] ?? 'manual',
            ]);

            $this->balances->apply($transaction);

            return $transaction;
        });
    }

    public function update(Transaction $transaction, array $data): Transaction
    {
        return DB::transaction(function () use ($transaction, $data): Transaction {
            $old = $transaction->toArray();
            $this->balances->reverse($transaction);
            $this->assertCategoryMatchesType($transaction->user, $data + $transaction->toArray());
            $transaction->fill($data);
            if (isset($data['amount'])) {
                $transaction->amount = abs((float) $data['amount']);
            }
            $transaction->save();
            $this->balances->apply($transaction);
            $this->audit->record('transaction.updated', $transaction, $old, $transaction->fresh()->toArray());

            return $transaction;
        });
    }

    public function delete(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction): void {
            $related = $transaction->relatedTransaction;
            if ($related && $transaction->transfer_group_uuid) {
                $this->balances->reverse($related);
                $related->delete();
                $this->audit->record('transaction.deleted', $related, $related->toArray());
            }

            $this->balances->reverse($transaction);
            $transaction->delete();
            $this->audit->record('transaction.deleted', $transaction, $transaction->toArray());
        });
    }

    private function assertCategoryMatchesType(User $user, array $data): void
    {
        $type = $data['type'] instanceof TransactionType ? $data['type'] : TransactionType::from($data['type']);

        if (! in_array($type, [TransactionType::Income, TransactionType::Expense], true)) {
            return;
        }

        if (empty($data['category_id'])) {
            throw ValidationException::withMessages(['category_id' => __('انتخاب دسته‌بندی برای درآمد و هزینه الزامی است.')]);
        }

        $expected = $type === TransactionType::Income ? CategoryType::Income : CategoryType::Expense;
        $category = Category::forUser($user)->findOrFail($data['category_id']);

        if ($category->type !== $expected) {
            throw ValidationException::withMessages(['category_id' => __('نوع دسته‌بندی با نوع تراکنش هم‌خوان نیست.')]);
        }
    }
}
