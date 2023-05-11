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
    public function create(Session $session, Domain $domain, ?Uri $uri = null, string $event = NULL): Log
    {
        return Log::create(['session_id' => $session->id, 'domain_id' => $domain->id, 'uri_id' => $uri?->id, 'event' => $event]);
    }

    /**
     * Creates a minimal log entry, for conversion events in backend
     */
    public function createMinimal(Session $session, ?int $domainId = null, ?int $uriId = null, ?string $event = NULL, ?string $entityType = NULL, ?string $entityId = NULL): Log
    {
        return Log::create(['session_id' => $session->id, 'domain_id' => $domainId, 'uri_id' => $uriId, 'event' => $event, 'entity_type' => $entityType, 'entity_id' => $entityId]);
    }

    public function updateWithEvent(Log $log, string $event, string $entityType = NULL, string $entityId = NULL): Log
    {
        $log->event = $event;
        $log->entity_type = $entityType;
        $log->entity_id = $entityId;
        $log->save();

        return $log;
    }

    public function updateWithComment(Log $log, string $comment): Log
    {
        $log->comment = $comment;
        $log->save();

        return $log;
    }
}
