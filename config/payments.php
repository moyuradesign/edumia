<?php

declare(strict_types=1);

return [
    // Configured only by the operator; earnings are HELD, never auto-payable.
    'platform_commission_bps' => (int) env('EDUMIA_COMMISSION_BPS', 2000),
    'stripe' => [
        // Fail closed: a customer installation has no payment capability until
        // the operator explicitly enables the gateway and provides both secrets.
        'enabled' => (bool) env('STRIPE_ENABLED', false),
        'mode' => env('STRIPE_MODE', 'test'),
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],
];
