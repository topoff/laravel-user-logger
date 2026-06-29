<?php

declare(strict_types=1);

namespace Topoff\LaravelUserLogger\Models;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * One aggregated record per calendar day, produced by
 * `user-logger:summarize-performance`. Lets you correlate daily request
 * performance (latency, errors, db load) with the day's conversions.
 *
 * @property int $id
 * @property Carbon $summary_date
 * @property int $requests
 * @property float|null $sample_rate
 * @property float|null $avg_duration_ms
 * @property float|null $p50_duration_ms
 * @property float|null $p95_duration_ms
 * @property float|null $p99_duration_ms
 * @property float|null $max_duration_ms
 * @property int $slow_requests
 * @property int $errors_4xx
 * @property int $errors_5xx
 * @property int $cold_boots
 * @property float|null $avg_boot_duration_ms
 * @property float|null $avg_queries
 * @property int|null $max_queries
 * @property int $sessions
 * @property int $conversions
 * @property float|null $conversion_rate
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PerformanceDailySummary extends Model
{
    protected $connection = 'user-logger';

    protected $table = 'performance_daily_summaries';

    protected $guarded = [];

    protected $casts = [
        'summary_date' => 'date',
        'requests' => 'integer',
        'sample_rate' => 'float',
        'avg_duration_ms' => 'float',
        'p50_duration_ms' => 'float',
        'p95_duration_ms' => 'float',
        'p99_duration_ms' => 'float',
        'max_duration_ms' => 'float',
        'slow_requests' => 'integer',
        'errors_4xx' => 'integer',
        'errors_5xx' => 'integer',
        'cold_boots' => 'integer',
        'avg_boot_duration_ms' => 'float',
        'avg_queries' => 'float',
        'max_queries' => 'integer',
        'sessions' => 'integer',
        'conversions' => 'integer',
        'conversion_rate' => 'float',
    ];

    /**
     * Always persist summary_date as a plain Y-m-d string so the unique key and
     * updateOrCreate lookups match consistently across MySQL and SQLite (the
     * date cast would otherwise store a full datetime under SQLite).
     */
    public function setSummaryDateAttribute(mixed $value): void
    {
        $this->attributes['summary_date'] = CarbonImmutable::parse($value)->toDateString();
    }
}
