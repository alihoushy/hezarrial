<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;

class SmsPattern extends Model
{
    use BelongsToUser;

    protected $fillable = ['user_id', 'name', 'bank_name', 'pattern', 'debit_keywords', 'credit_keywords', 'is_active'];

    protected function casts(): array
    {
        return [
            'debit_keywords' => 'array',
            'credit_keywords' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
