<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomingSms extends Model
{
    use BelongsToUser;

    protected $table = 'incoming_sms';

    protected $fillable = [
        'user_id', 'status', 'raw_message', 'amount', 'currency_detected', 'type',
        'balance_after', 'bank_name', 'occurred_at', 'tracking_number', 'card_last_four',
        'account_id', 'sms_pattern_id', 'confidence', 'transaction_id', 'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'raw_message' => 'encrypted',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function smsPattern(): BelongsTo
    {
        return $this->belongsTo(SmsPattern::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }
}
