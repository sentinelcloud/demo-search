import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Make Pusher available globally for Echo
window.Pusher = Pusher;

/**
 * Laravel Echo — auto-configured for either Reverb or Soketi.
 *
 * Both use the Pusher protocol. Set BROADCAST_CONNECTION in .env to switch:
 *   - 'reverb' → connects to Reverb on port 6001
 *   - 'soketi' → connects to Soketi on port 6002
 */
const driver = import.meta.env.VITE_BROADCAST_CONNECTION ?? 'reverb';

const isSoketi = driver === 'soketi';

const key = isSoketi
    ? import.meta.env.VITE_SOKETI_APP_KEY
    : import.meta.env.VITE_REVERB_APP_KEY;

const wsHost = isSoketi
    ? import.meta.env.VITE_SOKETI_HOST
    : import.meta.env.VITE_REVERB_HOST;

const wsPort = isSoketi
    ? (import.meta.env.VITE_SOKETI_PORT ?? 6002)
    : (import.meta.env.VITE_REVERB_PORT ?? 6001);

const scheme = isSoketi
    ? (import.meta.env.VITE_SOKETI_SCHEME ?? 'http')
    : (import.meta.env.VITE_REVERB_SCHEME ?? 'http');

const echo = new Echo({
    broadcaster: isSoketi ? 'pusher' : 'reverb',
    key,
    cluster: isSoketi ? 'mt1' : undefined,
    wsHost,
    wsPort,
    wssPort: wsPort,
    forceTLS: scheme === 'https',
    enabledTransports: ['ws', 'wss'],
});

export default echo;
