<?php

namespace Topoff\LaravelUserLogger\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log as LaravelLogger;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Topoff\LaravelUserLogger\Models\PerformanceLog;
use Topoff\LaravelUserLogger\UserLogger;

class InjectUserLogger
{
    protected array $exceptUris = [];

    protected array $exceptUsers = [];

    protected bool $performanceEnabled = false;

    /**
     * The query listener must be registered only once per PHP process: under
     * Octane / long-running workers the DB connection outlives the middleware
     * instance and listeners would otherwise accumulate.
     */
    protected static bool $queryListenerRegistered = false;

    /**
     * @var array{total: int, user_logger: int}
     */
    protected static array $queryCounts = ['total' => 0, 'user_logger' => 0];

    /**
     * Create a new middleware instance.
     */
    public function __construct(protected UserLogger $userLogger)
    {
        $this->exceptUris = config('user-logger.do_not_track_routes') ?: [];
        $this->exceptUsers = config('user-logger.do_not_track_user_ids') ?: [];
        $this->performanceEnabled = config('user-logger.performance.enabled', false) === true;
    }

    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $requestStart = microtime(true);
        self::$queryCounts = ['total' => 0, 'user_logger' => 0];
        $skipReason = null;
        $bootDurationMs = null;
        $booted = false;

        if ($this->performanceEnabled && config('user-logger.performance.log_queries', false) === true) {
            $this->registerQueryListener();
        }

        if (Auth::id() && $this->inExceptUserArray(Auth::id())) {
            $skipReason = 'except_user';
        } elseif (config('app.debug')) {
            // Error will be displayed to the user, because it's not in try-catch
            ['booted' => $booted, 'duration_ms' => $bootDurationMs, 'skip_reason' => $skipReason] = $this->bootUserLogger($request);
        } else {
            // try - catch in middleware not working as expected: https://github.com/laravel/framework/issues/14573
            // BUT - regardless use it:
            // this does not log the error, but suppresses it completely
            try {
                ['booted' => $booted, 'duration_ms' => $bootDurationMs, 'skip_reason' => $skipReason] = $this->bootUserLogger($request);
            } catch (Throwable $th) {
                // will mostly not be called
                LaravelLogger::warning('Error in topoff/laravel-user-logger: '.$th->getMessage(), ['exception' => $th]);
            }
        }

        $response = $next($request);
        $this->logPerformance($request, $response, $requestStart, $booted, $bootDurationMs, $skipReason, self::$queryCounts);

        return $response;
    }

    protected function registerQueryListener(): void
    {
        if (self::$queryListenerRegistered) {
            return;
        }

        self::$queryListenerRegistered = true;
        DB::listen(static function ($query): void {
            self::$queryCounts['total']++;
            if ($query->connectionName === 'user-logger') {
                self::$queryCounts['user_logger']++;
            }
        });
    }

    /**
     * @return array{booted: bool, duration_ms: float|null, skip_reason: string|null}
     */
    public function bootUserLogger(Request $request): array
    {
        if (! $this->userLogger->isEnabled()) {
            return ['booted' => false, 'duration_ms' => null, 'skip_reason' => 'disabled'];
        }
        if ($this->inExceptUriArray($request)) {
            return ['booted' => false, 'duration_ms' => null, 'skip_reason' => 'except_uri'];
        }
        if ($this->inIgnoreIpsArray($request)) {
            return ['booted' => false, 'duration_ms' => null, 'skip_reason' => 'ignore_ip'];
        }
        if (config('user-logger.only-events') === true) {
            return ['booted' => false, 'duration_ms' => null, 'skip_reason' => 'only_events'];
        }

        $bootStart = microtime(true);
        $this->userLogger->boot();

        return [
            'booted' => true,
            'duration_ms' => round((microtime(true) - $bootStart) * 1000, 3),
            'skip_reason' => null,
        ];
    }

    /**
     * Determine if the request has a URI that should be ignored.
     */
    protected function inExceptUriArray(Request $request): bool
    {
        foreach ($this->exceptUris as $except) {
            if ($except !== '/') {
                $except = trim((string) $except, '/');
            }

            if ($request->is($except)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the request is from a userId that should be ignored.
     */
    protected function inExceptUserArray(int $userId): bool
    {
        return in_array($userId, $this->exceptUsers, true);
    }

    protected function inIgnoreIpsArray(Request $request): bool
    {
        return in_array($request->ip(), config('user-logger.ignore_ips'), true);
    }

    protected function logPerformance(
        Request $request,
        Response $response,
        float $requestStart,
        bool $booted,
        ?float $bootDurationMs,
        ?string $skipReason,
        array $queryCounts
    ): void {
        if (! $this->performanceEnabled) {
            return;
        }

        // Always honor do_not_track_routes for performance logs, regardless of other skip reasons.
        if ($this->inExceptUriArray($request) || $skipReason === 'except_uri') {
            return;
        }

        $requestStartAt = defined('LARAVEL_START') ? LARAVEL_START : $requestStart;
        $requestDurationMs = round((microtime(true) - $requestStartAt) * 1000, 3);
        $slowMs = (int) config('user-logger.performance.slow_ms', 0);
        $userLogger = $this->userLogger->getPerformanceSnapshot();
        $currentLog = $this->userLogger->getCurrentLog();
        $currentLogId = $currentLog?->id;
        $currentDomainId = $currentLog?->domain_id ?? $this->userLogger->getCurrentDomain()?->id;

        $context = [
            'path' => $request->path(),
            'method' => $request->method(),
            'status' => $response->getStatusCode(),
            'booted' => $booted,
            'skip_reason' => $skipReason,
            'request_duration_ms' => $requestDurationMs,
            'boot_duration_ms' => $bootDurationMs,
            'queries_total' => null,
            'queries_user_logger' => null,
            'log_id' => $currentLogId,
            'domain_id' => $currentDomainId,
            'user_logger_segments' => $userLogger['segments'] ?? null,
            'user_logger_counters' => $userLogger['counters'] ?? null,
            'user_logger_meta' => $userLogger['meta'] ?? null,
        ];

        if (config('user-logger.performance.log_queries', false) === true) {
            $context['queries_total'] = (int) $queryCounts['total'];
            $context['queries_user_logger'] = (int) $queryCounts['user_logger'];
        }

        if ($this->isSampled()) {
            try {
                PerformanceLog::query()->create($context);
            } catch (Throwable $exception) {
                LaravelLogger::warning(
                    'user-logger.performance.persist-failed: '.$exception->getMessage(),
                    ['path' => $request->path(), 'method' => $request->method()],
                );
            }
        }

        // Slow-request warnings are intentionally not sampled.
        if ($slowMs > 0 && $requestDurationMs >= $slowMs) {
            LaravelLogger::warning('user-logger.performance.slow-request', [
                'path' => $request->path(),
                'method' => $request->method(),
                'status' => $response->getStatusCode(),
                'request_duration_ms' => $requestDurationMs,
                'slow_threshold_ms' => $slowMs,
            ]);
        }
    }

    protected function isSampled(): bool
    {
        $sampleRate = (float) config('user-logger.performance.sample_rate', 1.0);
        if ($sampleRate >= 1.0) {
            return true;
        }
        if ($sampleRate <= 0.0) {
            return false;
        }

        return mt_rand() / mt_getrandmax() < $sampleRate;
    }
}
