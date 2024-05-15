<?php

namespace Topoff\LaravelUserLogger\Repositories;

use Topoff\LaravelUserLogger\Models\Uri;

class UriRepository
{
    /**
     * Finds an existing Uri or creates a new DB Record
     *
     *
     * @return mixed
     */
    public function findOrCreate(array $attributes)
    {
        return Uri::firstOrCreate($attributes);
    }
}
