<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;

class RecurringTransaction extends Model
{
    use BelongsToUser;

    protected $fillable = ['user_id', 'account_id', 'category_id', 'person_id', 'type', 'amount', 'title', 'description', 'frequency', 'next_run_date', 'end_date', 'is_active'];

    protected function casts(): array
    {
        return ['next_run_date' => 'date', 'end_date' => 'date', 'is_active' => 'boolean'];
    }
}
