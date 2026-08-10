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

        /*
        | Web Push (browser FCM). Prefer FIREBASE_* (server/.env source), with
        | VITE_FIREBASE_* as fallback — Vite bakes VITE_* into the JS bundle.
        | The VAPID key here must be the *public* Web Push certificate only
        | (safe in the browser). Never store or expose a VAPID private key in
        | VITE_* or this config.
        */
        'web' => [
            'api_key' => env('FIREBASE_API_KEY', env('VITE_FIREBASE_API_KEY')),
            'auth_domain' => env('FIREBASE_AUTH_DOMAIN', env('VITE_FIREBASE_AUTH_DOMAIN')),
            'project_id' => env('FIREBASE_PROJECT_ID', env('VITE_FIREBASE_PROJECT_ID')),
            'messaging_sender_id' => env('FIREBASE_MESSAGING_SENDER_ID', env('VITE_FIREBASE_MESSAGING_SENDER_ID')),
            'app_id' => env('FIREBASE_APP_ID', env('VITE_FIREBASE_APP_ID')),
            'vapid_key' => env('FIREBASE_VAPID_KEY', env('VITE_FIREBASE_VAPID_KEY')),
            // Compat SDK scripts loaded by public/firebase-messaging-sw.js — keep in sync with package.json "firebase".
            'sdk_compat_version' => '12.17.1',
        ],
    ],

];
