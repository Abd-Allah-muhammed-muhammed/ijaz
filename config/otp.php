<?php

return [
    'send_cooldown_seconds' => env('OTP_SEND_COOLDOWN_SECONDS', 60),

    'ttl_minutes' => [
        'default' => (int) env('OTP_TTL_MINUTES_DEFAULT', 30),
        'provider_registration' => (int) env('OTP_TTL_MINUTES_PROVIDER_REGISTRATION', 5),
    ],

    // Pre-auth OTP session (verification_id) lifetime — matches the former 15-minute login token window.
    'session_ttl_minutes' => (int) env('OTP_SESSION_TTL_MINUTES', 15),

    'max_verification_attempts' => (int) env('OTP_MAX_VERIFICATION_ATTEMPTS', 5),
];
