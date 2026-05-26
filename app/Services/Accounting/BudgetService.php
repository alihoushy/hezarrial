<?php

namespace App\Services\Accounting;

use App\Enums\TransactionType;
use App\Models\Budget;
use App\Models\Transaction;

class BudgetService
{
    public function progress(Budget $budget): array
    {
        $spent = Transaction::query()
            ->forUser($budget->user_id)
            ->where('type', TransactionType::Expense)
            ->when($budget->category_id, fn ($query) => $query->where('category_id', $budget->category_id))
            ->whereDate('transaction_date', '>=', $budget->start_date)
            ->when($budget->end_date, fn ($query) => $query->whereDate('transaction_date', '<=', $budget->end_date))
            ->sum('amount');

        $percent = (float) $budget->amount > 0 ? round(((float) $spent / (float) $budget->amount) * 100) : 0;

        return [
            'spent' => (float) $spent,
            'remaining' => max(0, (float) $budget->amount - (float) $spent),
            'percent' => $percent,
            'over_threshold' => $percent >= $budget->alert_threshold_percent,
        ];
    }
}
