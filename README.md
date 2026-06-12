# Laravel User Logger

[![Latest Stable Version](https://poser.pugx.org/topoff/laravel-user-logger/v/stable)](https://packagist.org/packages/topoff/laravel-user-logger)
[![Latest Unstable Version](https://poser.pugx.org/topoff/laravel-user-logger/v/unstable)](https://packagist.org/packages/topoff/laravel-user-logger) 
[![License](https://poser.pugx.org/topoff/laravel-user-logger/license)](https://packagist.org/packages/topoff/laravel-user-logger)
[![Total Downloads](https://poser.pugx.org/topoff/laravel-user-logger/downloads)](https://packagist.org/packages/topoff/laravel-user-logger) 

Laravel User Logger with Pennant-based experiment measurement.

## Requirements

- Laravel
- `laravel/pennant`


## Installation

Using Composer is currently the only supported way to install this package.

```bash
composer require topoff/laravel-user-logger
```

## Getting started

Publish the package config:

```bash
php artisan vendor:publish --tag=config
```

If you want to, create a dedicated `user-logger` database connection in `config/database.php`:


```php
        'user-logger' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => 'userlogger',
            'username' => env('DB_USERNAME', ''),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ],
```
Run migrations:

```bash
php artisan migrate
```

Set up Pennant (required for experiment variant storage/resolution):

```bash
php artisan vendor:publish --provider="Laravel\Pennant\PennantServiceProvider"
```

Then set the Pennant DB connection to your user-logger connection in `config/pennant.php`:

```php
'stores' => [
    'database' => [
        'driver' => 'database',
        'connection' => 'user-logger',
        'table' => 'features',
    ],
],
```

Run migrations again so the Pennant `features` table is created:

```bash
php artisan migrate
```

## Experiments

Experiment measurement uses `laravel/pennant`. Configure tracked features in `config/user-logger.php`:

```php
'experiments' => [
    'enabled' => true,
    'features' => [
        'landing-page-headline',
        'checkout-flow',
    ],
    'conversion_events' => [
        'conversion',
    ],
    'conversion_entity_types' => [],
    'nova' => [
        'enabled' => true,
    ],
    'pennant' => [
        'store' => 'user-logger',
        'connection' => 'user-logger',
        'table' => 'pennant_features',
        'auto_install' => true,
        'scope' => 'session',
    ],
],
```

Pennant storage is installed by this package via migrations on the `user-logger` connection (`pennant_features` table).  
This makes feature resolutions shareable across multiple apps that point to the same `user-logger` database.
With `auto_install=true` (default), the package also creates the Pennant table automatically at boot if it is missing.

The package only registers its own named Pennant store (`user-logger` by default).
Your app's default `database` store is left untouched - point your own features at
whatever store you like in `config/pennant.php`.

Flush all measured experiment data (asks for confirmation, use `--force` to skip):

```bash
php artisan user-logger:flush
```

## Nova

When Nova is installed and `experiments.nova.enabled` is `true`, the package auto-registers the `ExperimentMeasurement` Nova resource.

If your app defines a fully custom `Nova::mainMenu(...)`, you must also add the resource manually in that menu.

## Testing

```bash
composer test
```

## Performance Profiling

You can enable runtime profiling logs in `config/user-logger.php`:

```php
'performance' => [
    'enabled' => true,
    'log_queries' => true,
    'slow_ms' => 500,
    'sample_rate' => 1.0,      // fraction of requests that persist a row
    'retention_days' => 30,    // used by model:prune, 0 disables pruning
],
```

When enabled, the package logs:

- total request duration (`request_duration_ms`) - server-side time until response
- user-logger boot duration (`boot_duration_ms`)
- user-logger internal segment timings (`user_logger.segments`)
- optional query counters (`queries_total`, `queries_user_logger`)
- skip reason (`skip_reason`) when logging is bypassed

Slow request warnings can be emitted with `slow_ms` (set `0` to disable warnings).
Slow-request warnings are always emitted, regardless of `sample_rate`.

## Privacy & Data Retention

- Client ips are pseudonymized by default with a keyed HMAC-SHA256
  (key: `user-logger.ip_salt`, falls back to `app.key`). Use
  `php artisan user-logger:haship` to compute the stored value for a given ip.
  Note this is pseudonymization, not anonymization.
- Set `hash_ip` to `false` to store ips in plain text instead. In that case you
  should configure `retention.ip_days` and schedule
  `php artisan user-logger:prune-ips` - it removes the stored ip from sessions
  older than the retention period while keeping the sessions themselves:

```php
Schedule::command('user-logger:prune-ips')->daily();
```
- Logs, sessions and performance logs can be pruned via Laravel's `model:prune`.
  Configure `retention.logs_days`, `retention.sessions_days` and
  `performance.retention_days` (all default to disabled except performance logs),
  then schedule for example:

```php
Schedule::command('model:prune', [
    '--model' => [
        \Topoff\LaravelUserLogger\Models\Log::class,
        \Topoff\LaravelUserLogger\Models\Session::class,
        \Topoff\LaravelUserLogger\Models\PerformanceLog::class,
    ],
])->daily();
```

Sessions are only pruned once they have no remaining logs, so logs should use an
equal or shorter retention than sessions.

## Host Header Caution

The `domains` table is keyed by `Request::getHost()`. Make sure your web server or
Laravel's `TrustHosts` middleware restricts allowed hosts, otherwise spoofed Host
headers create unbounded rows.

## Testing the logger in a host app

The logger is disabled in the `testing` environment by default. Set
`user-logger.enabled_in_testing` to `true` (plus `enabled`) in tests that want to
exercise it.

## User-Agent Parsing Performance

`matomo/device-detector` supports cache-backed parsing:

```php
'user_agent' => [
    'cache' => true,
],
```

- `cache`: uses Laravel's default cache store to speed up parser internals.

The package automatically skips DeviceDetector bot matching when the request was already classified as a crawler via `CrawlerDetect`.

## Update

This package uses https://github.com/snowplow-referer-parser/referer-parser.
Use that repository to update the known referer list when needed.
