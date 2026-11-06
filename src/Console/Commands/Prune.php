<?php

declare(strict_types=1);

namespace Topoff\LaravelUserLogger\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Topoff\LaravelUserLogger\Models\ExperimentMeasurement;
use Topoff\LaravelUserLogger\Models\Log;
use Topoff\LaravelUserLogger\Models\PerformanceLog;
use Topoff\LaravelUserLogger\Models\Session;

class Prune extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user-logger:prune {--pretend : Show how many records would be pruned without deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prunes user-logger tables (performance_logs, logs, experiment measurements of robot sessions, sessions) per the configured retention. Conversion logs are always kept; sessions are pruned only after their logs (see retention.* config).';

    /**
     * Robot-session measurements are removed this many days before their session
     * becomes prunable, so no session can cross the retention boundary between
     * this step and the session prune and leave orphaned robot measurements.
     */
    private const int ROBOT_MEASUREMENT_LEAD_DAYS = 3;

    /**
     * Execute the console command.
     *
     * Order matters: logs are pruned before sessions, because Session::prunable()
     * only deletes sessions that no longer have any logs.
     */
    public function handle(): int
    {
        $models = [
            PerformanceLog::class,
            Log::class,
            Session::class,
        ];

        foreach ($models as $model) {
            if ($model === Session::class) {
                $this->pruneRobotSessionMeasurements();
            }

            $options = ['--model' => [$model]];

            if ($this->option('pretend')) {
                $options['--pretend'] = true;
            }

            $this->call('model:prune', $options);
        }

        return self::SUCCESS;
    }

    /**
     * Experiment measurements of robot sessions are excluded from every report
     * (ExperimentMeasurement::visible()). Once their session is pruned they could
     * no longer be recognised as robots, so they go before the session does —
     * with a lead of ROBOT_MEASUREMENT_LEAD_DAYS on the session retention.
     */
    private function pruneRobotSessionMeasurements(): void
    {
        $days = (int) config('user-logger.retention.sessions_days', 0);
        if ($days <= 0) {
            return;
        }

        $robotSessions = Session::query()
            ->where('is_robot', true)
            ->where('updated_at', '<', now()->subDays(max($days - self::ROBOT_MEASUREMENT_LEAD_DAYS, 0)))
            ->whereDoesntHave('logs')
            ->select('id');

        $total = 0;
        $robotSessions->chunkById(1000, function (Collection $sessions) use (&$total): void {
            $measurements = ExperimentMeasurement::query()->whereIn('session_id', $sessions->pluck('id')->all());
            $total += $this->option('pretend') ? $measurements->count() : $measurements->delete();
        });

        $this->components->info(($this->option('pretend') ? 'Would prune ' : 'Pruned ').$total.' experiment measurements of robot sessions.');
    }
}
