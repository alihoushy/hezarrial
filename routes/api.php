<?php

use App\Http\Controllers\Api\SmsIngestController;
use App\Http\Middleware\VerifyIngestToken;
use Illuminate\Support\Facades\Route;

Route::post('/sms/ingest', SmsIngestController::class)
    ->middleware([VerifyIngestToken::class, 'throttle:sms-ingest']);
