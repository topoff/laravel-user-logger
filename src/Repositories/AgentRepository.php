<?php

namespace Topoff\LaravelUserLogger\Repositories;

use Topoff\LaravelUserLogger\Models\Agent;
use Topoff\LaravelUserLogger\Support\AttributeLimiter;

/**
 * Class AgentRepository
 */
class AgentRepository
{
    /**
     * Finds an existing Agent or creates a new DB Record
     */
    public function findOrCreate(array $attributes): Agent
    {
        if (empty($attributes['name'])) {
            $attributes['name'] = 'unknown';
        }

        $attributes = AttributeLimiter::apply($attributes, [
            'browser' => 255,
            'browser_version' => 255,
        ]);

        // Lookup via unique hash: `name` is a text column without a full
        // unique index, without one firstOrCreate is not race-safe.
        return Agent::firstOrCreate(
            ['lookup_hash' => sha1(json_encode($attributes, JSON_THROW_ON_ERROR))],
            $attributes,
        );
    }

    /**
     * Finds / creats the record for a not detected Agent
     */
    public function findOrCreateNotDetected(): Agent
    {
        return $this->findOrCreate([
            'name' => 'unknown',
            'browser' => null,
            'browser_version' => null,
        ]);
    }
}
