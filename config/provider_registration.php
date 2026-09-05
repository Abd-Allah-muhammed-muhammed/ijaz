<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Temporary upload disk / directory
    |--------------------------------------------------------------------------
    |
    | Guest registration uploads land here before the Provider row exists.
    | Distinct from `public` (logo destination) and `local` (Media Library certs).
    |
    */
    'temp_disk' => env('PROVIDER_REGISTRATION_TEMP_DISK', 'provider_registration_temp'),

    'temp_directory' => 'uploads',

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    */
    'retention_hours' => (int) env('PROVIDER_REGISTRATION_UPLOAD_RETENTION_HOURS', 48),

    /*
    |--------------------------------------------------------------------------
    | Per-token abuse caps
    |--------------------------------------------------------------------------
    |
    | Max rows and cumulative bytes stored under a single registration upload
    | token. 60MB sits above the ~40MB realistic Law-type worst case (5 × 8MB).
    |
    */
    'max_uploads_per_token' => (int) env('PROVIDER_REGISTRATION_MAX_UPLOADS_PER_TOKEN', 10),

    'max_bytes_per_token' => (int) env('PROVIDER_REGISTRATION_MAX_BYTES_PER_TOKEN', 60 * 1024 * 1024),

    /*
    |--------------------------------------------------------------------------
    | File size validation (kilobytes — Laravel `max:` rule units)
    |--------------------------------------------------------------------------
    |
    | Must stay in sync with ProviderRegistrationFileRules and frontend
    | REGISTRATION_MAX_FILE_SIZE_MB.
    |
    */
    'max_file_kilobytes' => 8192,

    /*
    |--------------------------------------------------------------------------
    | Rate limiting (upload endpoints)
    |--------------------------------------------------------------------------
    */
    'throttle' => [
        'max_attempts' => (int) env('PROVIDER_REGISTRATION_UPLOAD_THROTTLE_MAX', 30),
        'decay_minutes' => (int) env('PROVIDER_REGISTRATION_UPLOAD_THROTTLE_DECAY', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cleanup command chunk size
    |--------------------------------------------------------------------------
    */
    'prune_chunk_size' => 100,
];
