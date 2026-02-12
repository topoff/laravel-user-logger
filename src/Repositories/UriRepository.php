<?php

namespace Topoff\LaravelUserLogger\Repositories;

use Topoff\LaravelUserLogger\Models\Uri;

class UriRepository
{
    /**
     * Finds an existing Uri or creates a new DB Record
     */
    public function findOrCreate(array $attributes): Uri
    {
        return Uri::firstOrCreate($attributes);
    }
}
