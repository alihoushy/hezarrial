<?php

namespace App\Services\Messaging;

interface SmsGateway
{
    /** Sends a free-text message. $mobile is a normalised Iranian mobile (09xxxxxxxxx). */
    public function send(string $mobile, string $text): void;

    /** Sends a one-time verification code (through a template when the provider has one). */
    public function sendVerificationCode(string $mobile, string $code): void;
}
