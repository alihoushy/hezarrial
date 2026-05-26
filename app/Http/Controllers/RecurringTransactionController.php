<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\RecurringTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RecurringTransactionController extends Controller
{
    public function index(): View
    {
        return view('recurring.index', [
            'items' => RecurringTransaction::forUser(auth()->user())->orderBy('next_run_date')->get(),
            'accounts' => Account::forUser(auth()->user())->where('is_active', true)->get(),
            'categories' => Category::forUser(auth()->user())->where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        RecurringTransaction::create([
            ...$request->validate([
                'account_id' => ['required', Rule::exists('accounts', 'id')->where('user_id', auth()->id())],
                'category_id' => ['nullable', Rule::exists('categories', 'id')->where('user_id', auth()->id())],
                'type' => ['required', Rule::in(['income', 'expense', 'transfer', 'debt', 'loan_installment'])],
                'amount' => ['required', 'numeric', 'min:0.01'],
                'title' => ['required', 'string', 'max:160'],
                'description' => ['nullable', 'string', 'max:1000'],
                'frequency' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'yearly', 'custom'])],
                'next_run_date' => ['required', 'date'],
                'end_date' => ['nullable', 'date', 'after_or_equal:next_run_date'],
            ]),
            'user_id' => auth()->id(),
            'is_active' => true,
        ]);

        return back()->with('status', 'تراکنش تکرارشونده ثبت شد.');
    }

    public function update(Request $request, RecurringTransaction $recurring): RedirectResponse
    {
        abort_unless((int) $recurring->user_id === (int) auth()->id(), 403);
        $recurring->update(['is_active' => $request->boolean('is_active')]);

        return back()->with('status', 'وضعیت تراکنش تکرارشونده تغییر کرد.');
    }
}
