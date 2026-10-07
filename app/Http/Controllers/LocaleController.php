<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    /**
     * Switch the interface language. Open to guests so the login screen can
     * be switched too; for a signed-in user it is saved with their settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', Rule::in(array_keys(config('app.supported_locales')))],
        ]);

        if ($user = $request->user()) {
            $user->forceFill(['settings' => [...($user->settings ?? []), 'locale' => $data['locale']]])->save();
        }

        Cookie::queue(Cookie::forever('locale', $data['locale']));

        return back();
    }
}
