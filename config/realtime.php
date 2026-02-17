<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Realtime Features
    |--------------------------------------------------------------------------
    |
    | Toggle realtime broadcasting (Reverb/Pusher) without changing code.
    | Set to false in production if broadcaster credentials are not ready yet.
    |
    */
    'enabled' => (bool) env('REALTIME_ENABLED', true),
];

