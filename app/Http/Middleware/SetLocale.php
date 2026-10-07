<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * The signed-in user's saved language wins; guests (login screen) use the
     * language they last picked, remembered in a cookie.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $code = $request->user()?->settings['locale'] ?? $request->cookie('locale');

        if (! is_string($code) || ! array_key_exists($code, config('app.supported_locales'))) {
            $code = config('app.locale');
        }

        app()->setLocale($code);

        return $next($request);
    }
}
