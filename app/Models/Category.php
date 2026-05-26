<?php

namespace App\Models;

use App\Enums\CategoryType;
use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use BelongsToUser, HasFactory, SoftDeletes;

    protected $fillable = ['user_id', 'name', 'type', 'parent_id', 'icon', 'color', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['type' => CategoryType::class, 'is_active' => 'boolean'];
    }

    public function parent(): BelongsTo { return $this->belongsTo(Category::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(Category::class, 'parent_id'); }
    public function transactions(): HasMany { return $this->hasMany(Transaction::class); }
}
