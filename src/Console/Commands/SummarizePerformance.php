<?php

declare(strict_types=1);

namespace Topoff\LaravelUserLogger\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Throwable;
use Topoff\LaravelUserLogger\Services\PerformanceSummaryService;

class SummarizePerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user-logger:summarize-performance
        {--date= : Day to summarize as Y-m-d (defaults to yesterday)}
        {--days=1 : Number of trailing days to (re)summarize, ending at --date/yesterday}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregates a day of performance_logs (plus that day\'s conversions) into one performance_daily_summaries row.';

    /**
     * Execute the console command.
     */
    public function handle(PerformanceSummaryService $service): int
    {
        $end = $this->option('date') !== null
            ? CarbonImmutable::parse((string) $this->option('date'))->startOfDay()
            : CarbonImmutable::yesterday();

        $days = max(1, (int) $this->option('days'));

        $rows = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $end->subDays($i);

            try {
                $summary = $service->summarize($date);
            } catch (Throwable $exception) {
                $this->error("Failed to summarize {$date->toDateString()}: {$exception->getMessage()}");

                return self::FAILURE;
            }

            $rows[] = [
                $summary->summary_date->toDateString(),
                $summary->requests,
                $summary->p95_duration_ms ?? '-',
                $summary->errors_5xx,
                $summary->conversions,
                $summary->conversion_rate ?? '-',
            ];
        }

        $this->table(
            ['Date', 'Requests', 'p95 ms', '5xx', 'Conversions', 'Conv. rate'],
            array_reverse($rows),
        );

        return self::SUCCESS;
    }
}
