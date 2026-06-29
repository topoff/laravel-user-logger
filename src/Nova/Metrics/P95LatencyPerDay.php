<?php

declare(strict_types=1);

namespace Topoff\LaravelUserLogger\Nova\Metrics;

use Override;

class P95LatencyPerDay extends DailySummaryTrend
{
    protected function column(): string
    {
        return 'p95_duration_ms';
    }

    #[Override]
    protected function precision(): int
    {
        return 1;
    }

    protected function suffix(): ?string
    {
        return ' ms';
    }

    public function name(): string
    {
        return 'p95 Latency / day';
    }

    public function uriKey(): string
    {
        return 'ul-p95-latency-per-day';
    }
}
