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

    public function test_user_cannot_access_another_users_account(): void
    {
        $owner = User::forceCreate(['email_verified_at' => now(), 'name' => 'مالک', 'email' => 'owner@example.com', 'password' => Hash::make('password-password')]);
        $other = User::forceCreate(['email_verified_at' => now(), 'name' => 'دیگری', 'email' => 'other@example.com', 'password' => Hash::make('password-password')]);
        $account = Account::create(['user_id' => $other->id, 'name' => 'بانک', 'type' => 'bank', 'opening_balance' => 0, 'current_balance' => 0]);

        $this->actingAs($owner)->get(route('accounts.show', $account))->assertForbidden();
    }
}
