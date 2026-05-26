<?php

namespace App\Enums;

enum DebtType: string
{
    case Payable = 'payable';
    case Receivable = 'receivable';
}
