<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SmsIngestRequest;
use App\Models\User;
use App\Services\Sms\SmsIngestService;
use Illuminate\Http\JsonResponse;

class SmsIngestController extends Controller
{
    public function __invoke(SmsIngestRequest $request, SmsIngestService $ingest): JsonResponse
    {
        $userId = config('services.sms_ingest.user_id');

        $user = User::findOrFail($userId);

        $sms = $ingest->ingest($user, $request->input('message'));

        $isNew = $sms->wasRecentlyCreated;

        return response()->json([
            'status' => $isNew ? $sms->status : 'duplicate',
            'id' => $sms->id,
            'parsed' => $isNew ? [
                'amount' => $sms->amount,
                'type' => $sms->type,
                'bank' => $sms->bank_name,
                'confidence' => $sms->confidence,
            ] : null,
        ], $isNew ? 201 : 200);
    }
}
