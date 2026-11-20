<?php

declare(strict_types=1);

namespace Csiq\ConfigurationLaravel;

use Csiq\Configuration\ProductionGuard as HostGuard;
use RuntimeException;

final class ProductionGuard
{
    /** @var list<string> */
    private const LOCAL_ENVIRONMENTS = ['local', 'testing'];

    /** @param array<string, mixed> $settings */
    public static function assert(string $environment, array $settings): void
    {
        if (in_array(strtolower($environment), self::LOCAL_ENVIRONMENTS, true)) {
            return;
        }

        $uri = is_string($settings['uri'] ?? null) ? $settings['uri'] : '';
        $insecure = !empty($settings['insecure']);
        if (HostGuard::isLocalHost($uri)) {
            throw new RuntimeException('csiq-config uri must not be loopback outside local/testing');
        }
        if ($insecure) {
            throw new RuntimeException('csiq-config insecure must be false outside local/testing');
        }
    }
}
