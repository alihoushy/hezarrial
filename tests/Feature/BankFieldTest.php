<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Check;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BankFieldTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create(['name' => 'مالک', 'email' => 'owner@example.com', 'password' => Hash::make('password-password')]);
    }

    private function accountPayload(array $extra = []): array
    {
        return ['name' => 'حقوق', 'type' => 'bank', 'opening_balance' => 0, ...$extra];
    }

    public function test_account_can_be_created_with_a_known_bank(): void
    {
        $this->actingAs($this->user)->post(route('accounts.store'), $this->accountPayload(['bank' => 'mellat', 'bank_name' => 'متن قدیمی']))->assertRedirect();

        $this->assertDatabaseHas('accounts', ['name' => 'حقوق', 'bank' => 'mellat', 'bank_name' => null]);
    }

    public function test_account_keeps_a_typed_name_for_banks_outside_the_list(): void
    {
        $this->actingAs($this->user)->post(route('accounts.store'), $this->accountPayload(['bank_name' => 'بانک محلی']))->assertRedirect();

        $this->assertDatabaseHas('accounts', ['bank' => null, 'bank_name' => 'بانک محلی']);
    }

    public function test_unknown_bank_slug_is_rejected(): void
    {
        $this->actingAs($this->user)->post(route('accounts.store'), $this->accountPayload(['bank' => 'not-a-bank']))->assertSessionHasErrors('bank');
    }

    public function test_account_pages_expose_the_bank_and_the_picker_options(): void
    {
        $account = Account::create(['user_id' => $this->user->id, 'name' => 'ملی من', 'type' => 'bank', 'bank' => 'melli', 'opening_balance' => 0, 'current_balance' => 0]);

        $this->actingAs($this->user)->get(route('accounts.index'))->assertInertia(fn (Assert $page) => $page
            ->where('accounts.0.bank', 'melli')
            ->where('accounts.0.bank_label', 'بانک ملی ایران'));

        $this->actingAs($this->user)->get(route('accounts.edit', $account))->assertInertia(fn (Assert $page) => $page
            ->has('banks', fn (Assert $banks) => $banks->where('0.value', 'melli')->where('0.label', 'بانک ملی ایران')->etc()));
    }

    public function test_a_stored_slug_that_is_no_longer_known_does_not_break_the_page(): void
    {
        Account::create(['user_id' => $this->user->id, 'name' => 'قدیمی', 'type' => 'bank', 'bank' => 'gone-bank', 'bank_name' => 'بانک قدیمی', 'opening_balance' => 0, 'current_balance' => 0]);

        $this->actingAs($this->user)->get(route('accounts.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('accounts.0.bank', null)
            ->where('accounts.0.bank_name', 'بانک قدیمی'));
    }

    public function test_check_can_be_created_with_a_known_bank(): void
    {
        $this->actingAs($this->user)->post(route('checks.store'), ['type' => 'payable', 'amount' => 1000, 'due_date' => '2026-12-01', 'bank' => 'saderat'])->assertRedirect();

        $this->assertDatabaseHas('checks', ['bank' => 'saderat']);
        $this->actingAs($this->user)->get(route('checks.index'))->assertInertia(fn (Assert $page) => $page
            ->where('checks.0.bank_label', 'بانک صادرات ایران')
            ->has('banks'));
        $this->assertNotNull(Check::first());
    }
}
