<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Debt;
use App\Models\Person;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Accounting\BudgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BudgetAndPeopleTest extends TestCase
{
    use RefreshDatabase;

    public function test_budget_progress_tracks_spending_threshold(): void
    {
        $user = User::create(['name' => 'مالک', 'email' => 'owner@example.com', 'password' => Hash::make('password-password')]);
        $account = Account::create(['user_id' => $user->id, 'name' => 'بانک', 'type' => 'bank', 'opening_balance' => 0, 'current_balance' => 0]);
        $category = Category::create(['user_id' => $user->id, 'name' => 'خوراک', 'type' => 'expense']);
        $budget = Budget::create(['user_id' => $user->id, 'category_id' => $category->id, 'title' => 'ماهانه خوراک', 'period' => 'monthly', 'amount' => 100000, 'start_date' => '2026-05-01', 'alert_threshold_percent' => 80, 'is_active' => true]);
        Transaction::create(['user_id' => $user->id, 'account_id' => $account->id, 'category_id' => $category->id, 'type' => 'expense', 'amount' => 85000, 'transaction_date' => '2026-05-20']);

        $progress = app(BudgetService::class)->progress($budget);

        $this->assertSame(85000.0, $progress['spent']);
        $this->assertSame(15000.0, $progress['remaining']);
        $this->assertSame(85.0, (float) $progress['percent']);
        $this->assertTrue($progress['over_threshold']);
    }

    public function test_person_page_shows_open_financial_summary(): void
    {
        $user = User::create(['name' => 'مالک', 'email' => 'owner@example.com', 'password' => Hash::make('password-password')]);
        $person = Person::create(['user_id' => $user->id, 'full_name' => 'علی رضایی']);
        Debt::create(['user_id' => $user->id, 'person_id' => $person->id, 'type' => 'payable', 'original_amount' => 50000, 'remaining_amount' => 20000, 'status' => 'partially_settled']);
        Debt::create(['user_id' => $user->id, 'person_id' => $person->id, 'type' => 'receivable', 'original_amount' => 80000, 'remaining_amount' => 80000, 'status' => 'open']);

        $this->actingAs($user)->get(route('people.show', $person))
            ->assertOk()
            ->assertSee('بدهی من')
            ->assertSee('طلب من')
            ->assertSee('80,000');
    }
}
