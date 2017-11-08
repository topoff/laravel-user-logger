<?php namespace Topoff\Tracker\Middleware;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
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
     * @param  Container $container
     * @param Tracker    $tracker
     */
    public function __construct(Container $container, Tracker $tracker)
    {
        $this->container = $container;
        $this->tracker = $tracker;
//        $this->except = config('tracker.except') ?: [];
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($this->tracker->isEnabled() && !$this->inExceptArray($request)) {
            $this->tracker->boot();
        }

        return $next($request);
    }

    /**
     * Determine if the request has a URI that should be ignored.
     *
     * @param  \Illuminate\Http\Request  $request
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
