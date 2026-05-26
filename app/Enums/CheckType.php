<?php

namespace App\Enums;

enum CheckType: string
{
    case Payable = 'payable';
    case Receivable = 'receivable';
}
