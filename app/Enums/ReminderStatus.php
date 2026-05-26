<?php

namespace App\Enums;

enum ReminderStatus: string
{
    case Pending = 'pending';
    case Done = 'done';
    case Dismissed = 'dismissed';
}
