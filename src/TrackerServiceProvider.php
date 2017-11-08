<?php

namespace Spatie\Backup;

use Illuminate\Support\ServiceProvider;
use Spatie\Backup\Commands\ListCommand;
use Spatie\Backup\Helpers\ConsoleOutput;
use Spatie\Backup\Commands\BackupCommand;
use Spatie\Backup\Commands\CleanupCommand;
use Spatie\Backup\Commands\MonitorCommand;
use Spatie\Backup\Notifications\EventHandler;

class BackupServiceProvider extends ServiceProvider
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
