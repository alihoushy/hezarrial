<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountMailTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::forceCreate(['name' => 'سارا', 'email' => 'owner@example.com', 'password' => Hash::make('password-password'), 'email_verified_at' => now()]);
    }

    public function test_account_mails_follow_the_language(): void
    {
        $user = $this->owner();

        app()->setLocale('fa');
        $fa = (new VerifyEmail)->toMail($user);
        $this->assertSame('تأیید ایمیل '.config('app.name'), $fa->subject);
        $this->assertStringContainsString('سلام سارا', $fa->render());
        $this->assertStringContainsString('dir="rtl"', $fa->render());
        $this->assertStringNotContainsString('All rights reserved', $fa->render());

        app()->setLocale('en');
        $en = (new VerifyEmail)->toMail($user);
        $this->assertStringContainsString('Verify your', $en->subject);
        $this->assertStringContainsString('Hello سارا', $en->render());
        $this->assertStringContainsString('dir="ltr"', $en->render());

        $reset = (new ResetPassword('tok'))->toMail($user);
        $this->assertStringContainsString('/reset-password/tok?email=owner%40example.com', $reset->render());
    }
}
