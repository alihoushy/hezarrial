<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountRequest;
use App\Models\Account;
use App\Services\Accounting\AccountBalanceService;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(): View
    {
        return view('accounts.index', ['accounts' => Account::forUser(auth()->user())->orderBy('sort_order')->get()]);
    }

    public function create(): View
    {
        return view('accounts.form', ['account' => new Account()]);
    }

    public function store(AccountRequest $request): RedirectResponse
    {
        $account = Account::create([
            ...$request->validated(),
            'user_id' => auth()->id(),
            'current_balance' => $request->opening_balance,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('accounts.show', $account)->with('status', 'حساب ساخته شد.');
    }

    public function show(Account $account): View
    {
        $this->authorize('view', $account);

        return view('accounts.show', [
            'account' => $account,
            'transactions' => $account->transactions()->with(['category', 'person'])->latest('transaction_date')->paginate(20),
        ]);
    }

    public function edit(Account $account): View
    {
        $this->authorize('update', $account);

        return view('accounts.form', ['account' => $account]);
    }

    public function update(AccountRequest $request, Account $account, AuditLogService $audit): RedirectResponse
    {
        $this->authorize('update', $account);
        $old = $account->toArray();
        $account->update($request->validated() + ['is_active' => $request->boolean('is_active', true)]);
        $audit->record('account.updated', $account, $old, $account->fresh()->toArray());

        return redirect()->route('accounts.show', $account)->with('status', 'حساب به‌روز شد.');
    }

    public function destroy(Account $account, AuditLogService $audit): RedirectResponse
    {
        $this->authorize('delete', $account);
        $old = $account->toArray();
        $account->update(['is_active' => false]);
        $account->delete();
        $audit->record('account.archived', $account, $old);

        return redirect()->route('accounts.index')->with('status', 'حساب بایگانی شد.');
    }

    public function recalculate(Account $account, AccountBalanceService $balances): RedirectResponse
    {
        $this->authorize('update', $account);
        $result = $balances->recalculate($account, true);

        return back()->with('status', 'مانده حساب بازسازی شد. اختلاف: '.number_format($result['difference']));
    }
}
