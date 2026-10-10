<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use BelongsToUser, HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'name', 'type', 'bank_name', 'bank', 'card_last_four', 'masked_card_number',
        'opening_balance', 'current_balance', 'currency', 'color', 'icon', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['type' => AccountType::class, 'is_active' => 'boolean'];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
