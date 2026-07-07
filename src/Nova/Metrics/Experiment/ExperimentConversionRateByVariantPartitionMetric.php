<?php

namespace Topoff\LaravelUserLogger\Nova\Metrics\Experiment;

use Illuminate\Support\Facades\DB;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Topoff\LaravelUserLogger\Models\ExperimentMeasurement;

class ExperimentConversionRateByVariantPartitionMetric extends Partition
{
    public function calculate(NovaRequest $request)
    {
        $rows = ExperimentMeasurement::query()
            ->select([
                DB::raw("COALESCE(variant, '') as variant"),
                DB::raw('COUNT(*) as sessions'),
                DB::raw('SUM(CASE WHEN conversion_count > 0 THEN 1 ELSE 0 END) as converters'),
            ])
            ->groupBy('variant')
            ->get();

        $partitions = $rows->mapWithKeys(function ($row): array {
            $sessions = (int) $row->sessions;
            $rate = $sessions > 0 ? round(((int) $row->converters / $sessions) * 100, 2) : 0.0;

            return [(string) $row->variant => $rate];
        })->all();

        return $this->result($partitions)
            ->label(fn ($value): string => match ((string) $value) {
                'true' => 'B',
                'false' => 'A',
                '' => 'unknown',
                default => (string) $value,
            });
    }

    public function name(): string
    {
        return __('Conversion Rate by Variant (%)');
    }

    public function cacheFor()
    {
        return now()->addMinutes(10);
    }

    public function uriKey(): string
    {
        return 'experiment-conversion-rate-by-variant-partition-metric';
    }
}
