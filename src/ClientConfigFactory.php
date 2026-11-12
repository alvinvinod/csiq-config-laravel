<?php

declare(strict_types=1);

namespace Csiq\ConfigurationLaravel;

use Csiq\Configuration\ConfigurationClientConfig;
use Csiq\Configuration\RetryPolicy;

/** Maps config/csiq-config.php onto ConfigurationClientConfig. */
final class ClientConfigFactory
{
    /** @param array<string, mixed> $settings */
    public static function make(array $settings): ConfigurationClientConfig
    {
        $retry = is_array($settings['retry'] ?? null) ? $settings['retry'] : [];
        $root = $settings['root_cert_path'] ?? null;
        $root = is_string($root) && $root !== '' ? $root : null;

        return new ConfigurationClientConfig(
            host: (string) ($settings['uri'] ?? ''),
            insecure: (bool) ($settings['insecure'] ?? false),
            timeoutMs: (int) ($settings['timeout_ms'] ?? 2000),
            retryPolicy: new RetryPolicy(
                maxAttempts: (int) ($retry['max_attempts'] ?? 3),
                initialBackoffMs: (int) ($retry['initial_backoff_ms'] ?? 250),
                maxBackoffMs: (int) ($retry['max_backoff_ms'] ?? 250),
                backoffMultiplier: (float) ($retry['backoff_multiplier'] ?? 1.0),
            ),
            keepaliveTimeMs: (int) ($settings['keepalive_time_ms'] ?? 60000),
            keepaliveTimeoutMs: (int) ($settings['keepalive_timeout_ms'] ?? 10000),
            rootCertPath: $root,
        );
    }
}
