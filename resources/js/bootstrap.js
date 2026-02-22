import axios from 'axios';
window.axios = axios;

window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const getCsrfTokenFromMeta = () => {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? null;
};

const csrfToken = getCsrfTokenFromMeta();
if (csrfToken) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
}

window.axios.interceptors.request.use((config) => {
    const latestToken = getCsrfTokenFromMeta();
    if (latestToken) {
        config.headers['X-CSRF-TOKEN'] = latestToken;
    }
    return config;
});

window.axios.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error?.response?.status;

        // Session expired or CSRF token mismatch -> hard reload to refresh cookies/token.
        if (status === 419) {
            window.location.reload();
        }

        return Promise.reject(error);
    },
);

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const realtimeEnabled = (import.meta.env.VITE_REALTIME_ENABLED ?? 'true') === 'true';
const echoKey = import.meta.env.VITE_REVERB_APP_KEY ?? import.meta.env.VITE_PUSHER_APP_KEY;
const echoHost = import.meta.env.VITE_REVERB_HOST ?? import.meta.env.VITE_PUSHER_HOST ?? '127.0.0.1';
const echoPort = import.meta.env.VITE_REVERB_PORT ?? import.meta.env.VITE_PUSHER_PORT ?? 6001;
const echoScheme = import.meta.env.VITE_REVERB_SCHEME ?? import.meta.env.VITE_PUSHER_SCHEME ?? 'http';

if (realtimeEnabled && echoKey) {
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
} else {
    window.Echo = null;
}
