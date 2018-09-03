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
use Topoff\LaravelUserLogger\Parsers\UtmSourceParser;
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
     * @param Application        $app
     * @param AgentRepository    $agentRepository
     * @param DeviceRepository   $deviceRepository
     * @param DomainRepository   $domainRepository
     * @param LanguageRepository $languageRepository
     * @param LogRepository      $logRepository
     * @param UriRepository      $uriRepository
     * @param RefererRepository  $refererRepository
     * @param SessionRepository  $sessionRepository
     * @param Request            $request
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
            $crawlerDetect = new CrawlerDetect;
            if (config('user-logger.log_robots') || !$crawlerDetect->isCrawler()) {
                $this->log = $this->createLog();
            }
        } else {
            // try - catch in middleware not working as expected: https://github.com/laravel/framework/issues/14573
            // Intentional used twice, in InjectUserLogger Middleware -> completely suppresses errors in this package
            // and here: does log some of them..
            try {
                $crawlerDetect = new CrawlerDetect;
                if (config('user-logger.log_robots') || !$crawlerDetect->isCrawler()) {
                    $this->log = $this->createLog();
                }
            } catch (Exception $e) {
                // Sometimes reached..
                Logger::warning('Error in topoff/user-logger: ' . $e->getMessage(), $e->getTrace());
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
        // URI -> decoded path liefert ohne variablen
        $uri = $this->uriRepository->findOrCreate(['uri' => $this->request->decodedPath()]);

        // Domain
        $this->domain = $this->domainRepository->findOrCreate(['name' => $this->request->getHost(), 'local' => true]);

        // Log
        return $this->logRepository->create($this->getOrCreateSession(), $this->domain, $uri, $event);
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
        if ($session->isExistingSession()) {
            // Prüfen ob die Session wirklich in der DB vorhanden ist, sollte eigentlich zu 100%
            // todo, später entfernen und direkt über die session id gehen, bessere performance
            $this->session = $this->sessionRepository->find($session->getSessionUuid());
            \Log::warning(get_class($this) . '->' . __FUNCTION__ . ': die session ' . $session->getSessionUuid() . ' wurde nicht in der DB table sessions gefunden.');
        }

        if (empty($this->session)) {
            $this->referer = $this->getOrCreateReferer();

            try {
                $userAgentParser = new UserAgentParser($this->request);
                $this->device = $this->deviceRepository->findOrCreate($userAgentParser->getDeviceAttributes());
                $this->agent = $this->agentRepository->findOrCreate($userAgentParser->getAgentAttributes());
            } catch (NoResultFoundException $e) {
                $this->device = NULL; //$this->deviceRepository->findOrCreateNotDetected();
                $this->agent = NULL; //$this->agentRepository->findOrCreateNotDetected();
            }

            // Language
            $languageParser = new LanguageParser($this->request);
            if (!empty($languageParser)) {
                $this->language = $this->languageRepository->findOrCreate($languageParser->getLanguageAttributes());
            } else {
                $this->language = NULL;
            }

            // Session
            return $this->sessionRepository->findOrCreate($session->getSessionUuid(), Auth::user(), $this->device, $this->agent, $this->referer, $this->language, $this->request->ip(), $this->device['is_robot']);
        } else {
            return $this->session;
        }
    }

    /**
     * Get Referer, utm_source parameter wins over client referer
     *
     * @return null|Referer
     */
    protected function getOrCreateReferer(): ?Referer
    {
        $refererUrl = $this->request->headers->get('referer');
        $utmParser = new UtmSourceParser($this->request);
        if ($utmParser->hasUtmSource() === true) {
            $refererResult = $utmParser->getResult();
        } else {
            $refererParser = new RefererParser($refererUrl, $this->request->url());
            $refererResult = $refererParser->getResult();
        }

        if (!empty($refererResult->domain)) {
            $domain = $this->getOrCreateDomain($refererResult->domain);
            $referer = $this->refererRepository->findOrCreate($domain, $refererResult);
        }

        return $referer ?? NULL;
    }

    /**
     * @param string $name
     *
     * @return Domain
     */
    protected function getOrCreateDomain(string $name): Domain
    {
        return $this->domainRepository->findOrCreate(['name' => $name, 'local' => false]);
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
            $configEnabled = $config->get('user-logger.enabled') ?? false;

            $this->enabled = $configEnabled && !$this->app->runningInConsole() && !$this->app->environment('testing');
        }

        return $this->enabled;
    }

    /**
     * Update an existing Log with an Event or create a new Log
     *
     * @param string      $event
     *
     * @param string|null $entityType
     * @param string|null    $entityId
     *
     * @return Log
     * @throws \UserAgentParser\Exception\PackageNotLoadedException
     */
    public function setEvent(string $event, string $entityType = NULL, string $entityId = NULL): ?Log
    {
        if ($this->isEnabled()) {
            if ($this->log) {
                return $this->logRepository->updateWithEvent($this->log, $event, $entityType, $entityId);
            } else {
                return $this->createLog($event);
            }
        } else {
            return null;
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
        // because of performance it's just parsed in the first request,
        // so otherwise it has to be taken from the db out of the session
        if (empty($this->device)) {
            $this->device = $this->session->device;
        }

        return $this->device;
    }

    /**
     * Get the referer of the current Request
     *
     * @return null|Referer
     */
    public function getCurrentReferer(): ?Referer
    {
        // because of performance it's just parsed in the first request,
        // so otherwise it has to be taken from the db out of the session
        if (empty($this->referer)) {
            $this->referer = $this->session->referer;
        }

        return $this->referer;
    }

    /**
     * Get the (browser) language of the current Request
     * Private because it's not set in every request,
     * because of performance improvement, we just
     * parse it if there is no session
     *
     * @return null|Language
     */
    public function getCurrentLanguage(): ?Language
    {
        // because of performance it's just parsed in the first request,
        // so otherwise it has to be taken from the db out of the session
        if (empty($this->language)) {
            $this->language = $this->session->language;
        }

        return $this->language;
    }

    /**
     * Get the User Agent of the current Request
     *
     * @return null|Agent
     */
    public function getCurrentAgent(): ?Agent
    {
        // because of performance it's just parsed in the first request,
        // so otherwise it has to be taken from the db out of the session
        if (empty($this->agent)) {
            $this->agent = $this->session->agent;
        }

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