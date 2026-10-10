<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SessionTimeoutTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $settings = []): User
    {
        return User::forceCreate(['email_verified_at' => now(), 'name' => 'مالک', 'email' => 'owner@example.com', 'password' => Hash::make('password-password'), 'settings' => $settings]);
    }

    private function inertiaGet(string $url): \Illuminate\Testing\TestResponse
    {
        return $this->get($url, ['X-Inertia' => 'true', 'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(request())]);
    }

    public function test_idle_user_is_signed_out_after_the_chosen_minutes(): void
    {
        $user = $this->user(['session_timeout_minutes' => 30]);

        $this->actingAs($user)
            ->withSession(['last_activity_at' => now()->subMinutes(31)->timestamp])
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_active_user_stays_signed_in_and_activity_is_recorded(): void
    {
        $user = $this->user(['session_timeout_minutes' => 30]);

        $this->actingAs($user)
            ->withSession(['last_activity_at' => now()->subMinutes(10)->timestamp])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSessionHas('last_activity_at');

        $this->assertAuthenticatedAs($user);
    }

    public function test_default_timeout_is_two_hours(): void
    {
        $user = $this->user();

        $this->actingAs($user)->withSession(['last_activity_at' => now()->subMinutes(110)->timestamp])->get(route('dashboard'))->assertOk();
        $this->actingAs($user)->withSession(['last_activity_at' => now()->subMinutes(130)->timestamp])->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_expired_session_asks_the_browser_to_clear_its_history(): void
    {
        $user = $this->user(['session_timeout_minutes' => 30]);

        $this->actingAs($user)
            ->withSession(['last_activity_at' => now()->subMinutes(31)->timestamp])
            ->get(route('dashboard'))
            ->assertSessionHas('inertia.clear_history');
    }

    public function test_logout_clears_the_browser_history(): void
    {
        $this->actingAs($this->user())->post(route('logout'))->assertRedirect(route('login'))->assertSessionHas('inertia.clear_history');

        $this->inertiaGet(route('login'))->assertJsonPath('clearHistory', true);
    }

    public function test_pages_ask_the_browser_to_encrypt_history(): void
    {
        $this->actingAs($this->user())->inertiaGet(route('dashboard'))->assertOk()->assertJsonPath('encryptHistory', true);
    }
}
