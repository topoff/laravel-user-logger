<?php

namespace Topoff\LaravelUserLogger\Repositories;

use Topoff\LaravelUserLogger\Models\ExperimentLog;

class ExperimentLogRepository
{
    /**
     * Finds an existing Experiment or creates a new DB Record
     *
     *
     * @return mixed
     */
    public function firstOrCreate(array $attributes, array $delayedAttributes)
    {
        return ExperimentLog::firstOrCreate($attributes, $delayedAttributes);
    }
}
