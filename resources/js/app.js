import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const isRipple = import.meta.env.VITE_BROADCAST_CONNECTION === 'ripple' || !!import.meta.env.VITE_RIPPLE_KEY;
const broadcaster = isRipple ? 'pusher' : 'reverb';
const key = isRipple ? import.meta.env.VITE_RIPPLE_KEY : import.meta.env.VITE_REVERB_APP_KEY;
const port = isRipple ? (import.meta.env.VITE_RIPPLE_PORT || 8080) : (import.meta.env.VITE_REVERB_PORT || 8080);
const scheme = isRipple ? (import.meta.env.VITE_RIPPLE_SCHEME || 'http') : (import.meta.env.VITE_REVERB_SCHEME || 'http');

window.Echo = new Echo({

    broadcaster: 'reverb',

    key: import.meta.env.VITE_REVERB_APP_KEY,

    wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,

    wsPort: import.meta.env.VITE_REVERB_PORT || 8080,

    wssPort: import.meta.env.VITE_REVERB_PORT || 8080,

    /*
    |--------------------------------------------------------------------------
    | forceTLS
    |--------------------------------------------------------------------------
    |
    | Browsers BLOCK plain ws:// connections from HTTPS pages (mixed content).
    | Pusher-js already forces wss:// automatically when the page is HTTPS, so
    | we explicitly set it here to match the page protocol. This ensures the
    | Reverb server (which now has TLS enabled) is reached via wss://.
    |
    */
    forceTLS: window.location.protocol === 'https:',

    enabledTransports: ['ws', 'wss'],

});
