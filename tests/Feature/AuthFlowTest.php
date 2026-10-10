<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Support\Registration;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    private function owner(array $extra = []): User
    {
        return User::forceCreate(['name' => 'مالک', 'email' => 'owner@example.com', 'password' => Hash::make('password-password'), 'email_verified_at' => now(), ...$extra]);
    }

    /** Sign-up input as the form sends it: an honest token that is a few seconds old. */
    private function signUp(array $override = []): array
    {
        $token = Registration::token();
        $this->travel(10)->seconds();

        return ['name' => 'سارا', 'email' => 'Sara@Example.com', 'password' => 'a-long-password-1', 'password_confirmation' => 'a-long-password-1', 'website' => '', 'form_token' => $token, ...$override];
    }

    public function test_the_old_setup_address_redirects_to_register(): void
    {
        $this->get('/setup')->assertStatus(301)->assertRedirect('/register');
    }

    public function test_registration_is_closed_once_a_user_exists_unless_enabled(): void
    {
        $this->owner();

        $this->get(route('register'))->assertNotFound();
        $this->post(route('register.store'), $this->signUp())->assertNotFound();
        $this->assertSame(1, User::count());

        config(['app.registration_enabled' => true]);
        $this->get(route('register'))->assertOk();
    }

    public function test_the_first_account_on_a_fresh_install_is_verified_and_gets_categories(): void
    {
        $this->post(route('register.store'), $this->signUp())->assertRedirect(route('dashboard'));

        $user = User::first();
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('sara@example.com', $user->email);
        $this->assertSame(17, Category::where('user_id', $user->id)->count());
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_a_later_account_must_verify_its_email_before_using_the_app(): void
    {
        $this->owner();
        config(['app.registration_enabled' => true]);
        Notification::fake();

        $this->post(route('register.store'), $this->signUp())->assertRedirect(route('dashboard'));

        $user = User::where('email', 'sara@example.com')->firstOrFail();
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->assertNull($user->email_verified_at);
        $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));
        $this->get(route('accounts.index'))->assertRedirect(route('verification.notice'));

        $this->get(URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]))->assertRedirect();
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_starter_categories_follow_the_language_chosen_at_sign_up(): void
    {
        $this->owner();
        config(['app.registration_enabled' => true]);
        $this->withCookie('locale', 'en')->post(route('register.store'), $this->signUp())->assertRedirect();

        $this->assertDatabaseHas('categories', ['user_id' => User::where('email', 'sara@example.com')->value('id'), 'name' => 'Salary']);
    }

    public function test_bots_are_turned_away(): void
    {
        config(['app.registration_enabled' => true]);
        $this->owner();

        $this->post(route('register.store'), $this->signUp(['website' => 'http://spam.example']))->assertSessionHasErrors('email');
        $this->post(route('register.store'), $this->signUp(['form_token' => 'garbage']))->assertSessionHasErrors('email');

        $fast = ['form_token' => Registration::token()] + $this->signUp();
        $fast['form_token'] = Registration::token();
        $this->post(route('register.store'), $fast)->assertSessionHasErrors('email');

        $this->assertSame(1, User::count());
    }

    public function test_weak_and_duplicate_input_is_rejected(): void
    {
        config(['app.registration_enabled' => true]);
        $this->owner();

        $this->post(route('register.store'), $this->signUp(['password' => 'short1', 'password_confirmation' => 'short1']))->assertSessionHasErrors('password');
        $this->post(route('register.store'), $this->signUp(['password' => 'onlyletterslong', 'password_confirmation' => 'onlyletterslong']))->assertSessionHasErrors('password');
        $this->post(route('register.store'), $this->signUp(['email' => 'OWNER@example.com']))->assertSessionHasErrors('email');
    }

    public function test_sign_in_with_email_or_mobile(): void
    {
        $this->owner(['mobile' => '09120000000']);

        $this->post(route('login.store'), ['login' => 'OWNER@example.com', 'password' => 'password-password'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->post(route('logout'));

        $this->post(route('login.store'), ['login' => '09120000000', 'password' => 'password-password'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_wrong_credentials_are_rejected_and_throttled(): void
    {
        $this->owner();

        $this->post(route('login.store'), ['login' => 'owner@example.com', 'password' => 'nope'])->assertSessionHasErrors('login');
        $this->post(route('login.store'), ['login' => 'nobody@example.com', 'password' => 'nope'])->assertSessionHasErrors('login');
        $this->assertGuest();

        foreach (range(1, 5) as $ignored) {
            $this->post(route('login.store'), ['login' => 'owner@example.com', 'password' => 'nope']);
        }
        $this->post(route('login.store'), ['login' => 'owner@example.com', 'password' => 'password-password'])->assertStatus(429);
    }

    public function test_signing_out_clears_the_browser_history(): void
    {
        $this->actingAs($this->owner())->post(route('logout'))->assertRedirect(route('login'))->assertSessionHas('inertia.clear_history');
        $this->assertGuest();
    }

    public function test_a_reset_request_does_not_reveal_whether_an_address_is_registered(): void
    {
        Notification::fake();
        $user = $this->owner();

        $known = $this->post(route('password.email'), ['email' => $user->email]);
        $unknown = $this->post(route('password.email'), ['email' => 'nobody@example.com']);

        $known->assertSessionHasNoErrors();
        $unknown->assertSessionHasNoErrors();
        $this->assertSame(session('status'), $known->getSession()->get('status'));
        $this->assertSame($known->getSession()->get('status'), $unknown->getSession()->get('status'));
        Notification::assertSentTo($user, ResetPassword::class);
        Notification::assertCount(1);
    }

    public function test_a_password_can_be_reset_with_a_valid_link(): void
    {
        $user = $this->owner();
        $token = Password::createToken($user);

        $this->post(route('password.update'), ['token' => $token, 'email' => $user->email, 'password' => 'brand-new-pass-9', 'password_confirmation' => 'brand-new-pass-9'])
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('brand-new-pass-9', $user->fresh()->password));
        $this->post(route('password.update'), ['token' => $token, 'email' => $user->email, 'password' => 'another-pass-99', 'password_confirmation' => 'another-pass-99'])
            ->assertSessionHasErrors('email');
    }
}
