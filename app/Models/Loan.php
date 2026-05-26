<?php

namespace App\Models;

use App\Enums\LoanStatus;
use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Loan extends Model
{
    use BelongsToUser, HasFactory, SoftDeletes;

    protected $fillable = ['user_id', 'person_id', 'account_id', 'title', 'lender_name', 'principal_amount', 'total_payable_amount', 'installment_amount', 'installment_count', 'paid_installment_count', 'start_date', 'period', 'status', 'description'];

    protected function casts(): array
    {
        return ['status' => LoanStatus::class, 'start_date' => 'date'];
    }

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function person(): BelongsTo { return $this->belongsTo(Person::class); }
    public function installments(): HasMany { return $this->hasMany(LoanInstallment::class); }
}
