<?php

namespace Topoff\Tracker;

use Exception;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use Topoff\Tracker\Models\Device;
use Topoff\Tracker\Models\Log;
use Topoff\Tracker\Models\Session;
use Topoff\Tracker\Models\Uri;
use Topoff\Tracker\Parsers\LanguageParser;
use Topoff\Tracker\Parsers\RefererParser;
use Topoff\Tracker\Parsers\UserAgentParser;
use Topoff\Tracker\Repositories\AgentRepository;
use Topoff\Tracker\Repositories\CookieRepository;
use Topoff\Tracker\Repositories\DeviceRepository;
use Topoff\Tracker\Repositories\DomainRepository;
use Topoff\Tracker\Repositories\LanguageRepository;
use Topoff\Tracker\Repositories\LogRepository;
use Topoff\Tracker\Repositories\RefererRepository;
use Topoff\Tracker\Repositories\SessionRepository;
use Topoff\Tracker\Repositories\UriRepository;
use Log as Logger;
use Topoff\Tracker\Support\Authentication;
use Topoff\Tracker\Support\SessionHelper;

/**
 * Class Tracker
 *
 * @package Topoff\Tracker
 */
class Tracker
{

    /**
     * @var
     */
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
     * @var Request
     */
    private $request;

    /**
     * @var UriRepository
     */
    private $uriRepository;

    /**
     * @var AgentRepository
     */
    private $agentRepository;

    /**
     * @var LanguageRepository
     */
    private $languageRepository;

    /**
     * @var DomainRepository
     */
    private $domainRepository;

    /**
     * @var RefererRepository
     */
    private $refererRepository;

    /**
     * @var SessionRepository
     */
    private $sessionRepository;

    /**
     * @var LogRepository
     */
    private $logRepository;

    /**
     * @var CookieRepository
     */
    private $cookieRepository;

    /**
     * Log
     *
     * @var Log
     */
    private $log;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var
     */
    private $device;

    /**
     * Tracker constructor.
     *
     * @param null               $app
     * @param AgentRepository    $agentRepository
     * @param CookieRepository   $cookieRepository
     * @param DeviceRepository   $deviceRepository
     * @param DomainRepository   $domainRepository
     * @param LanguageRepository $languageRepository
     * @param LogRepository      $logRepository
     * @param UriRepository      $uriRepository
     * @param RefererRepository  $refererRepository
     * @param SessionRepository  $sessionRepository
     * @param Agent              $agent
     * @param Request            $request
     */
    public function __construct($app = NULL,
                                AgentRepository $agentRepository,
                                CookieRepository $cookieRepository,
                                DeviceRepository $deviceRepository,
                                DomainRepository $domainRepository,
                                LanguageRepository $languageRepository,
                                LogRepository $logRepository,
                                UriRepository $uriRepository,
                                RefererRepository $refererRepository,
                                SessionRepository $sessionRepository,
                                Agent $agent,
                                Request $request)
    {
        // ist config('key') in package ok or not?
        $this->app = $app ?? app();
        $config = $this->app['config'];
//        $this->connection = config('tracker.connection');
        $test = $GLOBALS['request']->getRequestUri();

        $this->deviceRepository = $deviceRepository;
        $this->agent = $agent;
        $this->request = $request;
        $this->uriRepository = $uriRepository;
        $this->agentRepository = $agentRepository;
        $this->languageRepository = $languageRepository;
        $this->domainRepository = $domainRepository;
        $this->refererRepository = $refererRepository;
        $this->sessionRepository = $sessionRepository;
        $this->logRepository = $logRepository;
        $this->cookieRepository = $cookieRepository;
    }

    /**
     * Boot the Tracker
     */
    public function boot()
    {
//        \Log::debug('Tracker boot, uri: ' . $this->request->getUri());

        // try - catch in middleware not working as expected: https://github.com/laravel/framework/issues/14573
        // Intentional used twice, in InjectTracker Middleware -> completly surpresses errors in this package
        // and here: does log some of them..
//        try {
            $this->log = $this->createLog();
//        } catch (Exception $e) {
            // Sometimes reached..
//            Logger::warning('Error in topoff/tracker: ' . $e->getMessage(), $e->getTrace());
//        }
    }

    /**
     * Create the Log of the Request
     *
     * @return Log
     */
    private function createLog(string $event = null): Log
    {
        $uri = $this->uriRepository->findOrCreate(['uri' => $this->request->getUri()]);

        return $this->logRepository->findOrCreate($this->getOrCreateSession(), $uri, $event);
    }

    /**
     * Get or Create The Session Record of the Request
     *
     * @return Session
     */
    private function getOrCreateSession(): Session
    {
        $userAgent = $this->request->userAgent();
        $userAgentParser = new UserAgentParser($userAgent);

        $refererUrl = $this->request->headers->get('referer');
        $refererParser = new RefererParser($refererUrl, $this->request->url());
        $refererAttributes = $refererParser->getRefererAttributes();

        $this->device = $this->deviceRepository->findOrCreate($userAgentParser->getDeviceAttributes());
        $agent = $this->agentRepository->findOrCreate($userAgentParser->getAgentAttributes());
        $languageParser = new LanguageParser($this->request);
        $language = $this->languageRepository->findOrCreate($languageParser->getLanguageAttributes());
        $domain = $refererAttributes ? $this->domainRepository->findOrCreate($refererAttributes['domain']) : NULL;
        $referer = $domain ? $this->refererRepository->findOrCreate(['domain_id' => $domain->id]) : NULL;

        $session = new SessionHelper($this->request);
        $user = null; // Not yet found a method the get the authenticated user in a middleware
        $this->session = $this->sessionRepository->findOrCreate($session->getSessionUuid(), $user, $this->device, $agent, $referer, $language, $this->request->ip(), $this->device['is_robot']);
        return $this->session;
    }

    /**
     * Check if the Tracker is enabled
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
     * Update an existing Log with an Event or create a new Log
     *
     * @param string $event
     *
     * @return Log
     */
    public function trackEvent(string $event): Log
    {
        if ($this->log){
            return $this->logRepository->updateWithEvent($this->log, $event);
        } else {
            return $this->createLog($event);
        }
    }

    /**
     * Gets the Session ofthe current Request
     *
     * @return Session
     */
    public function getCurrentSession(): ?Session
    {
        return $this->session;
    }

    /**
     * Gets the Log of the current Request
     *
     * @return null|Log
     */
    public function getCurrentLog(): ?Log
    {
        return $this->log;
    }

    /**
     * Gets the device of the current Request
     *
     * @return null|Device
     */
    public function getCurrentDevice(): ?Device
    {
        return $this->device;
    }
}