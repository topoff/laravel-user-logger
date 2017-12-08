<?php

namespace Topoff\Tracker\Repositories;

use Topoff\Tracker\Models\Cookie;

class CookieRepository
{
    /**
     * Finds an existing Cookie or creates a new DB Record
     *
     * @param array $attributes
     *
     * @return mixed
     */
    public function findOrCreate(Array $attributes)
    {
        return Cookie::firstOrCreate($attributes);
    }
}