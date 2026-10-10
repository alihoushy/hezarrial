<?php

namespace App\Models;

use App\Enums\CheckStatus;
use App\Enums\CheckType;
use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Check extends Model
{
    use BelongsToUser, HasFactory, SoftDeletes;

    protected $fillable = ['user_id', 'person_id', 'account_id', 'type', 'check_number', 'bank_name', 'bank', 'amount', 'due_date', 'issued_date', 'status', 'description', 'transaction_id'];

    protected function casts(): array
    {
        return ['type' => CheckType::class, 'status' => CheckStatus::class, 'due_date' => 'date', 'issued_date' => 'date'];
    }

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function person(): BelongsTo { return $this->belongsTo(Person::class); }
    public function transaction(): BelongsTo { return $this->belongsTo(Transaction::class); }
}
