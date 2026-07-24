import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.initializeEcho = function(config) {
    if (window.Echo) {
        return window.Echo;
    }
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: config.key,
        wsHost: config.wsHost,
        wsPort: config.wsPort,
        wssPort: config.wsPort,
        forceTLS: config.forceTLS,
        enabledTransports: ['ws', 'wss'],
    });
    return window.Echo;
};
