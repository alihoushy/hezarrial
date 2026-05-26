<?php

namespace App\Services\Accounting;

use App\Enums\DebtStatus;
use App\Enums\DebtType;
use App\Enums\TransactionType;
use App\Models\Debt;
use Illuminate\Support\Facades\DB;

class DebtService
{
    public function __construct(private readonly TransactionService $transactions) {}

    public function settle(Debt $debt, array $data): Debt
    {
        return DB::transaction(function () use ($debt, $data): Debt {
            $amount = min((float) $data['amount'], (float) $debt->remaining_amount);
            $type = $debt->type === DebtType::Payable ? TransactionType::DebtPayment : TransactionType::ReceivableCollection;

            $this->transactions->create($debt->user, [
                'account_id' => $data['account_id'],
                'person_id' => $debt->person_id,
                'type' => $type,
                'amount' => $amount,
                'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
                'description' => $data['description'] ?? 'تسویه طلب و بدهی',
            ]);

            $remaining = round((float) $debt->remaining_amount - $amount, 2);
            $debt->forceFill([
                'remaining_amount' => $remaining,
                'status' => $remaining <= 0 ? DebtStatus::Settled : DebtStatus::PartiallySettled,
                'settled_at' => $remaining <= 0 ? now() : null,
            ])->save();

            return $debt->fresh();
        });
    }
}
