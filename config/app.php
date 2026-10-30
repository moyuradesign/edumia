<?php

return [
    'name' => env('APP_NAME', 'Edumia'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'https://edumia.tonsamith.com'),
    'timezone' => env('APP_TIMEZONE', 'Asia/Phnom_Penh'),
    'locale' => env('APP_LOCALE', 'en'),
    'fallback_locale' => 'en',
    'faker_locale' => 'en_US',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',
    'maintenance' => ['driver' => 'file'],
    'previous_keys' => array_filter(explode(',', (string) env('APP_PREVIOUS_KEYS', ''))),
];
