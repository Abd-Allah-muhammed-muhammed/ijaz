<?php

return [
    'send_cooldown_seconds' => env('OTP_SEND_COOLDOWN_SECONDS', 60),

    'ttl_minutes' => [
        'default' => (int) env('OTP_TTL_MINUTES_DEFAULT', 30),
        'provider_registration' => (int) env('OTP_TTL_MINUTES_PROVIDER_REGISTRATION', 5),
    ],
];
