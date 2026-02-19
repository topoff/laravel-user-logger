<?php

namespace Topoff\LaravelUserLogger\Nova\Metrics\Experiment;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Value;
use Topoff\LaravelUserLogger\Models\ExperimentMeasurement;

class ExperimentConversionRateValueMetric extends Value
{
    public function name(): string
    {
        return __('Experiment Conversion Rate');
    }

    public function calculate(NovaRequest $request)
    {
        $exposures = (int) ExperimentMeasurement::query()->sum('exposure_count');
        $conversions = (int) ExperimentMeasurement::query()->sum('conversion_count');

        $rate = $exposures > 0 ? round(($conversions / $exposures) * 100, 2) : 0.0;

        return $this->result($rate)->suffix('%');
    }

    public function cacheFor()
    {
        return now()->addMinutes(10);
    }

    public function uriKey(): string
    {
        return 'experiment-conversion-rate-value-metric';
    }
}

