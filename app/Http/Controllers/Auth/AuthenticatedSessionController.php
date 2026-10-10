<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\SetupUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        if (User::query()->doesntExist()) {
            return redirect()->route('setup');
        }

        return Inertia::render('auth/login');
    }

    public function setup(): Response|RedirectResponse
    {
        if (User::query()->exists()) {
            return redirect()->route('login');
        }

        return Inertia::render('auth/setup');
    }

    public function storeSetup(SetupUserRequest $request): RedirectResponse
    {
        $user = User::create([
            ...$request->safe()->except('password_confirmation'),
            'password' => Hash::make($request->password),
            'settings' => ['currency_display' => 'both', 'persian_digits' => true, 'theme' => 'system'],
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        // Without this the Back button would still show the last financial pages after signing out.
        Inertia::clearHistory();

        return redirect()->route('login');
    }
}
