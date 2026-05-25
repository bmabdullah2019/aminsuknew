<?php

return [
    'payments' => [
        'hardened_verification' => env('FEATURE_PAYMENTS_HARDENED_VERIFICATION', true),
    ],
    'orders' => [
        'state_machine_enforced' => env('FEATURE_ORDERS_STATE_MACHINE_ENFORCED', true),
        'phone_cancel_auto_block_enabled' => env('FEATURE_ORDERS_PHONE_CANCEL_AUTO_BLOCK_ENABLED', true),
        'phone_cancel_block_threshold' => env('FEATURE_ORDERS_PHONE_CANCEL_BLOCK_THRESHOLD', 3),
        'returned_status_auto_return_enabled' => env('FEATURE_ORDERS_RETURNED_STATUS_AUTO_RETURN_ENABLED', true),
    ],
    'checkout' => [
        'guest_otp_required' => env('FEATURE_CHECKOUT_GUEST_OTP_REQUIRED', false),
        'guest_otp_verification_window_seconds' => env('FEATURE_CHECKOUT_GUEST_OTP_VERIFICATION_WINDOW_SECONDS', 1800),
        'server_breakdown_v2' => env('FEATURE_CHECKOUT_SERVER_BREAKDOWN_V2', true),
    ],
];
