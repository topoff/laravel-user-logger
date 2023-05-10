<?php

namespace Topoff\LaravelUserLogger\Repositories;

use Illuminate\Support\Facades\Cache;
use Topoff\LaravelUserLogger\Models\Domain;

class DomainRepository
{
    /**
     * Finds an existing Domain or creates a new DB Record
     */
    public function findOrCreate(Array $attributes): Domain
    {
        if (empty($attributes['name'])) {
            $attributes['name'] = 'unknown';
        }

        return Cache::rememberForever("userlogger:domain:{$attributes['name']}:{$attributes['local']}", static fn() => Domain::firstOrCreate($attributes));
    }
}
