<?php

return [
    'health_check' => [
        'notifications' => [
            /*
             * Disabled by default for backward compatibility.
             * Enable only after channel credentials and recipients are set.
             */
            'enabled' => (bool) env('RECONCILIATION_HEALTH_NOTIFICATIONS_ENABLED', false),

            /*
             * Comma-separated channels supported: log, mail, slack
             * Example: "log,slack"
             */
            'channels' => array_values(array_filter(array_map(
                static fn (string $channel): string => trim($channel),
                explode(',', (string) env('RECONCILIATION_HEALTH_NOTIFICATION_CHANNELS', 'log'))
            ))),

            /*
             * Log channel used when "log" notification channel is enabled.
             */
            'log_channel' => env('RECONCILIATION_HEALTH_NOTIFICATION_LOG_CHANNEL', env('LOG_CHANNEL', 'stack')),

            /*
             * Comma-separated email recipients used when "mail" channel is enabled.
             */
            'mail_to' => env('RECONCILIATION_HEALTH_NOTIFICATION_MAIL_TO', ''),

            /*
             * Slack incoming webhook URL for "slack" channel.
             */
            'slack_webhook_url' => env('RECONCILIATION_HEALTH_NOTIFICATION_SLACK_WEBHOOK_URL', ''),

            'http_timeout_seconds' => (int) env('RECONCILIATION_HEALTH_NOTIFICATION_HTTP_TIMEOUT_SECONDS', 5),

            /*
             * Duplicate failure alert suppression window (seconds).
             * Set 0 to disable suppression and notify every failure run.
             */
            'dedupe_seconds' => (int) env('RECONCILIATION_HEALTH_NOTIFICATION_DEDUPE_SECONDS', 1800),

            /*
             * Optional cache store for dedupe keys. Empty uses default store.
             */
            'cache_store' => env('RECONCILIATION_HEALTH_NOTIFICATION_CACHE_STORE', ''),

            'cache_key_prefix' => env(
                'RECONCILIATION_HEALTH_NOTIFICATION_CACHE_KEY_PREFIX',
                'reconciliation:health:notify:'
            ),
        ],
    ],
];
