<?php

namespace Topoff\LaravelUserLogger\Nova\Metrics\Experiment;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Topoff\LaravelUserLogger\Models\ExperimentMeasurement;

class ExperimentConversionsByVariantPartitionMetric extends Partition
{
    public function calculate(NovaRequest $request)
    {
        return $this->sum($request, ExperimentMeasurement::query(), 'conversion_count', 'variant')
            ->label(fn ($value): string => match ((string) $value) {
                'true' => 'B',
                'false' => 'A',
                '' => 'unknown',
                default => (string) $value,
            });
    }

    public function name(): string
    {
        return __('Conversions by Variant');
    }

    public function uriKey(): string
    {
        return 'experiment-conversions-by-variant-partition-metric';
    }
}
