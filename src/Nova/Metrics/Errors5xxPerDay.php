<?php

declare(strict_types=1);

namespace Topoff\LaravelUserLogger\Nova\Metrics;

class Errors5xxPerDay extends DailySummaryTrend
{
    protected function column(): string
    {
        return 'errors_5xx';
    }

    public function name(): string
    {
        return '5xx Errors / day';
    }

    public function uriKey(): string
    {
        return 'ul-errors-5xx-per-day';
    }
}
