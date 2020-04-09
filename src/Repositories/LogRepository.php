<?php

namespace Topoff\LaravelUserLogger\Repositories;

use Topoff\LaravelUserLogger\Models\Domain;
use Topoff\LaravelUserLogger\Models\Log;
use Topoff\LaravelUserLogger\Models\Session;
use Topoff\LaravelUserLogger\Models\Uri;

/**
 * Class LogRepository
 *
 * @package Topoff\LaravelUserLogger\Repositories
 */
class LogRepository
{
    /**
     * Finds an existing Log (Request) or creates a new DB Record
     *
     * @param Session $session
     * @param Domain  $domain
     * @param Uri     $uri
     * @param string  $event
     *
     * @return Log
     */
    public function create(Session $session, Domain $domain, Uri $uri, string $event = NULL): Log
    {
        return Log::create(['session_id' => $session->id, 'domain_id' => $domain->id, 'uri_id' => $uri->id, 'event' => $event]);
    }

    /**
     * @param Log         $log
     * @param string      $event
     * @param string|null $entityType
     * @param int|null    $entityId
     *
     * @return Log
     */
    public function updateWithEvent(Log $log, string $event, string $entityType = NULL, string $entityId = NULL): Log
    {
        $log->event = $event;
        $log->entity_type = $entityType;
        $log->entity_id = $entityId;
        $log->save();

        return $log;
    }

    /**
     * @param Log    $log
     * @param string $comment
     *
     * @return Log
     */
    public function updateWithComment(Log $log, string $comment): Log
    {
        $log->comment = $comment;
        $log->save();

        return $log;
    }
}
