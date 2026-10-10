<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates POST /api/sms/ingest with a personal token (Settings > SMS) that only holds
 * the "sms:ingest" ability. Some shared hosts strip the Authorization header, so the token may
 * also arrive as X-Ingest-Token.
 *
 * Transitional: until the owner switches their Shortcut to a personal token, the old shared
 * SMS_INGEST_TOKEN still works for the user in SMS_INGEST_USER_ID.
 */
class AuthenticateSmsIngest
{
    public const ABILITY = 'sms:ingest';

    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken() ?? $request->header('X-Ingest-Token');

        if (! $plain) {
            abort(401, 'Missing ingest token.');
        }

        $user = $this->userForPersonalToken($plain) ?? $this->userForLegacyToken($plain);

        if (! $user) {
            abort(401, 'Invalid ingest token.');
        }

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }

    private function userForPersonalToken(string $plain): ?User
    {
        $token = PersonalAccessToken::findToken($plain);

        if (! $token || ! $token->can(self::ABILITY) || ($token->expires_at && $token->expires_at->isPast())) {
            return null;
        }

        $user = $token->tokenable;

        if (! $user instanceof User || ! $user->hasVerifiedEmail()) {
            return null;
        }

        $token->forceFill(['last_used_at' => now()])->save();

        return $user;
    }

    private function userForLegacyToken(string $plain): ?User
    {
        $legacy = config('services.sms_ingest.token');
        $userId = config('services.sms_ingest.user_id');

        if (! $legacy || ! $userId || ! hash_equals((string) $legacy, $plain)) {
            return null;
        }

        return User::query()->find($userId);
    }
}
