<?php

declare(strict_types=1);

namespace Csiq\ConfigurationLaravel\Console;

use Csiq\Configuration\IConfigurationClient;
use Illuminate\Console\Command;

final class SmokeCommand extends Command
{
    protected $signature = 'csiq-config:smoke {--account=1} {--name=is_memo_enabled}';

    protected $description = 'Call CareStack Configuration once through the bound client';

    public function handle(IConfigurationClient $client): int
    {
        $account = (int) $this->option('account');
        $name = (string) $this->option('name');
        $on = $client->isFeatureEnabled($name, $account);
        $this->info(sprintf('%s @ account %d = %s', $name, $account, $on ? 'true' : 'false'));

        return self::SUCCESS;
    }
}
