<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SmsIngestRequest;
use App\Services\Sms\SmsIngestService;
use Illuminate\Http\JsonResponse;

class SmsIngestController extends Controller
{
    public function __invoke(SmsIngestRequest $request, SmsIngestService $ingest): JsonResponse
    {
        $sms = $ingest->ingest($request->user(), $request->input('message'));

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
