import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;
Pusher.logToConsole = true;

/**
 * Keep in sync with resources/js/echo.js.
 *
 * LOCAL-DEV FIX (see echo.js for the full rationale): pusher-js forces TLS on
 * https:// pages unless Runtime.getProtocol is overridden when
 * VITE_REVERB_SCHEME is plain http/ws. Production uses wss/https → forceTLS
 * stays true and this override never runs.
 */
const reverbScheme = String(import.meta.env.VITE_REVERB_SCHEME ?? 'https').toLowerCase();
const forceTLS = reverbScheme === 'https' || reverbScheme === 'wss';

if (!forceTLS && typeof Pusher.Runtime?.getProtocol === 'function') {
  Pusher.Runtime.getProtocol = () => 'http:';
}

window.Echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST,
  wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
  wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
  forceTLS,
  enabledTransports: forceTLS ? ['ws', 'wss'] : ['ws'],
});
