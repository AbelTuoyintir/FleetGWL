import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { io } from 'socket.io-client';

window.Pusher = Pusher;
window.io = io;

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
    forceTLS: window.location.protocol === 'https:',
    enabledTransports: ['ws', 'wss'],
});

// Socket.IO signaling connection helper for WebRTC calling
window.initializeSocket = function (userId) {
    if (window.socketInstance) {
        return window.socketInstance;
    }

    const socketUrl = import.meta.env.VITE_SOCKET_SERVER_URL || `${window.location.protocol}//${window.location.hostname}:6001`;
    const socket = io(socketUrl, {
        transports: ['websocket', 'polling'],
        autoConnect: true,
        reconnection: true,
        reconnectionAttempts: 10,
    });

    socket.on('connect', () => {
        console.log('[Socket.IO] Connected to signaling server:', socket.id);
        if (userId) {
            socket.emit('register-user', userId);
        }
    });

    socket.on('registered', (data) => {
        console.log('[Socket.IO] Registered in room:', data.room);
    });

    socket.on('disconnect', (reason) => {
        console.log('[Socket.IO] Disconnected:', reason);
    });

    window.socketInstance = socket;
    return socket;
};
