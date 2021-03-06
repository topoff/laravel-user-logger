<?php

namespace Topoff\LaravelUserLogger\Repositories;

use Topoff\LaravelUserLogger\Models\Uri;

class UriRepository
{
    /**
     * Finds an existing Uri or creates a new DB Record
     *
     * @param array $attributes
     *
     * @return mixed
     */
    public function findOrCreate(Array $attributes)
    {
        return Uri::firstOrCreate($attributes);
    }
}