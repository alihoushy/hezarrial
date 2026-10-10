<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SessionTimeoutTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $settings = []): User
    {
        return User::create(['name' => 'مالک', 'email' => 'owner@example.com', 'password' => Hash::make('password-password'), 'settings' => $settings]);
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
}
