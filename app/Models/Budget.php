<?php

namespace App\Models;

use App\Enums\BudgetPeriod;
use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Budget extends Model
{
    use BelongsToUser, HasFactory, SoftDeletes;

    protected $fillable = ['user_id', 'category_id', 'title', 'period', 'amount', 'start_date', 'end_date', 'alert_threshold_percent', 'is_active'];

    protected function casts(): array
    {
        return ['period' => BudgetPeriod::class, 'start_date' => 'date', 'end_date' => 'date', 'is_active' => 'boolean'];
    }

    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
}
