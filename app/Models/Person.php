<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends Model
{
    use BelongsToUser, HasFactory, SoftDeletes;

    protected $fillable = ['user_id', 'full_name', 'mobile', 'description', 'avatar_color', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function transactions(): HasMany { return $this->hasMany(Transaction::class); }
    public function debts(): HasMany { return $this->hasMany(Debt::class); }
}
