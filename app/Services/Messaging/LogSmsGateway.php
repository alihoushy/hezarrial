<?php

namespace App\Services\Messaging;

use Illuminate\Support\Facades\Log;

/** Development and tests: writes the message to the log instead of sending it. */
class LogSmsGateway implements SmsGateway
{
    public function send(string $mobile, string $text): void
    {
        Log::info('SMS to '.$mobile.': '.$text);
    }

    public function sendVerificationCode(string $mobile, string $code): void
    {
        Log::info('SMS verification code for '.$mobile.': '.$code);
    }
}
