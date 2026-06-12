<?php

namespace Topoff\LaravelUserLogger\Console\Commands;

use Illuminate\Console\Command;
use Topoff\LaravelUserLogger\Models\ExperimentMeasurement;

class Flush extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user-logger:flush {--force : Delete without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes experiment measurements to start a fresh measurement cycle.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('This will delete ALL experiment measurements. Continue?')) {
            $this->info('Aborted.');

            return self::FAILURE;
        }

        ExperimentMeasurement::truncate();
        $this->info('Experiment measurements flushed.');

        return self::SUCCESS;
    }
}
