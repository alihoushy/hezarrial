<?php

namespace App\Http\Controllers;

use App\Services\Messaging\MobileVerifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Adds, verifies and removes the mobile number of the signed-in user. */
class MobileController extends Controller
{
    public function sendCode(Request $request, MobileVerifier $verifier): RedirectResponse
    {
        $verifier->start($request->user(), $request->input('mobile'));

        return back()->with('status', __('کد تأیید پیامک شد.'));
    }

    public function verify(Request $request, MobileVerifier $verifier): RedirectResponse
    {
        $verifier->confirm($request->user(), $request->input('code'));

        return back()->with('status', __('شماره‌ی موبایل تأیید شد.'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->forceFill(['mobile' => null, 'mobile_verified_at' => null])->save();

        return back()->with('status', __('شماره‌ی موبایل حذف شد.'));
    }
}
