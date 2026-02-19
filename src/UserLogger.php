<?php

namespace Topoff\LaravelUserLogger;

use Exception;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log as LaravelLogger;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use Ramsey\Uuid\Uuid;
use Topoff\LaravelUserLogger\Models\Agent;
use Topoff\LaravelUserLogger\Models\Debug;
use Topoff\LaravelUserLogger\Models\Device;
use Topoff\LaravelUserLogger\Models\Domain;
use Topoff\LaravelUserLogger\Models\ExperimentMeasurement;
use Topoff\LaravelUserLogger\Models\Language;
use Topoff\LaravelUserLogger\Models\Log;
use Topoff\LaravelUserLogger\Models\Referer;
use Topoff\LaravelUserLogger\Models\Session;
use Topoff\LaravelUserLogger\Models\Uri;
use Topoff\LaravelUserLogger\Parsers\LanguageParser;
use Topoff\LaravelUserLogger\Parsers\RefererParser;
use Topoff\LaravelUserLogger\Parsers\RefererResult;
use Topoff\LaravelUserLogger\Parsers\UrlPathParser;
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
use Topoff\LaravelUserLogger\Services\ExperimentMeasurementService;
use Topoff\LaravelUserLogger\Support\PerformanceProfiler;
use Topoff\LaravelUserLogger\Support\SessionHelper;

/**
 * Class UserLogger
 */
class UserLogger
{
    protected ?bool $enabled = null;

    protected ?Agent $agent = null;

    protected ?Domain $domain = null;

    protected ?Log $log = null;

    protected ?Session $session = null;

    protected ?Device $device = null;

    protected ?Language $language = null;

    protected ?Referer $referer = null;

    protected ?Collection $experimentMeasurements = null;

    protected array $blacklistUris = [];

    protected bool $performanceEnabled = false;

    protected ?PerformanceProfiler $performanceProfiler = null;

    public function __construct(/**
     * The Laravel application instance.
     */
        protected Application $app,
        protected AgentRepository $agentRepository,
        protected DeviceRepository $deviceRepository,
        protected DomainRepository $domainRepository,
        protected LanguageRepository $languageRepository,
        protected LogRepository $logRepository,
        protected UriRepository $uriRepository,
        protected RefererRepository $refererRepository,
        protected SessionRepository $sessionRepository,
        protected ExperimentMeasurementService $experimentMeasurementService,
        protected Request $request)
    {
        $this->blacklistUris = Cache::rememberForever('user-logger.blacklist_routes', static fn () => config('user-logger.blacklist_routes') ?: []);
        $this->performanceEnabled = config('user-logger.performance.enabled', false) === true;
        if ($this->performanceEnabled) {
            $this->performanceProfiler = new PerformanceProfiler;
        }
    }

    public function boot(): void
    {
        if ($this->performanceEnabled) {
            $this->performanceProfiler?->start('user_logger_total');
        }

        $crawlerDetect = new CrawlerDetect;
        $isCrawler = $crawlerDetect->isCrawler();

        if (config('app.debug')) {
            if (config('user-logger.log_robots') || ! $isCrawler) {
                $this->log = $this->createLog(preClassifiedAsBot: $isCrawler);
            }
        } else {
            // try - catch in middleware not working as expected: https://github.com/laravel/framework/issues/14573
            // Intentional used twice, in InjectUserLogger Middleware -> completely suppresses errors in this package
            // and here: does log some of them.
            try {
                if (config('user-logger.log_robots') || ! $isCrawler) {
                    $this->log = $this->createLog(preClassifiedAsBot: $isCrawler);
                }
            } catch (Exception $e) {
                // Sometimes reached
                LaravelLogger::warning('Error in topoff/user-logger: '.$e->getMessage(), $e->getTrace());
            }
        }

        if ($this->performanceEnabled) {
            $this->performanceProfiler?->stop('user_logger_total');
        }
    }

    /**
     * Create the Log of the Request
     */
    protected function createLog(?string $event = null, ?string $entityType = null, ?string $entityId = null, bool $preClassifiedAsBot = false): ?Log
    {
        try {
            $isBlacklistedUri = $this->isInBlacklistedUriArray($this->request);

            // URI -> decoded path returns without query parameters
            $uri = $this->profile('uri_lookup', fn (): Uri => $this->uriRepository->findOrCreate(['uri' => $this->request->decodedPath()]));

            // Domain
            $this->domain = $this->profile('domain_lookup', fn (): Domain => $this->domainRepository->findOrCreate(['name' => $this->request->getHost(), 'local' => true]));

            // Session
            $this->session = $this->profile('session_resolution', fn (): Session => $this->getOrCreateSession($isBlacklistedUri, $preClassifiedAsBot));

            // Check if the uri is blacklisted, if so, set the session to robot and suspicious
            if (($this->session->isNoRobot() || $this->session->isNotSuspicious()) && $isBlacklistedUri) {
                $this->profile('session_mark_suspicious', fn (): Session => $this->sessionRepository->setRobotAndSuspicious($this->session));
            }

            // Log
            $this->log = $this->profile('log_create', fn (): \Topoff\LaravelUserLogger\Models\Log => $this->logRepository->create($this->session, $this->domain, $uri, $event, $entityType, $entityId));
            $this->profile('experiment_record_exposure', fn () => $this->experimentMeasurementService->recordExposure($this->session, $this->log));

            return $this->log;
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

            if ($this->session instanceof Session) {
                $this->session = $this->sessionRepository->updateUser($this->session, Auth::user());
            } else {
                LaravelLogger::warning(static::class.'->'.__FUNCTION__.': the sessionHelper '.$sessionHelper->getSessionUuid().' was not found in the DB table sessions.');
            }
        }
    }

