<?php

namespace Topoff\LaravelUserLogger\Repositories;

use Topoff\LaravelUserLogger\Models\Agent;

class AgentRepository
{
    /**
     * Finds an existing Agent or creates a new DB Record
     *
     * @param array $attributes
     *
     * @return mixed
     */
    public function findOrCreate(Array $attributes)
    {
        return Agent::firstOrCreate($attributes);
    }
}