<?php

namespace Topoff\Tracker;

/**
 * Class Tracker
 *
 * @package Topoff\Tracker
 */
class Tracker
{

    /**
     * The Laravel application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * Enabled
     *
     * @var bool
     */
    private $enabled;

    /**
     * Tracker constructor.
     *
     * @param null $app
     */
    public function __construct($app = NULL) {
        $this->app = $app ?? app();

        \Log::debug('Tracker construct');
    }

    /**
     *
     */
    public function boot()
    {
        \Log::debug('Tracker boot');
    }

    /**
     * Check if the Debugbar is enabled
     * @return boolean
     */
    public function isEnabled()
    {
        if ($this->enabled === null) {
            $config = $this->app['config'];
            $configEnabled = value($config->get('tracker.enabled'));

            $this->enabled = isset($configEnabled) && !$this->app->runningInConsole() && !$this->app->environment('testing');
        }

        return $this->enabled;
    }
}