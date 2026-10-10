<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/** Who may sign up, and the cheap bot checks on the sign-up form. */
class Registration
{
    /** Minimum seconds between showing the form and submitting it; people are slower than scripts. */
    private const MIN_SECONDS = 3;

    private const MAX_SECONDS = 7200;

    /**
     * Open when REGISTRATION_ENABLED is on. A fresh install (no users yet) is always open,
     * so a self-hosted copy can create its first account.
     */
    public static function isOpen(): bool
    {
        if (config('app.registration_enabled')) {
            return true;
        }

        // Public pages ask on every visit; once somebody has an account the answer never changes back.
        if (Cache::get('registration.has-users')) {
            return false;
        }

        if (User::query()->exists()) {
            Cache::forever('registration.has-users', true);

            return false;
        }

        return true;
    }

    /** An opaque token carrying the time the form was rendered. */
    public static function token(): string
    {
        return Crypt::encryptString((string) now()->timestamp);
    }

    /** True when the honeypot is empty and the token is neither too fresh nor too old. */
    public static function looksHuman(array $input): bool
    {
        if (filled($input['website'] ?? null)) {
            return false;
        }

        try {
            $age = now()->timestamp - (int) Crypt::decryptString((string) ($input['form_token'] ?? ''));
        } catch (DecryptException) {
            return false;
        }

        return $age >= self::MIN_SECONDS && $age <= self::MAX_SECONDS;
    }
}
