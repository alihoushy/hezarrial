<?php

namespace App\Http\Controllers;

use App\Http\Presenters\Present;
use App\Http\Requests\DebtRequest;
use App\Http\Requests\SettlementRequest;
use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Check;
use App\Models\Debt;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Person;
use App\Services\Accounting\BudgetService;
use App\Services\Accounting\CheckService;
use App\Services\Accounting\DebtService;
use App\Services\Accounting\LoanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SimpleModuleController extends Controller
{
    private const OPEN_DEBT_STATUSES = ['open', 'partially_settled', 'overdue'];

    public function people(): Response
    {
        $user = auth()->user();

        // Net open balance per person: positive means they owe me.
        $balances = Debt::forUser($user)
            ->whereIn('status', self::OPEN_DEBT_STATUSES)
            ->get(['person_id', 'type', 'remaining_amount'])
            ->groupBy('person_id')
            ->map(fn (Collection $debts) => (float) $debts->sum(
                fn (Debt $debt) => $debt->type->value === 'receivable' ? $debt->remaining_amount : -$debt->remaining_amount
            ));

        return Inertia::render('people/index', [
            'people' => Person::forUser($user)->latest()->get()->map(fn (Person $person) => [
                ...Present::person($person),
                'balance' => $balances->get($person->id, 0.0),
            ]),
        ]);
    }

    public function createPerson(): Response
    {
        return Inertia::render('people/form', ['person' => null]);
    }

    public function storePerson(Request $request): RedirectResponse
    {
        $data = $request->validate(['full_name' => ['required', 'string', 'max:160'], 'mobile' => ['nullable', 'string', 'max:30'], 'description' => ['nullable', 'string', 'max:1000']]);
        Person::create([...$data, 'user_id' => auth()->id(), 'avatar_color' => '#5b8def']);

        return redirect()->route('people.index')->with('status', 'شخص ذخیره شد.');
    }

    public function showPerson(Person $person): Response
    {
        $this->authorize('view', $person);
        $person->load(['debts', 'transactions.account', 'transactions.category']);

        $open = $person->debts->whereIn('status.value', self::OPEN_DEBT_STATUSES);
        $payable = (float) $open->where('type.value', 'payable')->sum('remaining_amount');
        $receivable = (float) $open->where('type.value', 'receivable')->sum('remaining_amount');

        return Inertia::render('people/show', [
            'person' => Present::person($person),
            'summary' => [
                'payable' => $payable,
                'receivable' => $receivable,
                'net' => $receivable - $payable,
            ],
            'openItems' => $open->values()->map(Present::debt(...)),
            'settledItems' => $person->debts->where('status.value', 'settled')->values()->map(Present::debt(...)),
            'transactions' => $person->transactions->sortByDesc('transaction_date')->values()->map(Present::transaction(...)),
        ]);
    }

    public function editPerson(Person $person): Response
    {
        $this->authorize('update', $person);

        return Inertia::render('people/form', ['person' => Present::person($person)]);
    }

    public function updatePerson(Request $request, Person $person): RedirectResponse
    {
        $this->authorize('update', $person);
        $person->update($request->validate(['full_name' => ['required', 'string', 'max:160'], 'mobile' => ['nullable', 'string', 'max:30'], 'description' => ['nullable', 'string', 'max:1000']]));

        return redirect()->route('people.show', $person)->with('status', 'شخص به‌روز شد.');
    }

    public function deletePerson(Person $person): RedirectResponse
    {
        $this->authorize('delete', $person);
        $person->delete();

        return redirect()->route('people.index')->with('status', 'شخص حذف شد.');
    }

    public function debts(): Response
    {
        $user = auth()->user();

        return Inertia::render('debts/index', [
            'debts' => Debt::forUser($user)->with('person')->latest()->get()->map(Present::debt(...)),
            'people' => Person::forUser($user)->orderBy('full_name')->get(['id', 'full_name']),
            'accounts' => $this->accountOptions(),
        ]);
    }

    public function storeDebt(DebtRequest $request): RedirectResponse
    {
        Debt::create([...$request->validated(), 'user_id' => auth()->id(), 'remaining_amount' => $request->original_amount, 'status' => 'open']);

        return back()->with('status', 'مورد طلب/بدهی ثبت شد.');
    }

    public function settleDebt(SettlementRequest $request, Debt $debt, DebtService $service): RedirectResponse
    {
        $this->authorize('update', $debt);
        $service->settle($debt, $request->validated());

        return back()->with('status', 'تسویه ثبت شد.');
    }

    public function loans(): Response
    {
        return Inertia::render('loans/index', [
            'loans' => Loan::forUser(auth()->user())->latest()->get()->map(Present::loan(...)),
            'accounts' => $this->accountOptions(),
        ]);
    }

    public function storeLoan(Request $request, LoanService $service): RedirectResponse
    {
        $data = $request->validate(['account_id' => ['required', Rule::exists('accounts', 'id')->where('user_id', auth()->id())], 'title' => ['required', 'string', 'max:160'], 'principal_amount' => ['required', 'numeric', 'min:0.01'], 'total_payable_amount' => ['required', 'numeric', 'min:0.01'], 'installment_amount' => ['required', 'numeric', 'min:0.01'], 'installment_count' => ['required', 'integer', 'min:1', 'max:240'], 'start_date' => ['required', 'date'], 'lender_name' => ['nullable', 'string', 'max:160']]);
        $service->create(auth()->user(), $data + ['period' => 'monthly']);

        return back()->with('status', 'وام و برنامه اقساط ساخته شد.');
    }

    public function showLoan(Loan $loan): Response
    {
        $this->authorize('view', $loan);

        return Inertia::render('loans/show', [
            'loan' => Present::loan($loan),
            'installments' => $loan->installments()->orderBy('due_date')->get()->map(Present::installment(...)),
            'accounts' => $this->accountOptions(),
        ]);
    }

    public function payInstallment(Request $request, Loan $loan, LoanInstallment $installment, LoanService $service): RedirectResponse
    {
        $this->authorize('update', $loan);
        abort_unless($installment->loan_id === $loan->id, 404);
        $service->payInstallment($installment, $request->validate(['account_id' => ['required', Rule::exists('accounts', 'id')->where('user_id', auth()->id())], 'transaction_date' => ['nullable', 'date']]));

        return back()->with('status', 'قسط پرداخت شد.');
    }

    public function checks(): Response
    {
        $user = auth()->user();

        return Inertia::render('checks/index', [
            'checks' => Check::forUser($user)->with(['account', 'person'])->orderBy('due_date')->get()->map(Present::check(...)),
            'accounts' => $this->accountOptions(),
            'people' => Person::forUser($user)->orderBy('full_name')->get(['id', 'full_name']),
        ]);
    }

    public function storeCheck(Request $request): RedirectResponse
    {
        Check::create([...$request->validate(['type' => ['required', 'in:payable,receivable'], 'amount' => ['required', 'numeric', 'min:0.01'], 'due_date' => ['required', 'date'], 'check_number' => ['nullable', 'string', 'max:80'], 'bank_name' => ['nullable', 'string', 'max:120'], 'person_id' => ['nullable', Rule::exists('people', 'id')->where('user_id', auth()->id())], 'account_id' => ['nullable', Rule::exists('accounts', 'id')->where('user_id', auth()->id())]]), 'user_id' => auth()->id(), 'status' => 'pending']);

        return back()->with('status', 'چک ثبت شد.');
    }

    public function passCheck(Request $request, Check $check, CheckService $service): RedirectResponse
    {
        $this->authorize('update', $check);
        $service->pass($check, $request->validate(['account_id' => ['required', Rule::exists('accounts', 'id')->where('user_id', auth()->id())]]));

        return back()->with('status', 'چک پاس شد.');
    }

    public function bounceCheck(Check $check): RedirectResponse
    {
        $this->authorize('update', $check);
        $check->update(['status' => 'bounced']);

        return back()->with('status', 'چک برگشتی ثبت شد.');
    }

    public function cancelCheck(Check $check): RedirectResponse
    {
        $this->authorize('update', $check);
        $check->update(['status' => 'cancelled']);

        return back()->with('status', 'چک باطل شد.');
    }

    public function budgets(BudgetService $budgetService): Response
    {
        $user = auth()->user();

        return Inertia::render('budgets/index', [
            'budgets' => Budget::forUser($user)->with('category')->latest()->get()
                ->map(fn (Budget $budget) => Present::budget($budget, $budgetService->progress($budget))),
            'categories' => Category::forUser($user)->where('type', 'expense')->orderBy('sort_order')->get(['id', 'name']),
        ]);
    }

    public function storeBudget(Request $request): RedirectResponse
    {
        Budget::create([...$request->validate(['title' => ['required', 'string', 'max:160'], 'amount' => ['required', 'numeric', 'min:0.01'], 'start_date' => ['required', 'date'], 'end_date' => ['nullable', 'date'], 'category_id' => ['nullable', Rule::exists('categories', 'id')->where('user_id', auth()->id())]]), 'user_id' => auth()->id(), 'period' => 'monthly', 'is_active' => true]);

        return back()->with('status', 'بودجه ثبت شد.');
    }

    private function accountOptions(): Collection
    {
        return Account::forUser(auth()->user())->where('is_active', true)->orderBy('sort_order')->get(['id', 'name']);
    }
}
