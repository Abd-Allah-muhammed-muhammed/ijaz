import Echo from 'laravel-echo';

import Pusher from 'pusher-js';

window.Pusher = Pusher;
Pusher.logToConsole = true;

/**
 * =============================================================================
 * LOCAL-DEV WebSocket scheme fix (DO NOT REMOVE without reading this)
 * =============================================================================
 *
 * WHY THIS EXISTS
 * ---------------
 * pusher-js 8.x ignores `forceTLS: false` when the *page* is served over https
 * (Laragon local: https://ijaz.test). Internally `shouldUseTLS()` returns true
 * whenever `Runtime.getProtocol() === 'https:'`, which forces **wss://** even
 * when `VITE_REVERB_SCHEME=http` and the local Reverb server only listens on
 * plain **ws://**. That breaks realtime chat locally (typing, live messages).
 *
 * WHAT WE DO
 * ----------
 * 1. Compute `forceTLS` from `VITE_REVERB_SCHEME` (not from the page protocol).
 * 2. When the scheme is non-TLS (`http` / `ws`), override
 *    `Pusher.Runtime.getProtocol` to return `'http:'` so pusher-js will use
 *    ws:// + wsPort, and restrict `enabledTransports` to `['ws']`.
 *
 * PRODUCTION SAFETY — THIS BRANCH MUST NEVER RUN IN PROD
 * ------------------------------------------------------
 * Production sets `VITE_REVERB_SCHEME=wss` (or `https`). Both are treated as
 * TLS below (`forceTLS === true`), so:
 *   - `Pusher.Runtime.getProtocol` is NOT overridden
 *   - `enabledTransports` stays `['ws', 'wss']`
 * Local Laragon uses `http` → override applies. Trace:
 *   scheme 'wss'|'https' → forceTLS true  → override skipped ✅
 *   scheme 'http'|'ws'   → forceTLS false → override runs (local only) ✅
 *
 * Keep `resources/js/store/use-echo.ts` in sync if that file is revived.
 * =============================================================================
 */
const reverbScheme = String(import.meta.env.VITE_REVERB_SCHEME ?? 'https').toLowerCase();

// TLS schemes used in production (`wss`) and Laravel docs (`https`).
// Anything else (local `http` / `ws`) opts into the plain-ws override below.
const forceTLS = reverbScheme === 'https' || reverbScheme === 'wss';

if (!forceTLS && typeof Pusher.Runtime?.getProtocol === 'function') {
    // Scoped to non-TLS VITE_REVERB_SCHEME only — never reached when scheme is wss/https.
    Pusher.Runtime.getProtocol = () => 'http:';
}

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS,
    // Local http/ws: ws only. Production wss/https: allow both (pusher default path).
    enabledTransports: forceTLS ? ['ws', 'wss'] : ['ws'],
});
