<?php

declare(strict_types=1);

namespace Csiq\ConfigurationLaravel;

use RuntimeException;

/** Blocks loopback hosts and plaintext gRPC outside local/testing. */
final class ProductionGuard
{
    /** @param array<string, mixed> $settings */
    public static function assert(string $environment, array $settings): void
    {
        if (in_array($environment, ['local', 'testing'], true)) {
            return;
        }

        $uri = (string) ($settings['uri'] ?? '');
        $host = strtolower((string) parse_url('//' . $uri, PHP_URL_HOST));
        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            throw new RuntimeException('csiq-config uri must not be loopback outside local/testing');
        }

        if (!empty($settings['insecure'])) {
            throw new RuntimeException('csiq-config insecure must be false outside local/testing');
        }
    }
}
