<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('site')->group(__DIR__.'/../routes/site.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The public website: no session, no cookies, so a crawler does not create a database
        // row per page it fetches. The contact form is the only public page in the "web" group.
        $middleware->group('site', [
            \App\Http\Middleware\CanonicalHost::class,
            \App\Http\Middleware\SiteLocale::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->web(prepend: [\App\Http\Middleware\CanonicalHost::class], append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\EnforceSessionTimeout::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
