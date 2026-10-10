<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * In production, every page has one address: the scheme and host of APP_URL. The apex domain,
 * http:// and any other alias redirect there permanently, so search engines see no duplicates.
 */
class CanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->isProduction() || ! $request->isMethodSafe() || $request->is('up')) {
            return $next($request);
        }

        $canonical = parse_url((string) config('app.url'));
        $host = $canonical['host'] ?? null;
        $scheme = $canonical['scheme'] ?? 'https';

        if ($host && (strcasecmp($request->getHost(), $host) !== 0 || $request->getScheme() !== $scheme)) {
            return redirect()->away($scheme.'://'.$host.$request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
