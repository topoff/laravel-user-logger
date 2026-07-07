<?php

declare(strict_types=1);

namespace Topoff\LaravelUserLogger\Nova\Metrics\Experiment;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\MetricTableRow;
use Laravel\Nova\Metrics\Table;
use Topoff\LaravelUserLogger\Support\ExperimentStats;

/**
 * Renders the A/B result of each measured feature as a table: one row per
 * variant (session conversion rate, sessions, converting sessions) plus a
 * verdict row with the two-proportion z-test outcome and 95% significance flag.
 */
class ExperimentResultsTableMetric extends Table
{
    /**
     * @return array<int, MetricTableRow>
     */
    public function calculate(NovaRequest $request): array
    {
        $rows = [];

        foreach ((new ExperimentStats)->summary() as $feature => $data) {
            $variants = collect($data['variants'])->sortByDesc('cvr_per_session_pct')->values();

            $rows[] = MetricTableRow::make()
                ->icon('beaker')
                ->iconClass('text-gray-400 dark:text-gray-600')
                ->title((string) $feature)
                ->subtitle(number_format(array_sum($variants->pluck('sessions')->all())).' Sessions · '.$variants->count().' Varianten');

            foreach ($variants as $index => $variant) {
                $isLeader = $index === 0 && $variant['converting_sessions'] > 0;

                $rows[] = MetricTableRow::make()
                    ->icon($isLeader ? 'check-circle' : 'chart-bar')
                    ->iconClass($isLeader ? 'text-green-500' : 'text-gray-400 dark:text-gray-600')
                    ->title($variant['variant'].' — '.$this->pct($variant['cvr_per_session_pct']).' CVR/Session')
                    ->subtitle(
                        number_format($variant['sessions']).' Sessions · '
                        .number_format($variant['converting_sessions']).' Conversions · '
                        .$this->pct($variant['cvr_per_exposure_pct']).' /Exposure'
                    );
            }

            if (($comparison = $data['comparison']) !== null) {
                $significant = $comparison['significant_95'];

                $rows[] = MetricTableRow::make()
                    ->icon($significant ? 'check-circle' : 'clock')
                    ->iconClass($significant ? 'text-green-500' : 'text-yellow-500')
                    ->title(
                        ($significant ? 'Gewinner: ' : 'Führt: ').$comparison['challenger']
                        .' (+'.$comparison['relative_uplift_pct'].'% relativ)'
                    )
                    ->subtitle(
                        'z '.$comparison['z_score'].' · p '.$comparison['p_value'].' · '
                        .($significant ? '95% signifikant' : 'noch nicht signifikant (95%)')
                    );
            }
        }

        return $rows;
    }

    private function pct(float $value): string
    {
        return number_format($value, 2).'%';
    }

    public function name(): string
    {
        return __('A/B Results (Session CVR)');
    }

    public function cacheFor()
    {
        return now()->addMinutes(10);
    }

    public function uriKey(): string
    {
        return 'experiment-results-table-metric';
    }
}
