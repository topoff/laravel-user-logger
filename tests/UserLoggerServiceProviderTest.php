<?php

namespace Topoff\LaravelUserLogger\Tests;

use Topoff\LaravelUserLogger\UserLoggerServiceProvider;

require_once __DIR__.'/TestCase.php';

class UserLoggerServiceProviderTest extends TestCase
{
    public function test_service_provider_is_registered(): void
    {
        $this->assertTrue($this->app->providerIsLoaded(UserLoggerServiceProvider::class));
    }

    public function test_user_logger_config_is_available(): void
    {
        $config = config('user-logger');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('experiments', $config);
        $this->assertIsArray($config['experiments']);
    }
}
