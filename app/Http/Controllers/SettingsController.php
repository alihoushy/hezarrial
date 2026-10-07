<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(): Response
    {
        $settings = auth()->user()->settings ?? [];

        return Inertia::render('settings/index', [
            'preferences' => [
                'currency_display' => $settings['currency_display'] ?? 'both',
                'persian_digits' => (bool) ($settings['persian_digits'] ?? true),
                'theme' => $settings['theme'] ?? 'system',
                'session_timeout_minutes' => (int) ($settings['session_timeout_minutes'] ?? 120),
                'recurring_mode' => $settings['recurring_mode'] ?? 'suggestion',
                'locale' => app()->getLocale(),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'currency_display' => ['required', Rule::in(['rial', 'toman', 'both'])],
            'persian_digits' => ['sometimes', 'boolean'],
            'theme' => ['required', Rule::in(['system', 'light', 'dark'])],
            'session_timeout_minutes' => ['nullable', 'integer', 'min:5', 'max:1440'],
            'recurring_mode' => ['required', Rule::in(['suggestion', 'automatic'])],
            'locale' => ['sometimes', Rule::in(array_keys(config('app.supported_locales')))],
        ]);

        $request->user()->forceFill([
            'settings' => [
                ...($request->user()->settings ?? []),
                ...$data,
                'persian_digits' => $request->boolean('persian_digits'),
            ],
        ])->save();

        // Confirm in the language that was just chosen, and remember it for the login screen.
        if (isset($data['locale'])) {
            app()->setLocale($data['locale']);
            Cookie::queue(Cookie::forever('locale', $data['locale']));
        }

        return back()->with('status', __(__('تنظیمات ذخیره شد.')));
    }
}
