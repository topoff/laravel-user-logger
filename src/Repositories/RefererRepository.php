<?php

namespace Topoff\LaravelUserLogger\Repositories;

use Topoff\LaravelUserLogger\Models\Domain;
use Topoff\LaravelUserLogger\Models\Referer;
use Topoff\LaravelUserLogger\Parsers\RefererResult;

/**
 * Class RefererRepository
 */
class RefererRepository
{
    /**
     * Finds an existing Referer or creates a new DB Record
     */
    public function findOrCreate(Domain $domain, RefererResult $refererResult): Referer
    {
        if (empty($refererResult->url)) {
            $refererResult->url = 'unknown';
        }

        $attributes = [
            'url' => $refererResult->url,
            'domain_id' => $domain->id,
            'source' => $refererResult->source,
            'medium' => $refererResult->medium,
            'keywords' => $refererResult->keywords,
            'campaign' => $refererResult->campaign,
            'adgroup' => $refererResult->adgroup,
            'matchtype' => $refererResult->matchtype,
            'device' => $refererResult->device,
            'adposition' => $refererResult->adposition,
            'network' => $refererResult->network,
        ];

        // Lookup via unique hash: the attribute columns can't carry a unique
        // index (url is text), without one firstOrCreate is not race-safe.
        return Referer::firstOrCreate(
            ['lookup_hash' => sha1(json_encode($attributes, JSON_THROW_ON_ERROR))],
            $attributes,
        );
    }
}
