<?php

namespace Topoff\LaravelUserLogger\Tests\Console;

use Illuminate\Support\Facades\DB;
use Topoff\LaravelUserLogger\Models\PerformanceDailySummary;
use Topoff\LaravelUserLogger\Tests\TestCase;

require_once __DIR__.'/../TestCase.php';

class SummarizePerformanceCommandTest extends TestCase
{
    private string $day = '2026-06-20';

    public function test_it_aggregates_a_day_into_one_summary_row(): void
    {
        config()->set('user-logger.performance.daily_summary.slow_threshold_ms', 50);

        $this->seedPerformanceLogs();
        $this->seedConversions();

        $this->artisan('user-logger:summarize-performance', ['--date' => $this->day])
            ->assertSuccessful();

        $this->assertSame(1, PerformanceDailySummary::query()->count());

        $summary = PerformanceDailySummary::query()->firstOrFail();

        // Volume + latency (durations 1..100 ms within the day).
        $this->assertSame(100, $summary->requests);
        $this->assertSame(50.5, $summary->avg_duration_ms);
        $this->assertSame(50.0, $summary->p50_duration_ms);
        $this->assertSame(95.0, $summary->p95_duration_ms);
        $this->assertSame(99.0, $summary->p99_duration_ms);
        $this->assertSame(100.0, $summary->max_duration_ms);
        $this->assertSame(51, $summary->slow_requests); // >= 50 ms

        // Reliability + boot + db load.
        $this->assertSame(5, $summary->errors_5xx);
        $this->assertSame(3, $summary->errors_4xx);
        $this->assertSame(10, $summary->cold_boots);
        $this->assertSame(20.0, $summary->avg_boot_duration_ms);
        $this->assertSame(10.0, $summary->avg_queries);
        $this->assertSame(10, $summary->max_queries);

        // Business correlation.
        $this->assertSame(4, $summary->sessions);
        $this->assertSame(3, $summary->conversions);
        $this->assertSame(0.75, $summary->conversion_rate);
    }

    public function test_it_is_idempotent_and_recomputes_in_place(): void
    {
        $this->seedPerformanceLogs();

        $this->artisan('user-logger:summarize-performance', ['--date' => $this->day])->assertSuccessful();
        $this->artisan('user-logger:summarize-performance', ['--date' => $this->day])->assertSuccessful();

        $this->assertSame(1, PerformanceDailySummary::query()->where('summary_date', $this->day)->count());
    }

    private function seedPerformanceLogs(): void
    {
        $rows = [];

        for ($ms = 1; $ms <= 100; $ms++) {
            $status = match (true) {
                $ms <= 5 => 500,
                $ms <= 8 => 404,
                default => 200,
            };
            $booted = $ms <= 10;

            $rows[] = [
                'request_duration_ms' => $ms,
                'status' => $status,
                'booted' => $booted,
                'boot_duration_ms' => $booted ? 20 : null,
                'queries_total' => 10,
                'created_at' => $this->day.' 10:00:00',
            ];
        }

        // Out-of-window row that must be ignored.
        $rows[] = [
            'request_duration_ms' => 99999,
            'status' => 500,
            'booted' => false,
            'boot_duration_ms' => null,
            'queries_total' => 999,
            'created_at' => '2026-06-19 23:59:59',
        ];

        DB::connection('user-logger')->table('performance_logs')->insert($rows);
    }

    private function seedConversions(): void
    {
        $sessions = [
            '00000000-0000-0000-0000-0000000004a1',
            '00000000-0000-0000-0000-0000000004a2',
            '00000000-0000-0000-0000-0000000004a3',
            '00000000-0000-0000-0000-0000000004a4',
        ];

        $logs = [];

        // 3 conversions on 3 distinct sessions, within the day.
        foreach (array_slice($sessions, 0, 3) as $i => $sessionId) {
            $logs[] = [
                'session_id' => $sessionId,
                'event' => 'conversion',
                'entity_id' => 'lead-'.$i,
                'created_at' => $this->day.' 11:00:00',
            ];
        }

        // A 4th session that only browsed (no conversion).
        $logs[] = [
            'session_id' => $sessions[3],
            'event' => 'pageview',
            'entity_id' => null,
            'created_at' => $this->day.' 12:00:00',
        ];

        // Out-of-window conversion that must be ignored.
        $logs[] = [
            'session_id' => $sessions[0],
            'event' => 'conversion',
            'entity_id' => 'lead-old',
            'created_at' => '2026-06-19 11:00:00',
        ];

        DB::connection('user-logger')->table('logs')->insert($logs);
    }
}
