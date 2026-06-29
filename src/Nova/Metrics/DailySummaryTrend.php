<?php

declare(strict_types=1);

namespace Topoff\LaravelUserLogger\Nova\Metrics;

use Carbon\CarbonImmutable;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Trend;
use Laravel\Nova\Metrics\TrendResult;
use Topoff\LaravelUserLogger\Models\PerformanceDailySummary;

/**
 * Base for trends over the pre-aggregated performance_daily_summaries table.
 *
 * The data is already one row per day, so instead of re-aggregating raw rows
 * (which would key off created_at - the night the summary was computed, not the
 * day it represents) we build the TrendResult directly from summary_date.
 */
abstract class DailySummaryTrend extends Trend
{
    /**
     * Column on performance_daily_summaries to plot.
     */
    abstract protected function column(): string;

    protected function precision(): int
    {
        return 0;
    }

    protected function suffix(): ?string
    {
        return null;
    }

    public function calculate(NovaRequest $request): TrendResult
    {
        $days = (int) ($request->range ?? 30);
        if ($days < 1) {
            $days = 30;
        }

        $start = CarbonImmutable::today()->subDays($days - 1);
        $column = $this->column();

        $values = [];
        $rows = PerformanceDailySummary::query()
            ->where('summary_date', '>=', $start->toDateString())
            ->get(['summary_date', $column]);

        foreach ($rows as $row) {
            $values[CarbonImmutable::parse($row->summary_date)->toDateString()] = $row->{$column};
        }

        $trend = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $start->addDays($i);
            $value = $values[$date->toDateString()] ?? null;
            $trend[$date->format('M j')] = $value === null ? null : round((float) $value, $this->precision());
        }

        $result = (new TrendResult)->trend($trend);

        if ($this->suffix() !== null) {
            $result->suffix($this->suffix());
        }

        return $result;
    }

    /**
     * @return array<int, string>
     */
    public function ranges(): array
    {
        return [
            30 => '30 Days',
            60 => '60 Days',
            90 => '90 Days',
            180 => '180 Days',
            365 => '365 Days',
        ];
    }
}
