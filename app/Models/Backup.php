<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;

class Backup extends Model
{
    use BelongsToUser;

    public $timestamps = false;

    protected $fillable = ['user_id', 'file_path', 'file_name', 'file_size', 'is_encrypted', 'created_at'];

    protected function casts(): array
    {
        return ['is_encrypted' => 'boolean', 'created_at' => 'datetime'];
    }
}
