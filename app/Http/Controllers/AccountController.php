<?php

namespace App\Http\Controllers;

use App\Http\Presenters\Present;
use App\Http\Requests\AccountRequest;
use App\Models\Account;
use App\Services\Accounting\AccountBalanceService;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('accounts/index', [
            'accounts' => Account::forUser(auth()->user())->orderBy('sort_order')->get()->map(Present::account(...)),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('accounts/form', ['account' => null]);
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

    public function show(Account $account): Response
    {
        $this->authorize('view', $account);

        return Inertia::render('accounts/show', [
            'account' => Present::account($account),
            'transactions' => Inertia::scroll(fn () => $account->transactions()
                ->with(['account', 'category', 'person'])
                ->latest('transaction_date')
                ->latest('id')
                ->paginate(25)
                ->through(Present::transaction(...))),
        ]);
    }

    public function edit(Account $account): Response
    {
        $this->authorize('update', $account);

        return Inertia::render('accounts/form', ['account' => Present::account($account)]);
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
