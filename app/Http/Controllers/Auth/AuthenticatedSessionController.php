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
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (User::query()->doesntExist()) {
            return redirect()->route('setup');
        }

        return view('auth.login');
    }

    public function setup(): View|RedirectResponse
    {
        if (User::query()->exists()) {
            return redirect()->route('login');
        }

        return view('auth.setup');
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

        return redirect()->route('login');
    }
}
