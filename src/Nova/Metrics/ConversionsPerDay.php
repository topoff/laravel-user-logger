<?php

declare(strict_types=1);

namespace Topoff\LaravelUserLogger\Nova\Metrics;

class ConversionsPerDay extends DailySummaryTrend
{
    protected function column(): string
    {
        return 'conversions';
    }

    public function name(): string
    {
        return 'Conversions / day';
    }

    public function uriKey(): string
    {
        return 'ul-conversions-per-day';
    }
}
