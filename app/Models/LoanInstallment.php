<?php

namespace App\Models;

use App\Enums\InstallmentStatus;
use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanInstallment extends Model
{
    use BelongsToUser, HasFactory;

    protected $fillable = ['user_id', 'loan_id', 'account_id', 'due_date', 'amount', 'paid_at', 'transaction_id', 'status'];

    protected function casts(): array
    {
        return ['status' => InstallmentStatus::class, 'due_date' => 'date', 'paid_at' => 'datetime'];
    }

    public function loan(): BelongsTo { return $this->belongsTo(Loan::class); }
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function transaction(): BelongsTo { return $this->belongsTo(Transaction::class); }
}
