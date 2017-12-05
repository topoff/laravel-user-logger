<?php

namespace Topoff\Tracker;

use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use Topoff\Tracker\Repositories\DeviceRepository;
use UserAgentParser\Provider;

/**
 * Class Tracker
 *
 * @package Topoff\Tracker
 */
class Tracker
{

    protected $connection;

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
     * @var DeviceRepository
     */
    private $deviceRepository;

    /**
     * @var Agent
     */
    private $agent;

    /**
     * Tracker constructor.
     *
     * @param null             $app
     * @param DeviceRepository $deviceRepository
     * @param Agent            $agent
     */
    public function __construct($app = NULL, DeviceRepository $deviceRepository, Agent $agent, Request $request)
    {
        \Log::debug('Tracker construct');

        $this->app = $app ?? app();
        $config = $this->app['config'];
        $this->connection = $config->get('tracker.connection2');
        $test = $GLOBALS['request']->getRequestUri();

        $this->deviceRepository = $deviceRepository;
        $this->agent = $agent;
    }

    /**
     *
     */
    public function boot()
    {
        \Log::debug('Tracker boot');

        $this->deviceRepository->findOrCreateDevice($this->agent);

        $userAgent = 'Mozilla/5.0 (iPod; U; CPU iPhone OS 4_3_5 like Mac OS X; en-us) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8J2 Safari/6533.18.5';
        $userAgent = $_SERVER['HTTP_USER_AGENT'];

        $chain = new Provider\Chain([
                                        new Provider\BrowscapPhp(),
                                        new Provider\JenssegersAgent(),
                                        new Provider\PiwikDeviceDetector(),
                                        new Provider\UAParser(),
                                    ]);

        /* @var $result \UserAgentParser\Model\UserAgent */
        $result = $chain->parse($userAgent);
        // optional add all headers, to improve the result further (used currently only by WhichBrowser)
        //$result = $chain->parse($userAgent, getallheaders());

        $result->getBrowser()->getName(); // Mobile Safari

        $result->getOperatingSystem()->getName(); // iOS

        $result->getDevice()->getBrand(); // iPod Touch
        $result->getDevice()->getBrand(); // Apple
        $result->getDevice()->getType(); // portable media player

        $resultArray = $result->toArray();
    }




    //        if (!$userAgent && isset($_SERVER['HTTP_USER_AGENT'])) {
    //            $userAgent = $_SERVER['HTTP_USER_AGENT'];
    //        }
    //        $this->parser = Parser::create()->parse($userAgent);
    //        $this->userAgent = $this->parser->ua;
    //        $this->operatingSystem = $this->parser->os;
    //        $this->device = $this->parser->device;
    //        $this->basePath = $basePath;
    //        $this->originalUserAgent = $this->parser->originalUserAgent;

    /**
     * Check if the Debugbar is enabled
     *
     * @return boolean
     */
    public function isEnabled(): bool
    {
        if ($this->enabled === NULL) {
            $config = $this->app['config'];
            $configEnabled = $config->get('tracker.enabled') ?? false;

            $this->enabled = $configEnabled && !$this->app->runningInConsole() && !$this->app->environment('testing');
        }

        return $this->enabled;
    }

    /**
     * @return mixed
     */
    //    private function getOperatingSystemFamily()
    //    {
    //        try {
    //            return $this->userAgentParser->operatingSystem->family;
    //        } catch (\Exception $e) {
    //            return;
    //        }
    //    }

}