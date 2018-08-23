<?php

namespace Topoff\Tracker;

use Illuminate\Routing\Router;
use Jenssegers\Agent\Agent;
use Topoff\Tracker\Middleware\InjectTracker;
use Topoff\Tracker\Repositories\AgentRepository;
use Topoff\Tracker\Repositories\DeviceRepository;
use Topoff\Tracker\Repositories\DomainRepository;
use Topoff\Tracker\Repositories\LanguageRepository;
use Topoff\Tracker\Repositories\LogRepository;
use Topoff\Tracker\Repositories\RefererRepository;
use Topoff\Tracker\Repositories\SessionRepository;
use Topoff\Tracker\Repositories\UriRepository;

class TrackerServiceProvider extends \Illuminate\Support\ServiceProvider
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
         $this->registerMiddleware(InjectTracker::class);
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/tracker.php', 'tracker');

        $this->app->singleton(Tracker::class, function ($app) {
            return new Tracker($app, new AgentRepository(), new DeviceRepository(), new DomainRepository(), new LanguageRepository(), new LogRepository(), new UriRepository(), new RefererRepository(), new SessionRepository(), new Agent(), $app['request']);
        });

        $this->app->alias(Tracker::class, 'tracker');
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
