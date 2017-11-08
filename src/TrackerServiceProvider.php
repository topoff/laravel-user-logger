<?php

namespace Todev\Tracker;

use Illuminate\Support\ServiceProvider;

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

        //$this->registerMiddleware(InjectTracker::class);
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/tracker.php', 'tracker');

        //$this->app->alias(LaravelTracker::class, 'tracker');
    }
}
