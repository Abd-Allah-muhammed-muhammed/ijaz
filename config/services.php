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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging
    |--------------------------------------------------------------------------
    |
    | Push notifications via FCM HTTP v1. Credentials are a Google service
    | account JSON file (FIREBASE_AUTH_FILE_PATH). Default path is private
    | under storage/app/firebase/ (gitignored, not web-accessible). OAuth
    | access tokens are cached under cache_key with a 3-minute skew before
    | expires_in.
    |
    | Decision point (Phase 1): live sends use FCM "notification" + "data"
    | only. A previous unused notify() path also set android.priority=high
    | and APNs headers/payload — that was never called from FirebaseChannel.
    | Revisit if mobile needs data-only / high-priority platform config.
    |
    */
    'firebase' => [
        'credentials' => env('FIREBASE_AUTH_FILE_PATH') ?: storage_path('app/firebase/ijaz.json'),
        'cache_key' => env('FIREBASE_CACHE_KEY', 'firebase-oauth-token'),
        'token_ttl_skew_seconds' => 180,
        'oauth_token_url' => 'https://oauth2.googleapis.com/token',
        'fcm_send_url' => 'https://fcm.googleapis.com/v1/projects/{project_id}/messages:send',
        'http_timeout' => 15,
    ],

];
