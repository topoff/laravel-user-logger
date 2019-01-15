<?php

namespace Topoff\LaravelUserLogger;

use Auth;
use Exception;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use Log as Logger;
use Topoff\LaravelUserLogger\Models\Agent;
use Topoff\LaravelUserLogger\Models\Debug;
use Topoff\LaravelUserLogger\Models\Device;
use Topoff\LaravelUserLogger\Models\Domain;
use Topoff\LaravelUserLogger\Models\ExperimentLog;
use Topoff\LaravelUserLogger\Models\Language;
use Topoff\LaravelUserLogger\Models\Log;
use Topoff\LaravelUserLogger\Models\Referer;
use Topoff\LaravelUserLogger\Models\Session;
use Topoff\LaravelUserLogger\Parsers\LanguageParser;
use Topoff\LaravelUserLogger\Parsers\RefererParser;
use Topoff\LaravelUserLogger\Parsers\UrlPathParser;
use Topoff\LaravelUserLogger\Parsers\UserAgentParser;
use Topoff\LaravelUserLogger\Parsers\UtmSourceParser;
use Topoff\LaravelUserLogger\Repositories\AgentRepository;
use Topoff\LaravelUserLogger\Repositories\DeviceRepository;
use Topoff\LaravelUserLogger\Repositories\DomainRepository;
use Topoff\LaravelUserLogger\Repositories\ExperimentLogRepository;
use Topoff\LaravelUserLogger\Repositories\LanguageRepository;
use Topoff\LaravelUserLogger\Repositories\LogRepository;
use Topoff\LaravelUserLogger\Repositories\RefererRepository;
use Topoff\LaravelUserLogger\Repositories\SessionRepository;
use Topoff\LaravelUserLogger\Repositories\UriRepository;
use Topoff\LaravelUserLogger\Support\SessionHelper;
use UserAgentParser\Exception\NoResultFoundException;
use UserAgentParser\Exception\PackageNotLoadedException;

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
     * @var ExperimentLogRepository
     */
    protected $experimentLogRepository;

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
     * @var ExperimentLog
     */
    protected $experimentLog;

    /**
     * UserLogger constructor.
     *
     * @param Application             $app
     * @param AgentRepository         $agentRepository
     * @param DeviceRepository        $deviceRepository
     * @param DomainRepository        $domainRepository
     * @param LanguageRepository      $languageRepository
     * @param LogRepository           $logRepository
     * @param UriRepository           $uriRepository
     * @param RefererRepository       $refererRepository
     * @param SessionRepository       $sessionRepository
     * @param ExperimentLogRepository $experimentLogRepository
     * @param Request                 $request
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
                                ExperimentLogRepository $experimentLogRepository,
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
        $this->experimentLogRepository = $experimentLogRepository;
    }

    /**
     * Boot the UserLogger
     *
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
     */
    protected function createLog(string $event = NULL): Log
    {
        // URI -> decoded path liefert ohne variablen
        $uri = $this->uriRepository->findOrCreate(['uri' => $this->request->decodedPath()]);

        // Domain
        $this->domain = $this->domainRepository->findOrCreate(['name' => $this->request->getHost(), 'local' => true]);

        // Session
        try {
            $this->session = $this->getOrCreateSession();
        } catch (Exception $e) {
            if (config('user-logger.debug') === true && !empty($this->request->userAgent())) {
                Debug::create(['kind' => 'user-agent', 'value' => 'Error in getOrCreateSession: ' . $e->getMessage()]);
            }
        }

        // Experiment
        if (config('user-logger.use_experiments')) $this->experimentLog = $this->getOrCreateExperimentLog($this->session);

        // Log
        return $this->logRepository->create($this->session, $this->domain, $uri, $event);
    }

    /**
     * Get or Create The Session Record of the Request
     *
     * @return Session
     * @throws Exception
     */
    protected function getOrCreateSession(): Session
    {
        $session = new SessionHelper($this->request);
        if ($session->isExistingSession()) {
            // Prüfen ob die Session wirklich in der DB vorhanden ist, sollte eigentlich zu 100%
            // todo, später entfernen und direkt über die session id gehen, bessere performance
            $this->session = $this->sessionRepository->find($session->getSessionUuid());
            if (!empty($this->session)) {
                $this->session = $this->sessionRepository->updateUser($this->session, Auth::user());
            } else {
                \Log::warning(get_class($this) . '->' . __FUNCTION__ . ': die session ' . $session->getSessionUuid() . ' wurde nicht in der DB table sessions gefunden.');
            }
        }

        if (empty($this->session)) {
            $this->referer = $this->getOrCreateReferer();

            try {
                $userAgentParser = new UserAgentParser($this->request);
                $this->device = $this->deviceRepository->findOrCreate($userAgentParser->getDeviceAttributes());
                $this->agent = $this->agentRepository->findOrCreate($userAgentParser->getAgentAttributes());
            } catch (NoResultFoundException $e) {
                if (config('user-logger.debug') === true && !empty($this->request->userAgent())) {
                    Debug::create(['kind' => 'user-agent', 'value' => $this->request->userAgent()]);
                }
                $this->device = NULL; //$this->deviceRepository->findOrCreateNotDetected();
                $this->agent = NULL; //$this->agentRepository->findOrCreateNotDetected();
            } catch (PackageNotLoadedException $e) {
                if (config('user-logger.debug') === true && !empty($this->request->userAgent())) {
                    Debug::create(['kind' => 'user-agent', 'value' => 'PackageNotLoadedException' . $e->getMessage()]);
                }
                $this->device = NULL; //$this->deviceRepository->findOrCreateNotDetected();
                $this->agent = NULL; //$this->agentRepository->findOrCreateNotDetected();
            }

            // Language
            $languageParser = new LanguageParser($this->request);
            if (!empty($languageParser)) {
                $this->language = $this->languageRepository->findOrCreate($languageParser->getLanguageAttributes());
            } else {
                if (config('user-logger.debug') === true) {
                    Debug::create(['kind' => 'language', 'value' => $this->request->header('accept-language')]);
                }
                $this->language = NULL;
            }

            // If the agent and the device couldn't be parsed, mark as suspicious
            $suspicious = empty($this->agent) && empty($this->device);

            // Agents can be set manually in the agents table as robots and this will overwrite the is_robot detection from
            // the UserAgentParser Result.
            $isRobot = (isset($this->agent) && $this->agent->is_robot) || $this->device['is_robot'];

            // Session
            return $this->sessionRepository->findOrCreate($session->getSessionUuid(), Auth::user(), $this->device, $this->agent, $this->referer, $this->language, $this->request->ip(), $suspicious, $isRobot);
        } else {
            return $this->session;
        }
    }

    /**
     * Get Referer, in this order
     *
     * 1 - from url: utm_source -ok
     * 2 - from referer: with referer-parser -ok
     * 3 - from referer: utm_source
     * 4 - from url: atlg
     * 5 - from referer: url & is local domain
     * 6 - NULL
     *
     * @return null|Referer
     */
    protected function getOrCreateReferer(): ?Referer
    {
        $refererUrl = $this->request->headers->get('referer');

        # 1 - from url: utm_source -ok
        $utmUrlParser = new UtmSourceParser($this->request->fullUrl());
        $refererResult = $utmUrlParser->getResult();

        # 2 - from referer: with referer-parser -ok
        if ((empty($refererResult) || empty($refererResult->source)) && !empty($refererUrl)) {
            $refererParser = new RefererParser($refererUrl);
            $refererResult = $refererParser->getResult();
        }
        # 3 - from referer: utm_source
        if ((empty($refererResult) || empty($refererResult->source)) && !empty($refererUrl)) {
            $utmRefParser = new UtmSourceParser($refererUrl);
            $refererResult = $utmRefParser->getResult();
        }
        # 4 - from referer: local domain
        if (empty($refererResult) || empty($refererResult->source)) {
            $refererParser = new RefererParser($refererUrl, $this->request->fullUrl());
            $refererResult = $refererParser->getResult();
        }
        # 5 - from url: atlg - mail
        if (empty($refererResult) || empty($refererResult->source)) {
            $urlPathParser = new UrlPathParser($this->request->fullUrl(), config('user-logger.internal_domains'));
            $refererResult = $urlPathParser->getResult();
        }

        if (!empty($refererResult->domain)) {
            $domain = $this->getOrCreateDomain($refererResult->domain, $refererResult->domain_intern);
            $referer = $this->refererRepository->findOrCreate($domain, $refererResult);
        } else {
            if (config('user-logger.debug') === true) {
                Debug::create(['kind' => 'url', 'value' => $this->request->fullUrl()]);
                if (!empty($this->request->headers->get('referer'))) Debug::create(['kind' => 'referer', 'value' => $this->request->headers->get('referer')]);
            }
        }

        return $referer ?? NULL;
    }

    /**
     * @param string $name
     * @param bool   $local
     *
     * @return Domain
     */
    protected function getOrCreateDomain(string $name, bool $local): Domain
    {
        return $this->domainRepository->findOrCreate(['name' => $name, 'local' => $local]);
    }

    /**
     * @param Session $session
     *
     * @return ExperimentLog
     */
    protected function getOrCreateExperimentLog(Session $session): ExperimentLog
    {
        $this->experimentLog = $this->experimentLogRepository->firstOrCreate(['client_ip' => $session->client_ip], ['experiment' => $this->getRandomExperimentName()]);

        return $this->experimentLog;
    }

    /**
     * Gets a Random Element from the Experiments from the config
     *
     * @return null|string
     */
    private Function getRandomExperimentName(): ?string
    {
        if (empty(config('user-logger.experiments'))) {
            return NULL;
        }

        return config('user-logger.experiments')[array_rand(config('user-logger.experiments'), 1)];
    }

    /**
     * Update an existing Log with an Event or create a new Log with an Event
     *
     * @param string      $event
     *
     * @param string|null $entityType
     * @param string|null $entityId
     *
     * @return Log
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
            return NULL;
        }
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
     * Update an existing Log with a Comment or create a new Log with a Comment
     *
     * @param string $comment
     *
     * @return Log|null
     */
    public function setComment(string $comment): ?Log
    {
        if ($this->isEnabled()) {
            // If there is no log present, don't do anything. Could be a robot when robot is disabled are something like this.
            if ($this->log) {
                return $this->logRepository->updateWithComment($this->log, $comment);
            }
        } else {
            return NULL;
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
        try {
            // because of performance it's just parsed in the first request,
            // so otherwise it has to be taken from the db out of the session
            if (empty($this->device) && !empty($this->session)) {
                $this->device = $this->session->device;
            }
        } catch (Exception $e) {
            $this->device = NULL;
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
        try {
            // because of performance it's just parsed in the first request,
            // so otherwise it has to be taken from the db out of the session
            if (empty($this->referer) && !empty($this->session)) {
                $this->referer = $this->session->referer;
            }
        } catch (Exception $e) {
            $this->referer = NULL;
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
        try {
            // because of performance it's just parsed in the first request,
            // so otherwise it has to be taken from the db out of the session
            if (empty($this->language) && !empty($this->session)) {
                $this->language = $this->session->language;
            }
        } catch (Exception $e) {
            $this->language = NULL;
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
        try {
            // because of performance it's just parsed in the first request,
            // so otherwise it has to be taken from the db out of the session
            if (empty($this->agent) && !empty($this->session)) {
                $this->agent = $this->session->agent;
            }
        } catch (Exception $e) {
            $this->agent = NULL;
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

    /**
     * Get the ExperimentLog of the current Request
     *
     * @return null|ExperimentLog
     */
    public function getCurrentExperimentLog(): ?ExperimentLog
    {
        return $this->experimentLog;
    }

    /**
     * Check if the current Request is running in the asked $experimentName
     *
     * @param string $experimentName
     *
     * @return bool
     */
    public function isExperiment(string $experimentName): bool
    {
        // Crawlers, immer erstes Experiment angeben, wird nicht geloggt
        if (empty($this->experimentLog)) return config('user-logger.experiments')[0] === $experimentName;

        return $this->experimentLog->experiment === $experimentName;
    }
}