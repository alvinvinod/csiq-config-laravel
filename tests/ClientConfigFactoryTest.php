<?php

declare(strict_types=1);

namespace Csiq\ConfigurationLaravel\Tests;

use Csiq\ConfigurationLaravel\ClientConfigFactory;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ClientConfigFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!extension_loaded('grpc')) {
            $this->markTestSkipped('ext-grpc is not loaded');
        }
    }

    public function testMapsLaravelConfigOntoClientConfig(): void
    {
        $config = ClientConfigFactory::make([
            'uri' => 'config.example:443',
            'insecure' => false,
            'root_cert_path' => '/tmp/ca.pem',
            'timeout_ms' => 2000,
            'keepalive_time_ms' => 30000,
            'keepalive_timeout_ms' => 5000,
            'retry' => [
                'max_attempts' => 5,
                'initial_backoff_ms' => 100,
                'max_backoff_ms' => 400,
                'backoff_multiplier' => 2.0,
            ],
        ]);

        self::assertSame('config.example:443', $config->host);
        self::assertFalse($config->insecure);
        self::assertSame('/tmp/ca.pem', $config->rootCertPath);
        self::assertSame(2000, $config->timeoutMs);
        self::assertSame(30000, $config->keepaliveTimeMs);
        self::assertSame(5000, $config->keepaliveTimeoutMs);
        self::assertSame(5, $config->retryPolicy->maxAttempts);
        self::assertSame(100, $config->retryPolicy->initialBackoffMs);
        self::assertSame(400, $config->retryPolicy->maxBackoffMs);
        self::assertSame(2.0, $config->retryPolicy->backoffMultiplier);
    }

    public function testTlsWithoutPemThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('rootCertPath is required for TLS');

        ClientConfigFactory::make([
            'uri' => 'config.example:443',
            'insecure' => false,
            'root_cert_path' => null,
        ]);
    }

    public function testInsecureDoesNotNeedPem(): void
    {
        $config = ClientConfigFactory::make([
            'uri' => '127.0.0.1:7154',
            'insecure' => true,
        ]);

        self::assertTrue($config->insecure);
        self::assertNull($config->rootCertPath);
    }

    public function testBlankRootCertPathIsNull(): void
    {
        $config = ClientConfigFactory::make([
            'uri' => '127.0.0.1:7154',
            'insecure' => true,
            'root_cert_path' => '',
        ]);

        self::assertNull($config->rootCertPath);
    }
}
