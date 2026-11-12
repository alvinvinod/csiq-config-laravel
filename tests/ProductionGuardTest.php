<?php

declare(strict_types=1);

namespace Csiq\ConfigurationLaravel\Tests;

use Csiq\ConfigurationLaravel\ProductionGuard;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ProductionGuardTest extends TestCase
{
    public function testAllowsLoopbackAndInsecureInLocalAndTesting(): void
    {
        $settings = ['uri' => '127.0.0.1:7154', 'insecure' => true];

        ProductionGuard::assert('local', $settings);
        ProductionGuard::assert('testing', $settings);

        $this->addToAssertionCount(1);
    }

    public function testRejectsLoopbackOutsideLocal(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('loopback');

        ProductionGuard::assert('production', ['uri' => 'localhost:7154', 'insecure' => false]);
    }

    public function testRejectsInsecureOutsideLocal(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('insecure');

        ProductionGuard::assert('staging', [
            'uri' => 'config.internal.carestack.com:443',
            'insecure' => true,
        ]);
    }

    public function testAllowsTlsRemoteHostInProduction(): void
    {
        ProductionGuard::assert('production', [
            'uri' => 'config.internal.carestack.com:443',
            'insecure' => false,
        ]);

        $this->addToAssertionCount(1);
    }
}
