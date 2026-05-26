<?php

namespace App\Services\Reports;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Check;
use App\Models\Debt;
use App\Models\LoanInstallment;
use App\Models\Reminder;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Carbon;

class ReportService
{
    public function dashboard(User $user): array
    {
        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();

        $income = Transaction::forUser($user)->where('type', TransactionType::Income)->whereBetween('transaction_date', [$start, $end])->sum('amount');
        $expense = Transaction::forUser($user)->where('type', TransactionType::Expense)->whereBetween('transaction_date', [$start, $end])->sum('amount');

        return [
            'income' => (float) $income,
            'expense' => (float) $expense,
            'net' => (float) $income - (float) $expense,
            'total_balance' => (float) Account::forUser($user)->where('is_active', true)->sum('current_balance'),
            'accounts' => Account::forUser($user)->where('is_active', true)->orderBy('sort_order')->get(),
            'recent_transactions' => Transaction::forUser($user)->with(['account', 'category', 'person'])->latest('transaction_date')->latest('id')->limit(8)->get(),
            'reminders' => Reminder::forUser($user)->where('status', 'pending')->whereDate('due_date', '<=', Carbon::now()->addDays(14))->orderBy('due_date')->limit(5)->get(),
            'installments' => LoanInstallment::forUser($user)->where('status', 'pending')->whereDate('due_date', '<=', Carbon::now()->addDays(30))->orderBy('due_date')->limit(5)->get(),
            'checks' => Check::forUser($user)->where('status', 'pending')->whereDate('due_date', '<=', Carbon::now()->addDays(30))->orderBy('due_date')->limit(5)->get(),
            'debts' => Debt::forUser($user)->whereIn('status', ['open', 'partially_settled', 'overdue'])->orderByRaw('due_date is null, due_date asc')->limit(5)->get(),
            'category_spending' => Transaction::forUser($user)->with('category')->where('type', TransactionType::Expense)->whereBetween('transaction_date', [$start, $end])->get()->groupBy(fn ($t) => $t->category?->name ?? 'بدون دسته')->map->sum('amount'),
        ];
    }
}
