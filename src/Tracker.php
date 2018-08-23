<?php

namespace Topoff\Tracker;

use Auth;
use Exception;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use Jenssegers\Agent\Agent;
use Log as Logger;
use Topoff\Tracker\Models\Device;
use Topoff\Tracker\Models\Language;
use Topoff\Tracker\Models\Log;
use Topoff\Tracker\Models\Referer;
use Topoff\Tracker\Models\Session;
use Topoff\Tracker\Parsers\LanguageParser;
use Topoff\Tracker\Parsers\RefererParser;
use Topoff\Tracker\Parsers\UserAgentParser;
use Topoff\Tracker\Repositories\AgentRepository;
use Topoff\Tracker\Repositories\DeviceRepository;
use Topoff\Tracker\Repositories\DomainRepository;
use Topoff\Tracker\Repositories\LanguageRepository;
use Topoff\Tracker\Repositories\LogRepository;
use Topoff\Tracker\Repositories\RefererRepository;
use Topoff\Tracker\Repositories\SessionRepository;
use Topoff\Tracker\Repositories\UriRepository;
use Topoff\Tracker\Support\SessionHelper;

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
    protected $enabled;

    /**
     * @var DeviceRepository
     */
    protected $deviceRepository;

    /**
     * @var Agent
     */
    protected $agent;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var UriRepository
     */
    protected $uriRepository;

    /**
     * @var AgentRepository
     */
    protected $agentRepository;

    /**
     * @var LanguageRepository
     */
    protected $languageRepository;

    /**
     * @var DomainRepository
     */
    protected $domainRepository;

    /**
     * @var RefererRepository
     */
    protected $refererRepository;

    /**
     * @var SessionRepository
     */
    protected $sessionRepository;

    /**
     * @var LogRepository
     */
    protected $logRepository;

    /**
     * Log
     *
     * @var Log
     */
    protected $log;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var Device
     */
    protected $device;

    /**
     * @var Language
     */
    protected $language;

    /**
     * @var Referer
     */
    protected $referer;

    /**
     * Tracker constructor.
     *
     * @param Application        $app
     * @param AgentRepository    $agentRepository
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
    public function __construct(Application $app, AgentRepository $agentRepository, DeviceRepository $deviceRepository, DomainRepository $domainRepository, LanguageRepository $languageRepository, LogRepository $logRepository, UriRepository $uriRepository, RefererRepository $refererRepository, SessionRepository $sessionRepository, Agent $agent, Request $request)
    {
        $this->app = $app;
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
    }

    /**
     * Boot the Tracker
     *
     * @throws \UserAgentParser\Exception\NoResultFoundException
     * @throws \UserAgentParser\Exception\PackageNotLoadedException
     */
    public function boot()
    {
        if (config('app.debug')) {
            // Display Error
            $this->log = $this->createLog();
        } else {
            // try - catch in middleware not working as expected: https://github.com/laravel/framework/issues/14573
            // Intentional used twice, in InjectTracker Middleware -> completely suppresses errors in this package
            // and here: does log some of them..
            try {
                $crawlerDetect = new CrawlerDetect;
                if (config('tracker.log_robots') || !$crawlerDetect->isCrawler()) {
                    $this->log = $this->createLog();
                }
            } catch (Exception $e) {
                // Sometimes reached..
                Logger::warning('Error in topoff/tracker: ' . $e->getMessage(), $e->getTrace());
            }
        }
    }

    /**
     * Create the Log of the Request
     *
     * @param string|null $event
     *
     * @return Log
     * @throws \UserAgentParser\Exception\NoResultFoundException
     * @throws \UserAgentParser\Exception\PackageNotLoadedException
     * @throws Exception
     */
    protected function createLog(string $event = NULL): Log
    {
        $uri = $this->uriRepository->findOrCreate(['uri' => $this->request->getRequestUri()]);

        return $this->logRepository->findOrCreate($this->getOrCreateSession(), $uri, $event);
    }

    /**
     * Get or Create The Session Record of the Request
     *
     * @return Session
     * @throws \UserAgentParser\Exception\NoResultFoundException
     * @throws \UserAgentParser\Exception\PackageNotLoadedException
     * @throws Exception
     */
    protected function getOrCreateSession(): Session
    {
        $userAgent = $this->request->userAgent();
        $userAgentParser = new UserAgentParser($userAgent);

        // Referer
        $refererUrl = $this->request->headers->get('referer');
        $refererParser = new RefererParser($refererUrl, $this->request->url());
        $refererParserAttributes = $refererParser->getRefererAttributes();
        $refererDomain = $refererParserAttributes ? $this->domainRepository->findOrCreate(['name' => $refererParserAttributes['domain']]) : NULL;
        $refererAttributes = $refererParserAttributes ? array_except($refererParserAttributes, ['domain']) : NULL;
        $this->referer = $refererDomain ? $this->refererRepository->findOrCreate(array_merge(['domain_id' => $refererDomain->id], $refererAttributes, ['url' => $refererUrl])) : NULL;

        // Device
        $this->device = $this->deviceRepository->findOrCreate($userAgentParser->getDeviceAttributes());

        // Agent
        $agent = $this->agentRepository->findOrCreate($userAgentParser->getAgentAttributes());

        // Language
        $languageParser = new LanguageParser($this->request);
        $this->language = $this->languageRepository->findOrCreate($languageParser->getLanguageAttributes());

        // Domain
        $domain = $this->domainRepository->findOrCreate(['name' => $this->request->getHost()]);

        // Session
        $session = new SessionHelper($this->request);
        $this->session = $this->sessionRepository->findOrCreate($session->getSessionUuid(), Auth::user(), $this->device, $agent, $this->referer, $this->language, $domain, $this->request->ip(), $this->device['is_robot']);

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
     * @throws \UserAgentParser\Exception\NoResultFoundException
     * @throws \UserAgentParser\Exception\PackageNotLoadedException
     */
    public function trackEvent(string $event): Log
    {
        if ($this->log) {
            return $this->logRepository->updateWithEvent($this->log, $event);
        } else {
            return $this->createLog($event);
        }
    }

    /**
     * Get the Session of the current Request
     *
     * @return Session
     */
    public function getCurrentSession(): ?Session
    {
        return $this->session;
    }

    /**
     * Get the Log of the current Request
     *
     * @return null|Log
     */
    public function getCurrentLog(): ?Log
    {
        return $this->log;
    }

    /**
     * Get the device of the current Request
     *
     * @return null|Device
     */
    public function getCurrentDevice(): ?Device
    {
        return $this->device;
    }

    /**
     * Get the referer of the current Request
     *
     * @return null|Referer
     */
    public function getCurrentReferer(): ?Referer
    {
        return $this->referer;
    }

    /**
     * Get the (browser) language of the current Request
     *
     * @return null|Language
     */
    public function getCurrentLanguage(): ?Language
    {
        return $this->language;
    }
}