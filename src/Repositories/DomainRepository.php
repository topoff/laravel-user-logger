<?php

namespace Topoff\Tracker\Repositories;

use Topoff\Tracker\Models\Domain;

class DomainRepository
{
    /**
     * Finds an existing Domain or creates a new DB Record
     *
     * @param array $attributes
     *
     * @return mixed
     */
    public function findOrCreate(Array $attributes)
    {
        return Domain::firstOrCreate($attributes);
    }
}