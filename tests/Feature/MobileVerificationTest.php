<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Messaging\SmsException;
use App\Services\Messaging\SmsGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    /** @var array<int, array{string, string}> */
    private array $codes = [];

    private bool $gatewayFails = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::forceCreate(['name' => 'مالک', 'email' => 'owner@example.com', 'password' => Hash::make('password-password'), 'email_verified_at' => now()]);

        $test = $this;
        $this->app->instance(SmsGateway::class, new class($test) implements SmsGateway
        {
            public function __construct(private MobileVerificationTest $test) {}

            public function send(string $mobile, string $text): void {}

            public function sendVerificationCode(string $mobile, string $code): void
            {
                $this->test->deliver($mobile, $code);
            }
        });
    }

    /** Called by the fake gateway. */
    public function deliver(string $mobile, string $code): void
    {
        if ($this->gatewayFails) {
            throw new SmsException('provider down');
        }

        $this->codes[] = [$mobile, $code];
    }

    private function requestCode(string $mobile = '۰۹۱۲۱۲۳۴۵۶۷')
    {
        return $this->actingAs($this->user)->post(route('mobile.code'), ['mobile' => $mobile]);
    }

    public function test_a_number_is_saved_only_after_the_code_is_entered(): void
    {
        $this->requestCode()->assertSessionHasNoErrors();
        [$mobile, $code] = $this->codes[0];
        $this->assertSame('09121234567', $mobile);
        $this->assertNull($this->user->fresh()->mobile);

        $this->actingAs($this->user)->get(route('account.edit'))->assertInertia(fn ($page) => $page->where('mobile.pending', '09121234567')->where('mobile.verified', false));

        $this->actingAs($this->user)->post(route('mobile.verify'), ['code' => $code])->assertSessionHasNoErrors();

        $user = $this->user->fresh();
        $this->assertSame('09121234567', $user->mobile);
        $this->assertNotNull($user->mobile_verified_at);
    }

    public function test_a_wrong_code_is_refused_and_too_many_wrong_codes_cancel_the_request(): void
    {
        $this->requestCode();
        $code = $this->codes[0][1];
        $wrong = $code === '111111' ? '222222' : '111111';

        foreach (range(1, 5) as $ignored) {
            $this->actingAs($this->user)->post(route('mobile.verify'), ['code' => $wrong])->assertSessionHasErrors('code');
        }

        $this->actingAs($this->user)->post(route('mobile.verify'), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertNull($this->user->fresh()->mobile);
    }

    public function test_a_code_cannot_be_requested_twice_in_a_minute(): void
    {
        $this->requestCode()->assertSessionHasNoErrors();
        $this->requestCode()->assertSessionHasErrors('mobile');
        $this->assertCount(1, $this->codes);

        $this->travel(61)->seconds();
        $this->requestCode()->assertSessionHasNoErrors();
        $this->assertCount(2, $this->codes);
    }

    public function test_a_code_expires_after_ten_minutes(): void
    {
        $this->requestCode();
        $code = $this->codes[0][1];

        $this->travel(11)->minutes();
        $this->actingAs($this->user)->post(route('mobile.verify'), ['code' => $code])->assertSessionHasErrors('code');
    }

    public function test_invalid_numbers_and_numbers_of_other_accounts_are_refused(): void
    {
        $this->requestCode('02112345678')->assertSessionHasErrors('mobile');

        User::forceCreate(['name' => 'دیگری', 'email' => 'other@example.com', 'password' => 'x', 'mobile' => '09121234567', 'mobile_verified_at' => now()]);
        $this->travel(61)->seconds();
        $this->requestCode()->assertSessionHasErrors('mobile');
        $this->assertSame([], $this->codes);
    }

    public function test_a_provider_failure_is_reported_without_leaking_details(): void
    {
        $this->gatewayFails = true;

        $response = $this->requestCode();

        $response->assertSessionHasErrors('mobile');
        $this->assertStringNotContainsString('provider down', session('errors')->first('mobile'));
        $this->actingAs($this->user)->post(route('mobile.verify'), ['code' => '123456'])->assertSessionHasErrors('code');
    }

    public function test_the_number_can_be_removed(): void
    {
        $this->user->forceFill(['mobile' => '09121234567', 'mobile_verified_at' => now()])->save();

        $this->actingAs($this->user)->delete(route('mobile.destroy'))->assertRedirect();

        $this->assertNull($this->user->fresh()->mobile);
        $this->assertNull($this->user->fresh()->mobile_verified_at);
    }

    public function test_only_a_verified_number_can_be_used_to_sign_in(): void
    {
        $this->user->forceFill(['mobile' => '09121234567'])->save();

        $this->post(route('login.store'), ['login' => '09121234567', 'password' => 'password-password'])->assertSessionHasErrors('login');
        $this->assertGuest();

        $this->user->forceFill(['mobile_verified_at' => now()])->save();
        $this->post(route('login.store'), ['login' => '۰۹۱۲۱۲۳۴۵۶۷', 'password' => 'password-password'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->user);
    }
}
