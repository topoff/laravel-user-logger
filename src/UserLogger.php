<?php

namespace Topoff\LaravelUserLogger;

use Auth;
use Exception;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use Log as Logger;
use Topoff\LaravelUserLogger\Models\Agent;
use Topoff\LaravelUserLogger\Models\Device;
use Topoff\LaravelUserLogger\Models\Domain;
use Topoff\LaravelUserLogger\Models\Language;
use Topoff\LaravelUserLogger\Models\Log;
use Topoff\LaravelUserLogger\Models\Referer;
use Topoff\LaravelUserLogger\Models\Session;
use Topoff\LaravelUserLogger\Parsers\LanguageParser;
use Topoff\LaravelUserLogger\Parsers\RefererParser;
use Topoff\LaravelUserLogger\Parsers\UserAgentParser;
use Topoff\LaravelUserLogger\Repositories\AgentRepository;
use Topoff\LaravelUserLogger\Repositories\DeviceRepository;
use Topoff\LaravelUserLogger\Repositories\DomainRepository;
use Topoff\LaravelUserLogger\Repositories\LanguageRepository;
use Topoff\LaravelUserLogger\Repositories\LogRepository;
use Topoff\LaravelUserLogger\Repositories\RefererRepository;
use Topoff\LaravelUserLogger\Repositories\SessionRepository;
use Topoff\LaravelUserLogger\Repositories\UriRepository;
use Topoff\LaravelUserLogger\Support\SessionHelper;
use UserAgentParser\Exception\NoResultFoundException;

/**
 * Class UserLogger
 *
 * @package Topoff\LaravelUserLogger
 */
class UserLogger
{
    /**
     * @var Domain
     */
    protected $domain;

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
     * UserLogger constructor.
     *
     * @param Application $app
     * @param AgentRepository $agentRepository
     * @param DeviceRepository $deviceRepository
     * @param DomainRepository $domainRepository
     * @param LanguageRepository $languageRepository
     * @param LogRepository $logRepository
     * @param UriRepository $uriRepository
     * @param RefererRepository $refererRepository
     * @param SessionRepository $sessionRepository
     * @param Request $request
     */
    public function __construct(Application $app,
                                AgentRepository $agentRepository,
                                DeviceRepository $deviceRepository,
                                DomainRepository $domainRepository,
                                LanguageRepository $languageRepository,
                                LogRepository $logRepository,
                                UriRepository $uriRepository,
                                RefererRepository $refererRepository,
                                SessionRepository $sessionRepository,
                                Request $request)
    {
        $this->app = $app;
        $this->deviceRepository = $deviceRepository;
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
     * Boot the UserLogger
     *
     * @throws \UserAgentParser\Exception\PackageNotLoadedException
     */
    public function boot()
    {
        if (config('app.debug')) {
            // Display Error
            $this->log = $this->createLog();
        } else {
            // try - catch in middleware not working as expected: https://github.com/laravel/framework/issues/14573
            // Intentional used twice, in InjectUserLogger Middleware -> completely suppresses errors in this package
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
     * @throws \UserAgentParser\Exception\PackageNotLoadedException
     * @throws Exception
     */
    protected function createLog(string $event = NULL): Log
    {
        // URI
        $uri = $this->uriRepository->findOrCreate(['uri' => $this->request->getRequestUri()]);

        // Domain
        $this->domain = $this->domainRepository->findOrCreate(['name' => $this->request->getHost()]);

        return $this->logRepository->findOrCreate($this->getOrCreateSession(), $this->domain, $uri, $event);
    }

    /**
     * Get or Create The Session Record of the Request
     *
     * @return Session
     * @throws \UserAgentParser\Exception\PackageNotLoadedException
     * @throws Exception
     */
    protected function getOrCreateSession(): Session
    {
        $session = new SessionHelper($this->request);
        if (!$session->isExistingSession()){

        }

        try {
            $userAgentParser = new UserAgentParser($this->request->userAgent());
            $this->device = $this->deviceRepository->findOrCreate($userAgentParser->getDeviceAttributes());
            $this->agent = $this->agentRepository->findOrCreate($userAgentParser->getAgentAttributes());
        } catch (NoResultFoundException $e) {
            $this->device = $this->deviceRepository->findOrCreateNotDetected();
            $this->agent = $this->agentRepository->findOrCreateNotDetected();
        }

        // Referer
        $refererUrl = $this->request->headers->get('referer');
        $refererParser = new RefererParser($refererUrl, $this->request->url());
        $refererParserAttributes = $refererParser->getRefererAttributes();
        $refererDomain = $refererParserAttributes ? $this->domainRepository->findOrCreate(['name' => $refererParserAttributes['domain']]) : NULL;
        $refererAttributes = $refererParserAttributes ? array_except($refererParserAttributes, ['domain']) : NULL;
        $this->referer = $refererDomain ? $this->refererRepository->findOrCreate(array_merge(['domain_id' => $refererDomain->id], $refererAttributes, ['url' => $refererUrl])) : NULL;

        // Language
        $languageParser = new LanguageParser($this->request);
        $this->language = $this->languageRepository->findOrCreate($languageParser->getLanguageAttributes());

        // Session
        $this->session = $this->sessionRepository->findOrCreate($session->getSessionUuid(),
                                                                Auth::user(),
                                                                $this->device,
                                                                $this->agent,
                                                                $this->referer,
                                                                $this->language,
                                                                $this->domain,
                                                                $this->request->ip(),
                                                                $this->device['is_robot']);

        return $this->session;
    }

    /**
     * Check if the UserLogger is enabled
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

    /**
     * Get the User Agent of the current Request
     *
     * @return null|Agent
     */
    public function getCurrentAgent(): ?Agent
    {
        return $this->agent;
    }

    /**
     * Get the Domain of the current Request
     *
     * @return null|Domain
     */
    public function getCurrentDomain(): ?Domain
    {
        return $this->domain;
    }
}