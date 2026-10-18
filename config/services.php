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

    'sendgrid' => [
        'key' => env('SENDGRID_API_KEY'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'supabase' => [
        'url'         => env('SUPABASE_URL'),
        'anon_key'    => env('SUPABASE_ANON_KEY'),
        'service_key' => env('SUPABASE_SERVICE_KEY'),
        'project_id'  => env('SUPABASE_PROJECT_ID'),
    ],

    /*
     | PayMongo — online bank payments (checkout sessions + signed webhooks). Online payment stays
     | switched off until the secret key is set; clients then only see the proof-of-payment upload.
     | payment_methods: the checkout's methods. Bank options: dob (BPI), dob_ubp (UnionBank),
     | brankas_bdo, brankas_landbank, brankas_metrobank, and qrph (InstaPay QR — any bank app).
     */
    'paymongo' => [
        'secret_key'      => env('PAYMONGO_SECRET_KEY'),
        'public_key'      => env('PAYMONGO_PUBLIC_KEY'),
        'webhook_secret'  => env('PAYMONGO_WEBHOOK_SECRET'),
        'base_url'        => env('PAYMONGO_BASE_URL', 'https://api.paymongo.com/v1'),
        'payment_methods' => array_values(array_filter(array_map('trim', explode(',', env(
            'PAYMONGO_PAYMENT_METHODS',
            'qrph,dob,dob_ubp,brankas_bdo,brankas_landbank,brankas_metrobank'
        ))))),
        // seconds of webhook timestamp drift tolerated before a signature is treated as a replay
        'webhook_tolerance' => (int) env('PAYMONGO_WEBHOOK_TOLERANCE', 600),
    ],

];