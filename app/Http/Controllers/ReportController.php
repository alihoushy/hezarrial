<?php

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Check;
use App\Models\Debt;
use App\Models\Loan;
use App\Models\Person;
use App\Models\Transaction;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        $month = [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()];

        return Inertia::render('reports/index', [
            'monthlyIncome' => (float) Transaction::forUser($user)->where('type', TransactionType::Income)->whereBetween('transaction_date', $month)->sum('amount'),
            'monthlyExpense' => (float) Transaction::forUser($user)->where('type', TransactionType::Expense)->whereBetween('transaction_date', $month)->sum('amount'),
        ]);
    }

    public function monthly(): Response { return $this->index(); }

    public function accounts(): Response
    {
        return $this->cards('گزارش حساب‌ها', Account::forUser(auth()->user())->get()->map(fn (Account $account) => [
            'id' => $account->id,
            'title' => $account->name,
            'amount' => (float) $account->current_balance,
            'href' => route('accounts.show', $account),
        ]));
    }

    public function categories(): Response
    {
        return $this->cards('گزارش دسته‌بندی‌ها', Category::forUser(auth()->user())->get()->map(fn (Category $category) => [
            'id' => $category->id,
            'title' => $category->name,
            'amount' => null,
            'href' => route('categories.edit', $category),
        ]));
    }

    public function people(): Response
    {
        return $this->cards('گزارش اشخاص', Person::forUser(auth()->user())->get()->map(fn (Person $person) => [
            'id' => $person->id,
            'title' => $person->full_name,
            'amount' => null,
            'href' => route('people.show', $person),
        ]));
    }

    public function loans(): Response
    {
        return $this->cards('گزارش وام‌ها', Loan::forUser(auth()->user())->get()->map(fn (Loan $loan) => [
            'id' => $loan->id,
            'title' => $loan->title,
            'amount' => (float) $loan->principal_amount,
            'href' => route('loans.show', $loan),
        ]));
    }

    public function checks(): Response
    {
        return $this->cards('گزارش چک‌ها', Check::forUser(auth()->user())->get()->map(fn (Check $check) => [
            'id' => $check->id,
            'title' => $check->check_number ?: 'چک',
            'amount' => (float) $check->amount,
            'href' => route('checks.index'),
        ]));
    }

    public function debts(): Response
    {
        return $this->cards('گزارش طلب و بدهی', Debt::forUser(auth()->user())->with('person')->get()->map(fn (Debt $debt) => [
            'id' => $debt->id,
            'title' => $debt->person?->full_name ?? 'مورد',
            'amount' => (float) $debt->remaining_amount,
            'href' => route('debts.index'),
        ]));
    }

    private function cards(string $title, $items): Response
    {
        return Inertia::render('reports/cards', ['title' => $title, 'items' => $items]);
    }
}
