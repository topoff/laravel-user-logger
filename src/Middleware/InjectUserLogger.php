<?php

namespace Topoff\LaravelUserLogger\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log as LaravelLogger;
use Throwable;
use Topoff\LaravelUserLogger\UserLogger;

class InjectUserLogger
{
    protected array $exceptUris = [];

    protected array $exceptUsers = [];

    /**
     * Create a new middleware instance.
     */
    public function __construct(protected UserLogger $userLogger)
    {
        $this->exceptUris = Cache::rememberForever('user-logger.do_not_track_routes', static fn () => config('user-logger.do_not_track_routes') ?: []);
        $this->exceptUsers = Cache::rememberForever('user-logger.do_not_track_user_ids', static fn () => config('user-logger.do_not_track_user_ids') ?: []);
    }

    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::id() && $this->inExceptUserArray(Auth::id())) {
            return $next($request);
        }

        if (config('app.debug')) {
            // Error will be displayed to the user, because it's not in try-catch
            $this->bootUserLogger($request);

            return $next($request);
        }

        // try - catch in middleware not working as expected: https://github.com/laravel/framework/issues/14573
        // BUT - regardless use it:
        // this does not log the error, but suppresses it completely
        try {
            $this->bootUserLogger($request);
        } catch (Throwable $th) {
            // will mostly not be called
            LaravelLogger::warning('Error in topoff/laravel-user-logger: '.$th->getMessage(), $th->getTrace());
        } finally {
            return $next($request);
        }
    }

    public function bootUserLogger(Request $request): void
    {
        if ($this->userLogger->isEnabled() && ! $this->inExceptUriArray($request) && ! $this->inIgnoreIpsArray($request) && config('user-logger.only-events') === false) {
            $this->userLogger->boot();
        }
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
}
