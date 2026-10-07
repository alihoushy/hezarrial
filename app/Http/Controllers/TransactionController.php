<?php

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use App\Http\Presenters\Present;
use App\Http\Requests\TransactionRequest;
use App\Models\Account;
use App\Models\Category;
use App\Models\Person;
use App\Models\Transaction;
use App\Services\Accounting\TransactionService;
use App\Services\Accounting\TransferService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('transactions/index', [
            'transactions' => Inertia::scroll(fn () => Transaction::forUser(auth()->user())
                ->with(['account', 'category', 'person'])
                ->latest('transaction_date')
                ->latest('id')
                ->paginate(25)
                ->through(Present::transaction(...))),
        ]);
    }

    public function create(): Response
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
            ? redirect()->route('transactions.create', ['type' => $data['type']])->with('status', __('تراکنش ذخیره شد.'))
            : redirect()->route('transactions.index')->with('status', __('تراکنش ذخیره شد.'));
    }

    public function show(Transaction $transaction): Response
    {
        $this->authorize('view', $transaction);

        return Inertia::render('transactions/show', [
            'transaction' => Present::transaction($transaction->load(['account', 'category', 'person'])),
        ]);
    }

    public function edit(Transaction $transaction): Response
    {
        $this->authorize('update', $transaction);

        return $this->form($transaction);
    }

    public function update(TransactionRequest $request, Transaction $transaction, TransactionService $transactions): RedirectResponse
    {
        $this->authorize('update', $transaction);
        $transactions->update($transaction, $request->validated());

        return redirect()->route('transactions.show', $transaction)->with('status', __('تراکنش به‌روز شد.'));
    }

    public function destroy(Transaction $transaction, TransactionService $transactions): RedirectResponse
    {
        $this->authorize('delete', $transaction);
        $transactions->delete($transaction);

        return redirect()->route('transactions.index')->with('status', __('تراکنش حذف شد.'));
    }

    private function form(Transaction $transaction): Response
    {
        $user = auth()->user();

        return Inertia::render('transactions/form', [
            'transaction' => $transaction->exists ? Present::transactionForm($transaction) : null,
            'initialType' => request('type'),
            'accounts' => Account::forUser($user)->where('is_active', true)->orderBy('sort_order')->get(['id', 'name']),
            'categories' => Category::forUser($user)->where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'type', 'color']),
            'people' => Person::forUser($user)->where('is_active', true)->orderBy('full_name')->get(['id', 'full_name']),
        ]);
    }
}
