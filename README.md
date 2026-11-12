# csiq/config-laravel

Laravel adapter for [`csiq/config-php`](../csiq-config-php). Merges `config/csiq-config.php` (so `config:cache` works) and binds `IConfigurationClient` as a singleton.

Do not call `ConfigurationClientConfig::fromEnvironment()` or `ConfigurationClientFactory::create()` from Laravel. Cached config no longer exposes `.env` to `getenv()`.

## Install

```bash
composer require csiq/config-laravel
php artisan vendor:publish --tag=csiq-config
```

PHP needs `ext-grpc` in the same PHP that serves requests. Composer cannot install the extension. Auto-discovers `CsiqConfigServiceProvider`. No `AppServiceProvider` bind.

## How to use

Type-hint `IConfigurationClient`. Laravel resolves the singleton (one gRPC channel per PHP-FPM worker) and injects it.

```php
use Csiq\Configuration\IConfigurationClient;

public function sendMemo(Request $request, IConfigurationClient $config)
{
    $practiceId = (int) Auth::user()->practice_id;
    if (!$config->isFeatureEnabled('is_memo_enabled', $practiceId)) {
        return $this->error('Memo pushing is currently disabled.');
    }
}
```

`accountId` is CSIQ `practice_id`. Prefer `getAll($practiceId, $names)` for several keys in one RPC.

Outside `local` / `testing`, loopback URIs and `insecure=true` throw.

## Supported operations

These are the `IConfigurationClient` methods the wrapper binds. Call them on the injected client. Optional ids (`$userId`, `$locationId`, `$locationGroupId`, `$attributeArea`) narrow the override layer. Omitted ids are not sent. Scope defaults to `ConfigDefinitionScope::SERVER`.

### Reads

| Method | Use when | Missing name / no stored row |
| --- | --- | --- |
| `isFeatureEnabled($name, $accountId, ?$userId, …)` | One **feature** toggle | `false`. Off, defined-but-empty, and unknown name are not distinguishable. A config or dev toggle name fails the call. |
| `isDevToggleEnabled($name, $accountId, …)` | One **dev** toggle | Same as `isFeatureEnabled`: `false`. |
| `tryGet($name, $accountId, …)` | One value, any type | `null` if the name is not a definition, or a non-toggle config has no value. A feature/dev toggle with no row is `false`, not `null`. |
| `getConfigurationDetails($name, $accountId, …)` | Winner plus which layer it came from | Throws `ConfigurationException`. Prefer `tryGet` when missing is expected. |
| `getAll($accountId, $names = [], …)` | Several keys, **one RPC** | Named list recommended. Empty `$names` dumps every definition. Toggles with no row are `false`. Configs and unknown names are **omitted**. |
| `getAllAccountConfigurations($name, $accountId, $overrideLevels, …)` | Stored rows at given `OverrideLevel`s, not the winner | A toggle with no stored value is absent here even though `isFeatureEnabled` would be `false`. |

```php
$set = $config->getAll($practiceId, ['is_memo_enabled', 'dni_regex'], userId: $userId);
$memoOn = $set->toggles['is_memo_enabled'] ?? false;
$regex = $set->configurations['dni_regex'] ?? null;
// missing vs off: array_key_exists('dni_regex', $set->configurations)
```

### Writes

The CareStack **definition must already exist**. These calls do not create one.

| Method | Effect |
| --- | --- |
| `upsert($name, $accountId, $value, $editedByUserId, …)` | Insert or replace at that layer. `bool` → boolean, `int` → number, `string` → string, anything else → JSON. |
| `delete($name, $accountId, $deletedByUserId, …)` | Remove the stored value. The definition stays. A later feature/dev toggle read is `false`. |

A down service throws `ConfigurationException`. `false` from `isFeatureEnabled` is a successful RPC.

## Env

Put these in `.env`. They are read from `config/csiq-config.php`, not `getenv()` inside the package.

| Key | Default | Notes |
| --- | --- | --- |
| `CONFIG_SERVICE_URI` | `127.0.0.1:7154` | `host:port`. Local HTTPS HTTP/2 is 7154. Use `localhost` if the cert SAN is not `127.0.0.1`. |
| `CONFIG_SERVICE_INSECURE` | `false` | `true` = plaintext HTTP/2. Forbidden outside `local`/`testing`. |
| `CONFIG_SERVICE_ROOT_CERT_PATH` | unset | PEM of the CA or self-signed server cert. **Required for TLS** (`ext-grpc` does not use the OS trust store). |
| `CONFIG_SERVICE_TIMEOUT_MS` | `2000` | Deadline for one client call, retries included. A PHP-FPM worker will not block longer than this. |
| `CONFIG_SERVICE_LOG_CHANNEL` | default | Laravel log channel for gRPC retries. |

Retry / keepalive keys match `csiq/config-php` (`CONFIG_SERVICE_RETRY_*`, `CONFIG_SERVICE_KEEPALIVE_*`).

## Artisan

Live gRPC (same bound client as HTTP):

```bash
php artisan csiq-config:smoke --account=1 --name=is_memo_enabled
php artisan csiq-config:set is_memo_enabled true --account=1 --edited-by=1
```

`--as=bool|int|string` overrides auto detection on `set`. The definition must already exist.

With a local `dotnet run` service:

```bash
APP_ENV=local \
CONFIG_SERVICE_URI=localhost:7154 \
CONFIG_SERVICE_INSECURE=false \
CONFIG_SERVICE_ROOT_CERT_PATH=/path/to/ca.pem \
php artisan csiq-config:smoke --account=1 --name=is_memo_enabled
```

## Tests

PHPUnit does not call the live service. `Tests\TestCase` in CSIQ-Laravel binds `FakeConfigurationClient`. In this package:

```php
use Csiq\Configuration\IConfigurationClient;
use Csiq\Configuration\Testing\FakeConfigurationClient;

$this->app->instance(IConfigurationClient::class, new FakeConfigurationClient(
    ['is_memo_enabled' => true],
    ['dni_regex' => '^[0-9]+$'],
));
```

```bash
composer test
```
