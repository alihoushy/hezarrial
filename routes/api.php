<?php

use App\Http\Controllers\Api\SmsIngestController;
use App\Http\Middleware\AuthenticateSmsIngest;
use Illuminate\Support\Facades\Route;

Route::post('/sms/ingest', SmsIngestController::class)
    ->middleware([AuthenticateSmsIngest::class, 'throttle:sms-ingest']);
