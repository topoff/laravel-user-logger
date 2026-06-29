<?php

declare(strict_types=1);

namespace Topoff\LaravelUserLogger\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Topoff\LaravelUserLogger\Models\PerformanceDailySummary;

/**
 * Aggregates one day of raw `performance_logs` (and the day's conversions) into
 * a single `performance_daily_summaries` row.
 *
 * All queries use only ANSI aggregates / CASE / OFFSET so they run identically
 * on MySQL (production) and SQLite (tests) - no STDDEV / PERCENTILE_CONT.
 */
class PerformanceSummaryService
{
    public function __construct(
        protected string $connection = 'user-logger',
    ) {}

    /**
     * Summarize the given calendar day and upsert the summary row.
     */
    public function summarize(CarbonInterface $date): PerformanceDailySummary
    {
        $day = CarbonImmutable::parse($date)->startOfDay();
        $start = $day->toDateTimeString();
        $end = $day->addDay()->toDateTimeString();

        $metrics = $this->performanceMetrics($start, $end)
            + $this->conversionMetrics($start, $end);

        return PerformanceDailySummary::query()->updateOrCreate(
            ['summary_date' => $day->toDateString()],
            $metrics,
        );
    }

    /**
     * @return array<string, float|int|null>
     */
    protected function performanceMetrics(string $start, string $end): array
    {
        $slowThreshold = (float) config('user-logger.performance.daily_summary.slow_threshold_ms', 1000);

        /** @var object{requests: int, avg_duration_ms: float|null, max_duration_ms: float|null, slow_requests: int, errors_4xx: int, errors_5xx: int, cold_boots: int, avg_boot_duration_ms: float|null, avg_queries: float|null, max_queries: int|null} $row */
        $row = $this->performanceQuery($start, $end)
            ->selectRaw('COUNT(*) as requests')
            ->selectRaw('AVG(request_duration_ms) as avg_duration_ms')
            ->selectRaw('MAX(request_duration_ms) as max_duration_ms')
            ->selectRaw('SUM(CASE WHEN request_duration_ms >= ? THEN 1 ELSE 0 END) as slow_requests', [$slowThreshold])
            ->selectRaw('SUM(CASE WHEN status >= 400 AND status < 500 THEN 1 ELSE 0 END) as errors_4xx')
            ->selectRaw('SUM(CASE WHEN status >= 500 THEN 1 ELSE 0 END) as errors_5xx')
            ->selectRaw('SUM(CASE WHEN booted = 1 THEN 1 ELSE 0 END) as cold_boots')
            ->selectRaw('AVG(boot_duration_ms) as avg_boot_duration_ms')
            ->selectRaw('AVG(queries_total) as avg_queries')
            ->selectRaw('MAX(queries_total) as max_queries')
            ->first();

        $requests = (int) ($row->requests ?? 0);

        return [
            'requests' => $requests,
            'sample_rate' => (float) config('user-logger.performance.sample_rate', 1.0),
            'avg_duration_ms' => $this->round($row->avg_duration_ms),
            'p50_duration_ms' => $this->percentile($start, $end, 0.50, $requests),
            'p95_duration_ms' => $this->percentile($start, $end, 0.95, $requests),
            'p99_duration_ms' => $this->percentile($start, $end, 0.99, $requests),
            'max_duration_ms' => $this->round($row->max_duration_ms),
            'slow_requests' => (int) ($row->slow_requests ?? 0),
            'errors_4xx' => (int) ($row->errors_4xx ?? 0),
            'errors_5xx' => (int) ($row->errors_5xx ?? 0),
            'cold_boots' => (int) ($row->cold_boots ?? 0),
            'avg_boot_duration_ms' => $this->round($row->avg_boot_duration_ms),
            'avg_queries' => $this->round($row->avg_queries, 2),
            'max_queries' => $row->max_queries === null ? null : (int) $row->max_queries,
        ];
    }

    /**
     * @return array<string, float|int|null>
     */
    protected function conversionMetrics(string $start, string $end): array
    {
        $conversionEvents = (array) config('user-logger.experiments.conversion_events', ['conversion']);

        $sessions = (int) $this->logsQuery($start, $end)->distinct()->count('session_id');

        $conversions = (int) $this->logsQuery($start, $end)
            ->whereIn('event', $conversionEvents)
            ->whereNotNull('entity_id')
            ->count();

        return [
            'sessions' => $sessions,
            'conversions' => $conversions,
            'conversion_rate' => $sessions > 0 ? round($conversions / $sessions, 5) : null,
        ];
    }

    /**
     * Nearest-rank percentile of request_duration_ms, computed with a single
     * ORDER BY ... LIMIT 1 OFFSET k - portable across MySQL and SQLite.
     */
    protected function percentile(string $start, string $end, float $p, int $count): ?float
    {
        if ($count <= 0) {
            return null;
        }

        $rank = (int) ceil($p * $count);
        $offset = max(0, min($count - 1, $rank - 1));

        $value = $this->performanceQuery($start, $end)
            ->orderBy('request_duration_ms')
            ->offset($offset)
            ->limit(1)
            ->value('request_duration_ms');

        return $this->round($value);
    }

    protected function performanceQuery(string $start, string $end): Builder
    {
        return DB::connection($this->connection)
            ->table('performance_logs')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end);
    }

    protected function logsQuery(string $start, string $end): Builder
    {
        return DB::connection($this->connection)
            ->table('logs')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end);
    }

    protected function round(float|int|string|null $value, int $precision = 3): ?float
    {
        return $value === null ? null : round((float) $value, $precision);
    }
}
