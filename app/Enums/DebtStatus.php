<?php

namespace App\Enums;

enum DebtStatus: string
{
    case Open = 'open';
    case PartiallySettled = 'partially_settled';
    case Settled = 'settled';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';
}
