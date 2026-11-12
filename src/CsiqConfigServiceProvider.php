<?php

declare(strict_types=1);

namespace Csiq\ConfigurationLaravel;

use Csiq\Configuration\ConfigurationClientFactory;
use Csiq\Configuration\IConfigurationClient;
use Csiq\ConfigurationLaravel\Console\SetCommand;
use Csiq\ConfigurationLaravel\Console\SmokeCommand;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class CsiqConfigServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/csiq-config.php', 'csiq-config');

        $this->app->singleton(IConfigurationClient::class, function (Application $app): IConfigurationClient {
            /** @var array<string, mixed> $settings */
            $settings = $app['config']->get('csiq-config', []);
            ProductionGuard::assert((string) $app->environment(), $settings);

            if (!extension_loaded('grpc')) {
                throw new RuntimeException('ext-grpc is required to construct IConfigurationClient');
            }

            return ConfigurationClientFactory::create(
                ClientConfigFactory::make($settings),
                $this->logger($app, $settings),
            );
        });
    }

    public function boot(): void
    {
        $this->publishes(
            [__DIR__ . '/../config/csiq-config.php' => config_path('csiq-config.php')],
            'csiq-config',
        );

        if ($this->app->runningInConsole()) {
            $this->commands([SmokeCommand::class, SetCommand::class]);
        }
    }

    /** @param array<string, mixed> $settings */
    private function logger(Application $app, array $settings): LoggerInterface
    {
        $log = $app->make('log');
        $channel = $settings['log_channel'] ?? null;
        $driver = is_string($channel) && $channel !== '' ? $log->channel($channel) : $log->driver();

        return $driver->getLogger();
    }
}
