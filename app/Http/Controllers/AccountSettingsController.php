<?php

namespace App\Http\Controllers;

use App\Services\Backup\BackupService;
use App\Services\Messaging\MobileVerifier;
use App\Support\UserAgent;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Profile, security and the data rights of the signed-in user. */
class AccountSettingsController extends Controller
{
    public function edit(Request $request, MobileVerifier $mobiles): Response
    {
        $user = $request->user();

        return Inertia::render('settings/account', [
            'mobile' => [
                'number' => $user->mobile,
                'verified' => (bool) $user->mobile_verified_at,
                // A code was sent to this number and is waiting to be entered.
                'pending' => $mobiles->pendingMobile($user),
            ],
        ]);
    }

    public function security(Request $request): Response
    {
        $user = $request->user();
        $pending = $user->two_factor_secret && ! $user->two_factor_confirmed_at;

        return Inertia::render('settings/security', [
            'twoFactor' => [
                'enabled' => (bool) $user->two_factor_secret,
                'confirmed' => (bool) $user->two_factor_confirmed_at,
                // Only while the user is still setting it up.
                'qr_svg' => $pending ? $user->twoFactorQrCodeSvg() : null,
                'setup_key' => $pending ? decrypt($user->two_factor_secret) : null,
                'recovery_codes' => $user->two_factor_confirmed_at ? $user->recoveryCodes() : [],
            ],
            'sessions' => DB::table('sessions')
                ->where('user_id', $user->id)
                ->orderByDesc('last_activity')
                ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
                ->map(fn ($session) => [
                    'device' => UserAgent::summary($session->user_agent),
                    'ip' => $session->ip_address,
                    'last_active' => Carbon::createFromTimestamp($session->last_activity)->toISOString(),
                    'current' => $session->id === $request->session()->getId(),
                ])
                ->values(),
        ]);
    }

    public function signOutOtherDevices(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password:web']], ['password.current_password' => __('رمز عبور درست نیست.')]);

        DB::table('sessions')->where('user_id', $request->user()->id)->where('id', '!=', $request->session()->getId())->delete();

        return back()->with('status', __('از بقیه‌ی دستگاه‌ها خارج شدید.'));
    }

    /** Everything the user owns, as one JSON file. Always available, on every plan. */
    public function export(Request $request, BackupService $backups): StreamedResponse
    {
        $json = json_encode($backups->payload($request->user()), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

        return response()->streamDownload(fn () => print ($json), 'hezarrial-export-'.now()->format('Ymd').'.json', ['Content-Type' => 'application/json']);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password:web']], ['password.current_password' => __('رمز عبور درست نیست.')]);

        $user = $request->user();

        // Foreign keys remove the rows; the backup files live outside the database.
        Storage::disk('local')->deleteDirectory('backups/'.$user->id);
        DB::table('sessions')->where('user_id', $user->id)->delete();

        Auth::guard('web')->logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Inertia::clearHistory();

        return redirect()->route('login')->with('status', __('حساب و همه‌ی داده‌هایتان حذف شد.'));
    }
}
