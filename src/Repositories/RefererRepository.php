<?php

namespace Topoff\LaravelUserLogger\Repositories;

use Topoff\LaravelUserLogger\Models\Referer;

class RefererRepository
{
    /**
     * Finds an existing Referer or creates a new DB Record
     *
     * @param array $attributes
     *
     * @return Referer
     */
    public function findOrCreate(Array $attributes): Referer
    {
        if (empty($attributes['url'])) {
            $attributes['url'] = 'unknown';
        }

        return Referer::firstOrCreate($attributes);
    }
}