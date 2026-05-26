<?php

namespace App\Enums;

enum BudgetPeriod: string
{
    case Monthly = 'monthly';
    case Weekly = 'weekly';
    case Yearly = 'yearly';
    case Custom = 'custom';
}
