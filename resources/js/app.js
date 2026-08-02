import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

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
