<?php

declare(strict_types=1);

namespace Topoff\LaravelUserLogger\Nova\Metrics;

class RequestsPerDay extends DailySummaryTrend
{
    protected function column(): string
    {
        return 'requests';
    }

    public function name(): string
    {
        return 'Requests / day';
    }

    public function uriKey(): string
    {
        return 'ul-requests-per-day';
    }
}
