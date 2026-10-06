<?php

namespace App\Http\Presenters;

use App\Models\Account;
use App\Models\Backup;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Check;
use App\Models\Debt;
use App\Models\Import;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Person;
use App\Models\RecurringTransaction;
use App\Models\Reminder;
use App\Models\SmsPattern;
use App\Models\Transaction;

/**
 * Shapes models into the props the React pages consume: only the fields a
 * page shows, amounts as numbers, and dates as plain Y-m-d strings so the
 * browser never shifts them across time zones.
 */
class Present
{
    public static function option(?object $model, string $label = 'name'): ?array
    {
        return $model ? ['id' => $model->id, 'name' => $model->{$label}] : null;
    }

    public static function account(Account $account): array
    {
        return [
            'id' => $account->id,
            'name' => $account->name,
            'type' => $account->type->value,
            'bank_name' => $account->bank_name,
            'card_last_four' => $account->card_last_four,
            'opening_balance' => (float) $account->opening_balance,
            'current_balance' => (float) $account->current_balance,
            'is_active' => $account->is_active,
        ];
    }

    public static function transaction(Transaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'type' => $transaction->type->value,
            'direction' => $transaction->type->affectsBalance(),
            'amount' => (float) $transaction->amount,
            'date' => $transaction->transaction_date?->toDateString(),
            'time' => $transaction->transaction_time ? substr($transaction->transaction_time, 0, 5) : null,
            'description' => $transaction->description,
            'reference_number' => $transaction->reference_number,
            'account' => self::option($transaction->account),
            'category' => $transaction->category ? [
                'id' => $transaction->category->id,
                'name' => $transaction->category->name,
                'color' => $transaction->category->color,
            ] : null,
            'person' => self::option($transaction->person, 'full_name'),
        ];
    }

    public static function transactionForm(Transaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'type' => $transaction->type->value,
            'amount' => (float) $transaction->amount,
            'account_id' => $transaction->account_id,
            'category_id' => $transaction->category_id,
            'person_id' => $transaction->person_id,
            'transaction_date' => $transaction->transaction_date?->toDateString(),
            'description' => $transaction->description,
        ];
    }

    public static function category(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'type' => $category->type->value,
            'color' => $category->color,
            'parent_id' => $category->parent_id,
            'is_active' => $category->is_active,
        ];
    }

    public static function person(Person $person): array
    {
        return [
            'id' => $person->id,
            'full_name' => $person->full_name,
            'mobile' => $person->mobile,
            'description' => $person->description,
        ];
    }

    public static function debt(Debt $debt): array
    {
        return [
            'id' => $debt->id,
            'type' => $debt->type->value,
            'status' => $debt->status->value,
            'original_amount' => (float) $debt->original_amount,
            'remaining_amount' => (float) $debt->remaining_amount,
            'due_date' => $debt->due_date?->toDateString(),
            'description' => $debt->description,
            'person' => self::option($debt->person, 'full_name'),
        ];
    }

    public static function loan(Loan $loan): array
    {
        return [
            'id' => $loan->id,
            'title' => $loan->title,
            'lender_name' => $loan->lender_name,
            'status' => $loan->status->value,
            'principal_amount' => (float) $loan->principal_amount,
            'total_payable_amount' => (float) $loan->total_payable_amount,
            'installment_amount' => (float) $loan->installment_amount,
            'installment_count' => (int) $loan->installment_count,
            'paid_installment_count' => (int) $loan->paid_installment_count,
            'start_date' => $loan->start_date?->toDateString(),
        ];
    }

    public static function installment(LoanInstallment $installment): array
    {
        return [
            'id' => $installment->id,
            'due_date' => $installment->due_date?->toDateString(),
            'amount' => (float) $installment->amount,
            'status' => $installment->status->value,
            'paid_at' => $installment->paid_at?->toDateString(),
        ];
    }

    public static function check(Check $check): array
    {
        return [
            'id' => $check->id,
            'type' => $check->type->value,
            'status' => $check->status->value,
            'amount' => (float) $check->amount,
            'due_date' => $check->due_date?->toDateString(),
            'check_number' => $check->check_number,
            'bank_name' => $check->bank_name,
            'account' => self::option($check->account),
            'person' => self::option($check->person, 'full_name'),
        ];
    }

    public static function budget(Budget $budget, array $progress): array
    {
        return [
            'id' => $budget->id,
            'title' => $budget->title,
            'amount' => (float) $budget->amount,
            'start_date' => $budget->start_date?->toDateString(),
            'end_date' => $budget->end_date?->toDateString(),
            'category' => self::option($budget->category),
            'progress' => $progress,
        ];
    }

    public static function reminder(Reminder $reminder): array
    {
        return [
            'id' => $reminder->id,
            'title' => $reminder->title,
            'due_date' => $reminder->due_date?->toDateString(),
            'due_time' => $reminder->due_time ? substr($reminder->due_time, 0, 5) : null,
            'status' => $reminder->status->value,
        ];
    }

    public static function recurring(RecurringTransaction $item): array
    {
        return [
            'id' => $item->id,
            'title' => $item->title,
            'type' => $item->type,
            'amount' => (float) $item->amount,
            'frequency' => $item->frequency,
            'next_run_date' => $item->next_run_date?->toDateString(),
            'end_date' => $item->end_date?->toDateString(),
            'is_active' => $item->is_active,
            'description' => $item->description,
        ];
    }

    public static function backup(Backup $backup): array
    {
        return [
            'id' => $backup->id,
            'file_name' => $backup->file_name,
            'file_size' => (int) $backup->file_size,
            'created_at' => $backup->created_at?->toIso8601String(),
        ];
    }

    public static function import(Import $import): array
    {
        return [
            'id' => $import->id,
            'type' => $import->type,
            'status' => $import->status->value,
            'total_rows' => (int) $import->total_rows,
            'imported_rows' => (int) $import->imported_rows,
            'created_at' => $import->created_at?->toIso8601String(),
        ];
    }

    public static function smsPattern(SmsPattern $pattern): array
    {
        return [
            'id' => $pattern->id,
            'name' => $pattern->name,
            'bank_name' => $pattern->bank_name,
            'is_active' => $pattern->is_active,
        ];
    }
}
