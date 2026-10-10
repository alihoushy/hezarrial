<?php

namespace App\Services\Messaging;

use App\Models\User;
use App\Support\Mobile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/** Proves that a user owns a mobile number by sending a one-time code to it. */
class MobileVerifier
{
    private const TTL_MINUTES = 10;

    private const MAX_ATTEMPTS = 5;

    private const DAILY_CODES = 5;

    public function __construct(private readonly SmsGateway $gateway) {}

    public function start(User $user, ?string $input): void
    {
        $mobile = Mobile::normalize($input);

        if (! $mobile) {
            throw ValidationException::withMessages(['mobile' => __('شماره‌ی موبایل درست نیست. مثال: ۰۹۱۲۱۲۳۴۵۶۷')]);
        }

        if (User::query()->where('mobile', $mobile)->whereNotNull('mobile_verified_at')->where('id', '!=', $user->id)->exists()) {
            throw ValidationException::withMessages(['mobile' => __('این شماره قبلاً برای حساب دیگری ثبت شده است.')]);
        }

        // Every code costs an SMS: one per minute, a handful per day.
        $minute = 'mobile-code:'.$user->id;
        $day = 'mobile-code-day:'.$user->id.':'.now()->toDateString();

        if (RateLimiter::tooManyAttempts($minute, 1) || RateLimiter::tooManyAttempts($day, self::DAILY_CODES)) {
            throw ValidationException::withMessages(['mobile' => __('کمی صبر کنید و دوباره درخواست کد بدهید.')]);
        }

        RateLimiter::hit($minute, 60);
        RateLimiter::hit($day, 86400);

        $code = (string) random_int(100000, 999999);
        Cache::put($this->key($user), ['mobile' => $mobile, 'hash' => $this->hash($code), 'attempts' => 0], now()->addMinutes(self::TTL_MINUTES));

        try {
            $this->gateway->sendVerificationCode($mobile, $code);
        } catch (SmsException $exception) {
            Cache::forget($this->key($user));
            Log::error('Mobile verification SMS failed: '.$exception->getMessage());

            throw ValidationException::withMessages(['mobile' => __('ارسال پیامک ممکن نشد. کمی بعد دوباره تلاش کنید.')]);
        }
    }

    public function pendingMobile(User $user): ?string
    {
        return Cache::get($this->key($user))['mobile'] ?? null;
    }

    public function confirm(User $user, ?string $code): void
    {
        $pending = Cache::get($this->key($user));

        if (! $pending) {
            throw ValidationException::withMessages(['code' => __('کد منقضی شده است. دوباره کد بگیرید.')]);
        }

        if ($pending['attempts'] >= self::MAX_ATTEMPTS) {
            Cache::forget($this->key($user));

            throw ValidationException::withMessages(['code' => __('تلاش‌های اشتباه زیاد بود. دوباره کد بگیرید.')]);
        }

        $given = preg_replace('/\D/', '', strtr((string) $code, ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9'])) ?? '';

        if (! hash_equals($pending['hash'], $this->hash($given))) {
            Cache::put($this->key($user), [...$pending, 'attempts' => $pending['attempts'] + 1], now()->addMinutes(self::TTL_MINUTES));

            throw ValidationException::withMessages(['code' => __('کد درست نیست.')]);
        }

        $user->forceFill(['mobile' => $pending['mobile'], 'mobile_verified_at' => now()])->save();
        Cache::forget($this->key($user));
    }

    private function key(User $user): string
    {
        return 'mobile-verify:'.$user->id;
    }

    private function hash(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }
}
