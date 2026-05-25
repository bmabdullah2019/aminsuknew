<?php

return [
    'webhooks' => [
        'allowed_clock_skew_seconds' => env('PAYMENT_WEBHOOK_MAX_SKEW_SECONDS', 300),
        'allow_unsigned_when_secret_missing' => env(
            'PAYMENT_WEBHOOK_ALLOW_UNSIGNED_WITHOUT_SECRET',
            in_array(env('APP_ENV', 'production'), ['local', 'testing'], true)
        ),
        'bkash' => [
            'signature_secret' => env('BKASH_WEBHOOK_SIGNATURE_SECRET', ''),
            'signature_header' => env('BKASH_WEBHOOK_SIGNATURE_HEADER', 'X-Webhook-Signature'),
            'timestamp_header' => env('BKASH_WEBHOOK_TIMESTAMP_HEADER', 'X-Webhook-Timestamp'),
        ],
        'shurjopay' => [
            'signature_secret' => env('SHURJOPAY_WEBHOOK_SIGNATURE_SECRET', ''),
            'signature_header' => env('SHURJOPAY_WEBHOOK_SIGNATURE_HEADER', 'X-Webhook-Signature'),
            'timestamp_header' => env('SHURJOPAY_WEBHOOK_TIMESTAMP_HEADER', 'X-Webhook-Timestamp'),
        ],
    ],
];
