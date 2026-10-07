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
    /**
     * Current month's income, expense and net, plus the total of active balances.
     */
    public function summary(User $user): array
    {
        [$start, $end] = $this->currentMonth();

        $income = (float) Transaction::forUser($user)->where('type', TransactionType::Income)->whereBetween('transaction_date', [$start, $end])->sum('amount');
        $expense = (float) Transaction::forUser($user)->where('type', TransactionType::Expense)->whereBetween('transaction_date', [$start, $end])->sum('amount');

        return [
            'income' => $income,
            'expense' => $expense,
            'net' => $income - $expense,
            'total_balance' => (float) Account::forUser($user)->where('is_active', true)->sum('current_balance'),
        ];
    }

    /**
     * Daily income/expense for the current month and expense per category.
     */
    public function charts(User $user): array
    {
        [$start, $end] = $this->currentMonth();

        $transactions = Transaction::forUser($user)
            ->with('category:id,name,color')
            ->whereIn('type', [TransactionType::Income, TransactionType::Expense])
            ->whereBetween('transaction_date', [$start, $end])
            ->get(['type', 'amount', 'transaction_date', 'category_id']);

        $daily = collect(Carbon::parse($start)->daysUntil(Carbon::parse($end)))
            ->map(function (Carbon $day) use ($transactions): array {
                $date = $day->toDateString();
                $sameDay = $transactions->filter(fn (Transaction $transaction) => $transaction->transaction_date?->toDateString() === $date);

                return [
                    'date' => $date,
                    'income' => (float) $sameDay->where('type', TransactionType::Income)->sum('amount'),
                    'expense' => (float) $sameDay->where('type', TransactionType::Expense)->sum('amount'),
                ];
            })
            ->values();

        $categories = $transactions
            ->where('type', TransactionType::Expense)
            ->groupBy(fn (Transaction $transaction) => $transaction->category?->name ?? __('بدون دسته'))
            ->map(fn ($rows, $name) => [
                'name' => $name,
                'color' => $rows->first()->category?->color,
                'value' => (float) $rows->sum('amount'),
            ])
            ->sortByDesc('value')
            ->values();

        return ['daily' => $daily, 'categories' => $categories];
    }

    /**
     * Pending reminders, installments, checks and open debts that are due soon,
     * merged into one list ordered by due date (undated debts last).
     */
    public function upcoming(User $user): array
    {
        $items = collect();

        Reminder::forUser($user)->where('status', 'pending')->whereDate('due_date', '<=', Carbon::now()->addDays(14))->orderBy('due_date')->limit(5)->get()
            ->each(fn (Reminder $reminder) => $items->push([
                'key' => 'reminder-'.$reminder->id,
                'kind' => 'reminder',
                'title' => $reminder->title,
                'amount' => null,
                'due_date' => $reminder->due_date?->toDateString(),
                'href' => route('reminders.index'),
            ]));

        LoanInstallment::forUser($user)->with('loan:id,title')->where('status', 'pending')->whereDate('due_date', '<=', Carbon::now()->addDays(30))->orderBy('due_date')->limit(5)->get()
            ->each(fn (LoanInstallment $installment) => $items->push([
                'key' => 'installment-'.$installment->id,
                'kind' => 'installment',
                'title' => __('قسط :title', ['title' => $installment->loan?->title ?? __('وام')]),
                'amount' => (float) $installment->amount,
                'due_date' => $installment->due_date?->toDateString(),
                'href' => route('loans.show', $installment->loan_id),
            ]));

        Check::forUser($user)->where('status', 'pending')->whereDate('due_date', '<=', Carbon::now()->addDays(30))->orderBy('due_date')->limit(5)->get()
            ->each(function (Check $check) use ($items): void {
                $payable = $check->type->value === 'payable';
                $number = $check->check_number;

                $items->push([
                    'key' => 'check-'.$check->id,
                    'kind' => 'check',
                    'title' => match (true) {
                        $payable && $number !== null => __('چک پرداختنی :number', ['number' => $number]),
                        $payable => __('چک پرداختنی'),
                        $number !== null => __('چک دریافتنی :number', ['number' => $number]),
                        default => __('چک دریافتنی'),
                    },
                    'amount' => (float) $check->amount,
                    'due_date' => $check->due_date?->toDateString(),
                    'href' => route('checks.index'),
                ]);
            });

        Debt::forUser($user)->with('person:id,full_name')->whereIn('status', ['open', 'partially_settled', 'overdue'])->orderByRaw('due_date is null, due_date asc')->limit(5)->get()
            ->each(function (Debt $debt) use ($items): void {
                $name = $debt->person?->full_name ?? __('شخص');

                $items->push([
                    'key' => 'debt-'.$debt->id,
                    'kind' => 'debt',
                    'title' => $debt->type->value === 'payable' ? __('بدهی به :name', ['name' => $name]) : __('طلب از :name', ['name' => $name]),
                    'amount' => (float) $debt->remaining_amount,
                    'due_date' => $debt->due_date?->toDateString(),
                    'href' => $debt->person_id ? route('people.show', $debt->person_id) : route('debts.index'),
                ]);
            });

        return $items
            ->sortBy(fn (array $item) => $item['due_date'] ?? '9999-12-31')
            ->values()
            ->all();
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function currentMonth(): array
    {
        return [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()];
    }
}
