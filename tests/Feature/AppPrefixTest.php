<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AppPrefixTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::forceCreate(['name' => 'مالک', 'email' => 'owner@example.com', 'password' => Hash::make('password-password'), 'email_verified_at' => now()]);
    }

    public function test_the_app_lives_under_app(): void
    {
        $user = $this->user();

        $this->assertSame('/app', parse_url(route('dashboard'), PHP_URL_PATH));
        $this->assertSame('/app/transactions', parse_url(route('transactions.index'), PHP_URL_PATH));
        $this->assertSame('/app/settings/security', parse_url(route('security.edit'), PHP_URL_PATH));
        $this->actingAs($user)->get('/app')->assertOk();
    }

    public function test_old_addresses_redirect_permanently_and_keep_the_rest_of_the_path(): void
    {
        $this->get('/dashboard')->assertStatus(301)->assertRedirect('/app');
        $this->get('/transactions')->assertStatus(301)->assertRedirect('/app/transactions');
        $this->get('/transactions/12/edit?from=list')->assertStatus(301)->assertRedirect('/app/transactions/12/edit?from=list');
        $this->get('/settings/account')->assertStatus(301)->assertRedirect('/app/settings/account');
    }

    public function test_addresses_that_were_never_the_apps_are_still_not_found(): void
    {
        $this->get('/nothing-here')->assertNotFound();
        $this->get('/transactionsx')->assertNotFound();
    }

    public function test_guests_cannot_open_the_app(): void
    {
        $this->get('/app/accounts')->assertRedirect(route('login'));
    }

    public function test_the_root_leads_to_the_app_or_the_sign_in_page(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->actingAs($this->user())->get('/')->assertRedirect(route('dashboard'));
    }
}
