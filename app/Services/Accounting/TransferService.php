<?php

namespace App\Services\Accounting;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TransferService
{
    public function __construct(private readonly TransactionService $transactions) {}

    public function create(User $user, array $data): array
    {
        if ((int) $data['account_id'] === (int) $data['destination_account_id']) {
            throw ValidationException::withMessages(['destination_account_id' => 'حساب مبدا و مقصد نباید یکی باشد.']);
        }

        return DB::transaction(function () use ($user, $data): array {
            $uuid = (string) Str::uuid();

            $out = $this->transactions->create($user, [
                'account_id' => $data['account_id'],
                'type' => TransactionType::TransferOut,
                'amount' => $data['amount'],
                'transaction_date' => $data['transaction_date'],
                'transaction_time' => $data['transaction_time'] ?? null,
                'description' => $data['description'] ?? 'انتقال بین حساب‌ها',
                'reference_number' => $data['reference_number'] ?? null,
                'transfer_group_uuid' => $uuid,
                'source' => 'manual',
            ]);

            $in = $this->transactions->create($user, [
                'account_id' => $data['destination_account_id'],
                'type' => TransactionType::TransferIn,
                'amount' => $data['amount'],
                'transaction_date' => $data['transaction_date'],
                'transaction_time' => $data['transaction_time'] ?? null,
                'description' => $data['description'] ?? 'انتقال بین حساب‌ها',
                'reference_number' => $data['reference_number'] ?? null,
                'transfer_group_uuid' => $uuid,
                'source' => 'manual',
            ]);

            $out->forceFill(['related_transaction_id' => $in->id])->save();
            $in->forceFill(['related_transaction_id' => $out->id])->save();

            return [$out, $in];
        });
    }
}
