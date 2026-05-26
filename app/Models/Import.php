<?php

namespace App\Models;

use App\Enums\ImportStatus;
use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;

class Import extends Model
{
    use BelongsToUser;

    protected $fillable = ['user_id', 'type', 'file_path', 'status', 'total_rows', 'imported_rows', 'failed_rows', 'error_log'];

    protected function casts(): array
    {
        return ['status' => ImportStatus::class, 'error_log' => 'array'];
    }
}