    /**
     * Get or Create The Session Record of the Request
     *
     * @throws Exception
     */
    protected function getOrCreateSession(?bool $isBlacklistedUri = null, bool $preClassifiedAsBot = false): Session
    {
        $sessionHelper = new SessionHelper($this->request);
        $isBlacklistedUri ??= $this->isInBlacklistedUriArray($this->request);

        if (! $this->session instanceof Session) {
            $this->profile('session_lookup_from_cookie', fn () => $this->setSessionFromRequest($sessionHelper));
        }

        if (! $this->session instanceof Session) {
            $this->referer ??= $this->profile('referer_resolution', fn (): ?Referer => $this->getOrCreateReferer());

            $userAgentParser = $this->profile('user_agent_parse', fn (): UserAgentParser => new UserAgentParser($this->request, $preClassifiedAsBot));
            if ($userAgentParser->hasResult()) {
                $this->device = $this->profile('device_lookup', fn (): Device => $this->deviceRepository->findOrCreate($userAgentParser->getDeviceAttributes()));
                $this->agent = $this->profile('agent_lookup', fn (): Agent => $this->agentRepository->findOrCreate($userAgentParser->getAgentAttributes()));
            } else {
                if (config('user-logger.debug') === true && ! empty($this->request->userAgent())) {
                    Debug::create(['kind' => 'user-agent', 'value' => $this->request->userAgent()]);
                }
                $this->device = null;
                $this->agent = null;
            }

            // Language
            $languageParser = $this->profile('language_parse', fn (): LanguageParser => new LanguageParser($this->request));
            if ($languageParser->getLanguageAttributes() !== null) {
                $this->language = $this->profile('language_lookup', fn (): Language => $this->languageRepository->findOrCreate($languageParser->getLanguageAttributes()));
            } else {
                if (config('user-logger.debug') === true) {
                    Debug::create(['kind' => 'language', 'value' => $this->request->header('accept-language')]);
                }
                $this->language = null;
            }

            if ($isBlacklistedUri) {
                $suspicious = true;
                $isRobot = true;
            } else {
                // If the agent and the device couldn't be parsed, mark as suspicious
                $suspicious = ! $this->agent instanceof Agent && ! $this->device instanceof Device;

                // Agents can be set manually in the agents table as robots and this will overwrite the is_robot detection from
                // the UserAgentParser Result.
                $isRobot = $preClassifiedAsBot
                    || ($this->agent instanceof Agent && $this->agent->is_robot)
                    || ($this->device instanceof Device && $this->device['is_robot']);
            }

            // Session
            return $this->profile('session_persist', fn (): Session => $this->sessionRepository->findOrCreate($sessionHelper->getSessionUuid(), Auth::user(), $this->device, $this->agent, $this->referer, $this->language, $this->request->ip(), $suspicious, $isRobot));
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
        $hasRefererSource = static fn (?RefererResult $result): bool => $result instanceof RefererResult && ! in_array($result->source, ['', '0'], true);

        $fullUrl = $this->request->fullUrl();
        $refererUrl = $this->request->headers->get('referer');
        $hasRefererUrl = ! in_array($refererUrl, [null, '', '0'], true);

        $refererResult = null;
        if (str_contains($fullUrl, 'utm_source=')) {
            // 1 - from url: utm_source -ok
            $refererResult = (new UtmSourceParser($fullUrl))->getResult();
        }

        // 2 - from referer: with referer-parser -ok
        if (! $hasRefererSource($refererResult) && $hasRefererUrl) {
            $refererParser = new RefererParser($refererUrl);
            $refererResult = $refererParser->getResult();
        }
        // 3 - from referer: utm_source
        if (! $hasRefererSource($refererResult) && $hasRefererUrl && str_contains((string) $refererUrl, 'utm_source=')) {
            $utmRefParser = new UtmSourceParser($refererUrl);
            $refererResult = $utmRefParser->getResult();
        }
        // 4 - from referer: local domain
        if (! $hasRefererSource($refererResult)) {
            $refererParser = new RefererParser($refererUrl, $this->request->fullUrl());
            $refererResult = $refererParser->getResult();
        }
        // 5 - from url: atlg - mail
        if (! $hasRefererSource($refererResult)) {
            $urlPathParser = new UrlPathParser($fullUrl, config('user-logger.internal_domains'));
            $refererResult = $urlPathParser->getResult();
        }

        if (! empty($refererResult->domain)) {
            $domain = $this->getOrCreateDomain($refererResult->domain, $refererResult->domain_intern);
            $referer = $this->refererRepository->findOrCreate($domain, $refererResult);
        } elseif (config('user-logger.debug') === true) {
            Debug::create(['kind' => 'url', 'value' => $fullUrl]);
            if ($hasRefererUrl) {
                Debug::create(['kind' => 'referer', 'value' => $refererUrl]);
            }
        }

        return $referer ?? null;
    }

    protected function getOrCreateDomain(string $name, bool $local): Domain
    {
        return $this->domainRepository->findOrCreate(['name' => $name, 'local' => $local]);
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
        if (! $this->isEnabled()) {
            return null;
        }

        $log = null;
        if ($this->log instanceof Log) {
            $log = $this->logRepository->updateWithEvent($this->log, $event, $entityType, $entityId);
        } else {
            $log = $this->createLog($event, $entityType, $entityId);
        }

        if ($log instanceof Log && $this->session instanceof Session) {
            $this->profile('experiment_record_conversion', fn () => $this->experimentMeasurementService->recordConversion($this->session, $event, $entityType, $entityId, $log));
        }

        return $log;
    }

    /**
     * Create an Event if the UserLogger is in only-event mode
     */
    public function setEventWithSessionId(?string $sessionId = null, ?string $event = null, ?string $entityType = null, ?string $entityId = null): ?Log
    {
        if ($this->isDisabled()) {
            return null;
        }

        $sessionId ??= Uuid::uuid7()->toString();
        $this->session = $this->profile('event_session_find_or_create', fn (): Session => $this->sessionRepository->findOrCreate($sessionId));
        $lastLog = $this->session->logs()->orderBy('created_at', 'desc')->first();

        $log = $this->profile('event_log_create_minimal', fn (): \Topoff\LaravelUserLogger\Models\Log => $this->logRepository->createMinimal($this->session, $lastLog?->domain_id, null, $event, $entityType, $entityId));

        $this->profile('experiment_record_conversion', fn () => $this->experimentMeasurementService->recordConversion($this->session, $event, $entityType, $entityId, $log));

        return $log;
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
        }

        return null;
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
            if (! $this->device instanceof Device && $this->session instanceof Session) {
                $this->device = $this->session->device;
            }
        } catch (Exception) {
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
            if (! $this->referer instanceof Referer && $this->session instanceof Session) {
                $this->referer = $this->session->referer;
            }
        } catch (Exception) {
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
            if (! $this->language instanceof Language && $this->session instanceof Session) {
                $this->language = $this->session->language;
            }
        } catch (Exception) {
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
            if (! $this->agent instanceof Agent && $this->session instanceof Session) {
                $this->agent = $this->session->agent;
            }
        } catch (Exception) {
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
     * Get experiment measurements of the current Request
     */
    public function getCurrentExperimentMeasurements(): Collection
    {
        if (! $this->session instanceof Session) {
            return collect();
        }

        $this->experimentMeasurements = ExperimentMeasurement::query()->where('session_id', $this->session->id)->get();

        return $this->experimentMeasurements;
    }

    /**
     * Get the resolved Pennant variant for the current Request.
     */
    public function getExperimentVariant(string $feature): ?string
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $session = $this->session;
        if (! $session instanceof Session) {
            $session = $this->getOrCreateSession();
        }

        return $this->experimentMeasurementService->getVariant($feature, $session);
    }

