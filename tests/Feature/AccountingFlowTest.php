<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use App\Services\Accounting\AccountBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountingFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Account $bank;
    private Account $cash;
    private Category $income;
    private Category $expense;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create(['name' => 'مالک', 'email' => 'owner@example.com', 'password' => Hash::make('password-password')]);
        $this->bank = Account::create(['user_id' => $this->user->id, 'name' => 'بانک', 'type' => 'bank', 'opening_balance' => 100000, 'current_balance' => 100000]);
        $this->cash = Account::create(['user_id' => $this->user->id, 'name' => 'نقد', 'type' => 'cash', 'opening_balance' => 0, 'current_balance' => 0]);
        $this->income = Category::create(['user_id' => $this->user->id, 'name' => 'حقوق', 'type' => 'income']);
        $this->expense = Category::create(['user_id' => $this->user->id, 'name' => 'خوراک', 'type' => 'expense']);
    }

    public function test_income_and_expense_update_balance(): void
    {
        $this->actingAs($this->user)->post(route('transactions.store'), [
            'account_id' => $this->bank->id,
            'category_id' => $this->income->id,
            'type' => 'income',
            'amount' => 50000,
            'transaction_date' => '2026-05-26',
        ])->assertRedirect(route('transactions.index'));

        $this->actingAs($this->user)->post(route('transactions.store'), [
            'account_id' => $this->bank->id,
            'category_id' => $this->expense->id,
            'type' => 'expense',
            'amount' => 20000,
            'transaction_date' => '2026-05-26',
        ])->assertRedirect(route('transactions.index'));

        $this->assertEquals(130000.0, (float) $this->bank->fresh()->current_balance);
    }

    public function test_transfer_creates_two_linked_transactions(): void
    {
        $this->actingAs($this->user)->post(route('transactions.store'), [
            'account_id' => $this->bank->id,
            'destination_account_id' => $this->cash->id,
            'type' => 'transfer_out',
            'amount' => 25000,
            'transaction_date' => '2026-05-26',
        ])->assertRedirect(route('transactions.index'));

        $this->assertEquals(75000.0, (float) $this->bank->fresh()->current_balance);
        $this->assertEquals(25000.0, (float) $this->cash->fresh()->current_balance);
        $this->assertDatabaseCount('transactions', 2);
    }

    public function test_recalculate_balances_reports_difference(): void
    {
        $this->bank->forceFill(['current_balance' => 10])->save();

        $result = app(AccountBalanceService::class)->recalculate($this->bank, true);

        $this->assertSame(99990.0, $result['difference']);
        $this->assertEquals(100000.0, (float) $this->bank->fresh()->current_balance);
    }
}
