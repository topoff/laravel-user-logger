<?php

namespace Topoff\LaravelUserLogger\Repositories;

use Topoff\LaravelUserLogger\Models\Domain;

class DomainRepository
{
    /**
     * Finds an existing Domain or creates a new DB Record
     *
     * @param array $attributes
     *
     * @return Domain
     */
    public function findOrCreate(Array $attributes): Domain
    {
        if (empty($attributes['name'])) {
            $attributes['name'] = 'unknown';
        }

        return Domain::firstOrCreate($attributes);
    }
}