    /**
     * Check if the current Request is running in the requested variant of a Pennant feature.
     */
    public function isExperiment(string $feature, mixed $variant = true): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $session = $this->session;
        if (! $session instanceof Session) {
            $session = $this->getOrCreateSession();
        }

        return $this->experimentMeasurementService->isVariant($feature, $variant, $session);
    }

    public function setExperimentVariant(string $feature, mixed $variant): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $session = $this->session;
        if (! $session instanceof Session) {
            $session = $this->getOrCreateSession();
        }

        $this->experimentMeasurementService->setVariant($session, $feature, $variant, $this->log);
    }

    /**
     * Determine if the request has a URI that should be ignored.
     */
    protected function isInBlacklistedUriArray(Request $request): bool
    {
        foreach ($this->blacklistUris as $blacklistUri) {
            if ($blacklistUri !== '/') {
                $blacklistUri = trim((string) $blacklistUri, '/');
            }

            if ($request->is($blacklistUri)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{segments: array<string, float>, counters: array<string, int>, meta: array<string, mixed>}|null
     */
    public function getPerformanceSnapshot(): ?array
    {
        if (! $this->performanceEnabled) {
            return null;
        }

        return $this->performanceProfiler?->snapshot();
    }

    protected function profile(string $segment, callable $callback): mixed
    {
        if (! $this->performanceEnabled || ! $this->performanceProfiler instanceof PerformanceProfiler) {
            return $callback();
        }

        $this->performanceProfiler->start($segment);
        try {
            return $callback();
        } finally {
            $this->performanceProfiler->stop($segment);
        }
    }
}
