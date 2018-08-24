# UserAgentParser

[![Build Status](https://travis-ci.org/topoff/laravel-user-logger.svg?branch=master)](https://travis-ci.org/topoff/laravel-user-logger)
[![Code Coverage](https://scrutinizer-ci.com/g/topoff/laravel-user-logger/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/topoff/laravel-user-logger/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/topoff/laravel-user-logger/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/topoff/laravel-user-logger/?branch=master)

[![Latest Stable Version](https://poser.pugx.org/topoff/laravel-user-logger/v/stable)](https://packagist.org/packages/topoff/laravel-user-logger)
[![Latest Unstable Version](https://poser.pugx.org/topoff/laravel-user-logger/v/unstable)](https://packagist.org/packages/topoff/laravel-user-logger) 
[![License](https://poser.pugx.org/topoff/laravel-user-logger/license)](https://packagist.org/packages/topoff/laravel-user-logger)
[![Total Downloads](https://poser.pugx.org/topoff/laravel-user-logger/downloads)](https://packagist.org/packages/topoff/laravel-user-logger) 

This is a Simple user logger for laravel.

## Requirements  

Needs Laravel 5.6


## Installation

Using composer is currently the only supported way to install this package.

```
composer require topoff/laravel-user-logger
```

## Getting started

You can change the configuration with

```
php artisan vendor:publish
```

You need to create a connection namend laravel-user-logger in your config/database.php


```php
        'laravel-user-logger' => [
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

## Update

This package uses https://github.com/snowplow-referer-parser/referer-parser. There you find information to update the list of known referers.
