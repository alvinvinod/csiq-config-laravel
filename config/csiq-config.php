<?php

declare(strict_types=1);

return [
    'uri' => env('CONFIG_SERVICE_URI', '127.0.0.1:7154'),
    'insecure' => filter_var(env('CONFIG_SERVICE_INSECURE', false), FILTER_VALIDATE_BOOLEAN),
    'root_cert_path' => env('CONFIG_SERVICE_ROOT_CERT_PATH'),
    'timeout_ms' => (int) env('CONFIG_SERVICE_TIMEOUT_MS', 2000),
    'keepalive_time_ms' => (int) env('CONFIG_SERVICE_KEEPALIVE_TIME_MS', 60000),
    'keepalive_timeout_ms' => (int) env('CONFIG_SERVICE_KEEPALIVE_TIMEOUT_MS', 10000),
    'log_channel' => env('CONFIG_SERVICE_LOG_CHANNEL'),
    'retry' => [
        'max_attempts' => (int) env('CONFIG_SERVICE_RETRY_MAX_ATTEMPTS', 3),
        'initial_backoff_ms' => (int) env('CONFIG_SERVICE_RETRY_INITIAL_BACKOFF_MS', 250),
        'max_backoff_ms' => (int) env('CONFIG_SERVICE_RETRY_MAX_BACKOFF_MS', 250),
        'backoff_multiplier' => (float) env('CONFIG_SERVICE_RETRY_BACKOFF_MULTIPLIER', 1.0),
    ],
];
