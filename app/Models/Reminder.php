<?php

namespace App\Models;

use App\Enums\ReminderStatus;
use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Reminder extends Model
{
    use BelongsToUser, HasFactory;

    protected $fillable = ['user_id', 'remindable_type', 'remindable_id', 'title', 'due_date', 'due_time', 'status', 'notify_in_app', 'notify_email'];

    protected function casts(): array
    {
        return ['status' => ReminderStatus::class, 'due_date' => 'date', 'notify_in_app' => 'boolean', 'notify_email' => 'boolean'];
    }

    public function remindable(): MorphTo { return $this->morphTo(); }
}
