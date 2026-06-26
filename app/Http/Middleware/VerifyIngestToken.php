<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyIngestToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = config('services.sms_ingest.token');

        if (! $configuredToken) {
            abort(503, 'SMS ingest is not configured.');
        }

        $providedToken = $request->bearerToken()
            ?? $request->header('X-Ingest-Token');

        if (! $providedToken || ! hash_equals($configuredToken, $providedToken)) {
            abort(401, 'Invalid ingest token.');
        }

        return $next($request);
    }
}
