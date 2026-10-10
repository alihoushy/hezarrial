<?php

namespace App\Notifications\Channels;

use App\Models\User;
use App\Services\Messaging\SmsGateway;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Sends a notification as an SMS to the user's verified mobile number. SMS costs money, so a
 * notification can be skipped silently: no verified number, or the daily limit is used up.
 * A notification opts in with toSms($notifiable): string.
 */
class SmsChannel
{
    public function __construct(private readonly SmsGateway $gateway) {}

    public function send(User $notifiable, Notification $notification): void
    {
        if (! $notifiable->mobile || ! $notifiable->mobile_verified_at) {
            return;
        }

        $key = 'sms-notifications:'.$notifiable->id.':'.now()->toDateString();
        $limit = (int) config('services.sms.daily_limit_per_user');

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            return;
        }

        RateLimiter::hit($key, 86400);
        $this->gateway->send($notifiable->mobile, $notification->toSms($notifiable));
    }
}
