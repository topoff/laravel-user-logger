<?php

namespace Topoff\Tracker\Repositories;

use Topoff\Tracker\Models\Log;
use Topoff\Tracker\Models\Session;
use Topoff\Tracker\Models\Uri;

/**
 * Class LogRepository
 *
 * @package Topoff\Tracker\Repositories
 */
class LogRepository
{
    /**
     * Finds an existing Log (Request) or creates a new DB Record
     *
     * @param Session $session
     * @param Uri     $uri
     * @param string  $event
     *
     * @return Log
     */
    public function findOrCreate(Session $session, Uri $uri, string $event = NULL): Log
    {
        return Log::create(['session_id' => $session->id ?? NULL, 'uri_id' => $uri->id, 'event' => $event]);
    }

    /**
     * @param Log    $log
     * @param string $event
     *
     * @return Log
     */
    public function updateWithEvent(Log $log, string $event): Log
    {
        $log->event = $event;
        $log->save();
        return $log;
    }
}