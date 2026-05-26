<?php

namespace App\Services\Accounting;

use App\Enums\InstallmentStatus;
use App\Enums\LoanStatus;
use App\Enums\TransactionType;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LoanService
{
    public function __construct(private readonly TransactionService $transactions) {}

    public function create(User $user, array $data): Loan
    {
        return DB::transaction(function () use ($user, $data): Loan {
            $loan = Loan::create([...$data, 'user_id' => $user->id, 'status' => LoanStatus::Active]);

            $this->transactions->create($user, [
                'account_id' => $loan->account_id,
                'person_id' => $loan->person_id,
                'type' => TransactionType::LoanReceive,
                'amount' => $loan->principal_amount,
                'transaction_date' => $loan->start_date,
                'description' => 'دریافت وام: '.$loan->title,
            ]);

            $date = Carbon::parse($loan->start_date);
            for ($i = 1; $i <= $loan->installment_count; $i++) {
                LoanInstallment::create([
                    'user_id' => $user->id,
                    'loan_id' => $loan->id,
                    'due_date' => $date->copy()->addMonthsNoOverflow($i)->toDateString(),
                    'amount' => $loan->installment_amount,
                    'status' => InstallmentStatus::Pending,
                ]);
            }

            return $loan->load('installments');
        });
    }

    public function payInstallment(LoanInstallment $installment, array $data): LoanInstallment
    {
        return DB::transaction(function () use ($installment, $data): LoanInstallment {
            $transaction = $this->transactions->create($installment->user, [
                'account_id' => $data['account_id'],
                'type' => TransactionType::LoanInstallmentPayment,
                'amount' => $installment->amount,
                'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
                'description' => 'پرداخت قسط وام: '.$installment->loan->title,
            ]);

            $installment->forceFill([
                'account_id' => $data['account_id'],
                'paid_at' => now(),
                'transaction_id' => $transaction->id,
                'status' => InstallmentStatus::Paid,
            ])->save();

            $installment->loan()->increment('paid_installment_count');

            if ($installment->loan->fresh()->paid_installment_count >= $installment->loan->installment_count) {
                $installment->loan->forceFill(['status' => LoanStatus::Completed])->save();
            }

            return $installment->fresh();
        });
    }
}
