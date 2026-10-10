<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs the user out after the idle time chosen in Settings (default two hours).
 * The session lifetime alone is not enough: it is one value for everybody.
 */
class EnforceSessionTimeout
{
    public const DEFAULT_MINUTES = 120;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $minutes = (int) ($user->settings['session_timeout_minutes'] ?? self::DEFAULT_MINUTES);
        $lastActivity = (int) $request->session()->get('last_activity_at', now()->timestamp);

        if ($minutes > 0 && now()->timestamp - $lastActivity > $minutes * 60) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            Inertia::clearHistory();

            return redirect()->route('login')->with('status', __('به دلیل بی‌فعالیتی از حساب خارج شدید. دوباره وارد شوید.'));
        }

        $request->session()->put('last_activity_at', now()->timestamp);

        return $next($request);
    }
}
