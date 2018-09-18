# UserAgentParser

[![Latest Stable Version](https://poser.pugx.org/topoff/laravel-user-logger/v/stable)](https://packagist.org/packages/topoff/laravel-user-logger)
[![Latest Unstable Version](https://poser.pugx.org/topoff/laravel-user-logger/v/unstable)](https://packagist.org/packages/topoff/laravel-user-logger) 
[![License](https://poser.pugx.org/topoff/laravel-user-logger/license)](https://packagist.org/packages/topoff/laravel-user-logger)
[![Total Downloads](https://poser.pugx.org/topoff/laravel-user-logger/downloads)](https://packagist.org/packages/topoff/laravel-user-logger) 

This is a Simple user logger and A/B Testing Tool for laravel.

## Requirements  

Needs Laravel 5.6


## Installation

Using composer is currently the only supported way to install this package.

```
composer require topoff/laravel-user-logger
```

## Getting started

You can publish & change the configuration with this command:

```
php artisan vendor:publish
```

You need to create a connection namend user-logger in your config/database.php


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
## Experiences

To start with experiences, A/B testing, set use_experiments in the config file to true and define at least two experiments, per example a,b. 


```php
/*
    * use A/B Testing experiments
    */
    'use_experiments'     => true,

    /*
     * active experiments - max 16 chars
     * crawlers will always run as in the first experiment, but will not be logged
     */
    'experiments'         => [
        'a',
        'b',
    ],
```
To start e new experience, flush the old data with
```
php artisan user-logger:flush
```


## Update

This package uses https://github.com/snowplow-referer-parser/referer-parser. There you find information to update the list of known referers, which should sequently be done, manually.
