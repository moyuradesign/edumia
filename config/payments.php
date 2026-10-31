<?php

declare(strict_types=1);

return [
    'stripe' => [
        // Fail closed: a customer installation has no payment capability until
        // the operator explicitly enables the gateway and provides both secrets.
        'enabled' => (bool) env('STRIPE_ENABLED', false),
        'mode' => env('STRIPE_MODE', 'test'),
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],
];
