<?php

declare(strict_types=1);

namespace Csiq\ConfigurationLaravel\Console;

use Csiq\Configuration\IConfigurationClient;
use Illuminate\Console\Command;

final class SmokeCommand extends Command
{
    protected $signature = 'csiq-config:smoke {--tenant=1 : CAS tenant id} {--name=is_memo_enabled}';

    protected $description = 'Call CareStack Configuration once through the bound client';

    public function handle(IConfigurationClient $client): int
    {
        $casTenantId = (int) $this->option('tenant');
        $name = (string) $this->option('name');
        $on = $client->isFeatureEnabled($name, $casTenantId);
        $this->info(sprintf('%s @ casTenantId %d = %s', $name, $casTenantId, $on ? 'true' : 'false'));

        return self::SUCCESS;
    }
}
