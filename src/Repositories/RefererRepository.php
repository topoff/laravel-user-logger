<?php

namespace Topoff\LaravelUserLogger\Repositories;

use Topoff\LaravelUserLogger\Models\Domain;
use Topoff\LaravelUserLogger\Models\Referer;
use Topoff\LaravelUserLogger\Parsers\RefererResult;

/**
 * Class RefererRepository
 *
 * @package Topoff\LaravelUserLogger\Repositories
 */
class RefererRepository
{
    /**
     * Finds an existing Referer or creates a new DB Record
     *
     * @param Domain        $domain
     * @param RefererResult $refererResult
     *
     * @return Referer
     */
    public function findOrCreate(Domain $domain, RefererResult $refererResult): Referer
    {
        if (empty($refererResult->url)) {
            $refererResult->url = 'unknown';
        }

        return Referer::firstOrCreate([
                                          'url'        => $refererResult->url,
                                          'domain_id'  => $domain->id,
                                          'source'     => $refererResult->source,
                                          'medium'     => $refererResult->medium,
                                          'keywords'   => $refererResult->keywords,
                                          'campaign'   => $refererResult->campaign,
                                          'adgroup'    => $refererResult->adgroup,
                                          'matchtype'  => $refererResult->matchtype,
                                          'device'     => $refererResult->device,
                                          'adposition' => $refererResult->adposition,
                                          'network'    => $refererResult->network,
                                      ]);
    }
}