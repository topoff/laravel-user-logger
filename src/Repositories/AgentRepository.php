<?php

namespace Topoff\LaravelUserLogger\Repositories;

use Topoff\LaravelUserLogger\Models\Agent;

/**
 * Class AgentRepository
 * @package Topoff\LaravelUserLogger\Repositories
 */
class AgentRepository
{
    /**
     * Finds an existing Agent or creates a new DB Record
     *
     * @param array $attributes
     *
     * @return Agent
     */
    public function findOrCreate(Array $attributes): Agent
    {
        if (empty($attributes['name'])) {
            $attributes['name'] = 'unknown';
        }

        return Agent::firstOrCreate($attributes);
    }

    /**
     * Finds / creats the record for a not detected Agent
     *
     * @return Agent
     */
    public function findOrCreateNotDetected(): Agent
    {
        return Agent::firstOrCreate([
                                        'name'            => 'unknown',
                                        'browser'         => NULL,
                                        'browser_version' => NULL
                                    ]);
    }
}