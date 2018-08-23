<?php namespace Topoff\Tracker\Middleware;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Monolog\Logger;
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
     * @var Logger
     */
    private $logger;

    /**
     * Create a new middleware instance.
     *
     * @param  Container $container
     * @param Tracker    $tracker
     */
    public function __construct(Container $container, Tracker $tracker)
    {
        $this->container = $container;
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
     */
    public function handle($request, Closure $next)
    {
        // try - catch in middleware not working as expected: https://github.com/laravel/framework/issues/14573
        // BUT - regardless use it:
        // this does not log the error, but surpresses it completly
        // -> nur diese von middleware oder alle?
//        try {
            if ($this->tracker->isEnabled() && !$this->inExceptArray($request)) {
                $this->tracker->boot();
            }
//        } catch (Exception $e) {
//             Never reached
//            Logger::warning('Error in topoff/tracker: ' . $e->getMessage(), $e->getTrace());
//        } finally {
            return $next($request);
//        }
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
