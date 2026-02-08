import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
if (csrfToken) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
}

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const echoKey = import.meta.env.VITE_REVERB_APP_KEY ?? import.meta.env.VITE_PUSHER_APP_KEY;
const echoHost = import.meta.env.VITE_REVERB_HOST ?? import.meta.env.VITE_PUSHER_HOST ?? '127.0.0.1';
const echoPort = import.meta.env.VITE_REVERB_PORT ?? import.meta.env.VITE_PUSHER_PORT ?? 6001;
const echoScheme = import.meta.env.VITE_REVERB_SCHEME ?? import.meta.env.VITE_PUSHER_SCHEME ?? 'http';

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: echoKey,
    cluster: 'mt1',
    wsHost: echoHost,
    wsPort: echoPort,
    wssPort: echoPort,
    forceTLS: echoScheme === 'https',
    enabledTransports: ['ws', 'wss'],
});
