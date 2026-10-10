<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** The public website is written in Persian; a visitor's app language does not change it. */
class SiteLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale('fa');

        return $next($request);
    }
}
