<?php namespace Topoff\Tracker\Middleware;

use Closure;
use Exception;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Log;
use Topoff\Tracker\Tracker;

class InjectTracker
{
    /**
     * The Tracker instance
     *
     * @var Tracker
     */
    protected $tracker;

    /**
     * The URIs that should be excluded.
     *
     * @var array
     */
    protected $except = [];

    /**
     * Create a new middleware instance.
     *
     * @param Tracker    $tracker
     */
    public function __construct(Tracker $tracker)
    {
        $this->tracker = $tracker;
        $this->except = config('tracker.do_not_track_routes') ?: [];
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request $request
     * @param  Closure $next
     *
     * @return mixed
     * @throws \UserAgentParser\Exception\NoResultFoundException
     * @throws \UserAgentParser\Exception\PackageNotLoadedException
     */
    public function handle($request, Closure $next)
    {
        if (config('app.debug')) {
            // Error will be displayed
            if ($this->tracker->isEnabled() && !$this->inExceptArray($request)) {
                $this->tracker->boot();
            }

            return $next($request);
        } else {
            // try - catch in middleware not working as expected: https://github.com/laravel/framework/issues/14573
            // BUT - regardless use it:
            // this does not log the error, but suppresses it completely
            try {
                if ($this->tracker->isEnabled() && !$this->inExceptArray($request)) {
                    $this->tracker->boot();
                }
            } catch (Exception $e) {
                // will mostly not be called
                Log::warning('Error in topoff/tracker: ' . $e->getMessage(), $e->getTrace());
            } finally {
                return $next($request);
            }
        }
    }

    /**
     * Determine if the request has a URI that should be ignored.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return bool
     */
    protected function inExceptArray($request)
    {
        foreach ($this->except as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->is($except)) {
                return true;
            }
        }

        return false;
    }
}
