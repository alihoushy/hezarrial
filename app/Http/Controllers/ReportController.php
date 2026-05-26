<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\Check;
use App\Models\Debt;
use App\Models\Loan;
use App\Models\Person;
use App\Models\Transaction;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        return view('reports.index', [
            'monthlyIncome' => Transaction::forUser($user)->where('type', 'income')->whereMonth('transaction_date', now()->month)->sum('amount'),
            'monthlyExpense' => Transaction::forUser($user)->where('type', 'expense')->whereMonth('transaction_date', now()->month)->sum('amount'),
            'accounts' => Account::forUser($user)->get(),
            'categories' => Category::forUser($user)->get(),
        ]);
    }

    public function monthly(): View { return $this->index(); }
    public function accounts(): View { return view('reports.cards', ['title' => 'گزارش حساب‌ها', 'items' => Account::forUser(auth()->user())->get()]); }
    public function categories(): View { return view('reports.cards', ['title' => 'گزارش دسته‌بندی‌ها', 'items' => Category::forUser(auth()->user())->get()]); }
    public function people(): View { return view('reports.cards', ['title' => 'گزارش اشخاص', 'items' => Person::forUser(auth()->user())->get()]); }
    public function loans(): View { return view('reports.cards', ['title' => 'گزارش وام‌ها', 'items' => Loan::forUser(auth()->user())->get()]); }
    public function checks(): View { return view('reports.cards', ['title' => 'گزارش چک‌ها', 'items' => Check::forUser(auth()->user())->get()]); }
    public function debts(): View { return view('reports.cards', ['title' => 'گزارش طلب و بدهی', 'items' => Debt::forUser(auth()->user())->get()]); }
}
