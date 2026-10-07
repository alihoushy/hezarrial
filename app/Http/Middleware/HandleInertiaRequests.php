<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Controllers report outcomes with `->with('status', ...)`. Re-flash that
     * message as Inertia flash data so the client shows it once as a toast
     * instead of keeping it in browser history like a regular prop.
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->hasSession() && filled($status = $request->session()->get('status'))) {
            Inertia::flash('status', $status);
        }

        return parent::handle($request, $next);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user?->only(['id', 'name', 'email', 'mobile']),
            ],
            'settings' => fn () => [
                'currency_display' => $user?->settings['currency_display'] ?? 'both',
                'persian_digits' => (bool) ($user?->settings['persian_digits'] ?? true),
                'theme' => in_array($user?->settings['theme'] ?? null, ['light', 'dark'], true) ? $user->settings['theme'] : 'system',
            ],
            // Plain (non-Inertia) form posts such as file downloads need the
            // current token; the one in the initial HTML goes stale on login.
            'csrf_token' => Inertia::always(fn () => csrf_token()),
        ];
    }
}
