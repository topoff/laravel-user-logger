<?php

namespace Topoff\LaravelUserLogger\Repositories;

use Topoff\LaravelUserLogger\Models\Agent;

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

        return Agent::firstOrCreate($attributes);
    }

    /**
     * Finds / creats the record for a not detected Agent
     */
    public function findOrCreateNotDetected(): Agent
    {
        return Agent::firstOrCreate([
            'name' => 'unknown',
            'browser' => null,
            'browser_version' => null,
        ]);
    }
}
