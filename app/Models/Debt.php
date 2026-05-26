<?php

namespace App\Models;

use App\Enums\DebtStatus;
use App\Enums\DebtType;
use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Debt extends Model
{
    use BelongsToUser, HasFactory, SoftDeletes;

    protected $fillable = ['user_id', 'person_id', 'type', 'original_amount', 'remaining_amount', 'due_date', 'status', 'description', 'created_transaction_id', 'settled_at'];

    protected function casts(): array
    {
        return ['type' => DebtType::class, 'status' => DebtStatus::class, 'due_date' => 'date', 'settled_at' => 'datetime'];
    }

    public function person(): BelongsTo { return $this->belongsTo(Person::class); }
    public function createdTransaction(): BelongsTo { return $this->belongsTo(Transaction::class, 'created_transaction_id'); }
}
