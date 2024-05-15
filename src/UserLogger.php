<?php

namespace Topoff\LaravelUserLogger;

use Exception;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log as LaravelLogger;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use Ramsey\Uuid\Uuid;
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
 */
class UserLogger
{
    /**
     * The Laravel application instance.
     */
    protected Application $app;

    protected ?bool $enabled = null;
    protected ?Agent $agent = null;
    protected ?Domain $domain = null;
    protected Request $request;
    protected DeviceRepository $deviceRepository;
    protected UriRepository $uriRepository;
    protected AgentRepository $agentRepository;
    protected LanguageRepository $languageRepository;
    protected DomainRepository $domainRepository;
    protected RefererRepository $refererRepository;
    protected SessionRepository $sessionRepository;
    protected LogRepository $logRepository;
    protected ExperimentLogRepository $experimentLogRepository;
    protected ?Log $log = null;
    protected ?Session $session = null;
    protected ?Device $device = null;
    protected ?Language $language = null;
    protected ?Referer $referer = null;
    protected ?ExperimentLog $experimentLog;
    protected array $blacklistUris = [];

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

        $this->blacklistUris = Cache::rememberForever('user-logger.blacklist_routes', static fn () => config('user-logger.blacklist_routes') ?: []);
    }

    public function boot(): void
    {
        if (config('app.debug')) {
            // Display Error if app.debug is true
            $crawlerDetect = new CrawlerDetect;
            if (config('user-logger.log_robots') || ! $crawlerDetect->isCrawler()) {
                $this->log = $this->createLog();
            }
        } else {
            // try - catch in middleware not working as expected: https://github.com/laravel/framework/issues/14573
            // Intentional used twice, in InjectUserLogger Middleware -> completely suppresses errors in this package
            // and here: does log some of them.
            try {
                $crawlerDetect = new CrawlerDetect;
                if (config('user-logger.log_robots') || ! $crawlerDetect->isCrawler()) {
                    $this->log = $this->createLog();
                }
            } catch (Exception $e) {
                // Sometimes reached
                LaravelLogger::warning('Error in topoff/user-logger: '.$e->getMessage(), $e->getTrace());
            }
        }
    }

    /**
     * Create the Log of the Request
     */
    protected function createLog(?string $event = null, ?string $entityType = null, ?string $entityId = null): ?Log
    {
        try {
            // URI -> decoded path liefert ohne variablen
            $uri = $this->uriRepository->findOrCreate(['uri' => $this->request->decodedPath()]);

            // Domain
            $this->domain = $this->domainRepository->findOrCreate(['name' => $this->request->getHost(), 'local' => true]);

            // Session
            $this->session = $this->getOrCreateSession();

            // Check if the uri is blacklisted, if so, set the session to robot and suspicious
            if (($this->session->isNoRobot() || $this->session->isNotSuspicious()) && $this->isInBlacklistedUriArray($this->request)) {
                $this->sessionRepository->setRobotAndSuspicious($this->session);
            }

            // Experiment
            if (config('user-logger.use_experiments')) {
                $this->experimentLog = $this->getOrCreateExperimentLog($this->session);
            }

            // Log
            return $this->logRepository->create($this->session, $this->domain, $uri, $event, $entityType, $entityId);
        } catch (Exception $e) {
            if (config('user-logger.debug') === true && ! empty($this->request->userAgent())) {
                Debug::create(['kind' => 'user-agent', 'value' => 'Error in getOrCreateSession: '.$e->getMessage().' in '.$e->getFile().' on line '.$e->getLine().' - Trace: '.$e->getTraceAsString()]);
            }
        }

        return null;
    }

    protected function setSessionFromRequest(SessionHelper $sessionHelper): void
    {
        if ($sessionHelper->isExistingSession()) {
            $this->session = $this->sessionRepository->find($sessionHelper->getSessionUuid());

            if (! empty($this->session)) {
                $this->session = $this->sessionRepository->updateUser($this->session, Auth::user());
            } else {
                LaravelLogger::warning(get_class($this).'->'.__FUNCTION__.': die sessionHelper '.$sessionHelper->getSessionUuid().' wurde nicht in der DB table sessions gefunden.');
            }
        }
    }

    /**
     * Get or Create The Session Record of the Request
     *
     * @throws Exception
     */
    protected function getOrCreateSession(): Session
    {
        if ($this->session === null) {
            $sessionHelper = new SessionHelper($this->request);
            $this->setSessionFromRequest($sessionHelper);
        }

        if ($this->session === null) {
            $this->referer ??= $this->getOrCreateReferer();

            try {
                $userAgentParser = new UserAgentParser($this->request);
                $this->device = $this->deviceRepository->findOrCreate($userAgentParser->getDeviceAttributes());
                $this->agent = $this->agentRepository->findOrCreate($userAgentParser->getAgentAttributes());
            } catch (NoResultFoundException $e) {
                if (config('user-logger.debug') === true && ! empty($this->request->userAgent())) {
                    Debug::create(['kind' => 'user-agent', 'value' => $this->request->userAgent()]);
                }
                $this->device = null; //$this->deviceRepository->findOrCreateNotDetected();
                $this->agent = null; //$this->agentRepository->findOrCreateNotDetected();
            } catch (PackageNotLoadedException $e) {
                if (config('user-logger.debug') === true && ! empty($this->request->userAgent())) {
                    Debug::create(['kind' => 'user-agent', 'value' => 'PackageNotLoadedException'.$e->getMessage()]);
                }
                $this->device = null; //$this->deviceRepository->findOrCreateNotDetected();
                $this->agent = null; //$this->agentRepository->findOrCreateNotDetected();
            }

            // Language
            $languageParser = new LanguageParser($this->request);
            if ($languageParser->getLanguageAttributes() !== null) {
                $this->language = $this->languageRepository->findOrCreate($languageParser->getLanguageAttributes());
            } else {
                if (config('user-logger.debug') === true) {
                    Debug::create(['kind' => 'language', 'value' => $this->request->header('accept-language')]);
                }
                $this->language = null;
            }

            if ($this->isInBlacklistedUriArray($this->request)) {
                $suspicious = true;
                $isRobot = true;
            } else {
                // If the agent and the device couldn't be parsed, mark as suspicious
                $suspicious = empty($this->agent) && empty($this->device);

                // Agents can be set manually in the agents table as robots and this will overwrite the is_robot detection from
                // the UserAgentParser Result.
                $isRobot = (isset($this->agent) && $this->agent->is_robot) || (isset($this->device) && $this->device['is_robot']);
            }

            // Session
            return $this->sessionRepository->findOrCreate($sessionHelper->getSessionUuid(), Auth::user(), $this->device, $this->agent, $this->referer, $this->language, $this->request->ip(), $suspicious, $isRobot);
        }

        return $this->session;
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
     */
    protected function getOrCreateReferer(): ?Referer
    {
        $refererUrl = $this->request->headers->get('referer');

        // 1 - from url: utm_source -ok
        $utmUrlParser = new UtmSourceParser($this->request->fullUrl());
        $refererResult = $utmUrlParser->getResult();

        // 2 - from referer: with referer-parser -ok
        if ((empty($refererResult) || empty($refererResult->source)) && ! empty($refererUrl)) {
            $refererParser = new RefererParser($refererUrl);
            $refererResult = $refererParser->getResult();
        }
        // 3 - from referer: utm_source
        if ((empty($refererResult) || empty($refererResult->source)) && ! empty($refererUrl)) {
            $utmRefParser = new UtmSourceParser($refererUrl);
            $refererResult = $utmRefParser->getResult();
        }
        // 4 - from referer: local domain
        if (empty($refererResult) || empty($refererResult->source)) {
            $refererParser = new RefererParser($refererUrl, $this->request->fullUrl());
            $refererResult = $refererParser->getResult();
        }
        // 5 - from url: atlg - mail
        if (empty($refererResult) || empty($refererResult->source)) {
            $urlPathParser = new UrlPathParser($this->request->fullUrl(), config('user-logger.internal_domains'));
            $refererResult = $urlPathParser->getResult();
        }

        if (! empty($refererResult->domain)) {
            $domain = $this->getOrCreateDomain($refererResult->domain, $refererResult->domain_intern);
            $referer = $this->refererRepository->findOrCreate($domain, $refererResult);
        } else {
            if (config('user-logger.debug') === true) {
                Debug::create(['kind' => 'url', 'value' => $this->request->fullUrl()]);
                if (! empty($this->request->headers->get('referer'))) {
                    Debug::create(['kind' => 'referer', 'value' => $this->request->headers->get('referer')]);
                }
            }
        }

        return $referer ?? null;
    }

    protected function getOrCreateDomain(string $name, bool $local): Domain
    {
        return $this->domainRepository->findOrCreate(['name' => $name, 'local' => $local]);
    }

    protected function getOrCreateExperimentLog(Session $session): ExperimentLog
    {
        $this->experimentLog = $this->experimentLogRepository->firstOrCreate(['session_id' => $session->id], ['experiment' => $this->getRandomExperimentName()]);

        return $this->experimentLog;
    }

    /**
     * Gets a Random Element from the Experiments from the config
     */
    private function getRandomExperimentName(): ?string
    {
        if (empty(config('user-logger.experiments'))) {
            return null;
        }

        return config('user-logger.experiments')[array_rand(config('user-logger.experiments'), 1)];
    }

    public function setRefererFromExternalUrl(string $refererUrl): self
    {
        if ($this->isEnabled()) {
            $refererParser = new RefererParser($refererUrl);
            $refererResult = $refererParser->getResultFromPartnerUrl();
            $domain = $this->getOrCreateDomain($refererResult->domain, $refererResult->domain_intern);
            $this->referer = $this->refererRepository->findOrCreate($domain, $refererResult);
        }

        return $this;
    }

    /**
     * Update an existing Log with an Event or create a new Log with an Event
     */
    public function setEvent(string $event, ?string $entityType = null, ?string $entityId = null): ?Log
    {
        if ($this->isEnabled()) {
            if ($this->log) {
                return $this->logRepository->updateWithEvent($this->log, $event, $entityType, $entityId);
            } else {
                return $this->createLog($event, $entityType, $entityId);
            }
        } else {
            return null;
        }
    }

    /**
     * Create an Event if the UserLogger is in only-event mode
     */
    public function setEventWithSessionId(?string $sessionId = null, ?string $event = null, ?string $entityType = null, ?string $entityId = null): ?Log
    {
        if ($this->isDisabled()) {
            return null;
        }

        $sessionId = $sessionId ?? Uuid::uuid7()->toString();
        $this->session = $this->sessionRepository->findOrCreate($sessionId);
        $lastLog = $this->session->logs()->orderBy('created_at', 'desc')->first();

        return $this->logRepository->createMinimal($this->session, $lastLog?->domain_id, null, $event, $entityType, $entityId);
    }

    public function isDisabled(): bool
    {
        return ! $this->isEnabled();
    }

    public function isEnabled(): bool
    {
        if ($this->enabled === null) {
            $config = $this->app['config'];
            $configEnabled = $config->get('user-logger.enabled') ?? false;

            $this->enabled = $configEnabled && ! $this->app->runningInConsole() && ! $this->app->environment('testing');
        }

        return $this->enabled;
    }

    /**
     * Update an existing Log with a Comment or create a new Log with a Comment
     */
    public function setComment(string $comment): ?Log
    {
        // If there is no log present, don't do anything. Could be a robot when robot is disabled are something like this.
        if ($this->isEnabled() && $this->log) {
            return $this->logRepository->updateWithComment($this->log, $comment);
        } else {
            return null;
        }
    }

    /**
     * Get the Session of the current Request
     */
    public function getCurrentSession(): ?Session
    {
        return $this->session;
    }

    /**
     * Get the Log of the current Request
     */
    public function getCurrentLog(): ?Log
    {
        return $this->log;
    }

    /**
     * Get the device of the current Request
     */
    public function getCurrentDevice(): ?Device
    {
        try {
            // because of performance it's just parsed in the first request,
            // so otherwise it has to be taken from the db out of the session
            if (empty($this->device) && ! empty($this->session)) {
                $this->device = $this->session->device;
            }
        } catch (Exception $e) {
            $this->device = null;
        }

        return $this->device;
    }

    /**
     * Get the referer of the current Request
     */
    public function getCurrentReferer(): ?Referer
    {
        try {
            // because of performance it's just parsed in the first request,
            // so otherwise it has to be taken from the db out of the session
            if (empty($this->referer) && ! empty($this->session)) {
                $this->referer = $this->session->referer;
            }
        } catch (Exception $e) {
            $this->referer = null;
        }

        return $this->referer;
    }

    /**
     * Get the (browser) language of the current Request
     * Private because it's not set in every request,
     * because of performance improvement, we just
     * parse it if there is no session
     */
    public function getCurrentLanguage(): ?Language
    {
        try {
            // because of performance it's just parsed in the first request,
            // so otherwise it has to be taken from the db out of the session
            if (empty($this->language) && ! empty($this->session)) {
                $this->language = $this->session->language;
            }
        } catch (Exception $e) {
            $this->language = null;
        }

        return $this->language;
    }

    /**
     * Get the User Agent of the current Request
     */
    public function getCurrentAgent(): ?Agent
    {
        try {
            // because of performance it's just parsed in the first request,
            // so otherwise it has to be taken from the db out of the session
            if (empty($this->agent) && ! empty($this->session)) {
                $this->agent = $this->session->agent;
            }
        } catch (Exception $e) {
            $this->agent = null;
        }

        return $this->agent;
    }

    /**
     * Get the Domain of the current Request
     */
    public function getCurrentDomain(): ?Domain
    {
        return $this->domain;
    }

    /**
     * Get the ExperimentLog of the current Request
     */
    public function getCurrentExperimentLog(): ?ExperimentLog
    {
        return $this->experimentLog;
    }

    /**
     * Check if the current Request is running in the asked $experimentName
     */
    public function isExperiment(string $experimentName): bool
    {
        // Crawlers, immer erstes Experiment angeben, wird nicht geloggt
        if (empty($this->experimentLog)) {
            return config('user-logger.experiments')[0] === $experimentName;
        }

        return $this->experimentLog->experiment === $experimentName;
    }

    /**
     * Determine if the request has a URI that should be ignored.
     */
    protected function isInBlacklistedUriArray(Request $request): bool
    {
        foreach ($this->blacklistUris as $blacklistUri) {
            if ($blacklistUri !== '/') {
                $blacklistUri = trim($blacklistUri, '/');
            }

            if ($request->is($blacklistUri)) {
                return true;
            }
        }

        return false;
    }
}
