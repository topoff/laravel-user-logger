<?php

namespace Topoff\LaravelUserLogger;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log as LaravelLogger;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Nova;
use Laravel\Pennant\Feature;
use Override;
use Throwable;
use Topoff\LaravelUserLogger\Console\Commands\Flush;
use Topoff\LaravelUserLogger\Console\Commands\HashIp;
use Topoff\LaravelUserLogger\Console\Commands\PruneIps;
use Topoff\LaravelUserLogger\Console\Commands\SummarizePerformance;
use Topoff\LaravelUserLogger\Console\Commands\UpdateReferers;
use Topoff\LaravelUserLogger\Middleware\InjectUserLogger;
use Topoff\LaravelUserLogger\Nova\Resources\ExperimentMeasurement;
use Topoff\LaravelUserLogger\Nova\Resources\PerformanceDailySummary;
use Topoff\LaravelUserLogger\Nova\Resources\PerformanceLog;
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
        $this->configurePennantStore();
        if ($this->isUserLoggerEnabled()) {
            $this->ensurePennantStoreInfrastructure();
        }

        $this->publishes([__DIR__.'/../config/user-logger.php' => config_path('user-logger.php')], 'config');

        if ($this->isUserLoggerEnabled()) {
            $this->loadMigrationsFrom(__DIR__.'/../resources/Migrations/');
            $this->registerMiddleware(InjectUserLogger::class);
            $this->registerNovaResources();
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                Flush::class,
                HashIp::class,
                PruneIps::class,
                SummarizePerformance::class,
                UpdateReferers::class,
            ]);

            $this->registerPerformanceSummarySchedule();
        }
    }

    /**
     * Auto-register the nightly performance summary in the host scheduler.
     * Opt out via user-logger.performance.daily_summary.schedule = false.
     */
    protected function registerPerformanceSummarySchedule(): void
    {
        if (config('user-logger.performance.daily_summary.schedule', true) !== true) {
            return;
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $at = (string) config('user-logger.performance.daily_summary.schedule_at', '00:21');

            $schedule->command(SummarizePerformance::class)
                ->dailyAt($at)
                ->withoutOverlapping();
        });
    }

    protected function configurePennantStore(): void
    {
        if (! class_exists(Feature::class)) {
            return;
        }

        $store = (string) config('user-logger.experiments.pennant.store', 'user-logger');
        $connection = (string) config('user-logger.experiments.pennant.connection', 'user-logger');
        $table = (string) config('user-logger.experiments.pennant.table', 'pennant_features');

        // Only register the package's own named store. The host app's default
        // "database" store must not be redirected to the user-logger connection.
        config([
            "pennant.stores.{$store}" => [
                'driver' => 'database',
                'connection' => $connection,
                'table' => $table,
            ],
        ]);
    }

    protected function ensurePennantStoreInfrastructure(): void
    {
        if (! class_exists(Feature::class)) {
            return;
        }

        if (config('user-logger.experiments.pennant.auto_install', true) !== true) {
            return;
        }

        $connection = (string) config('user-logger.experiments.pennant.connection', 'user-logger');
        $table = (string) config('user-logger.experiments.pennant.table', 'pennant_features');
        $cacheKey = "user-logger.pennant-table-exists.{$connection}.{$table}";

        try {
            if (Cache::get($cacheKey) === true) {
                return;
            }

            if (! Schema::connection($connection)->hasTable($table)) {
                Schema::connection($connection)->create($table, function (Blueprint $table): void {
                    $table->id();
                    $table->string('name');
                    $table->string('scope');
                    $table->text('value');
                    $table->timestamps();
                    $table->unique(['name', 'scope']);
                });
            }

            Cache::forever($cacheKey, true);
        } catch (Throwable $exception) {
            LaravelLogger::warning('user-logger.pennant.auto-install-failed: '.$exception->getMessage(), [
                'connection' => $connection,
                'table' => $table,
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

        if (! class_exists(Nova::class)) {
            return;
        }

        Nova::resources([
            ExperimentMeasurement::class,
            PerformanceLog::class,
            PerformanceDailySummary::class,
        ]);
    }

    protected function isUserLoggerEnabled(): bool
    {
        return config('user-logger.enabled', false) === true;
    }

    /**
     * Register the application services.
     */
    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/user-logger.php', 'user-logger');

        // scoped (not singleton): the instance captures the current request and
        // per-request state, which must be flushed between requests under Octane.
        $this->app->scoped(UserLogger::class, fn ($app): UserLogger => new UserLogger(
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
