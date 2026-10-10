<?php

namespace App\Http\Responses;

use Inertia\Inertia;
use Laravel\Fortify\Contracts\LogoutResponse;

/** After signing out, the browser must forget the financial pages it still holds in history. */
class SignedOutResponse implements LogoutResponse
{
    public function toResponse($request)
    {
        Inertia::clearHistory();

        return redirect()->route('login');
    }
}
