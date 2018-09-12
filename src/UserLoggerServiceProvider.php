<?php

namespace Topoff\LaravelUserLogger;

use Illuminate\Routing\Router;
use Topoff\LaravelUserLogger\Middleware\InjectUserLogger;
use Topoff\LaravelUserLogger\Repositories\AgentRepository;
use Topoff\LaravelUserLogger\Repositories\DeviceRepository;
use Topoff\LaravelUserLogger\Repositories\DomainRepository;
use Topoff\LaravelUserLogger\Repositories\ExperimentLogRepository;
use Topoff\LaravelUserLogger\Repositories\LanguageRepository;
use Topoff\LaravelUserLogger\Repositories\LogRepository;
use Topoff\LaravelUserLogger\Repositories\RefererRepository;
use Topoff\LaravelUserLogger\Repositories\SessionRepository;
use Topoff\LaravelUserLogger\Repositories\UriRepository;

class UserLoggerServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->publishes([
                             __DIR__ . '/../config/user-logger.php' => config_path('user-logger.php'),
                         ], 'config');

        $this->loadMigrationsFrom(__DIR__ . '/../resources/Migrations/');

        $this->registerMiddleware(InjectUserLogger::class);
    }

    /**
     * Register the Middleware
     *
     * @param  string $middleware
     */
    protected function registerMiddleware($middleware)
    {
        $router = $this->app->make(Router::class);
        $router->pushMiddlewareToGroup('web', $middleware);
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/user-logger.php', 'user-logger');

        $this->app->singleton(UserLogger::class, function ($app) {
            return new UserLogger($app, new AgentRepository(), new DeviceRepository(), new DomainRepository(), new LanguageRepository(), new LogRepository(), new UriRepository(), new RefererRepository(), new SessionRepository(), new ExperimentLogRepository(), $app['request']);
        });

        $this->app->alias(UserLogger::class, 'userLogger');
    }
}
