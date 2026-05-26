<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('settings.index', ['settings' => auth()->user()->settings ?? []]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'currency_display' => ['required', Rule::in(['rial', 'toman', 'both'])],
            'persian_digits' => ['sometimes', 'boolean'],
            'theme' => ['required', Rule::in(['system', 'light', 'dark'])],
            'session_timeout_minutes' => ['nullable', 'integer', 'min:5', 'max:1440'],
            'recurring_mode' => ['required', Rule::in(['suggestion', 'automatic'])],
        ]);

        $request->user()->forceFill([
            'settings' => [
                ...($request->user()->settings ?? []),
                ...$data,
                'persian_digits' => $request->boolean('persian_digits'),
            ],
        ])->save();

        return back()->with('status', 'تنظیمات ذخیره شد.');
    }
}
