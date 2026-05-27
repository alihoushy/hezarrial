<?php

namespace App\Http\Controllers;

use App\Http\Requests\DebtRequest;
use App\Http\Requests\SettlementRequest;
use App\Models\Budget;
use App\Models\Check;
use App\Models\Debt;
use App\Models\Account;
use App\Models\Category;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Person;
use App\Models\Reminder;
use App\Services\Accounting\CheckService;
use App\Services\Accounting\BudgetService;
use App\Services\Accounting\DebtService;
use App\Services\Accounting\LoanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SimpleModuleController extends Controller
{
    public function people(): View { return view('people.index', ['people' => Person::forUser(auth()->user())->latest()->get()]); }
    public function createPerson(): View { return view('people.form', ['person' => new Person()]); }
    public function storePerson(Request $request): RedirectResponse
    {
        $data = $request->validate(['full_name' => ['required', 'string', 'max:160'], 'mobile' => ['nullable', 'string', 'max:30'], 'description' => ['nullable', 'string', 'max:1000']]);
        Person::create([...$data, 'user_id' => auth()->id(), 'avatar_color' => '#5b8def']);
        return redirect()->route('people.index')->with('status', 'شخص ذخیره شد.');
    }
    public function showPerson(Person $person): View
    {
        $this->authorize('view', $person);
        $person->load(['debts', 'transactions.account', 'transactions.category']);

        $payable = (float) $person->debts->where('type.value', 'payable')->whereIn('status.value', ['open', 'partially_settled', 'overdue'])->sum('remaining_amount');
        $receivable = (float) $person->debts->where('type.value', 'receivable')->whereIn('status.value', ['open', 'partially_settled', 'overdue'])->sum('remaining_amount');

        return view('people.show', [
            'person' => $person,
            'summary' => [
                'payable' => $payable,
                'receivable' => $receivable,
                'net' => $receivable - $payable,
                'open_items' => $person->debts->whereIn('status.value', ['open', 'partially_settled', 'overdue'])->values(),
                'settled_items' => $person->debts->where('status.value', 'settled')->values(),
            ],
        ]);
    }
    public function editPerson(Person $person): View { $this->authorize('update', $person); return view('people.form', compact('person')); }
    public function updatePerson(Request $request, Person $person): RedirectResponse
    {
        $this->authorize('update', $person);
        $person->update($request->validate(['full_name' => ['required', 'string', 'max:160'], 'mobile' => ['nullable', 'string', 'max:30'], 'description' => ['nullable', 'string', 'max:1000']]));
        return redirect()->route('people.show', $person)->with('status', 'شخص به‌روز شد.');
    }
    public function deletePerson(Person $person): RedirectResponse { $this->authorize('delete', $person); $person->delete(); return redirect()->route('people.index'); }

    public function debts(): View { return view('debts.index', ['debts' => Debt::forUser(auth()->user())->with('person')->latest()->get(), 'people' => Person::forUser(auth()->user())->get(), 'accounts' => Account::forUser(auth()->user())->where('is_active', true)->get()]); }
    public function storeDebt(DebtRequest $request): RedirectResponse
    {
        Debt::create([...$request->validated(), 'user_id' => auth()->id(), 'remaining_amount' => $request->original_amount, 'status' => 'open']);
        return back()->with('status', 'مورد طلب/بدهی ثبت شد.');
    }
    public function settleDebt(SettlementRequest $request, Debt $debt, DebtService $service): RedirectResponse { $this->authorize('update', $debt); $service->settle($debt, $request->validated()); return back()->with('status', 'تسویه ثبت شد.'); }

    public function loans(): View { return view('loans.index', ['loans' => Loan::forUser(auth()->user())->with('installments')->latest()->get(), 'accounts' => Account::forUser(auth()->user())->where('is_active', true)->get()]); }
    public function storeLoan(Request $request, LoanService $service): RedirectResponse
    {
        $data = $request->validate(['account_id' => ['required', Rule::exists('accounts', 'id')->where('user_id', auth()->id())], 'title' => ['required', 'string', 'max:160'], 'principal_amount' => ['required', 'numeric', 'min:0.01'], 'total_payable_amount' => ['required', 'numeric', 'min:0.01'], 'installment_amount' => ['required', 'numeric', 'min:0.01'], 'installment_count' => ['required', 'integer', 'min:1', 'max:240'], 'start_date' => ['required', 'date'], 'lender_name' => ['nullable', 'string', 'max:160']]);
        $service->create(auth()->user(), $data + ['period' => 'monthly']);
        return back()->with('status', 'وام و برنامه اقساط ساخته شد.');
    }
    public function showLoan(Loan $loan): View { $this->authorize('view', $loan); return view('loans.show', ['loan' => $loan->load('installments')]); }
    public function payInstallment(Request $request, Loan $loan, LoanInstallment $installment, LoanService $service): RedirectResponse
    {
        $this->authorize('update', $loan);
        abort_unless($installment->loan_id === $loan->id, 404);
        $service->payInstallment($installment, $request->validate(['account_id' => ['required', Rule::exists('accounts', 'id')->where('user_id', auth()->id())], 'transaction_date' => ['nullable', 'date']]));
        return back()->with('status', 'قسط پرداخت شد.');
    }

    public function checks(): View { return view('checks.index', ['checks' => Check::forUser(auth()->user())->latest()->get(), 'accounts' => Account::forUser(auth()->user())->where('is_active', true)->get(), 'people' => Person::forUser(auth()->user())->get()]); }
    public function storeCheck(Request $request): RedirectResponse
    {
        Check::create([...$request->validate(['type' => ['required', 'in:payable,receivable'], 'amount' => ['required', 'numeric', 'min:0.01'], 'due_date' => ['required', 'date'], 'check_number' => ['nullable', 'string', 'max:80'], 'bank_name' => ['nullable', 'string', 'max:120'], 'person_id' => ['nullable', Rule::exists('people', 'id')->where('user_id', auth()->id())], 'account_id' => ['nullable', Rule::exists('accounts', 'id')->where('user_id', auth()->id())]]), 'user_id' => auth()->id(), 'status' => 'pending']);
        return back()->with('status', 'چک ثبت شد.');
    }
    public function passCheck(Request $request, Check $check, CheckService $service): RedirectResponse { $this->authorize('update', $check); $service->pass($check, $request->validate(['account_id' => ['required', Rule::exists('accounts', 'id')->where('user_id', auth()->id())]])); return back()->with('status', 'چک پاس شد.'); }
    public function bounceCheck(Check $check): RedirectResponse { $this->authorize('update', $check); $check->update(['status' => 'bounced']); return back(); }
    public function cancelCheck(Check $check): RedirectResponse { $this->authorize('update', $check); $check->update(['status' => 'cancelled']); return back(); }

    public function budgets(BudgetService $budgetService): View
    {
        $budgets = Budget::forUser(auth()->user())->with('category')->latest()->get();

        return view('budgets.index', [
            'budgets' => $budgets,
            'progress' => $budgets->mapWithKeys(fn (Budget $budget) => [$budget->id => $budgetService->progress($budget)]),
            'categories' => Category::forUser(auth()->user())->where('type', 'expense')->get(),
        ]);
    }
    public function storeBudget(Request $request): RedirectResponse
    {
        Budget::create([...$request->validate(['title' => ['required', 'string', 'max:160'], 'amount' => ['required', 'numeric', 'min:0.01'], 'start_date' => ['required', 'date'], 'end_date' => ['nullable', 'date'], 'category_id' => ['nullable', Rule::exists('categories', 'id')->where('user_id', auth()->id())]]), 'user_id' => auth()->id(), 'period' => 'monthly', 'is_active' => true]);
        return back()->with('status', 'بودجه ثبت شد.');
    }
    public function reminders(): View { return view('reminders.index', ['reminders' => Reminder::forUser(auth()->user())->latest()->get()]); }
}
