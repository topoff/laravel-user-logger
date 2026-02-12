<?php

namespace Topoff\LaravelUserLogger\Repositories;

use Topoff\LaravelUserLogger\Models\ExperimentLog;

class ExperimentLogRepository
{
    /**
     * Finds an existing Experiment or creates a new DB Record
     */
    public function firstOrCreate(array $attributes, array $delayedAttributes): ExperimentLog
    {
        return ExperimentLog::firstOrCreate($attributes, $delayedAttributes);
    }
}
