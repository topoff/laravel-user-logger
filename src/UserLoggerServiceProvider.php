<?php

namespace Topoff\LaravelUserLogger;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Topoff\LaravelUserLogger\Console\Commands\Flush;
use Topoff\LaravelUserLogger\Console\Commands\HashIp;
use Topoff\LaravelUserLogger\Middleware\InjectUserLogger;
use Topoff\LaravelUserLogger\Repositories\AgentRepository;
use Topoff\LaravelUserLogger\Repositories\DeviceRepository;
use Topoff\LaravelUserLogger\Repositories\DomainRepository;
use Topoff\LaravelUserLogger\Repositories\LanguageRepository;
use Topoff\LaravelUserLogger\Repositories\LogRepository;
use Topoff\LaravelUserLogger\Repositories\RefererRepository;
use Topoff\LaravelUserLogger\Repositories\SessionRepository;
use Topoff\LaravelUserLogger\Repositories\UriRepository;
use Topoff\LaravelUserLogger\Services\ExperimentMeasurementService;

class UserLoggerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->publishes([__DIR__.'/../config/user-logger.php' => config_path('user-logger.php')], 'config');

        $this->loadMigrationsFrom(__DIR__.'/../resources/Migrations/');

        $this->registerMiddleware(InjectUserLogger::class);
        $this->registerNovaResources();

        if ($this->app->runningInConsole()) {
            $this->commands([
                Flush::class,
                HashIp::class,
            ]);
        }
    }

    /**
     * Register the Middleware
     */
    protected function registerMiddleware(string $middleware): void
    {
        $router = $this->app->make(Router::class);
        $router->pushMiddlewareToGroup('web', $middleware);
    }

    protected function registerNovaResources(): void
    {
        if (config('user-logger.experiments.nova.enabled', true) !== true) {
            return;
        }

        if (! class_exists(\Laravel\Nova\Nova::class)) {
            return;
        }

        \Laravel\Nova\Nova::resources([
            \Topoff\LaravelUserLogger\Nova\Resources\ExperimentMeasurement::class,
            \Topoff\LaravelUserLogger\Nova\Resources\PerformanceLog::class,
        ]);
    }

    /**
     * Register the application services.
     */
    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/user-logger.php', 'user-logger');

        $this->app->singleton(UserLogger::class, fn ($app): UserLogger => new UserLogger(
            $app,
            new AgentRepository,
            new DeviceRepository,
            new DomainRepository,
            new LanguageRepository,
            new LogRepository,
            new UriRepository,
            new RefererRepository,
            new SessionRepository,
            new ExperimentMeasurementService,
            $app['request'],
        ));

        $this->app->alias(UserLogger::class, 'userLogger');
    }
}
