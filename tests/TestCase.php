<?php

declare(strict_types=1);

namespace Csiq\ConfigurationLaravel\Tests;

use Csiq\ConfigurationLaravel\CsiqConfigServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    /** @return list<class-string> */
    protected function getPackageProviders($app): array
    {
        return [CsiqConfigServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('csiq-config.uri', '127.0.0.1:7154');
        $app['config']->set('csiq-config.insecure', true);
        $app['config']->set('csiq-config.root_cert_path', null);
        $app['config']->set('csiq-config.log_channel', null);
    }
}
