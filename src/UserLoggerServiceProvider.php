<?php

namespace Topoff\LaravelUserLogger;

use Illuminate\Routing\Router;
use Jenssegers\Agent\Agent;
use Topoff\LaravelUserLogger\Middleware\InjectUserLogger;
use Topoff\LaravelUserLogger\Repositories\AgentRepository;
use Topoff\LaravelUserLogger\Repositories\DeviceRepository;
use Topoff\LaravelUserLogger\Repositories\DomainRepository;
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
                             __DIR__ . '/../config/tracker.php' => config_path('tracker.php'),
                         ], 'config');

        $this->loadMigrationsFrom(__DIR__ . '/../resources/Migrations/');

        // Problem, it's not possible (yet?) to control the execution order of the middlewares
         $this->registerMiddleware(InjectUserLogger::class);
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/tracker.php', 'tracker');

        $this->app->singleton(UserLogger::class, function ($app) {
            return new UserLogger($app, new AgentRepository(), new DeviceRepository(), new DomainRepository(), new LanguageRepository(), new LogRepository(), new UriRepository(), new RefererRepository(), new SessionRepository(), new Agent(), $app['request']);
        });

        $this->app->alias(UserLogger::class, 'tracker');
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
}
