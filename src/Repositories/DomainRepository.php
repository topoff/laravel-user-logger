<?php

namespace Topoff\LaravelUserLogger\Repositories;

use Illuminate\Support\Facades\Cache;
use Topoff\LaravelUserLogger\Models\Domain;

class DomainRepository
{
    /**
     * Finds an existing Domain or creates a new DB Record
     */
    public function findOrCreate(array $attributes): Domain
    {
        if (empty($attributes['name'])) {
            $attributes['name'] = 'unknown';
        }

        // TTL instead of rememberForever: the key space is client-controlled
        // (Host header), an unbounded forever-cache would grow without limit.
        return Cache::remember("userlogger:domain:{$attributes['name']}", 86400, static fn () => Domain::firstOrCreate(
            ['name' => $attributes['name']],
            ['local' => $attributes['local']],
        ));
    }
}
