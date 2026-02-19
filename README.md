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

Create a dedicated `user-logger` database connection in `config/database.php`:


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
],
```

Flush all measured experiment data:

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

## Update

This package uses https://github.com/snowplow-referer-parser/referer-parser.
Use that repository to update the known referer list when needed.
