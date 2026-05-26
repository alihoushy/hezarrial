<?php

namespace App\Models;

use App\Enums\TransactionType;
use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use BelongsToUser, HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'account_id', 'category_id', 'person_id', 'type', 'amount',
        'transaction_date', 'transaction_time', 'description', 'reference_number',
        'source', 'related_transaction_id', 'transfer_group_uuid', 'attachment_path', 'is_reconciled',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'transaction_date' => 'date',
            'is_reconciled' => 'boolean',
        ];
    }

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function person(): BelongsTo { return $this->belongsTo(Person::class); }
    public function relatedTransaction(): BelongsTo { return $this->belongsTo(Transaction::class, 'related_transaction_id'); }
}
