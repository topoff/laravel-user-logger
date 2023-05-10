<?php

namespace Topoff\LaravelUserLogger\Support;

use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;

/**
 * Class Session
 *
 * @package Topoff\LaravelUserLogger\Support
 */
class SessionHelper
{
    /**
     * Session Name from Config
     *
     * @var string
     */
    protected $sessionName;

    /**
     * Request
     *
     * @var Request
     */
    protected $request;

    /**
     * Session constructor.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->sessionName = config('user-logger.session_name') ?? 'user_logger_session';

        $this->request = $request;
    }

    /**
     * Get current Session UUID
     */
    public function getSessionUuid(): string
    {
        return $this->request->session()->has($this->sessionName) ? $this->request->session()->get($this->sessionName) : $this->createSessionUuid();
    }

    /**
     * Create new Session UUID
     *
     * @return string
     * @throws \Exception
     */
    private function createSessionUuid(): string
    {
        $uuid = Uuid::uuid1()->toString();
        $this->request->session()->put($this->sessionName, $uuid);

        return $uuid;
    }

    /**
     * Is it an existing session?
     *
     * @return bool
     */
    public function isExistingSession(): bool
    {
        return $this->request->session()->has($this->sessionName);
    }
}
