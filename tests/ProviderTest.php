<?php

declare(strict_types=1);

namespace Csiq\ConfigurationLaravel\Tests;

use Csiq\Configuration\IConfigurationClient;
use Csiq\Configuration\Testing\FakeConfigurationClient;
use Csiq\ConfigurationLaravel\Console\SetCommand;
use Csiq\ConfigurationLaravel\Console\SmokeCommand;
use Csiq\ConfigurationLaravel\CsiqConfigServiceProvider;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

final class ProviderTest extends TestCase
{
    public function testMergesPackageConfig(): void
    {
        self::assertSame('127.0.0.1:7154', config('csiq-config.uri'));
        self::assertTrue(config('csiq-config.insecure'));
        self::assertSame(2000, config('csiq-config.timeout_ms'));
        self::assertSame(3, config('csiq-config.retry.max_attempts'));
        self::assertArrayHasKey('root_cert_path', config('csiq-config'));
    }

    public function testPublishesConfigFile(): void
    {
        $paths = ServiceProvider::pathsToPublish(CsiqConfigServiceProvider::class, 'csiq-config');

        self::assertNotEmpty($paths);
        self::assertStringEndsWith('config/csiq-config.php', (string) array_key_first($paths));
    }

    public function testRegistersSmokeCommand(): void
    {
        self::assertArrayHasKey('csiq-config:smoke', Artisan::all());
        self::assertArrayHasKey('csiq-config:set', Artisan::all());
    }

    public function testFakeCanReplaceBoundClient(): void
    {
        $fake = new FakeConfigurationClient(['is_memo_enabled' => true]);
        $this->app->instance(IConfigurationClient::class, $fake);

        self::assertSame($fake, $this->app->make(IConfigurationClient::class));
        self::assertTrue($this->app->make(IConfigurationClient::class)->isFeatureEnabled('is_memo_enabled', 1));
    }

    public function testSmokeCommandUsesBoundClient(): void
    {
        $this->app->instance(IConfigurationClient::class, new FakeConfigurationClient(['is_memo_enabled' => true]));

        $this->artisan(SmokeCommand::class, ['--account' => 42, '--name' => 'is_memo_enabled'])
            ->expectsOutputToContain('is_memo_enabled @ account 42 = true')
            ->assertSuccessful();
    }

    public function testSetCommandUpsertsAndReadsBack(): void
    {
        $this->app->instance(IConfigurationClient::class, new FakeConfigurationClient());

        $this->artisan(SetCommand::class, [
            'name' => 'is_memo_enabled',
            'value' => 'true',
            '--account' => 42,
            '--edited-by' => 9,
        ])
            ->expectsOutputToContain('set is_memo_enabled @ account 42 = true (read back true)')
            ->assertSuccessful();

        self::assertTrue(
            $this->app->make(IConfigurationClient::class)->isFeatureEnabled('is_memo_enabled', 42)
        );
    }

    public function testResolvingClientRequiresGrpcExtension(): void
    {
        if (extension_loaded('grpc')) {
            $this->markTestSkipped('ext-grpc is loaded');
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ext-grpc');

        $this->app->make(IConfigurationClient::class);
    }

    public function testClientBindingIsRegistered(): void
    {
        self::assertTrue($this->app->bound(IConfigurationClient::class));
    }

    public function testProductionGuardRunsWhenClientIsResolved(): void
    {
        $this->app['env'] = 'production';
        $this->app['config']->set('csiq-config.uri', '127.0.0.1:7154');
        $this->app['config']->set('csiq-config.insecure', false);
        $this->app['config']->set('csiq-config.root_cert_path', '/tmp/ca.pem');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('loopback');

        $this->app->make(IConfigurationClient::class);
    }

    public function testTlsWithoutPemFailsWhenClientIsResolved(): void
    {
        if (!extension_loaded('grpc')) {
            $this->markTestSkipped('ext-grpc is not loaded');
        }

        $this->app['config']->set('csiq-config.insecure', false);
        $this->app['config']->set('csiq-config.root_cert_path', null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('rootCertPath is required for TLS');

        $this->app->make(IConfigurationClient::class);
    }
}
