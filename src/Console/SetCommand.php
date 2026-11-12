<?php

declare(strict_types=1);

namespace Csiq\ConfigurationLaravel\Console;

use Csiq\Configuration\IConfigurationClient;
use Illuminate\Console\Command;
use InvalidArgumentException;

final class SetCommand extends Command
{
    protected $signature = 'csiq-config:set
        {name : Definition name, e.g. is_memo_enabled}
        {value : Value to store}
        {--account=1 : CSIQ practice_id (CareStack accountId)}
        {--edited-by=1 : editedByUserId}
        {--user= : Optional user override layer}
        {--as=auto : auto|bool|int|string}';

    protected $description = 'Upsert one CareStack Configuration value through the bound client';

    public function handle(IConfigurationClient $client): int
    {
        $name = (string) $this->argument('name');
        $account = (int) $this->option('account');
        $editedBy = (int) $this->option('edited-by');
        $userRaw = $this->option('user');
        $userId = is_numeric($userRaw) ? (int) $userRaw : null;
        $value = $this->decodeValue((string) $this->argument('value'), (string) $this->option('as'));

        $client->upsert($name, $account, $value, $editedBy, userId: $userId);

        $read = is_bool($value)
            ? $client->isFeatureEnabled($name, $account, userId: $userId)
            : $client->tryGet($name, $account, userId: $userId);

        $this->info(sprintf(
            'set %s @ account %d = %s (read back %s)',
            $name,
            $account,
            $this->show($value),
            $this->show($read),
        ));

        return self::SUCCESS;
    }

    private function decodeValue(string $raw, string $as): mixed
    {
        $as = strtolower($as);

        return match ($as) {
            'bool' => $this->toBool($raw),
            'int' => $this->toInt($raw),
            'string' => $raw,
            'auto' => $this->auto($raw),
            default => throw new InvalidArgumentException('--as must be auto, bool, int, or string'),
        };
    }

    private function auto(string $raw): mixed
    {
        $lower = strtolower($raw);
        if (in_array($lower, ['true', 'false', 'yes', 'no', 'on', 'off', '1', '0'], true)) {
            return $this->toBool($raw);
        }
        if (preg_match('/^-?\d+$/', $raw) === 1) {
            return $this->toInt($raw);
        }

        return $raw;
    }

    private function toBool(string $raw): bool
    {
        return in_array(strtolower($raw), ['1', 'true', 'yes', 'on'], true);
    }

    private function toInt(string $raw): int
    {
        $int = filter_var($raw, FILTER_VALIDATE_INT);
        if ($int === false) {
            throw new InvalidArgumentException(sprintf('value must be an integer, got "%s"', $raw));
        }

        return $int;
    }

    private function show(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            $value === null => 'null',
            is_scalar($value) => (string) $value,
            default => json_encode($value, JSON_THROW_ON_ERROR),
        };
    }
}
