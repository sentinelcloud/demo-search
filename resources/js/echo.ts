import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Make Pusher available globally for Echo
window.Pusher = Pusher;

/**
 * Laravel Echo — auto-configured for either Reverb or Sockudo.
 *
 * Both use the Pusher protocol. Set BROADCAST_CONNECTION in .env to switch:
 *   - 'reverb'  → connects to Reverb on port 6001
 *   - 'sockudo' → connects to Sockudo on port 6002
 */
const driver = import.meta.env.VITE_BROADCAST_CONNECTION ?? 'reverb';

const isSockudo = driver === 'sockudo';

const key = isSockudo
    ? import.meta.env.VITE_SOCKUDO_APP_KEY
    : import.meta.env.VITE_REVERB_APP_KEY;

const wsHost = isSockudo
    ? import.meta.env.VITE_SOCKUDO_HOST
    : import.meta.env.VITE_REVERB_HOST;

const wsPort = isSockudo
    ? (import.meta.env.VITE_SOCKUDO_PORT ?? 6002)
    : (import.meta.env.VITE_REVERB_PORT ?? 6001);

const scheme = isSockudo
    ? (import.meta.env.VITE_SOCKUDO_SCHEME ?? 'http')
    : (import.meta.env.VITE_REVERB_SCHEME ?? 'http');

const echo = new Echo({
    broadcaster: isSockudo ? 'pusher' : 'reverb',
    key,
    cluster: isSockudo ? 'mt1' : undefined,
    wsHost,
    wsPort,
    wssPort: wsPort,
    forceTLS: scheme === 'https',
    enabledTransports: ['ws', 'wss'],
});

export default echo;
