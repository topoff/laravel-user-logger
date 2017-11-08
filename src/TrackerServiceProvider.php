<?php

namespace Topoff\Tracker;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use Topoff\Tracker\Middleware\InjectTracker;

class TrackerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/tracker.php' => config_path('tracker.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/tracker'),
        ]);

        $this->loadTranslationsFrom(__DIR__.'/../resources/lang/', 'tracker');

        $this->registerMiddleware(InjectTracker::class);
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/tracker.php', 'tracker');

        //$this->app->alias(LaravelTracker::class, 'tracker');
    }

    /**
     * Register the Middleware
     *
     * @param  string $middleware
     */
    protected function registerMiddleware($middleware)
    {
        $kernel = $this->app[Kernel::class];
        $kernel->pushMiddleware($middleware);
    }
}
