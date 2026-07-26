<?php

// This file registers ONLY Laravel Echo/Reverb broadcast authentication
// (POST /broadcasting/auth) — used to verify a user can subscribe to a
// private/presence channel. It does NOT contain REST API endpoints.
//
// Real API routes live under:
//   - routes/Api/V1/{auth,platform}.php (core)
//   - Modules/*/Routes/V1/*.php (per-module)
//
// See routes/channels.php for the actual channel authorization rules
// (who is allowed on which channel).

use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['middleware' => ['auth:sanctum']]);
