<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AuthenticateSmsIngest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Personal tokens that let a phone send bank SMS to the account (Settings > SMS). */
class SmsTokenController extends Controller
{
    private const MAX_TOKENS = 5;

    public function index(Request $request): Response
    {
        return Inertia::render('settings/sms', [
            'tokens' => $request->user()->tokens()->latest()->get(['id', 'name', 'created_at', 'last_used_at'])
                ->map(fn ($token) => [
                    'id' => $token->id,
                    'name' => $token->name,
                    'created_at' => $token->created_at?->toISOString(),
                    'last_used_at' => $token->last_used_at?->toISOString(),
                ])->values(),
            'endpoint' => url('/api/sms/ingest'),
            'limit' => self::MAX_TOKENS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:60']]);

        if ($request->user()->tokens()->count() >= self::MAX_TOKENS) {
            return back()->withErrors(['name' => __('حداکثر :count توکن می‌توانید داشته باشید. اول یکی را حذف کنید.', ['count' => self::MAX_TOKENS])]);
        }

        $token = $request->user()->createToken(trim($data['name']), [AuthenticateSmsIngest::ABILITY]);

        // Shown once: only a hash is kept, so it cannot be displayed again.
        Inertia::flash('sms_token', $token->plainTextToken);

        return back()->with('status', __('توکن ساخته شد. همین حالا آن را کپی کنید.'));
    }

    public function destroy(Request $request, int $token): RedirectResponse
    {
        $request->user()->tokens()->whereKey($token)->delete();

        return back()->with('status', __('توکن حذف شد.'));
    }
}
