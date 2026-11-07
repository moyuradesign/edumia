<?php

declare(strict_types=1);

return [
    // Operator-controlled activation gates, never persisted via web forms.
    'transactional_mail_enabled' => (bool) env('EDUMIA_TRANSACTIONAL_MAIL_ENABLED', false),
    'provider_verified' => (bool) env('EDUMIA_MAIL_PROVIDER_VERIFIED', false),
    'smtp_test_enabled' => (bool) env('EDUMIA_SMTP_TEST_ENABLED', false),
    // Exact approved sandbox recipient, never accepted from HTTP input.
    'smtp_test_recipient' => env('EDUMIA_SMTP_TEST_RECIPIENT'),
    'queue_name' => 'notifications',
    // Operator attestation after verifying a real Supervisor/cron worker.
    'queue_worker_verified' => (bool) env('EDUMIA_QUEUE_WORKER_VERIFIED', false),
];
