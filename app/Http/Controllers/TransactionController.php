<?php

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use App\Http\Requests\TransactionRequest;
use App\Models\Account;
use App\Models\Category;
use App\Models\Person;
use App\Models\Transaction;
use App\Services\Accounting\TransactionService;
use App\Services\Accounting\TransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(): View
    {
        return view('transactions.index', [
            'transactions' => Transaction::forUser(auth()->user())->with(['account', 'category', 'person'])->latest('transaction_date')->latest('id')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Transaction());
    }

    public function store(TransactionRequest $request, TransactionService $transactions, TransferService $transfers): RedirectResponse
    {
        $data = $request->validated();

        if ($data['type'] === TransactionType::TransferOut->value || $data['type'] === 'transfer') {
            $transfers->create($request->user(), $data);
        } else {
            $transactions->create($request->user(), $data);
        }

        return $request->has('save_add_another')
            ? redirect()->route('transactions.create')->with('status', 'تراکنش ذخیره شد.')
            : redirect()->route('transactions.index')->with('status', 'تراکنش ذخیره شد.');
    }

    public function show(Transaction $transaction): View
    {
        $this->authorize('view', $transaction);

        return view('transactions.show', ['transaction' => $transaction->load(['account', 'category', 'person'])]);
    }

    public function edit(Transaction $transaction): View
    {
        $this->authorize('update', $transaction);

        return $this->form($transaction);
    }

    public function update(TransactionRequest $request, Transaction $transaction, TransactionService $transactions): RedirectResponse
    {
        $this->authorize('update', $transaction);
        $transactions->update($transaction, $request->validated());

        return redirect()->route('transactions.show', $transaction)->with('status', 'تراکنش به‌روز شد.');
    }

    public function destroy(Transaction $transaction, TransactionService $transactions): RedirectResponse
    {
        $this->authorize('delete', $transaction);
        $transactions->delete($transaction);

        return redirect()->route('transactions.index')->with('status', 'تراکنش حذف شد.');
    }

    private function form(Transaction $transaction): View
    {
        $user = auth()->user();

        return view('transactions.form', [
            'transaction' => $transaction,
            'accounts' => Account::forUser($user)->where('is_active', true)->get(),
            'categories' => Category::forUser($user)->where('is_active', true)->orderBy('sort_order')->get(),
            'people' => Person::forUser($user)->where('is_active', true)->get(),
        ]);
    }
}
