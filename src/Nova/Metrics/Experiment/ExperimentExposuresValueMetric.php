<?php

namespace Topoff\LaravelUserLogger\Nova\Metrics\Experiment;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Value;
use Topoff\LaravelUserLogger\Models\ExperimentMeasurement;

class ExperimentExposuresValueMetric extends Value
{
    public function name(): string
    {
        return __('Experiment Exposures');
    }

    public function calculate(NovaRequest $request)
    {
        return $this->sum($request, ExperimentMeasurement::query(), 'exposure_count');
    }

    public function ranges(): array
    {
        return [
            7 => '7 Days',
            30 => '30 Days',
            60 => '60 Days',
            90 => '90 Days',
            365 => '365 Days',
            'ALL' => 'All',
        ];
    }

    public function cacheFor()
    {
        return now()->addMinutes(10);
    }

    public function uriKey(): string
    {
        return 'experiment-exposures-value-metric';
    }
}

