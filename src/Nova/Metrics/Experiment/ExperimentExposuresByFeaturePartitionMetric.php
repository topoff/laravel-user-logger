<?php

namespace Topoff\LaravelUserLogger\Nova\Metrics\Experiment;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Topoff\LaravelUserLogger\Models\ExperimentMeasurement;

class ExperimentExposuresByFeaturePartitionMetric extends Partition
{
    public function calculate(NovaRequest $request)
    {
        return $this->sum($request, ExperimentMeasurement::query(), 'exposure_count', 'feature')
            ->label(fn ($value): string => (string) $value);
    }

    public function name(): string
    {
        return __('Exposures by Feature');
    }

    public function uriKey(): string
    {
        return 'experiment-exposures-by-feature-partition-metric';
    }
}

