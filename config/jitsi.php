<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Jitsi Meet — Self-hosted instance
    |--------------------------------------------------------------------------
    |
    | JITSI_BASE_URL  → your VPS domain, e.g. https://meet.yourdomain.com
    | JITSI_APP_ID    → the APP_ID you set during Jitsi install (prosody config)
    | JITSI_APP_SECRET→ the APP_SECRET you set during Jitsi install
    | JITSI_TOKEN_TTL → how many seconds a join token stays valid (default 4h)
    |
    */

    'base_url'   => env('JITSI_BASE_URL', 'https://meet.jit.si'),
    'app_id'     => env('JITSI_APP_ID', ''),
    'app_secret' => env('JITSI_APP_SECRET', ''),
    'token_ttl'  => (int) env('JITSI_TOKEN_TTL', 14400), // 4 hours
];
