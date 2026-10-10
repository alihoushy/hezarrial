<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Backup;
use App\Models\User;
use App\Services\Backup\BackupService;
use App\Support\UserAgent;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::forceCreate(['name' => 'مالک', 'email' => 'owner@example.com', 'password' => Hash::make('password-password'), 'email_verified_at' => now()]);
    }

    /** Skips the "confirm your password" step that guards the sensitive pages. */
    private function confirmed(): static
    {
        return $this->actingAs($this->user)->withSession(['auth.password_confirmed_at' => time()]);
    }

    public function test_the_profile_can_be_updated_and_a_new_email_must_be_verified_again(): void
    {
        Notification::fake();

        $this->actingAs($this->user)->put(route('user-profile-information.update'), ['name' => 'مالک جدید', 'email' => 'New@Example.com'])->assertRedirect();

        $user = $this->user->fresh();
        $this->assertSame('مالک جدید', $user->name);
        $this->assertSame('new@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_changing_the_password_needs_the_current_one_and_ends_other_sessions(): void
    {
        DB::table('sessions')->insert(['id' => 'other-device', 'user_id' => $this->user->id, 'ip_address' => '1.2.3.4', 'user_agent' => 'x', 'payload' => '', 'last_activity' => time()]);

        $this->actingAs($this->user)->put(route('user-password.update'), ['current_password' => 'wrong', 'password' => 'brand-new-pass-9', 'password_confirmation' => 'brand-new-pass-9'])->assertSessionHasErrors('current_password');
        $this->assertDatabaseHas('sessions', ['id' => 'other-device']);

        $this->actingAs($this->user)->put(route('user-password.update'), ['current_password' => 'password-password', 'password' => 'brand-new-pass-9', 'password_confirmation' => 'brand-new-pass-9'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('brand-new-pass-9', $this->user->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'other-device']);
    }

    public function test_the_security_page_needs_a_recent_password_confirmation(): void
    {
        $this->actingAs($this->user)->get(route('security.edit'))->assertRedirect(route('password.confirm'));

        $this->confirmed()->get(route('security.edit'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('settings/security')
            ->where('twoFactor.enabled', false)
            ->has('sessions'));
    }

    public function test_confirming_the_password_really_checks_the_password(): void
    {
        $this->actingAs($this->user)->post(route('password.confirm.store'), ['password' => 'wrong'])->assertSessionHasErrors('password');
        $this->assertSame('رمز عبور درست نیست.', session('errors')->first('password'));

        $this->actingAs($this->user)->get(route('security.edit'))->assertRedirect(route('password.confirm'));
        $this->post(route('password.confirm.store'), ['password' => 'password-password'])->assertRedirect(route('security.edit'));
        $this->get(route('security.edit'))->assertOk();
    }

    public function test_two_factor_setup_shows_the_qr_code_only_until_it_is_confirmed(): void
    {
        $this->confirmed()->post(route('two-factor.enable'))->assertRedirect();

        $this->confirmed()->get(route('security.edit'))->assertInertia(fn (Assert $page) => $page
            ->where('twoFactor.enabled', true)
            ->where('twoFactor.confirmed', false)
            ->where('twoFactor.qr_svg', fn ($svg) => str_contains($svg, '<svg'))
            ->where('twoFactor.setup_key', fn ($key) => strlen($key) >= 16)
            ->where('twoFactor.recovery_codes', []));

        $secret = decrypt($this->user->fresh()->two_factor_secret);
        $code = (new \PragmaRX\Google2FA\Google2FA)->getCurrentOtp($secret);
        $this->confirmed()->post(route('two-factor.confirm'), ['code' => $code])->assertSessionHasNoErrors();

        $this->confirmed()->get(route('security.edit'))->assertInertia(fn (Assert $page) => $page
            ->where('twoFactor.confirmed', true)
            ->where('twoFactor.qr_svg', null)
            ->where('twoFactor.setup_key', null)
            ->has('twoFactor.recovery_codes', 8));
    }

    public function test_sign_in_asks_for_the_second_factor_once_it_is_on(): void
    {
        $this->confirmed()->post(route('two-factor.enable'));
        $google = new \PragmaRX\Google2FA\Google2FA;
        $this->confirmed()->post(route('two-factor.confirm'), ['code' => $google->getCurrentOtp(decrypt($this->user->fresh()->two_factor_secret))]);
        $this->post(route('logout'));

        $this->post(route('login.store'), ['login' => 'owner@example.com', 'password' => 'password-password'])->assertRedirect(route('two-factor.login'));
        $this->assertGuest();

        // The code that confirmed the setup is spent (replays are refused), so use the next 30-second window.
        $next = $google->oathTotp(decrypt($this->user->fresh()->two_factor_secret), $google->getTimestamp() + 1);
        $this->post(route('two-factor.login.store'), ['code' => $next])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_other_devices_can_be_signed_out_with_the_password(): void
    {
        DB::table('sessions')->insert(['id' => 'other-device', 'user_id' => $this->user->id, 'ip_address' => '1.2.3.4', 'user_agent' => 'x', 'payload' => '', 'last_activity' => time()]);

        $this->actingAs($this->user)->post(route('security.sign-out-others'), ['password' => 'nope'])->assertSessionHasErrors('password');
        $this->assertDatabaseHas('sessions', ['id' => 'other-device']);

        $this->actingAs($this->user)->post(route('security.sign-out-others'), ['password' => 'password-password'])->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('sessions', ['id' => 'other-device']);
    }

    public function test_the_full_export_contains_only_the_users_own_data(): void
    {
        $other = User::forceCreate(['name' => 'دیگری', 'email' => 'other@example.com', 'password' => Hash::make('password-password'), 'email_verified_at' => now()]);
        Account::create(['user_id' => $this->user->id, 'name' => 'حساب من', 'type' => 'bank', 'opening_balance' => 0, 'current_balance' => 0]);
        Account::create(['user_id' => $other->id, 'name' => 'حساب دیگری', 'type' => 'bank', 'opening_balance' => 0, 'current_balance' => 0]);

        $response = $this->confirmed()->get(route('account.export'))->assertOk();
        $json = $response->streamedContent();

        $this->assertStringContainsString('حساب من', $json);
        $this->assertStringNotContainsString('حساب دیگری', $json);
        $this->assertStringNotContainsString('password', $json);
        $this->assertSame(1, json_decode($json, true)['schema_version']);
    }

    public function test_deleting_the_account_removes_everything_and_needs_the_password(): void
    {
        Storage::fake('local');
        Account::create(['user_id' => $this->user->id, 'name' => 'حساب من', 'type' => 'bank', 'opening_balance' => 0, 'current_balance' => 0]);
        $backup = app(BackupService::class)->create($this->user);

        $this->actingAs($this->user)->delete(route('account.destroy'), ['password' => 'wrong'])->assertSessionHasErrors('password');
        $this->assertDatabaseHas('users', ['id' => $this->user->id]);

        $this->actingAs($this->user)->delete(route('account.destroy'), ['password' => 'password-password'])->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $this->user->id]);
        $this->assertDatabaseCount('accounts', 0);
        $this->assertDatabaseCount('backups', 0);
        Storage::disk('local')->assertMissing($backup->file_path);
    }

    public function test_user_agents_are_summarised_for_humans(): void
    {
        $this->assertSame('Chrome · Windows', UserAgent::summary('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36'));
        $this->assertSame('Safari · iOS', UserAgent::summary('Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1'));
        $this->assertSame('?', UserAgent::summary(null));
    }
}
