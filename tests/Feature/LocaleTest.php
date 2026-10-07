<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $settings = []): User
    {
        return User::create(['name' => 'مالک', 'email' => 'owner@example.com', 'password' => Hash::make('password-password'), 'settings' => $settings]);
    }

    public function test_persian_rtl_is_the_default(): void
    {
        $this->user();

        $this->get(route('login'))
            ->assertSee('<html lang="fa" dir="rtl"', false)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('locale.code', 'fa')
                ->where('locale.dir', 'rtl')
                ->has('locales', 2));
    }

    public function test_a_guest_can_switch_the_login_screen_language(): void
    {
        $this->user();

        $this->post(route('locale.update'), ['locale' => 'en'])->assertRedirect()->assertCookie('locale', 'en');

        $this->withCookie('locale', 'en')->get(route('login'))
            ->assertSee('<html lang="en" dir="ltr"', false)
            ->assertInertia(fn (AssertableInertia $page) => $page->where('locale.code', 'en')->where('locale.dir', 'ltr'));
    }

    public function test_a_signed_in_users_language_is_saved_with_their_settings(): void
    {
        $user = $this->user(['theme' => 'dark']);

        $this->actingAs($user)->post(route('locale.update'), ['locale' => 'en'])->assertRedirect();

        $this->assertSame('en', $user->fresh()->settings['locale']);
        $this->assertSame('dark', $user->fresh()->settings['theme'], 'other settings are kept');

        $this->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('locale.code', 'en')->where('locale.dir', 'ltr'));

        // The saved language beats a stale cookie.
        $this->withCookie('locale', 'fa')->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('locale.code', 'en'));
    }

    public function test_unsupported_languages_are_rejected_and_unknown_stored_values_fall_back(): void
    {
        $user = $this->user(['locale' => 'xx']);

        $this->actingAs($user)->post(route('locale.update'), ['locale' => 'xx'])->assertSessionHasErrors('locale');

        $this->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('locale.code', config('app.locale')));
    }

    public function test_settings_can_change_the_language_and_confirm_in_it(): void
    {
        $user = $this->user();

        $this->actingAs($user)->put(route('settings.update'), [
            'currency_display' => 'both',
            'theme' => 'system',
            'recurring_mode' => 'suggestion',
            'locale' => 'en',
        ])->assertRedirect();

        $this->assertSame('en', $user->fresh()->settings['locale']);
    }

    public function test_validation_messages_follow_the_language(): void
    {
        $user = $this->user(['locale' => 'en']);

        $this->actingAs($user)->post(route('categories.store'), ['name' => '', 'type' => 'expense'])
            ->assertSessionHasErrors(['name' => 'The name field is required.']);

        $user->forceFill(['settings' => ['locale' => 'fa']])->save();

        $this->actingAs($user)->post(route('categories.store'), ['name' => '', 'type' => 'expense'])
            ->assertSessionHasErrors(['name' => 'نام الزامی است.']);
    }
}
