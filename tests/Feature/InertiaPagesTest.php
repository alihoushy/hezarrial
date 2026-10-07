<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Check;
use App\Models\Debt;
use App\Models\Person;
use App\Models\RecurringTransaction;
use App\Models\Reminder;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Accounting\LoanService;
use App\Services\Backup\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class InertiaPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Account $account;
    private Category $category;
    private Person $person;
    private Transaction $transaction;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->user = User::create(['name' => 'مالک', 'email' => 'owner@example.com', 'password' => Hash::make('password-password')]);
        $this->account = Account::create(['user_id' => $this->user->id, 'name' => 'بانک', 'type' => 'bank', 'opening_balance' => 1000000, 'current_balance' => 1000000]);
        $this->category = Category::create(['user_id' => $this->user->id, 'name' => 'خوراک', 'type' => 'expense']);
        $this->person = Person::create(['user_id' => $this->user->id, 'full_name' => 'علی رضایی']);
        $this->transaction = Transaction::create(['user_id' => $this->user->id, 'account_id' => $this->account->id, 'category_id' => $this->category->id, 'type' => 'expense', 'amount' => 25000, 'transaction_date' => now()->toDateString()]);
    }

    public function test_every_page_renders_its_inertia_component(): void
    {
        $loan = app(LoanService::class)->create($this->user, [
            'account_id' => $this->account->id,
            'title' => 'وام خودرو',
            'principal_amount' => 120000,
            'total_payable_amount' => 144000,
            'installment_amount' => 12000,
            'installment_count' => 12,
            'start_date' => now()->toDateString(),
            'period' => 'monthly',
        ]);
        $backup = app(BackupService::class)->create($this->user);
        Debt::create(['user_id' => $this->user->id, 'person_id' => $this->person->id, 'type' => 'payable', 'original_amount' => 5000, 'remaining_amount' => 5000, 'status' => 'open']);
        Check::create(['user_id' => $this->user->id, 'type' => 'payable', 'amount' => 9000, 'due_date' => now()->addWeek()->toDateString(), 'status' => 'pending']);
        Budget::create(['user_id' => $this->user->id, 'category_id' => $this->category->id, 'title' => 'خوراک', 'period' => 'monthly', 'amount' => 100000, 'start_date' => now()->startOfMonth()->toDateString(), 'alert_threshold_percent' => 80, 'is_active' => true]);
        Reminder::create(['user_id' => $this->user->id, 'title' => 'قبض', 'due_date' => now()->toDateString(), 'status' => 'pending']);
        RecurringTransaction::create(['user_id' => $this->user->id, 'account_id' => $this->account->id, 'type' => 'expense', 'amount' => 1000, 'title' => 'اجاره', 'frequency' => 'monthly', 'next_run_date' => now()->toDateString(), 'is_active' => true]);

        $pages = [
            [route('dashboard'), 'dashboard'],
            [route('home'), 'dashboard'],
            [route('transactions.index'), 'transactions/index'],
            [route('transactions.create'), 'transactions/form'],
            [route('transactions.show', $this->transaction), 'transactions/show'],
            [route('transactions.edit', $this->transaction), 'transactions/form'],
            [route('accounts.index'), 'accounts/index'],
            [route('accounts.create'), 'accounts/form'],
            [route('accounts.show', $this->account), 'accounts/show'],
            [route('accounts.edit', $this->account), 'accounts/form'],
            [route('categories.index'), 'categories/index'],
            [route('categories.create'), 'categories/form'],
            [route('categories.edit', $this->category), 'categories/form'],
            [route('people.index'), 'people/index'],
            [route('people.create'), 'people/form'],
            [route('people.show', $this->person), 'people/show'],
            [route('people.edit', $this->person), 'people/form'],
            [route('debts.index'), 'debts/index'],
            [route('loans.index'), 'loans/index'],
            [route('loans.show', $loan), 'loans/show'],
            [route('checks.index'), 'checks/index'],
            [route('budgets.index'), 'budgets/index'],
            [route('reminders.index'), 'reminders/index'],
            [route('recurring.index'), 'recurring/index'],
            [route('reports.index'), 'reports/index'],
            [route('reports.monthly'), 'reports/index'],
            [route('reports.accounts'), 'reports/cards'],
            [route('reports.categories'), 'reports/cards'],
            [route('reports.people'), 'reports/cards'],
            [route('reports.loans'), 'reports/cards'],
            [route('reports.checks'), 'reports/cards'],
            [route('backups.index'), 'backups/index'],
            [route('imports.index'), 'imports/index'],
            [route('settings.index'), 'settings/index'],
        ];

        foreach ($pages as [$url, $component]) {
            $this->actingAs($this->user)->get($url)
                ->assertOk()
                ->assertInertia(fn (AssertableInertia $page) => $page->component($component));
        }

        $this->assertNotNull($backup->id);
    }

    public function test_auth_pages_render_for_guests(): void
    {
        $this->get(route('setup'))->assertRedirect(route('login'));

        $this->get(route('login'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->component('auth/login'));

        User::query()->delete();

        $this->get(route('setup'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->component('auth/setup'));
        $this->get(route('login'))->assertRedirect(route('setup'));
    }

    public function test_dashboard_defers_charts_and_upcoming_items(): void
    {
        $this->actingAs($this->user)->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('dashboard')
                ->where('summary.expense', 25000)
                ->where('summary.total_balance', 1000000)
                ->has('accounts', 1)
                ->has('recent', 1)
                ->missing('charts')
                ->missing('upcoming')
                ->loadDeferredProps(fn (AssertableInertia $loaded) => $loaded
                    ->has('charts.daily')
                    ->where('charts.categories.0.name', 'خوراک')
                    ->where('charts.categories.0.value', 25000)
                    ->has('upcoming')));
    }

    public function test_transactions_are_paginated_for_infinite_scroll(): void
    {
        $this->actingAs($this->user)->get(route('transactions.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('transactions/index')
                ->has('transactions.data', 1)
                ->where('transactions.data.0.amount', 25000)
                ->where('transactions.data.0.direction', -1)
                ->where('transactions.data.0.date', now()->toDateString()));
    }

    public function test_status_messages_reach_the_client_as_flash_data(): void
    {
        $this->actingAs($this->user)
            ->post(route('categories.store'), ['name' => 'سفر', 'type' => 'expense'])
            ->assertRedirect(route('categories.index'));

        $this->get(route('categories.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->component('categories/index')->hasFlash('status', 'دسته‌بندی ساخته شد.'));

        // The message is shown once, not again on the next navigation.
        $this->get(route('categories.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->missingFlash('status'));
    }

    public function test_validation_errors_are_shared_with_the_page(): void
    {
        $this->actingAs($this->user)
            ->from(route('categories.create'))
            ->post(route('categories.store'), ['name' => '', 'type' => 'expense'])
            ->assertRedirect(route('categories.create'));

        $this->get(route('categories.create'))
            ->assertInertia(fn (AssertableInertia $page) => $page->component('categories/form')->has('errors.name'));
    }

    public function test_saved_theme_is_shared_and_applied_before_first_paint(): void
    {
        $this->actingAs($this->user)->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('settings.theme', 'system'));

        $this->actingAs($this->user)->put(route('settings.update'), [
            'currency_display' => 'both',
            'theme' => 'dark',
            'recurring_mode' => 'suggestion',
        ])->assertRedirect();

        $this->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('settings.theme', 'dark'));

        // The server renders the class itself, so a dark user never sees a light flash.
        $this->get(route('dashboard'))->assertSee('<html lang="fa" dir="rtl" class="dark">', false);

        $this->user->forceFill(['settings' => ['theme' => 'neon']])->save();
        $this->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('settings.theme', 'system'));
    }

    public function test_forbidden_and_missing_pages_render_the_error_component(): void
    {
        $other = User::create(['name' => 'دیگری', 'email' => 'other@example.com', 'password' => Hash::make('password-password')]);
        $foreign = Account::create(['user_id' => $other->id, 'name' => 'بانک', 'type' => 'bank', 'opening_balance' => 0, 'current_balance' => 0]);

        $this->actingAs($this->user)->get(route('accounts.show', $foreign))
            ->assertForbidden()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('error')->where('status', 403));

        $this->actingAs($this->user)->get('/no-such-page')
            ->assertNotFound()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('error')->where('status', 404));
    }

    public function test_api_clients_keep_json_error_responses(): void
    {
        config(['services.sms_ingest.token' => 'test-ingest-token-secret']);

        $this->postJson('/api/sms/ingest', ['message' => 'واریز مبلغ 500,000 ریال'])
            ->assertStatus(401)
            ->assertHeaderMissing('X-Inertia')
            ->assertJsonStructure(['message']);
    }
}
