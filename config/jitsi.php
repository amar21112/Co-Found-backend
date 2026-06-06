<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Jitsi Meet — Self-hosted instance
    |--------------------------------------------------------------------------
    |
    | JITSI_BASE_URL            → VPS domain, e.g. https://meet.cofound.example.com
    | JITSI_APP_ID              → must match app_id in Prosody config
    | JITSI_APP_SECRET          → must match app_secret in Prosody config
    |
    | JITSI_TOKEN_TTL           → JWT lifetime in seconds (default 30).
    |                             Short-lived by design — the frontend refreshes
    |                             silently every JITSI_TOKEN_REFRESH_INTERVAL
    |                             seconds. Anyone holding a stolen token is
    |                             evicted when it expires and they cannot refresh
    |                             (they have no backend auth session).
    |
    | JITSI_TOKEN_REFRESH_INTERVAL → how often (seconds) the frontend should
    |                             call POST /calls/{id}/join to get a fresh token.
    |                             Must be less than JITSI_TOKEN_TTL.
    |                             Default 25 (5s before the 30s token expires).
    |
    | JITSI_ROOM_DURATION       → how long (seconds) mod_reservations keeps the
    |                             room alive. Separate from token TTL — the room
    |                             stays open for hours; only tokens are short-lived.
    |                             Default 14400 (4 hours).
    |
    | JITSI_RESERVATION_SECRET  → shared secret between Laravel and Prosody's
    |                             mod_reservations / mod_cofound_access.
    |                             Set the same value in reservations_api_headers
    |                             and cofound_access_api_secret in Prosody config.
    |
    */

    'base_url'                => env('JITSI_BASE_URL', 'https://meet.jit.si'),
    'app_id'                  => env('JITSI_APP_ID', ''),
    'app_secret'              => env('JITSI_APP_SECRET', ''),
    'token_ttl'               => (int) env('JITSI_TOKEN_TTL', 30),
    'token_refresh_interval'  => (int) env('JITSI_TOKEN_REFRESH_INTERVAL', 25),
    'room_duration'           => (int) env('JITSI_ROOM_DURATION', 14400),
    'reservation_secret'      => env('JITSI_RESERVATION_SECRET', ''),
];
