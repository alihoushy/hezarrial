<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_user_setup_logs_in_owner(): void
    {
        $this->post('/setup', [
            'name' => 'مالک',
            'email' => 'owner@example.com',
            'password' => 'very-secure-password',
            'password_confirmation' => 'very-secure-password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'owner@example.com']);
    }

    public function test_setup_is_disabled_after_first_user(): void
    {
        User::create(['name' => 'مالک', 'email' => 'owner@example.com', 'password' => Hash::make('password-password')]);

        $this->get('/setup')->assertRedirect('/login');
    }

    public function test_user_cannot_access_another_users_account(): void
    {
        $owner = User::create(['name' => 'مالک', 'email' => 'owner@example.com', 'password' => Hash::make('password-password')]);
        $other = User::create(['name' => 'دیگری', 'email' => 'other@example.com', 'password' => Hash::make('password-password')]);
        $account = Account::create(['user_id' => $other->id, 'name' => 'بانک', 'type' => 'bank', 'opening_balance' => 0, 'current_balance' => 0]);

        $this->actingAs($owner)->get(route('accounts.show', $account))->assertForbidden();
    }
}
