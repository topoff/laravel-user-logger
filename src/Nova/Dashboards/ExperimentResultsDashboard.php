<?php

namespace Topoff\LaravelUserLogger\Nova\Dashboards;

use Laravel\Nova\Dashboard;
use Topoff\LaravelUserLogger\Nova\Metrics\Experiment\ExperimentConversionRateValueMetric;
use Topoff\LaravelUserLogger\Nova\Metrics\Experiment\ExperimentConversionsValueMetric;
use Topoff\LaravelUserLogger\Nova\Metrics\Experiment\ExperimentExposuresByFeaturePartitionMetric;
use Topoff\LaravelUserLogger\Nova\Metrics\Experiment\ExperimentExposuresByVariantPartitionMetric;
use Topoff\LaravelUserLogger\Nova\Metrics\Experiment\ExperimentExposuresValueMetric;

class ExperimentResultsDashboard extends Dashboard
{
    public function label(): string
    {
        return 'Experiment Results';
    }

    public function uriKey(): string
    {
        return 'experiment-results-dashboard';
    }

    public function cards(): array
    {
        return [
            (new ExperimentExposuresValueMetric)->width('1/3'),
            (new ExperimentConversionsValueMetric)->width('1/3'),
            (new ExperimentConversionRateValueMetric)->width('1/3'),
            (new ExperimentExposuresByFeaturePartitionMetric)->width('1/2'),
            (new ExperimentExposuresByVariantPartitionMetric)->width('1/2'),
        ];
    }
}
