<?php

namespace App\Services\Accounting;

use App\Enums\CheckStatus;
use App\Enums\CheckType;
use App\Enums\TransactionType;
use App\Models\Check;
use Illuminate\Support\Facades\DB;

class CheckService
{
    public function __construct(private readonly TransactionService $transactions) {}

    public function pass(Check $check, array $data): Check
    {
        return DB::transaction(function () use ($check, $data): Check {
            $type = $check->type === CheckType::Payable ? TransactionType::CheckPayment : TransactionType::CheckReceive;
            $transaction = $this->transactions->create($check->user, [
                'account_id' => $data['account_id'] ?? $check->account_id,
                'person_id' => $check->person_id,
                'type' => $type,
                'amount' => $check->amount,
                'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
                'description' => 'پاس شدن چک '.$check->check_number,
            ]);

            $check->forceFill([
                'account_id' => $data['account_id'] ?? $check->account_id,
                'transaction_id' => $transaction->id,
                'status' => CheckStatus::Passed,
            ])->save();

            return $check->fresh();
        });
    }
}
