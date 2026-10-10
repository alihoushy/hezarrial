<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;

/**
 * A reset request for an unknown address answers exactly like a known one, so the form
 * cannot be used to find out who has an account. Only throttling is reported.
 */
class PasswordResetLinkFailedResponse implements FailedPasswordResetLinkRequestResponse
{
    public function __construct(private readonly string $status) {}

    public function toResponse($request)
    {
        if ($this->status === Password::INVALID_USER) {
            return back()->with('status', __(Password::RESET_LINK_SENT));
        }

        return back()->withInput($request->only('email'))->withErrors(['email' => __($this->status)]);
    }
}
