<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default API Version
    |--------------------------------------------------------------------------
    | Used when a request doesn't specify a version via header or the version
    | can't otherwise be determined. Also used by module route providers as
    | the version to register by default.
    */
    'default_version' => env('API_DEFAULT_VERSION', 'v1'),

    /*
    |--------------------------------------------------------------------------
    | Versions
    |--------------------------------------------------------------------------
    | Each version controls its own routing lifecycle: whether it's active,
    | its URL prefix, its route-name prefix, its folder name (the physical
    | Api/{folder} PHP namespace segment — this does NOT change dynamically,
    | it's just referenced here so the router knows which folder to load),
    | and deprecation/sunset metadata (RFC 8594).
    */
    'versions' => [
        'v1' => [
            'enabled' => true,
            'folder' => 'V1',
            'prefix' => 'api/v1',
            'name' => 'api.v1.',
            'deprecated' => false,
            'sunset_at' => null, // e.g. '2027-01-01T00:00:00Z'
            'successor' => null, // e.g. 'v2', used to build the Link header
        ],
        // 'v2' => [ ... ] — add when a real v2 is built; see docs/API_VERSIONING_GUIDE.md
    ],

    /*
    |--------------------------------------------------------------------------
    | Version Negotiation
    |--------------------------------------------------------------------------
    | The URL prefix (api/v1/...) is ALWAYS the source of truth and must never
    | change for existing clients. This section adds an OPTIONAL alternative:
    | clients MAY also send a version header; if present and valid, it's
    | exposed to the app for logging/response purposes, but it does NOT
    | change which routes are registered or matched (URL prefix still owns
    | that) — this is metadata/negotiation-awareness, not URL-alternative
    | routing, to guarantee zero risk to existing URL-based clients.
    */
    'negotiation' => [
        'header_enabled' => false, // feature-flagged off by default
        'header_name' => 'X-API-Version',
        'expose_response_header' => false, // adds X-API-Version to every response
    ],

    /*
    |--------------------------------------------------------------------------
    | Deprecation Response Headers (RFC 8594)
    |--------------------------------------------------------------------------
    | When a version's 'deprecated' flag is true, optionally emit Deprecation/
    | Sunset/Link headers on every response for that version. Off by default.
    */
    'deprecation' => [
        'emit_headers' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sunset Behavior
    |--------------------------------------------------------------------------
    | When a version's 'enabled' flag is false, requests to that version's
    | prefix return this status instead of 404, with a clear message.
    */
    'sunset' => [
        'status_code' => 410,
        'message' => 'This API version is no longer available.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting (placeholder — not wired yet, reserved for future use)
    |--------------------------------------------------------------------------
    */
    'rate_limits' => [
        // 'v1' => 'api', // named limiter, see RouteServiceProvider::configureRateLimiting
    ],
];
