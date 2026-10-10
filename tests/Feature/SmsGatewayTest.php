<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\Channels\SmsChannel;
use App\Services\Messaging\LogSmsGateway;
use App\Services\Messaging\SmsException;
use App\Services\Messaging\SmsGateway;
use App\Services\Messaging\SmsIrGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsGatewayTest extends TestCase
{
    use RefreshDatabase;

    private function gateway(?string $template = null, ?string $key = 'secret-key', ?string $line = '30001234'): SmsIrGateway
    {
        return new SmsIrGateway($key, $line, $template, 'Code');
    }

    public function test_a_free_text_message_goes_to_the_bulk_endpoint_with_the_api_key(): void
    {
        Http::fake(['api.sms.ir/*' => Http::response(['status' => 1, 'message' => 'موفق', 'data' => []])]);

        $this->gateway()->send('09121234567', 'سلام');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.sms.ir/v1/send/bulk'
            && $request->hasHeader('X-API-KEY', 'secret-key')
            && $request['lineNumber'] === 30001234
            && $request['messageText'] === 'سلام'
            && $request['mobiles'] === ['09121234567']);
    }

    public function test_a_verification_code_uses_the_template_when_one_is_configured(): void
    {
        Http::fake(['api.sms.ir/*' => Http::response(['status' => 1, 'message' => 'موفق', 'data' => ['messageId' => 1]])]);

        $this->gateway('12345')->sendVerificationCode('09121234567', '654321');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.sms.ir/v1/send/verify'
            && $request['mobile'] === '09121234567'
            && $request['templateId'] === 12345
            && $request['parameters'] === [['name' => 'Code', 'value' => '654321']]);
    }

    public function test_without_a_template_the_code_is_sent_as_free_text(): void
    {
        Http::fake(['api.sms.ir/*' => Http::response(['status' => 1])]);

        $this->gateway(null)->sendVerificationCode('09121234567', '654321');

        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/send/bulk') && str_contains($request['messageText'], '654321'));
    }

    public function test_a_rejected_request_raises_an_error_that_does_not_leak_secrets(): void
    {
        Http::fake(['api.sms.ir/*' => Http::response(['status' => 101, 'message' => 'کلید نامعتبر'], 200)]);

        try {
            $this->gateway()->send('09121234567', 'متن محرمانه');
            $this->fail('Expected an SmsException.');
        } catch (SmsException $exception) {
            $this->assertStringContainsString('status 101', $exception->getMessage());
            $this->assertStringNotContainsString('secret-key', $exception->getMessage());
            $this->assertStringNotContainsString('متن محرمانه', $exception->getMessage());
        }
    }

    public function test_server_errors_and_missing_settings_are_errors_too(): void
    {
        Http::fake(['api.sms.ir/*' => Http::response('', 500)]);
        $this->expectException(SmsException::class);
        $this->gateway()->send('09121234567', 'x');
    }

    public function test_missing_credentials_fail_before_any_request(): void
    {
        Http::fake();

        foreach ([fn () => $this->gateway(key: null)->send('09121234567', 'x'), fn () => $this->gateway(line: null)->send('09121234567', 'x')] as $attempt) {
            try {
                $attempt();
                $this->fail('Expected an SmsException.');
            } catch (SmsException) {
            }
        }

        Http::assertNothingSent();
    }

    public function test_the_driver_is_chosen_from_the_configuration(): void
    {
        config(['services.sms.driver' => 'log']);
        $this->assertInstanceOf(LogSmsGateway::class, app(SmsGateway::class));

        config(['services.sms.driver' => 'smsir']);
        $this->assertInstanceOf(SmsIrGateway::class, app(SmsGateway::class));
    }

    private function notification(): Notification
    {
        return new class extends Notification
        {
            public function toSms(object $notifiable): string
            {
                return 'یادآوری';
            }
        };
    }

    public function test_the_sms_channel_only_texts_verified_numbers_and_respects_the_daily_limit(): void
    {
        $sent = [];
        $this->app->instance(SmsGateway::class, new class($sent) implements SmsGateway
        {
            public function __construct(public array &$sent) {}

            public function send(string $mobile, string $text): void
            {
                $this->sent[] = [$mobile, $text];
            }

            public function sendVerificationCode(string $mobile, string $code): void {}
        });
        config(['services.sms.daily_limit_per_user' => 2]);

        $channel = app(SmsChannel::class);
        $noNumber = User::forceCreate(['name' => 'الف', 'email' => 'a@example.com', 'password' => 'x']);
        $unverified = User::forceCreate(['name' => 'ب', 'email' => 'b@example.com', 'password' => 'x', 'mobile' => '09120000001']);
        $verified = User::forceCreate(['name' => 'پ', 'email' => 'c@example.com', 'password' => 'x', 'mobile' => '09120000002', 'mobile_verified_at' => now()]);

        $channel->send($noNumber, $this->notification());
        $channel->send($unverified, $this->notification());
        $this->assertSame([], $sent);

        foreach (range(1, 4) as $ignored) {
            $channel->send($verified, $this->notification());
        }

        $this->assertSame([['09120000002', 'یادآوری'], ['09120000002', 'یادآوری']], $sent);
    }
}
