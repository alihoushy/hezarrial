<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Must exist before the view renders: Vite tags and the inline scripts in app.blade.php carry it.
        $nonce = Vite::useCspNonce();

        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        // The camera stays available to this site for attaching receipts; nothing else is needed.
        $response->headers->set('Permissions-Policy', 'camera=(self), microphone=(), geolocation=(), payment=(), usb=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if ($request->isSecure() && app()->isProduction()) {
            // No includeSubDomains/preload on purpose: those are promises about every subdomain.
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        // The Vite dev server serves scripts and HMR from another origin.
        if (Vite::isRunningHot()) {
            return $response;
        }

        $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self' 'nonce-{$nonce}'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");

        return $response;
    }
}
