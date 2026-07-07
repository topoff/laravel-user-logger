<?php

namespace Topoff\LaravelUserLogger\Nova\Dashboards;

use Laravel\Nova\Dashboard;
use Topoff\LaravelUserLogger\Nova\Metrics\Experiment\ExperimentConversionRateByVariantPartitionMetric;
use Topoff\LaravelUserLogger\Nova\Metrics\Experiment\ExperimentConversionRateValueMetric;
use Topoff\LaravelUserLogger\Nova\Metrics\Experiment\ExperimentConversionsByVariantPartitionMetric;
use Topoff\LaravelUserLogger\Nova\Metrics\Experiment\ExperimentConversionsValueMetric;
use Topoff\LaravelUserLogger\Nova\Metrics\Experiment\ExperimentExposuresValueMetric;
use Topoff\LaravelUserLogger\Nova\Metrics\Experiment\ExperimentResultsTableMetric;

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
            (new ExperimentResultsTableMetric)
                ->emptyText('Noch keine Experiment-Messungen vorhanden.')
                ->width('full'),
            (new ExperimentExposuresValueMetric)->width('1/3'),
            (new ExperimentConversionsValueMetric)->width('1/3'),
            (new ExperimentConversionRateValueMetric)->width('1/3'),
            (new ExperimentConversionRateByVariantPartitionMetric)->width('1/2'),
            (new ExperimentConversionsByVariantPartitionMetric)->width('1/2'),
        ];
    }
}
