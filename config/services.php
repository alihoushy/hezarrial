<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Where messages from the public contact form are mailed (empty = saved only).
    'contact' => [
        'to' => env('CONTACT_TO_ADDRESS', env('MAIL_FROM_ADDRESS')),
    ],

    // Outgoing SMS (reminders, mobile verification). "log" only writes to the log; "smsir" uses sms.ir.
    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
        // Cost control: SMS notifications sent to one user per day.
        'daily_limit_per_user' => (int) env('SMS_DAILY_LIMIT_PER_USER', 5),
        'smsir' => [
            'api_key' => env('SMSIR_API_KEY'),
            // The approved sender line for free-text messages.
            'line_number' => env('SMSIR_LINE_NUMBER'),
            // A verify (OTP) template with one parameter; without it the code goes out as free text.
            'verify_template_id' => env('SMSIR_VERIFY_TEMPLATE_ID'),
            'verify_parameter' => env('SMSIR_VERIFY_PARAMETER', 'Code'),
        ],
    ],

    'sms_ingest' => [
        'token' => env('SMS_INGEST_TOKEN'),
        'user_id' => env('SMS_INGEST_USER_ID', 1),
    ],

];
