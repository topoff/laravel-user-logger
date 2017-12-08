<?php

namespace Topoff\Tracker;

use Exception;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use Topoff\Tracker\Models\Log;
use Topoff\Tracker\Models\Session;
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
        $this->app = $app ?? app();
        $config = $this->app['config'];
        $this->connection = $config->get('tracker.connection2');
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
        \Log::debug('Tracker boot, uri: ' . $this->request->getUri());

        try {
            $this->createLog();
        } catch (Exception $e) {
            // WTF: try - catch not working in middleware : https://github.com/laravel/framework/issues/14573
            // With this code, every error is surpressed, if one occurs it will jump directly in the finally
            // Block with ignoring the error.. strange
            Logger::warning('Error in topoff/tracker: ' . $e->getMessage(), $e->getTrace());
        } finally {
            echo 'BAL';
            //
        }
    }

    /**
     * Create the Log of the Request
     *
     * @return Log
     */
    public function createLog(): Log
    {
        $uri = $this->uriRepository->findOrCreate(['uri' => $this->request->getUri()]);

        return $this->logRepository->findOrCreate($this->getOrCreateSession(), $uri);
    }

    /**
     * Get or Create The Session Record of the Request
     *
     * @return Session
     */
    public function getOrCreateSession(): Session
    {
        //$userAgent = 'Mozilla/5.0 (iPod; U; CPU iPhone OS 4_3_5 like Mac OS X; en-us) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8J2 Safari/6533.18.5';
        //$userAgent = $_SERVER['HTTP_USER_AGENT'];
        $userAgent = $this->request->userAgent();
        $userAgentParser = new UserAgentParser($userAgent);

        $refererUrl = $this->request->headers->get('referer');
        //$refererUrl = "http://www.google.com/search?q=gateway+oracle+cards+denise+linn&hl=en&client=safari";
        $refererParser = new RefererParser($refererUrl, 'https://www.top-offerten.ch/blabla');
        $refererAttributes = $refererParser->getRefererAttributes();

        $device = $this->deviceRepository->findOrCreate($userAgentParser->getDeviceAttributes());
        $agent = $this->agentRepository->findOrCreate($userAgentParser->getAgentAttributes());
        $language = $this->languageRepository->findOrCreate($userAgentParser->getLanguageAttributes());
        $domain = $refererAttributes ? $this->domainRepository->findOrCreate($refererAttributes['domain']) : NULL;
        $referer = $domain ? $this->refererRepository->findOrCreate(['domain_id' => $domain->id]) : NULL;

        return $this->sessionRepository->findOrCreate($this->request->session()->getId(), $this->request->user(), $device, $agent, $referer, $language, $this->request->ip(), $device['is_robot']);
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
}