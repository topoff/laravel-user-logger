<?php

namespace Topoff\LaravelUserLogger\Repositories;

use Topoff\LaravelUserLogger\Models\ExperimentLog;

class ExperimentLogRepository
{
    /**
     * Finds an existing Experiment or creates a new DB Record
     *
     * @param array $attributes
     * @param array $delayedAttributes
     *
     * @return mixed
     */
    public function firstOrCreate(Array $attributes, array $delayedAttributes)
    {
        return ExperimentLog::firstOrCreate($attributes, $delayedAttributes);
    }
}