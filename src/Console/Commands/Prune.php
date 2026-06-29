<?php

declare(strict_types=1);

namespace Topoff\LaravelUserLogger\Console\Commands;

use Illuminate\Console\Command;
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
    protected $description = 'Prunes user-logger tables (performance_logs, logs, sessions) per the configured retention. Conversion logs are always kept; sessions are pruned only after their logs (see retention.* config).';

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
            $options = ['--model' => [$model]];

            if ($this->option('pretend')) {
                $options['--pretend'] = true;
            }

            $this->call('model:prune', $options);
        }

        return self::SUCCESS;
    }
}
